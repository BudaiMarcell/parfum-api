<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Customer-facing saved-card management.
 *
 * Reminder: this controller deliberately does NOT accept the full card
 * number. The frontend is expected to send only the last 4 digits and the
 * brand — see the migration docblock for the rationale. The validation
 * rules below act as a second line of defence: even if the frontend
 * regressed, a `card_number` field in the request would be silently
 * dropped because it isn't in the `$validated` whitelist.
 */
class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $methods = PaymentMethod::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return PaymentMethodResource::collection($methods);
    }

    public function store(Request $request)
    {
        $now = now();

        $validated = $request->validate([
            'brand'      => 'required|string|max:32',
            'last_four'  => ['required', 'string', 'regex:/^\d{4}$/'],
            // Reasonable bounds — month 1-12, year between this year and 30
            // years out. Keeps obviously-bogus dates out of the database
            // while leaving room for prepaid cards with long expiries.
            'exp_month'  => 'required|integer|min:1|max:12',
            'exp_year'   => 'required|integer|min:' . $now->year . '|max:' . ($now->year + 30),
            'is_default' => 'boolean',
        ]);

        // Normalise the brand string for stable display ("Visa" not "VISA").
        $validated['brand'] = ucfirst(strtolower($validated['brand']));

        return DB::transaction(function () use ($request, $validated) {
            // If the caller asked for default, clear it on every other row
            // first. Doing it inside the same transaction means a saved-
            // cards page that's checking is_default never sees a transient
            // state where two rows are both true.
            if (!empty($validated['is_default'])) {
                PaymentMethod::where('user_id', $request->user()->id)
                    ->update(['is_default' => false]);
            }

            // updateOrCreate handles "user re-saves the same card" without
            // tripping the unique index. If they had it on file already
            // we just bump updated_at and merge any flag changes.
            $method = PaymentMethod::updateOrCreate(
                [
                    'user_id'   => $request->user()->id,
                    'last_four' => $validated['last_four'],
                    'exp_month' => $validated['exp_month'],
                    'exp_year'  => $validated['exp_year'],
                ],
                [
                    'brand'      => $validated['brand'],
                    'is_default' => $validated['is_default'] ?? false,
                ]
            );

            return new PaymentMethodResource($method);
        });
    }

    public function update(Request $request, int $id)
    {
        $method = PaymentMethod::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'is_default' => 'sometimes|boolean',
        ]);

        return DB::transaction(function () use ($request, $method, $validated) {
            if (!empty($validated['is_default']) && $validated['is_default']) {
                PaymentMethod::where('user_id', $request->user()->id)
                    ->where('id', '!=', $method->id)
                    ->update(['is_default' => false]);
            }

            $method->update($validated);

            return new PaymentMethodResource($method->fresh());
        });
    }

    public function destroy(Request $request, int $id)
    {
        $method = PaymentMethod::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $method->delete();

        return response()->json(['message' => 'Payment method removed.']);
    }
}
