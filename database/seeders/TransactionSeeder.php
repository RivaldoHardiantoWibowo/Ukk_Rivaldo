<?php

namespace Database\Seeders;

use Whoops\Run;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transactions')->insert([
            'member_id' => 1,
            'total_price' => 40000,
            'total_pay' => 40000,
            'total_return' => 0,
            'user_id' => 2,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('detail_orders')->insert([
            'transaction_id' => 1,
            'product_id' => 1,
            'qty'=> 2,
            'sub_total'=> 40000,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
