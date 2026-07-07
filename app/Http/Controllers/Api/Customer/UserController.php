<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['Admin', 'Finance', 'Purchasing', 'Viewer'];

    public function index(Request $request): JsonResponse
    {
        $customerId = $request->user()->customer_id;

        $users = CustomerUser::where('customer_id', $customerId)
            ->orderBy('name')
            ->get()
            ->map(fn (CustomerUser $u) => $this->row($u));

        return response()->json(['data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $customerId = $request->user()->customer_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:customer_users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $user = CustomerUser::create([
            'customer_id' => $customerId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => true,
        ]);

        return response()->json(['data' => $this->row($user)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);
        $customerId = $request->user()->customer_id;

        $user = CustomerUser::where('customer_id', $customerId)->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('customer_users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', Rule::in(self::ROLES)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        unset($data['password']);

        $user->fill($data)->save();

        return response()->json(['data' => $this->row($user)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);
        $customerId = $request->user()->customer_id;

        $user = CustomerUser::where('customer_id', $customerId)->findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User dihapus.']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403, 'Hanya Admin yang dapat mengelola user.');
    }

    private function row(CustomerUser $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'is_active' => (bool) $u->is_active,
            'last_login_at' => optional($u->last_login_at)->toDateTimeString(),
        ];
    }
}
