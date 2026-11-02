<?php

namespace Database\Seeders;

use App\Models\Shop;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    /**
     * Seed the shops. Created in id order so auto-increment yields 1, 2 and 3.
     */
    public function run(): void
    {
        Shop::create([
            'config' => [
                'sprite_key' => 'my_house',
                'textureURL' => '/meoportfolious-assets/Items/my_house.png',
                'atlasURL' => '/meoportfolious-assets/Items/my_house.atlas.json',
                'provider' => 'react',
                'shop_link' => 'https://wheretoknowaboutme.netlify.app/',
                'password' => null,
                'position' => ['x' => 550, 'y' => 120],
            ],
        ]);

        Shop::create([
            'config' => [
                'sprite_key' => 'clothes_shop',
                'textureURL' => '/meoportfolious-assets/Items/clothes_shop.png',
                'atlasURL' => '/meoportfolious-assets/Items/clothes_shop_atlas.json',
                'provider' => 'shopify',
                'shop_link' => 'https://gilson-store-owrd5zo6.myshopify.com/',
                'password' => 'rtsia',
                'position' => ['x' => 180, 'y' => 150],
            ],
        ]);

        Shop::create([
            'config' => [
                'sprite_key' => 'pizza_shop',
                'textureURL' => '/meoportfolious-assets/Items/pizza_shop.png',
                'atlasURL' => '/meoportfolious-assets/pizza_shop_atlas.json',
                'provider' => 'react',
                'shop_link' => 'https://mernfoods.netlify.app/',
                'password' => null,
                'position' => ['x' => 840, 'y' => 200],
            ],
        ]);
    }
}
