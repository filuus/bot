<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::orderBy('id', 'desc')->get();
        return view('welcome', [
            'transactions' => $transactions,
            'sell' => Transaction::TRANSACTION_TYPES[Transaction::SELL],
            'buy' => Transaction::TRANSACTION_TYPES[Transaction::BUY]
        ]);
    }
}
