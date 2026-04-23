<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        // Alapértelmezetten csak aktív (nem soft-deleted) termékeket listázunk.
        // Ha admin látni akarja az archiváltakat, külön ?with_trashed=1 paraméter kell.
        $query = Product::with(['category', 'primaryImage']);

        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->boolean('low_stock')) {
            $query->where('stock_quantity', '<=', 10);
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

        AuditLogger::log('created', 'Product', $product->id,
            "Új termék létrehozva: {$product->name}",
            ['new' => $product->toArray()]);

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

        $old = $product->only(array_keys($validated));
        $product->update($validated);

        AuditLogger::log('updated', 'Product', $product->id,
            "Termék frissítve: {$product->name}",
            ['old' => $old, 'new' => $validated]);

        return response()->json($product->load(['category', 'images']));
    }

    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);
        $name = $product->name;
        $product->delete();

        AuditLogger::log('deleted', 'Product', $id,
            "Termék törölve: {$name}");

        return response()->json(['message' => 'Termék sikeresen törölve.']);
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        $count = Product::whereIn('id', $validated['ids'])->delete();

        AuditLogger::log('bulk_deleted', 'Product', null,
            "Tömeges törlés: {$count} termék",
            ['ids' => $validated['ids']]);

        return response()->json([
            'message' => "{$count} termék sikeresen törölve.",
            'count'   => $count,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids'       => 'required|array|min:1',
            'ids.*'     => 'integer|exists:products,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $update = [];
        if (array_key_exists('is_active', $validated)) {
            $update['is_active'] = $validated['is_active'];
        }

        if (empty($update)) {
            return response()->json(['message' => 'Nincs frissítendő mező.'], 422);
        }

        $count = Product::whereIn('id', $validated['ids'])->update($update);

        AuditLogger::log('bulk_updated', 'Product', null,
            "Tömeges frissítés: {$count} termék",
            ['ids' => $validated['ids'], 'changes' => $update]);

        return response()->json([
            'message' => "{$count} termék sikeresen frissítve.",
            'count'   => $count,
        ]);
    }
}