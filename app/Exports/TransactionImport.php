<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionImport implements FromArray, WithHeadings
{
    /**
    * @return array
    */
    public function array(): array
    {
        return Transaction::with('member', 'user', 'details.product')->get()->map(function ($transaction) {
            return $transaction->details->map(function ($detail) use ($transaction) {
                return [
                    'Tanggal' => $transaction->created_at->format('Y-m-d'),
                    'Nama Member' => $transaction->member ? $transaction->member->name : 'Non Member',
                    'Total Harga' => $transaction->total_price,
                    'Nama Kasir' => $transaction->user->name,
                    'Product' => $detail->product->name,
                    'Qty' => $detail->qty,
                    'Poin Yang Digunakan' => $transaction ? $transaction->poin : 0,
                ];
            });
        })->flatten(1)->toArray();
    }

    /**
    * Define the header row.
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Member',
            'Total Harga',
            'Nama Kasir',
            'Product',
            'Qty',
            'Poin Yang Digunakan',
        ];
    }
}
