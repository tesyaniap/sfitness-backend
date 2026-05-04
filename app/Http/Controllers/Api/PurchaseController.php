<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchases = Purchase::with('distributor:id,name')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(15);
        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $request->validate([
            'distributor_id'         => 'required|exists:distributors,id',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.price'          => 'required|numeric|min:0',
            'payment_method'         => 'required|in:cash,transfer,debt',
            'due_date'               => 'nullable|date|required_if:payment_method,debt',
            'notes'                  => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $total = 0;
            $items = [];

            foreach ($request->items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $total   += $subtotal;
                $items[]  = [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                    'subtotal'   => $subtotal,
                ];
                // Tambah stok
                Product::find($item['product_id'])->increment('stock', $item['quantity']);
            }

            $purchase = Purchase::create([
                'distributor_id' => $request->distributor_id,
                'total_amount'   => $total,
                'payment_method' => $request->payment_method,
                'status'         => $request->payment_method === 'debt' ? 'pending' : 'paid',
                'due_date'       => $request->due_date,
                'notes'          => $request->notes,
            ]);

            $purchase->items()->createMany($items);

            // Buat utang jika metode debt
            if ($request->payment_method === 'debt') {
                Debt::create([
                    'purchase_id'      => $purchase->id,
                    'distributor_id'   => $request->distributor_id,
                    'total_amount'     => $total,
                    'paid_amount'      => 0,
                    'remaining_amount' => $total,
                    'due_date'         => $request->due_date,
                    'status'           => 'unpaid',
                ]);
            }

            return response()->json($purchase->load('items.product', 'distributor'), 201);
        });
    }

    public function show(Purchase $purchase)
    {
        return response()->json($purchase->load('items.product', 'distributor', 'debt'));
    }
}
