<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class OrganizerUserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('users.manage');

        return Inertia::render('Organizer/Users', [
            'users' => User::query()
                ->with('roles:id,name')
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = trim((string) $request->query('search'));

                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('roles', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                    });
                })
                ->latest('id')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at?->toISOString(),
                    'roles' => $user->roles->pluck('name')->values()->all(),
                ]),
            'roles' => $this->availableRoles($request->user()),
            'filters' => [
                'search' => (string) $request->query('search', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('users.manage');

        $allowedRoles = $this->availableRoles($request->user());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($allowedRoles)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole($data['role']);

        return back()->with('success', "Akun {$user->name} berhasil dibuat.");
    }

    /**
     * @return array<int, string>
     */
    private function availableRoles(User $user): array
    {
        $roles = ['admin', 'staff'];

        if ($user->hasRole('owner')) {
            array_unshift($roles, 'owner');
        }

        return Role::query()
            ->whereIn('name', $roles)
            ->pluck('name')
            ->sortBy(fn (string $role): int => array_search($role, $roles, true))
            ->values()
            ->all();
    }
}
