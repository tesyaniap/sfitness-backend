<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $debts = Debt::with('distributor:id,name', 'purchase:id,purchase_number')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->get();
        return response()->json($debts);
    }

    public function pay(Request $request, Debt $debt)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1|max:' . $debt->remaining_amount,
            'payment_method' => 'required|in:cash,transfer',
            'notes'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $debt) {
            DebtPayment::create([
                'debt_id'        => $debt->id,
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
            ]);

            $newPaid      = $debt->paid_amount + $request->amount;
            $newRemaining = $debt->total_amount - $newPaid;
            $status       = $newRemaining <= 0 ? 'paid' : 'partial';

            $debt->update([
                'paid_amount'      => $newPaid,
                'remaining_amount' => max(0, $newRemaining),
                'status'           => $status,
            ]);

            // Update purchase status
            if ($status === 'paid') {
                $debt->purchase->update(['status' => 'paid']);
            }

            return response()->json($debt->fresh()->load('distributor', 'payments'));
        });
    }

    public function show(Debt $debt)
    {
        return response()->json($debt->load('distributor', 'purchase.items.product', 'payments'));
    }
}
