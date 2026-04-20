<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupons;

class CouponsController extends Controller
{
    public function index(){
      return response()->json([
        "data" => Coupons::all(),
        "message" => "Sikeres lekérés!"
      ], 200);  
    }

    public function store(Request $request){
        $request->validate([
            "coupon_code" => "required|string|max:16",
            "expiry_date" => "required|date",
            "coupon_value" => "required|string|max:5",
            "is_active" => "required|boolean"
        ]);

        Coupons::create($request->all());

        return response()->json([
            "message" => "Sikeres rögzítés!"
        ], 201);
    }
}
