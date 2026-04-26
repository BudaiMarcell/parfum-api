<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\VerifyEmail;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Queue the verification email. We swallow errors here so a misconfigured
        // mail driver doesn't break registration — verification can be resent
        // later via /api/email/resend, and the user can still use the site.
        try {
            Mail::to($user->email)->queue(new VerifyEmail($user));
        } catch (\Throwable $e) {
            \Log::warning('Failed to queue verification email', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Hibás email vagy jelszó.'
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function adminRegister(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users|unique:admins',
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
            'role'     => 'sometimes|string|max:50',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Admin::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role ?? 'admin',
        ]);

        $token = $user->createToken('admin_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sikeresen kijelentkezve.'
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Update the authenticated user's profile (name, email, phone).
     *
     * Email changes go through `unique:users,email,{id}` so the user can
     * keep their own address without tripping the unique constraint while
     * still preventing duplicates with other accounts. Password changes
     * have their own endpoint (changePassword) — keeping them separate
     * means a "current password" check is mandatory for password updates
     * but not for the cheaper profile fields.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'unique:users,email,' . $user->id],
            'phone' => 'sometimes|nullable|string|max:32',
        ]);

        // If the email actually changed, reset the verification timestamp
        // and re-send the verification mail. Otherwise an attacker who took
        // over a session could swap the address to their own without proving
        // ownership.
        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $validated['email_verified_at'] = null;

            $user->update($validated);

            try {
                Mail::to($user->email)->queue(new VerifyEmail($user));
            } catch (\Throwable $e) {
                \Log::warning('Failed to queue verification email after email change', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        } else {
            $user->update($validated);
        }

        return new UserResource($user->fresh());
    }

    /**
     * Change the authenticated user's password.
     *
     * `current_password` rule (Laravel 10+) verifies against the signed-in
     * user's hash — a stolen token can't silently rotate the password
     * without also knowing the old one. New password runs through the
     * same policy as registration so weak rotations are rejected too.
     *
     * On success we revoke ALL existing tokens (including this request's)
     * and issue a fresh one. That way a stolen-token rotation kicks the
     * attacker off every device while keeping the legitimate user signed
     * in on the device they used to change the password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke every Sanctum token for this user. This invalidates the
        // bearer that's making the current request, so we hand back a new
        // one in the response — the client swaps it into localStorage.
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Password updated.',
            'token'   => $token,
        ]);
    }

    /**
     * Verify the user's email via signed link.
     *
     * Hit by the link in VerifyEmail. The signed URL middleware
     * (`signed`) rejects anything tampered with or expired, so by the
     * time we get here {id} and {hash} are trustworthy. We still re-check
     * sha1(email) so a leaked link can't be reused after the email
     * address changes.
     *
     * On success we redirect to the frontend with a query param so the
     * SPA can show a friendly "verified!" toast — the API doesn't render
     * HTML and a JSON 200 in a browser is not user-friendly.
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->email), $hash)) {
            return response()->json(['message' => 'Érvénytelen ellenőrző link.'], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // Bounce back to the frontend with a flag the SPA can read.
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
        return redirect($frontendUrl . '/?verified=1');
    }

    /**
     * Resend the verification email. Throttled at the route level (6/min)
     * so a hostile client can't blast the mail provider.
     */
    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Az e-mail cím már megerősítve.'
            ], 200);
        }

        try {
            Mail::to($user->email)->queue(new VerifyEmail($user));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Nem sikerült elküldeni a megerősítő e-mailt. Próbáld újra később.'
            ], 503);
        }

        return response()->json([
            'message' => 'Megerősítő e-mail elküldve.'
        ]);
    }
}
