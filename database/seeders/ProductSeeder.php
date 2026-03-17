<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $ferfiEdp  = Category::where('slug', 'ferfi-eau-de-parfum')->first();
        $ferfiEdt  = Category::where('slug', 'ferfi-eau-de-toilette')->first();
        $noiEdp    = Category::where('slug', 'noi-eau-de-parfum')->first();
        $noiEdt    = Category::where('slug', 'noi-eau-de-toilette')->first();
        $unisex    = Category::where('slug', 'unisex-eau-de-parfum')->first();
        $premium   = Category::where('slug', 'unisex-premium')->first();

        $products = [
            [
                'category_id'    => $ferfiEdp->id,
                'name'           => 'Bleu de Chanel',
                'slug'           => 'bleu-de-chanel',
                'description'    => 'Egy szabad, határokat nem ismerő férfi képét idézi. Friss, tiszta és mélyen fás illatú.',
                'price'          => 45990,
                'stock_quantity' => 25,
                'volume_ml'      => 100,
                'gender'         => 'male',
                'is_active'      => true,
            ],
            [
                'category_id'    => $ferfiEdp->id,
                'name'           => 'Sauvage',
                'slug'           => 'dior-sauvage',
                'description'    => 'A vadon szelleme. Nyers és nemes egyszerre – bors és ambroxán dominanciával.',
                'price'          => 42990,
                'stock_quantity' => 30,
                'volume_ml'      => 100,
                'gender'         => 'male',
                'is_active'      => true,
            ],
            [
                'category_id'    => $ferfiEdt->id,
                'name'           => 'Acqua di Giò',
                'slug'           => 'acqua-di-gio',
                'description'    => 'A mediterrán tenger frissessége és a természet ereje. Ikonikus vízi-fás illat.',
                'price'          => 38990,
                'stock_quantity' => 20,
                'volume_ml'      => 100,
                'gender'         => 'male',
                'is_active'      => true,
            ],
            [
                'category_id'    => $ferfiEdt->id,
                'name'           => 'Terre d\'Hermès',
                'slug'           => 'terre-d-hermes',
                'description'    => 'A föld és az ég között. Narancs, szantálfa és vetiver harmonikus ötvözete.',
                'price'          => 47990,
                'stock_quantity' => 15,
                'volume_ml'      => 100,
                'gender'         => 'male',
                'is_active'      => true,
            ],
            [
                'category_id'    => $noiEdp->id,
                'name'           => 'Chanel No. 5',
                'slug'           => 'chanel-no-5',
                'description'    => 'A világ legikonikusabb parfümje. Virágos-aldehid illat, az elegancia szimbóluma.',
                'price'          => 52990,
                'stock_quantity' => 18,
                'volume_ml'      => 100,
                'gender'         => 'female',
                'is_active'      => true,
            ],
            [
                'category_id'    => $noiEdp->id,
                'name'           => 'La Vie Est Belle',
                'slug'           => 'la-vie-est-belle',
                'description'    => 'Az élet szép. Édes és virágos illat, iris és pralina jegyekkel.',
                'price'          => 39990,
                'stock_quantity' => 22,
                'volume_ml'      => 75,
                'gender'         => 'female',
                'is_active'      => true,
            ],
            [
                'category_id'    => $noiEdt->id,
                'name'           => 'Miss Dior',
                'slug'           => 'miss-dior',
                'description'    => 'Friss és virágos illat, amely a modern nőiességet ünnepli. Rózsa és pacsuli.',
                'price'          => 44990,
                'stock_quantity' => 20,
                'volume_ml'      => 100,
                'gender'         => 'female',
                'is_active'      => true,
            ],
            [
                'category_id'    => $noiEdt->id,
                'name'           => 'Flowerbomb',
                'slug'           => 'flowerbomb',
                'description'    => 'Egy virágos robbanás. Édes, intenzív és addiktív illat, jázmin és rózsa szívjegyekkel.',
                'price'          => 41990,
                'stock_quantity' => 17,
                'volume_ml'      => 50,
                'gender'         => 'female',
                'is_active'      => true,
            ],
            [
                'category_id'    => $unisex->id,
                'name'           => 'CK One',
                'slug'           => 'ck-one',
                'description'    => 'Az első igazán unisex parfüm. Friss, citrusos és tiszta – mindenki számára.',
                'price'          => 24990,
                'stock_quantity' => 35,
                'volume_ml'      => 100,
                'gender'         => 'unisex',
                'is_active'      => true,
            ],
            [
                'category_id'    => $premium->id,
                'name'           => 'Oud Wood',
                'slug'           => 'tom-ford-oud-wood',
                'description'    => 'Ritka oud fa, szantálfa és brazil rózsa kombinációja. A luxus megtestesítője.',
                'price'          => 89990,
                'stock_quantity' => 8,
                'volume_ml'      => 50,
                'gender'         => 'unisex',
                'is_active'      => true,
            ],
        ];

        foreach ($products as $data) {
            $product = Product::create($data);

            // minden termékhez létrehozunk egy placeholder képet
            ProductImage::create([
                'product_id' => $product->id,
                'image_url'  => 'https://placehold.co/600x600?text=' . urlencode($product->name),
                'sort_order' => 1,
                'is_primary' => true,
            ]);
        }
    }
}