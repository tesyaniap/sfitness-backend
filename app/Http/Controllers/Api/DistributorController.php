<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Distributor;
use Illuminate\Http\Request;

class DistributorController extends Controller
{
    public function index()
    {
        return response()->json(Distributor::withCount('purchases')->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string',
            'contact_person' => 'nullable|string|max:100',
        ]);
        return response()->json(Distributor::create($data), 201);
    }

    public function update(Request $request, Distributor $distributor)
    {
        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string',
            'contact_person' => 'nullable|string|max:100',
        ]);
        $distributor->update($data);
        return response()->json($distributor);
    }

    public function destroy(Distributor $distributor)
    {
        $distributor->delete();
        return response()->json(null, 204);
    }
}
