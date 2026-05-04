<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\SalePayment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('items.product', 'user:id,name,email', 'salePayments')
            ->latest();

        if ($request->user()->isMember()) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'payment_method'         => 'nullable|string',
            'notes'                  => 'nullable|string',
            // Split payment
            'payments'               => 'nullable|array',
            'payments.*.method'      => 'required_with:payments|in:cash,qris,transfer,debit,debt',
            'payments.*.amount'      => 'required_with:payments|numeric|min:0',
            // Piutang
            'is_debt'                => 'nullable|boolean',
            'due_date'               => 'nullable|date',
        ]);

        return DB::transaction(function () use ($request) {
            $total = 0;
            $items = [];

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                if ($product->stock < $item['quantity']) {
                    abort(422, "Stok {$product->name} tidak cukup.");
                }
                $subtotal = $product->price * $item['quantity'];
                $total   += $subtotal;
                $items[]  = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'subtotal'   => $subtotal,
                ];
                $product->decrement('stock', $item['quantity']);
            }

            $isDebt  = $request->boolean('is_debt');
            $status  = $isDebt ? 'pending' : 'paid';

            $transaction = Transaction::create([
                'user_id'        => $request->user()->id,
                'total_amount'   => $total,
                'payment_method' => $request->payment_method ?? 'cash',
                'notes'          => $request->notes,
                'status'         => $status,
            ]);

            $transaction->items()->createMany($items);

            // Split payments
            if ($request->payments && count($request->payments) > 0) {
                foreach ($request->payments as $payment) {
                    SalePayment::create([
                        'transaction_id' => $transaction->id,
                        'method'         => $payment['method'],
                        'amount'         => $payment['amount'],
                    ]);
                }
            }

            // Buat piutang jika is_debt
            if ($isDebt) {
                Receivable::create([
                    'transaction_id'   => $transaction->id,
                    'user_id'          => $request->user()->id,
                    'total_amount'     => $total,
                    'paid_amount'      => 0,
                    'remaining_amount' => $total,
                    'due_date'         => $request->due_date,
                    'status'           => 'unpaid',
                ]);
            }

            return response()->json($transaction->load('items.product', 'salePayments'), 201);
        });
    }

    public function show(Request $request, Transaction $transaction)
    {
        if ($request->user()->isMember() && $transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        return response()->json($transaction->load('items.product', 'user:id,name,email', 'salePayments'));
    }
}
