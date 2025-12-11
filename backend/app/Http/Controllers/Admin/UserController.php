<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('company:id,name,type');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->latest()->paginate(15);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('company');

        return response()->json([
            'user' => $user,
            'stats' => [
                'orders_count' => $user->orders()->count(),
                'shipments_count' => $user->shipments()->count(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['customer', 'carrier', 'admin'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['email_verified_at'] = now();

        $user = User::create($validated);

        return response()->json([
            'message' => 'Пользователь создан',
            'user' => $user->load('company')
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['customer', 'carrier', 'admin'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Пользователь обновлён',
            'user' => $user->fresh()->load('company')
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Нельзя удалить самого себя'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'Пользователь удалён'
        ]);
    }

    public function toggleActive(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Нельзя заблокировать самого себя'
            ], 400);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'Пользователь разблокирован' : 'Пользователь заблокирован',
            'user' => $user
        ]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'message' => 'Пароль сброшен'
        ]);
    }
}
