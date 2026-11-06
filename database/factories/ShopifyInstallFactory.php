<?php

namespace Database\Factories;

use App\Models\ShopifyInstall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopifyInstall>
 */
class ShopifyInstallFactory extends Factory
{
    /**
     * @return array{shop_domain: string, access_token: string, scopes: string}
     */
    public function definition(): array
    {
        return [
            'shop_domain' => fake()->unique()->slug().'.myshopify.com',
            'access_token' => 'shpat_'.fake()->sha1(),
            'scopes' => 'read_products,write_products',
        ];
    }
}
