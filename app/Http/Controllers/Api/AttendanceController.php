<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceMember;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $attendances = Attendance::with('fitnessClass:id,name', 'instructor:id,name')
            ->withCount('members')
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->date, fn($q) => $q->whereDate('date', $request->date))
            ->when($request->month, fn($q) => $q->whereMonth('date', $request->month))
            ->latest('date')->paginate(20);

        return response()->json($attendances);
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'instructor_id' => 'required|exists:users,id',
            'date'          => 'required|date',
            'notes'         => 'nullable|string',
            'members'       => 'required|array|min:1',
            'members.*.type'         => 'required|in:member,single_visit',
            'members.*.membership_id'=> 'required_if:members.*.type,member|nullable|exists:memberships,id',
            'members.*.user_id'      => 'nullable|exists:users,id',
            'members.*.guest_name'   => 'required_if:members.*.type,single_visit|nullable|string',
            'members.*.price_paid'   => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $attendance = Attendance::create([
                'class_id'      => $request->class_id,
                'instructor_id' => $request->instructor_id,
                'date'          => $request->date,
                'notes'         => $request->notes,
            ]);

            foreach ($request->members as $member) {
                // Kurangi visit jika member
                if ($member['type'] === 'member' && isset($member['membership_id'])) {
                    $membership = Membership::findOrFail($member['membership_id']);

                    if (!$membership->isActive()) {
                        abort(422, "Membership {$membership->user->name} sudah tidak aktif atau habis.");
                    }

                    $membership->increment('visit_used');
                    $membership->decrement('visit_remaining');

                    // Update status jika visit habis
                    if ($membership->fresh()->visit_remaining <= 0) {
                        $membership->update(['status' => 'used_up']);
                    }
                }

                AttendanceMember::create([
                    'attendance_id' => $attendance->id,
                    'membership_id' => $member['membership_id'] ?? null,
                    'user_id'       => $member['user_id'] ?? null,
                    'guest_name'    => $member['guest_name'] ?? null,
                    'type'          => $member['type'],
                    'price_paid'    => $member['price_paid'],
                ]);
            }

            return response()->json($attendance->load('fitnessClass', 'instructor', 'members.user', 'members.membership.package'), 201);
        });
    }

    public function show(Attendance $attendance)
    {
        return response()->json($attendance->load(
            'fitnessClass:id,name',
            'instructor:id,name',
            'members.user:id,name,member_number',
            'members.membership.package'
        )->append(['total_present', 'total_member', 'total_single']));
    }

    // Laporan harian
    public function dailyReport(Request $request)
    {
        $date = $request->date ?? today()->toDateString();

        $attendances = Attendance::with('fitnessClass:id,name', 'instructor:id,name')
            ->withCount([
                'members',
                'members as member_count'       => fn($q) => $q->where('type', 'member'),
                'members as single_visit_count'  => fn($q) => $q->where('type', 'single_visit'),
            ])
            ->whereDate('date', $date)
            ->get();

        $totalRevenue = AttendanceMember::whereHas('attendance', fn($q) => $q->whereDate('date', $date))
            ->sum('price_paid');

        return response()->json([
            'date'          => $date,
            'total_classes' => $attendances->count(),
            'total_present' => $attendances->sum('members_count'),
            'total_revenue' => $totalRevenue,
            'classes'       => $attendances,
        ]);
    }
}
