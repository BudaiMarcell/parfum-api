<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Http\Resources\AddressResource;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)->get();
        return AddressResource::collection($addresses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'      => 'required|string|max:50',
            'country'    => 'required|string|max:100',
            'city'       => 'required|string|max:100',
            'zip_code'   => 'required|string|max:20',
            'street'     => 'required|string|max:255',
            'is_default' => 'boolean',
        ]);

        if (!empty($validated['is_default']) && $validated['is_default']) {
            Address::where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        $address = Address::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return new AddressResource($address);
    }

    public function update(Request $request, int $id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'label'      => 'sometimes|string|max:50',
            'country'    => 'sometimes|string|max:100',
            'city'       => 'sometimes|string|max:100',
            'zip_code'   => 'sometimes|string|max:20',
            'street'     => 'sometimes|string|max:255',
            'is_default' => 'sometimes|boolean',
        ]);

        if (!empty($validated['is_default']) && $validated['is_default']) {
            Address::where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return new AddressResource($address);
    }

    public function destroy(Request $request, int $id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $address->delete();

        return response()->json(['message' => 'Cím sikeresen törölve.']);
    }
}