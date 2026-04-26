<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product.primaryImage', 'address'])
            ->orderBy('created_at', 'desc')
            ->get();

        return OrderResource::collection($orders);
    }

    public function show(Request $request, int $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['items.product.primaryImage', 'address'])
            ->firstOrFail();

        return new OrderResource($order);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'address_id'     => 'required|exists:addresses,id',
            'payment_method' => 'required|string|max:50',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // DB tranzakció – ha bármi hibázik, az egész visszagörget
        $order = DB::transaction(function () use ($validated, $request) {

            $total = 0;
            $orderItems = [];

            // árak és készlet ellenőrzése
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    abort(422, "Nincs elegendő készlet: {$product->name}");
                }

                $subtotal      = $product->price * $item['quantity'];
                $total        += $subtotal;
                $orderItems[]  = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal'   => $subtotal,
                ];

                // készlet csökkentése
                $product->decrement('stock_quantity', $item['quantity']);
            }

            // rendelés létrehozása
            $order = Order::create([
                'user_id'        => $request->user()->id,
                'address_id'     => $validated['address_id'],
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'status'         => 'pending',
                'total_amount'   => $total,
                'notes'          => $validated['notes'] ?? null,
            ]);

            // tételek mentése
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    ...$item,
                ]);
            }

            return $order;
        });

        // Confirmation email is queued AFTER the transaction commits, never
        // inside it. If we queued it inside the closure and the transaction
        // rolled back, the worker would dequeue an Order::find() that
        // returns null (or worse, a stale row) and throw at render time.
        try {
            Mail::to($order->user->email)->queue(new OrderPlaced($order));
        } catch (\Throwable $e) {
            // The order is already saved — emailing is best-effort. Log
            // and move on rather than 500-ing the user after they've paid.
            Log::warning('Failed to queue order confirmation email', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        return new OrderResource($order->load(['items.product', 'address']));
    }
}
