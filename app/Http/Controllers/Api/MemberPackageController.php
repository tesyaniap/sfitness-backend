<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\MemberPackage;
use Illuminate\Http\Request;

class MemberPackageController extends Controller
{
    public function index()
    {
        return response()->json(MemberPackage::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:single,4x,8x',
            'visit_quota' => 'required|integer|min:1',
            'active_days' => 'required|integer|min:0',
        ]);
        return response()->json(MemberPackage::create($data), 201);
    }

    public function update(Request $request, MemberPackage $memberPackage)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'visit_quota' => 'sometimes|integer|min:1',
            'active_days' => 'sometimes|integer|min:0',
        ]);
        $memberPackage->update($data);
        return response()->json($memberPackage);
    }

    public function destroy(MemberPackage $memberPackage)
    {
        $memberPackage->delete();
        return response()->json(null, 204);
    }
}
