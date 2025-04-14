<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Member;
use App\Models\Product;
use App\Models\DetailOrder;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transaction = Transaction::with('user', 'member','details.product')->get();

        return view('pembelian.index', compact('transaction'));
    }

    public function search(Request $request) {
        $search = Transaction::where('member_id', $request->search)->first();

        if ($search == null) {
            $search = Transaction::where('user_id', $request->search)->first();
        }

        return view('pembelian.index', compact('search'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::all();
        return view('pembelian.tambah', compact('products'));
    }

    public function cart(Request $request) {
        $request->validate([
            'cart_data' => 'required|json'
        ]);

        $cartItem = json_decode($request->cart_data, true);

        foreach ($cartItem as $productList => $qty) {
            Cart::create([
                'product_id' => $productList,
                'qty' => $qty,
            ]);
        }

        $cartItems = Cart::all();

        $totalPrice = 0;

        foreach ($cartItems as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $totalPrice += $product->price * $item->qty;
            }
        }

        return view('pembelian.member', compact('cartItems', 'totalPrice'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'member' => 'required|in:non-member,member',
            'total_bayar' => 'required|numeric',
        ]);

        $user = Auth::user();
        $carts = Cart::with('product')->get();

        $totalPrice = 0;
        foreach ($carts as $cart) {
            $totalPrice += $cart->product->price * $cart->qty;
        }

        $kembalian = $request->total_bayar - $totalPrice;

        if ($request->member == 'member') {
            $request->validate([
                'phoneNumber' => 'required|numeric',
            ]);

            $phonenumber = $request->phoneNumber;

            $member = Member::where('phone_number', $phonenumber)->first();


            if ($member == null) {
                $poinmember = $totalPrice * 1 / 100;

                $member = Member::create([
                    'phone_number' => $phonenumber,
                    'poin_member' => $poinmember,
                ]);
            }

            $sellingData = [];
            foreach ($carts as $cart) {

                $sellingData[] = [
                    'product_name' => $cart->product->name,
                    'price' => $cart->product->price,
                    'qty' => $cart->qty,
                    'subtotal' => $cart->product->price * $cart->qty,
                ];

                $checkpoin = 0;

                if ($member) {
                    $checkpoin = Transaction::where('member_id', $member->id)->count();
                }
            }
            return view('pembelian.checkMember', [
                'dataTransaction' => $sellingData,
                'member' => $member,
                'totalBayar' => $request->total_bayar,
                'subtotal' => $cart->product->price * $cart->qty,
                'poinmember' => $member->poin_member,
                'checkPoint' => $checkpoin
            ]);
        }

        $sellingData = [];
        $transaction = Transaction::create([
            'member_id' => null,
            'user_id' => $user->id,
            'poin' => 0,
            'total_poin' => 0,
            'total_pay' => $request->total_bayar,
            'total_return' => $kembalian,
            'total_price' => $totalPrice,
        ]);
        foreach ($carts as $cart) {
            DetailOrder::create([
                'transaction_id' => $transaction->id,
                'product_id' => $cart->product->id,
                'qty' => $cart->qty,
                'sub_total' => $totalPrice,
            ]);

            $product = Product::find($cart->product->id);
            $product->stock = $product->stock - $cart->qty;
            $product->save();

            $sellingData[] = [
                'product_name' => $cart->product->name,
                'price' => $cart->product->price,
                'qty' => $cart->qty,
                'sub_total' => $cart->product->price * $cart->qty,
            ];
        }

        Cart::truncate();

        $invoiceNumber = Transaction::orderBy('created_at', 'desc')->count();
        $userName = $user->name;

        return view('pembelian.result', [
            'sellingData' => $sellingData,
            'totalPrice' => $totalPrice,
            'userName' => $userName,
            'kembalian' => $kembalian,
            'invoiceNumber' => $invoiceNumber,
            'poinUsed' => 0
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
    }

    public function checkMember(Request $request)
    {
        $member = Member::where('phone_number', $request->phone_number)->first();

        if ($member && $request->name) {
            $member->name = $request->name;
            $member->save();
        }

        $user = Auth::user();
        $carts = Cart::with('product')->get();

        $totalPrice = 0;
        foreach ($carts as $cart) {
            $totalPrice += $cart->product->price * $cart->qty;
        }
        $poinUsed = $member->poin_member;
        $poinmember = $totalPrice * 1 / 100;
        if ($request->checkPoin) {
            $totalPrice -= $member->poin_member;
            if ($totalPrice < 0) {
                $totalPrice = 0;
            }
            $member->poin_member = 0;
            $member->save();
        }

        $totalPrice = (int) $totalPrice;

        $kembalian = $request->total_bayar - $totalPrice;

        $sellingData = [];
        $transaction = Transaction::create([
            'member_id' => $member->id,
            'user_id' => $user->id,
            'poin' => $poinUsed,
            'total_poin' => $member->poin_member += $poinmember,
            'total_pay' => $request->total_bayar,
            'total_return' => $kembalian,
            'total_price' => $totalPrice,
        ]);
        foreach ($carts as $cart) {
            DetailOrder::create([
                'transaction_id' => $transaction->id,
                'product_id' => $cart->product->id,
                'qty' => $cart->qty,
                'sub_total' => $totalPrice,
            ]);

            $product = Product::find($cart->product->id);
            $product->stock = $product->stock - $cart->qty;
            $product->save();

            $sellingData[] = [
                'product_name' => $cart->product->name,
                'price' => $cart->product->price,
                'qty' => $cart->qty,
                'sub_total' => $cart->product->price * $cart->qty,
            ];
        }
        $member->save();
        Cart::truncate();

        $invoiceNumber = Transaction::orderBy('created_at', 'desc')->count() + 1;
        $userName = $user->name;


        return view('pembelian.result', [
            'sellingData' => $sellingData,
            'totalPrice' => $totalPrice,
            'userName' => $userName,
            'kembalian' => $kembalian,
            'poinUsed'=> $poinUsed,
            'invoiceNumber' => $invoiceNumber
        ]);
    }

    public function CetakPdf(Request $request, $id)
    {

        $transaction = Transaction::where('id', $id)->with('user', 'member', 'details.product')->first();

        $data = [
            'transaction' => $transaction,
            'member' => $transaction->member,
            'details' => $transaction->details,
        ];

        $pdf = Pdf::loadView('pembelian.invoice', $data);
        return $pdf->stream('bukti-pembelian.pdf');
    }


}
