<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'primaryImage'])
            ->withTrashed();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('like', '%' . $request->search . '%');
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $products = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($products);
    }

    public function show(int $id)
    {
        $product = Product::with(['category', 'images'])
            ->withTrashed()
            ->findOrFail($id);

        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'volume_ml'      => 'nullable|integer|min:1',
            'gender'         => 'required|in:male,female,unisex',
            'is_active'      => 'boolean',
            'images'         => 'nullable|array',
            'images.*.image_url'  => 'required|string|max:500',
            'images.*.is_primary' => 'boolean',
            'images.*.sort_order' => 'integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $images = $validated['images'] ?? [];
        unset($validated['images']);

        $product = Product::create($validated);

        foreach ($images as $index => $image) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_url'  => $image['image_url'],
                'is_primary' => $image['is_primary'] ?? ($index === 0),
                'sort_order' => $image['sort_order'] ?? $index,
            ]);
        }

        return response()->json($product->load('images'), 201);
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id'    => 'sometimes|exists:categories,id',
            'name'           => 'sometimes|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'sometimes|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'volume_ml'      => 'nullable|integer|min:1',
            'gender'         => 'sometimes|in:male,female,unisex',
            'is_active'      => 'sometimes|boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        return response()->json($product->load(['category', 'images']));
    }

    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Termék sikeresen törölve.']);
    }
}