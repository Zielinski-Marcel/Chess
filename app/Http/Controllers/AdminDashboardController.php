<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles'])
            ->withTrashed()
            ->withCount(['whiteGames', 'blackGames'])
            ->when($request->search, fn($q, $s) =>
            $q->where('name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")
            )
            ->when($request->filter === 'deleted',  fn($q) => $q->whereNotNull('deleted_at'))
            ->when($request->filter === 'active',   fn($q) => $q->whereNull('deleted_at'))
            ->latest()
            ->paginate(15)
            ->through(fn($u) => [
                'id'                => $u->id,
                'name'              => $u->name,
                'email'             => $u->email,
                'roles'             => $u->roles->pluck('name'),
                'is_super_admin'    => $u->hasRole('super-admin'),
                'deleted_at'        => $u->deleted_at,
                'white_games_count' => $u->white_games_count,
                'black_games_count' => $u->black_games_count,
                'created_at'        => $u->created_at->format('Y-m-d'),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'admin'        => auth()->user()->only('id', 'name', 'email'),
            'isSuperAdmin' => auth()->user()->hasRole('super-admin'),
            'users'        => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'filter' => $request->input('filter', 'all'),
            ],
        ]);
    }

    public function destroy(string $id)
    {
        $user    = User::findOrFail($id);
        $current = auth()->user();

        if ($user->id === $current->id) {
            return back()->withErrors(['error' => 'Nie możesz usunąć własnego konta.']);
        }

        if ($user->hasRole('super-admin')) {
            return back()->withErrors(['error' => 'Konto super-admina jest chronione i nie może zostać usunięte.']);
        }

        if ($user->hasRole('admin') && !$current->hasRole('super-admin')) {
            return back()->withErrors(['error' => 'Nie możesz usunąć konta admina.']);
        }

        $user->delete();
        return back()->with('success', "Konto {$user->name} zostało usunięte.");
    }

    public function restore(string $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return back()->with('success', "Konto {$user->name} zostało przywrócone.");
    }

    public function toggleAdmin(string $id)
    {
        $user    = User::findOrFail($id);
        $current = auth()->user();

        if ($user->id === $current->id) {
            return back()->withErrors(['error' => 'Nie możesz zmienić własnej roli.']);
        }

        if ($user->hasRole('super-admin')) {
            return back()->withErrors(['error' => 'Nie możesz zmienić roli super-admina.']);
        }

        if (!$current->hasRole('super-admin')) {
            return back()->withErrors(['error' => 'Tylko super-admin może zarządzać rolami adminów.']);
        }

        if ($user->hasRole('admin')) {
            $user->removeRole('admin');
            $msg = "Rola admina została odebrana użytkownikowi {$user->name}.";
        } else {
            $user->assignRole('admin');
            $msg = "{$user->name} został adminem.";
        }

        return back()->with('success', $msg);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return back()->withErrors(['error' => 'Tylko super-admin może tworzyć konta.']);
        }

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'is_admin' => 'boolean',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (!empty($data['is_admin'])) {
            $user->assignRole('admin');
        }

        return back()->with('success', "Konto {$user->name} zostało utworzone.");
    }
}
