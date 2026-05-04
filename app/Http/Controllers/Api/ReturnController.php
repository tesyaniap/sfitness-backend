<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ReturnOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index()
    {
        return response()->json(
            ReturnOrder::with('distributor:id,name', 'items.product:id,name')
                ->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'distributor_id'         => 'required|exists:distributors,id',
            'purchase_id'            => 'nullable|exists:purchases,id',
            'reason'                 => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.price'          => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $items = [];
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                if ($product->stock < $item['quantity']) {
                    abort(422, "Stok {$product->name} tidak cukup untuk diretur.");
                }
                $product->decrement('stock', $item['quantity']);
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                    'subtotal'   => $item['price'] * $item['quantity'],
                ];
            }

            $return = ReturnOrder::create([
                'distributor_id' => $request->distributor_id,
                'purchase_id'    => $request->purchase_id,
                'reason'         => $request->reason,
            ]);

            $return->items()->createMany($items);

            return response()->json($return->load('distributor', 'items.product'), 201);
        });
    }

    public function show(ReturnOrder $return)
    {
        return response()->json($return->load('distributor', 'purchase', 'items.product'));
    }
}
