<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MemberPackage;
use App\Models\FitnessClass;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function index(Request $request)
    {
        // Auto-update expired memberships
        Membership::where('status', 'active')
            ->whereNotNull('expired_date')
            ->where('expired_date', '<', today())
            ->update(['status' => 'expired']);

        Membership::where('status', 'active')
            ->where('visit_remaining', 0)
            ->update(['status' => 'used_up']);

        $memberships = Membership::with('user:id,name,member_number,phone', 'class_:id,name', 'package:id,name,type')
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(20);

        return response()->json($memberships);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'class_id'       => 'required|exists:classes,id',
            'package_id'     => 'required|exists:member_packages,id',
            'price'          => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,qris,transfer,midtrans',
            'notes'          => 'nullable|string',
        ]);

        $package   = MemberPackage::findOrFail($request->package_id);
        $startDate = Carbon::today();
        $expiredDate = $package->active_days > 0
            ? $startDate->copy()->addDays($package->active_days - 1)
            : null;

        $membership = Membership::create([
            'user_id'        => $request->user_id,
            'class_id'       => $request->class_id,
            'package_id'     => $request->package_id,
            'price'          => $request->price,
            'visit_quota'    => $package->visit_quota,
            'visit_used'     => 0,
            'visit_remaining'=> $package->visit_quota,
            'start_date'     => $startDate,
            'expired_date'   => $expiredDate,
            'status'         => 'active',
            'payment_method' => $request->payment_method,
            'payment_status' => 'paid',
            'notes'          => $request->notes,
        ]);

        return response()->json($membership->load('user', 'class_', 'package'), 201);
    }

    public function show(Membership $membership)
    {
        $membership->checkAndUpdateStatus();
        return response()->json($membership->load('user', 'class_', 'package', 'attendances'));
    }

    // Cek membership aktif member untuk kelas tertentu
    public function checkActive(Request $request)
    {
        $request->validate([
            'user_id'  => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        $membership = Membership::where('user_id', $request->user_id)
            ->where('class_id', $request->class_id)
            ->where('status', 'active')
            ->where('visit_remaining', '>', 0)
            ->where(fn($q) => $q->whereNull('expired_date')->orWhere('expired_date', '>=', today()))
            ->latest()
            ->first();

        return response()->json([
            'has_active' => (bool) $membership,
            'membership' => $membership?->load('package'),
        ]);
    }

    public function myMemberships(Request $request)
    {
        $memberships = Membership::with('class_:id,name', 'package:id,name,type')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($memberships);
    }
}
