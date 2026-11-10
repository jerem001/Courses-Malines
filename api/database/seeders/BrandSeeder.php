<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Leclerc' => 'leclerc',
            'Intermarché' => 'intermarche',
            'Carrefour' => 'carrefour',
        ] as $name => $slug) {
            Brand::updateOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
