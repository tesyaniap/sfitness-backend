<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FitnessClass;
use App\Models\Booking;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $classes = FitnessClass::with('instructor:id,name')
            ->where('status', 'active')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->date, fn($q) => $q->whereDate('schedule_at', $request->date))
            ->orderBy('schedule_at')
            ->get()
            ->append('remaining_quota');

        return response()->json($classes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'type'             => 'required|string',
            'instructor_id'    => 'required|exists:users,id',
            'schedule_at'      => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15',
            'quota'            => 'required|integer|min:1',
            'price'            => 'required|numeric|min:0',
            'location'         => 'nullable|string',
        ]);

        return response()->json(FitnessClass::create($data), 201);
    }

    public function show(FitnessClass $class)
    {
        return response()->json($class->load('instructor:id,name')->append('remaining_quota'));
    }

    public function update(Request $request, FitnessClass $class)
    {
        $data = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'type'             => 'sometimes|string',
            'instructor_id'    => 'sometimes|exists:users,id',
            'schedule_at'      => 'sometimes|date',
            'duration_minutes' => 'sometimes|integer|min:15',
            'quota'            => 'sometimes|integer|min:1',
            'price'            => 'sometimes|numeric|min:0',
            'location'         => 'nullable|string',
            'status'           => 'sometimes|in:active,cancelled,completed',
        ]);

        $class->update($data);
        return response()->json($class);
    }

    public function destroy(FitnessClass $class)
    {
        $class->delete();
        return response()->json(null, 204);
    }

    // Booking endpoints
    public function book(Request $request, FitnessClass $class)
    {
        if ($class->status !== 'active') {
            return response()->json(['message' => 'Kelas tidak tersedia.'], 422);
        }

        if ($class->remaining_quota <= 0) {
            return response()->json(['message' => 'Kuota penuh.'], 422);
        }

        $booking = Booking::firstOrCreate(
            ['user_id' => $request->user()->id, 'class_id' => $class->id],
            ['status' => 'confirmed']
        );

        if (!$booking->wasRecentlyCreated) {
            return response()->json(['message' => 'Sudah booking kelas ini.'], 422);
        }

        return response()->json($booking->load('fitnessClass'), 201);
    }

    public function cancelBooking(Request $request, FitnessClass $class)
    {
        $booking = Booking::where('user_id', $request->user()->id)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $booking->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Booking dibatalkan.']);
    }

    public function myBookings(Request $request)
    {
        $bookings = Booking::with('fitnessClass.instructor:id,name')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($bookings);
    }
}
