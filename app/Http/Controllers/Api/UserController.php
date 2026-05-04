<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('member_number', 'like', "%{$request->search}%")
                ->orWhere('whatsapp', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(15);

        return response()->json($users);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'whatsapp'    => 'nullable|string|max:20',
            'birth_place' => 'nullable|string|max:100',
            'birth_date'  => 'nullable|date',
            'religion'    => 'nullable|string|max:50',
            'occupation'  => 'nullable|string|max:100',
            'address'     => 'nullable|string',
            'role'        => 'sometimes|in:super_admin,admin,member',
            'is_active'   => 'sometimes|boolean',
        ]);

        if (isset($data['role']) && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $user->update($data);
        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'whatsapp'    => 'nullable|string|max:20',
            'birth_date'  => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'religion'    => 'nullable|string|max:50',
            'occupation'  => 'nullable|string|max:100',
            'address'     => 'nullable|string',
            'avatar'      => 'nullable|string',
        ]);

        $request->user()->update($data);
        return response()->json($request->user()->fresh());
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update(['password' => $request->password]);
        return response()->json(['message' => 'Password berhasil diubah.']);
    }
}
