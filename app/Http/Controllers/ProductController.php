<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // All query-string parameters that reach the DB are validated here
        // so no unexpected value can sneak into ORDER BY / WHERE.
        $validated = $request->validate([
            'category'       => 'sometimes|string|max:120',
            'brand'          => 'sometimes|string|max:120',
            'gender'         => ['sometimes', Rule::in(['male', 'female', 'unisex'])],
            'min_price'      => 'sometimes|numeric|min:0',
            'max_price'      => 'sometimes|numeric|min:0',
            'search'         => 'sometimes|string|max:120',
            'sort_by'        => ['sometimes', Rule::in(['price', 'name', 'created_at'])],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page'       => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Product::where('is_active', true)
            ->with(['primaryImage', 'category']);

        if (isset($validated['category'])) {
            $query->whereHas('category', function ($q) use ($validated) {
                $q->where('slug', $validated['category']);
            });
        }

        if (isset($validated['brand'])) {
            $query->where('brand', $validated['brand']);
        }

        if (isset($validated['gender'])) {
            $query->where('gender', $validated['gender']);
        }

        if (isset($validated['min_price'])) {
            $query->where('price', '>=', $validated['min_price']);
        }
        if (isset($validated['max_price'])) {
            $query->where('price', '<=', $validated['max_price']);
        }

        if (isset($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%')
                  ->orWhere('brand', 'like', '%' . $validated['search'] . '%');
            });
        }

        $sortBy        = $validated['sort_by']        ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $products = $query->paginate($validated['per_page'] ?? 12);

        return ProductResource::collection($products);
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['images', 'category'])
            ->firstOrFail();

        return new ProductResource($product);
    }
}