<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            'name' => 'Kambing',
            'price' => 20000,
            'image' => 'product/6pV6aNWD76LXAXHMIn5zTUCDa8T7dNGR3qfgfxIH.jpg',
            'stock' => 10,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
