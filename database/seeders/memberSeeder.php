<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class memberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('members')->insert([
            'name' => 'Cimit',
            'phone_number' => '081122334',
            'poin_member' => 100000,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
