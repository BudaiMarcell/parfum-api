<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function getCart(Request $request): array
    {
        return $request->session()->get('cart', []);
    }

    private function saveCart(Request $request, array $cart): void
    {
        $request->session()->put('cart', $cart);
    }

    public function index(Request $request)
    {
        $cart     = $this->getCart($request);
        $products = [];

        foreach ($cart as $productId => $item) {
            $product = Product::with('primaryImage')->find($productId);
            if ($product) {
                $products[] = [
                    'product'  => $product,
                    'quantity' => $item['quantity'],
                    'subtotal' => $product->price * $item['quantity'],
                ];
            }
        }

        $total = array_sum(array_column($products, 'subtotal'));

        return response()->json([
            'items' => $products,
            'total' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock_quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Nincs elegendő készlet.'
            ], 422);
        }

        $cart = $this->getCart($request);

        $productId = $validated['product_id'];

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $validated['quantity'];
        } else {
            $cart[$productId] = ['quantity' => $validated['quantity']];
        }

        $this->saveCart($request, $cart);

        return response()->json(['message' => 'Termék hozzáadva a kosárhoz.']);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getCart($request);

        if (!isset($cart[$id])) {
            return response()->json(['message' => 'A termék nincs a kosárban.'], 404);
        }

        $cart[$id]['quantity'] = $validated['quantity'];
        $this->saveCart($request, $cart);

        return response()->json(['message' => 'Kosár frissítve.']);
    }

    public function destroy(Request $request, int $id)
    {
        $cart = $this->getCart($request);
        unset($cart[$id]);
        $this->saveCart($request, $cart);

        return response()->json(['message' => 'Termék eltávolítva a kosárból.']);
    }

    public function clear(Request $request)
    {
        $request->session()->forget('cart');

        return response()->json(['message' => 'Kosár kiürítve.']);
    }
}