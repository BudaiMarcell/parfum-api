<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Fake vásárlók létrehozása
        $customers = [
            ['name' => 'Kovács Anna',   'email' => 'anna.kovacs@example.hu'],
            ['name' => 'Nagy Péter',    'email' => 'peter.nagy@example.hu'],
            ['name' => 'Szabó Bence',   'email' => 'bence.szabo@example.hu'],
            ['name' => 'Tóth Eszter',   'email' => 'eszter.toth@example.hu'],
        ];

        $users = [];
        foreach ($customers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $users[] = $user;
        }

        // Cím minden vásárlóhoz
        $addressData = [
            ['city' => 'Budapest',  'zip_code' => '1011', 'street' => 'Fő utca 12.'],
            ['city' => 'Debrecen',  'zip_code' => '4024', 'street' => 'Piac utca 5.'],
            ['city' => 'Pécs',      'zip_code' => '7621', 'street' => 'Király utca 8.'],
            ['city' => 'Győr',      'zip_code' => '9021', 'street' => 'Aradi vértanúk útja 3.'],
        ];

        $addresses = [];
        foreach ($users as $i => $user) {
            $addr = Address::firstOrCreate(
                ['user_id' => $user->id, 'label' => 'Otthon'],
                array_merge($addressData[$i], [
                    'user_id'    => $user->id,
                    'country'    => 'Magyarország',
                    'is_default' => true,
                ])
            );
            $addresses[$user->id] = $addr;
        }

        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command->warn('Nincs termék – futtasd előbb a ProductSeeder-t.');
            return;
        }

        // Rendelési adatok különféle státuszokkal
        $ordersData = [
            ['user' => 0, 'status' => 'pending',    'payment_status' => 'pending',    'payment_method' => 'card',    'items' => [[0, 1], [1, 2]]],
            ['user' => 1, 'status' => 'processing', 'payment_status' => 'processing', 'payment_method' => 'card',    'items' => [[2, 1]]],
            ['user' => 2, 'status' => 'shipped',    'payment_status' => 'paid',        'payment_method' => 'card',    'items' => [[3, 1], [4, 1]]],
            ['user' => 3, 'status' => 'arrived',    'payment_status' => 'paid',        'payment_method' => 'transfer','items' => [[5, 2]]],
            ['user' => 0, 'status' => 'arrived',    'payment_status' => 'paid',        'payment_method' => 'card',    'items' => [[6, 1], [7, 1], [8, 1]]],
            ['user' => 1, 'status' => 'canceled',   'payment_status' => 'failed',      'payment_method' => 'card',    'items' => [[9, 1]]],
            ['user' => 2, 'status' => 'refunded',   'payment_status' => 'refunded',    'payment_method' => 'card',    'items' => [[0, 1]]],
            ['user' => 3, 'status' => 'pending',    'payment_status' => 'pending',     'payment_method' => 'transfer','items' => [[1, 3]]],
            ['user' => 0, 'status' => 'processing', 'payment_status' => 'processing',  'payment_method' => 'card',    'items' => [[2, 1], [3, 2]]],
            ['user' => 1, 'status' => 'shipped',    'payment_status' => 'paid',         'payment_method' => 'card',    'items' => [[4, 1]]],
        ];

        foreach ($ordersData as $data) {
            $user    = $users[$data['user']];
            $address = $addresses[$user->id];

            // Összeg számítása
            $total = 0;
            $itemsToCreate = [];
            foreach ($data['items'] as [$productIdx, $qty]) {
                $product = $products[$productIdx % $products->count()];
                $subtotal = $product->price * $qty;
                $total   += $subtotal;
                $itemsToCreate[] = [
                    'product'   => $product,
                    'quantity'  => $qty,
                    'unit_price'=> $product->price,
                    'subtotal'  => $subtotal,
                ];
            }

            $order = Order::create([
                'user_id'        => $user->id,
                'address_id'     => $address->id,
                'status'         => $data['status'],
                'total_amount'   => $total,
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'],
            ]);

            foreach ($itemsToCreate as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $item['subtotal'],
                ]);
            }
        }
    }
}
