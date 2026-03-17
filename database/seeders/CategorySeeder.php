<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Férfi parfümök',
                'slug'        => 'ferfi-parfumok',
                'description' => 'Exkluzív férfi illatok a világ vezető márkáitól.',
                'is_active'   => true,
                'children'    => [
                    ['name' => 'Eau de Parfum', 'slug' => 'ferfi-eau-de-parfum'],
                    ['name' => 'Eau de Toilette', 'slug' => 'ferfi-eau-de-toilette'],
                    ['name' => 'Ajándékszett', 'slug' => 'ferfi-ajandekszett'],
                ],
            ],
            [
                'name'        => 'Női parfümök',
                'slug'        => 'noi-parfumok',
                'description' => 'Elegáns és romantikus illatok hölgyeknek.',
                'is_active'   => true,
                'children'    => [
                    ['name' => 'Eau de Parfum', 'slug' => 'noi-eau-de-parfum'],
                    ['name' => 'Eau de Toilette', 'slug' => 'noi-eau-de-toilette'],
                    ['name' => 'Ajándékszett', 'slug' => 'noi-ajandekszett'],
                ],
            ],
            [
                'name'        => 'Unisex parfümök',
                'slug'        => 'unisex-parfumok',
                'description' => 'Nemektől független, egyedi illatok.',
                'is_active'   => true,
                'children'    => [
                    ['name' => 'Eau de Parfum', 'slug' => 'unisex-eau-de-parfum'],
                    ['name' => 'Prémium kollekció', 'slug' => 'unisex-premium'],
                ],
            ],
        ];

        foreach ($categories as $data) {
            $children = $data['children'] ?? [];
            unset($data['children']);

            $parent = Category::create($data);

            foreach ($children as $child) {
                Category::create([
                    ...$child,
                    'parent_id' => $parent->id,
                    'is_active' => true,
                ]);
            }
        }
    }
}