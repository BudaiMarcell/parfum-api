<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupons;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupons::query();

        if ($request->filled('search')) {
            $query->where('coupon_code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('status')) {
            $today = now()->toDateString();
            if ($request->status === 'expired') {
                $query->where('expiry_date', '<', $today);
            } elseif ($request->status === 'active') {
                $query->where('expiry_date', '>=', $today)->where('is_active', true);
            }
        }

        $coupons = $query->orderByDesc('created_at')->paginate(15);

        return response()->json($coupons);
    }

    public function show($id)
    {
        $coupon = Coupons::findOrFail($id);
        return response()->json($coupon);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'coupon_code'    => 'nullable|string|max:32|unique:coupons,coupon_code',
            'discount_type'  => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'expiry_date'    => 'required|date',
            'usage_limit'    => 'nullable|integer|min:1',
            'is_active'      => 'boolean',
        ]);

        // Ha nincs megadva kód, generáljunk egyet.
        if (empty($data['coupon_code'])) {
            do {
                $code = strtoupper(Str::random(10));
            } while (Coupons::where('coupon_code', $code)->exists());
            $data['coupon_code'] = $code;
        }

        // Százalékos kedvezménynél 0-100 közötti érték
        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            return response()->json([
                'message' => 'A százalékos kedvezmény nem lehet nagyobb 100-nál.',
            ], 422);
        }

        $data['used_count'] = 0;
        $data['is_active']  = $data['is_active'] ?? true;

        $coupon = Coupons::create($data);

        AuditLogger::log('created', 'Coupon', $coupon->id,
            "Új kupon létrehozva: {$coupon->coupon_code}",
            ['new' => $coupon->toArray()]);

        return response()->json($coupon, 201);
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupons::findOrFail($id);

        $data = $request->validate([
            'coupon_code'    => 'sometimes|string|max:32|unique:coupons,coupon_code,' . $id,
            'discount_type'  => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'expiry_date'    => 'sometimes|date',
            'usage_limit'    => 'nullable|integer|min:1',
            'is_active'      => 'sometimes|boolean',
        ]);

        $type  = $data['discount_type']  ?? $coupon->discount_type;
        $value = $data['discount_value'] ?? $coupon->discount_value;
        if ($type === 'percentage' && $value > 100) {
            return response()->json([
                'message' => 'A százalékos kedvezmény nem lehet nagyobb 100-nál.',
            ], 422);
        }

        $old = $coupon->only(array_keys($data));
        $coupon->update($data);

        AuditLogger::log('updated', 'Coupon', $coupon->id,
            "Kupon frissítve: {$coupon->coupon_code}",
            ['old' => $old, 'new' => $data]);

        return response()->json($coupon);
    }

    public function destroy($id)
    {
        $coupon = Coupons::findOrFail($id);
        $code = $coupon->coupon_code;
        $coupon->delete();

        AuditLogger::log('deleted', 'Coupon', $id, "Kupon törölve: {$code}");

        return response()->json(['message' => 'Kupon törölve.']);
    }
}
