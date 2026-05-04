<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $receivables = Receivable::with('user:id,name,phone', 'transaction:id,invoice_number')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->get();
        return response()->json($receivables);
    }

    public function pay(Request $request, Receivable $receivable)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1|max:' . $receivable->remaining_amount,
            'payment_method' => 'required|in:cash,transfer,qris',
            'notes'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $receivable) {
            ReceivablePayment::create([
                'receivable_id'  => $receivable->id,
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method,
                'notes'          => $request->notes,
            ]);

            $newPaid      = $receivable->paid_amount + $request->amount;
            $newRemaining = $receivable->total_amount - $newPaid;
            $status       = $newRemaining <= 0 ? 'paid' : 'partial';

            $receivable->update([
                'paid_amount'      => $newPaid,
                'remaining_amount' => max(0, $newRemaining),
                'status'           => $status,
            ]);

            if ($status === 'paid') {
                $receivable->transaction->update(['status' => 'paid']);
            }

            return response()->json($receivable->fresh()->load('user', 'payments'));
        });
    }

    public function show(Receivable $receivable)
    {
        return response()->json($receivable->load('user', 'transaction.items.product', 'payments'));
    }
}
