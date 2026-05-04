<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Debt;
use App\Models\FitnessClass;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function admin()
    {
        $overdueDebts       = Debt::where('status', '!=', 'paid')->where('due_date', '<', today())->sum('remaining_amount');
        $overdueReceivables = Receivable::where('status', '!=', 'paid')->where('due_date', '<', today())->sum('remaining_amount');

        return response()->json([
            'total_members'         => User::where('role', 'member')->count(),
            'total_classes'         => FitnessClass::where('status', 'active')->count(),
            'bookings_today'        => Booking::whereDate('created_at', today())->count(),
            'revenue_today'         => Transaction::whereDate('created_at', today())->where('status', 'paid')->sum('total_amount'),
            'revenue_this_month'    => Transaction::whereMonth('created_at', now()->month)->where('status', 'paid')->sum('total_amount'),
            'low_stock_products'    => Product::where('stock', '<=', 5)->where('is_available', true)->get(['id', 'name', 'stock']),
            // Utang & Piutang
            'total_debt'            => Debt::where('status', '!=', 'paid')->sum('remaining_amount'),
            'total_receivable'      => Receivable::where('status', '!=', 'paid')->sum('remaining_amount'),
            'overdue_debt'          => $overdueDebts,
            'overdue_receivable'    => $overdueReceivables,
            'unpaid_debts_count'    => Debt::where('status', '!=', 'paid')->count(),
            'unpaid_recv_count'     => Receivable::where('status', '!=', 'paid')->count(),
        ]);
    }

    public function member(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'upcoming_bookings' => Booking::with('fitnessClass:id,name,schedule_at,location')
                ->where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->whereHas('fitnessClass', fn($q) => $q->where('schedule_at', '>=', now()))
                ->orderBy('created_at')->take(5)->get(),
            'total_bookings'     => Booking::where('user_id', $user->id)->count(),
            'total_transactions' => Transaction::where('user_id', $user->id)->count(),
            'total_spent'        => Transaction::where('user_id', $user->id)->where('status', 'paid')->sum('total_amount'),
            'outstanding_debt'   => Receivable::where('user_id', $user->id)->where('status', '!=', 'paid')->sum('remaining_amount'),
        ]);
    }
}
