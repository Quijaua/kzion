<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->isAdmin(), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $users = User::with('people')->orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $people = Person::orderBy('name')->get();

        return view('users.create', compact('people'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,user'],
            'active' => ['boolean'],
            'person_ids' => ['array'],
            'person_ids.*' => ['integer', 'exists:people,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'active' => $data['active'] ?? true,
        ]);

        $user->people()->sync($data['person_ids'] ?? []);

        return redirect()->route('users.index')->with('status', 'Usuário criado.');
    }

    public function edit(User $user): View
    {
        $people = Person::orderBy('name')->get();

        return view('users.edit', compact('user', 'people'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,user'],
            'active' => ['boolean'],
            'person_ids' => ['array'],
            'person_ids.*' => ['integer', 'exists:people,id'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Você não pode remover seu próprio papel de administrador.']);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'active' => $data['active'] ?? true,
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => $data['password']]);
        }

        $user->people()->sync($data['person_ids'] ?? []);

        return redirect()->route('users.index')->with('status', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Você não pode excluir a si mesmo.');

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Usuário excluído.');
    }
}
