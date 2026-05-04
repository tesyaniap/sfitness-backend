<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FitnessClass;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$clientKey    = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = config('midtrans.is_sanitized');
        Config::$is3ds        = config('midtrans.is_3ds');
    }

    // ─── Kantin: create Snap token ───────────────────────────────────────────
    public function createKantinPayment(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $total = 0;
            $items = [];
            $snapItems = [];

            foreach ($request->items as $item) {
                $product = \App\Models\Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    abort(422, "Stok {$product->name} tidak cukup.");
                }

                $subtotal    = (int) ($product->price * $item['quantity']);
                $total      += $subtotal;

                $items[]     = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'subtotal'   => $subtotal,
                ];

                $snapItems[] = [
                    'id'       => 'PROD-' . $product->id,
                    'price'    => (int) $product->price,
                    'quantity' => $item['quantity'],
                    'name'     => substr($product->name, 0, 50),
                ];

                $product->decrement('stock', $item['quantity']);
            }

            $transaction = Transaction::create([
                'user_id'      => $request->user()->id,
                'total_amount' => $total,
                'status'       => 'pending',
                'payment_method' => 'midtrans',
            ]);

            $transaction->items()->createMany($items);

            $orderId = 'KANTIN-' . $transaction->id . '-' . time();
            $transaction->update(['midtrans_order_id' => $orderId]);

            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id'     => $orderId,
                    'gross_amount' => $total,
                ],
                'item_details'    => $snapItems,
                'customer_details' => [
                    'first_name' => $request->user()->name,
                    'email'      => $request->user()->email,
                    'phone'      => $request->user()->phone ?? '08000000000',
                ],
                'callbacks' => [
                    'finish' => config('app.frontend_url') . '/dashboard/orders',
                ],
            ]);

            $transaction->update(['snap_token' => $snapToken]);

            return response()->json([
                'snap_token'     => $snapToken,
                'client_key'     => config('midtrans.client_key'),
                'transaction_id' => $transaction->id,
                'order_id'       => $orderId,
                'total'          => $total,
            ]);
        });
    }

    // ─── Booking Kelas: create Snap token ────────────────────────────────────
    public function createBookingPayment(Request $request, FitnessClass $class)
    {
        if ($class->status !== 'active') {
            return response()->json(['message' => 'Kelas tidak tersedia.'], 422);
        }

        if ($class->remaining_quota <= 0) {
            return response()->json(['message' => 'Kuota penuh.'], 422);
        }

        $existing = Booking::where('user_id', $request->user()->id)
            ->where('class_id', $class->id)
            ->first();

        if ($existing && $existing->payment_status === 'paid') {
            return response()->json(['message' => 'Sudah booking kelas ini.'], 422);
        }

        // Buat atau update booking
        $booking = $existing ?? Booking::create([
            'user_id'  => $request->user()->id,
            'class_id' => $class->id,
            'status'   => 'pending',
            'price'    => $class->price,
        ]);

        $orderId = 'BOOKING-' . $booking->id . '-' . time();

        $snapToken = Snap::getSnapToken([
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $class->price,
            ],
            'item_details' => [[
                'id'       => 'CLASS-' . $class->id,
                'price'    => (int) $class->price,
                'quantity' => 1,
                'name'     => substr($class->name, 0, 50),
            ]],
            'customer_details' => [
                'first_name' => $request->user()->name,
                'email'      => $request->user()->email,
                'phone'      => $request->user()->phone ?? '08000000000',
            ],
            'callbacks' => [
                'finish' => config('app.frontend_url') . '/dashboard/bookings',
            ],
        ]);

        $booking->update([
            'snap_token'        => $snapToken,
            'midtrans_order_id' => $orderId,
        ]);

        return response()->json([
            'snap_token'  => $snapToken,
            'client_key'  => config('midtrans.client_key'),
            'booking_id'  => $booking->id,
            'order_id'    => $orderId,
            'total'       => (int) $class->price,
            'class_name'  => $class->name,
        ]);
    }

    // ─── Webhook / Notification Handler ──────────────────────────────────────
    public function handleNotification(Request $request)
    {
        $notif     = new Notification();
        $orderId   = $notif->order_id;
        $status    = $notif->transaction_status;
        $fraudStatus = $notif->fraud_status;

        $isPaid = ($status === 'capture' && $fraudStatus === 'accept')
               || $status === 'settlement';
        $isFailed  = in_array($status, ['cancel', 'deny', 'expire']);
        $isPending = $status === 'pending';

        // Kantin transaction
        if (str_starts_with($orderId, 'KANTIN-')) {
            $parts = explode('-', $orderId);
            $transaction = Transaction::find($parts[1]);
            if ($transaction) {
                $transaction->update([
                    'status'       => $isPaid ? 'paid' : ($isFailed ? 'cancelled' : 'pending'),
                    'payment_type' => $notif->payment_type ?? null,
                ]);
            }
        }

        // Booking kelas
        if (str_starts_with($orderId, 'BOOKING-')) {
            $parts   = explode('-', $orderId);
            $booking = Booking::find($parts[1]);
            if ($booking) {
                $booking->update([
                    'status'         => $isPaid ? 'confirmed' : ($isFailed ? 'cancelled' : 'pending'),
                    'payment_status' => $isPaid ? 'paid' : ($isFailed ? 'failed' : 'unpaid'),
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    // ─── Client-side payment success callback ────────────────────────────────
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'order_id'           => 'required|string',
            'transaction_status' => 'required|string',
            'fraud_status'       => 'nullable|string',
        ]);

        $orderId = $request->order_id;
        $status  = $request->transaction_status;
        $fraud   = $request->fraud_status;

        $isPaid = ($status === 'capture' && $fraud === 'accept')
               || $status === 'settlement'
               || $status === 'pending'; // pending = waiting payment (e.g. VA)

        if (str_starts_with($orderId, 'KANTIN-')) {
            $parts = explode('-', $orderId);
            $transaction = Transaction::find($parts[1]);
            if ($transaction) {
                $transaction->update([
                    'status'       => $isPaid ? 'paid' : 'cancelled',
                    'payment_type' => $request->payment_type ?? null,
                ]);
                return response()->json(['message' => 'ok', 'transaction' => $transaction->load('items.product')]);
            }
        }

        if (str_starts_with($orderId, 'BOOKING-')) {
            $parts   = explode('-', $orderId);
            $booking = Booking::find($parts[1]);
            if ($booking) {
                $booking->update([
                    'status'         => $isPaid ? 'confirmed' : 'cancelled',
                    'payment_status' => $isPaid ? 'paid' : 'failed',
                ]);
                return response()->json(['message' => 'ok', 'booking' => $booking->load('fitnessClass')]);
            }
        }

        return response()->json(['message' => 'order not found'], 404);
    }
}
