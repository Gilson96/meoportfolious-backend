<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Seed the items. The player is created first so it is always item 1.
     */
    public function run(): void
    {
        Item::create([
            'shop_id' => null,
            'external_variant_id' => null,
            'sprite_key' => 'player',
            'textureURL' => '/meoportfolious-assets/Items/player.png',
            'atlasURL' => '/meoportfolious-assets/Items/player_atlas.json',
            'active' => true,
        ]);
    }
}
