<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(20);
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'role'     => ['required', 'in:admin,manager,viewer'],
            'password' => ['nullable', Password::min(8)],
        ]);

        // Auto-generate a password if not provided
        $plain    = $data['password'] ?? \Str::random(12);
        $data['password']   = Hash::make($plain);
        $data['is_active']  = true;

        $user = User::create($data);

        ActivityLog::record(
            userId:      auth()->id(),
            action:      'user.invited',
            modelType:   'User',
            modelId:     $user->id,
            description: "Invited user: {$user->name} ({$user->email}) as {$user->role}",
            newValues:   $user->only(['name', 'email', 'role'])
        );

        // TODO: send welcome email with $plain password
        // Mail::to($user->email)->send(new WelcomeMail($user, $plain));

        return back()->with('success', "User \"{$user->name}\" invited. Temp password: {$plain}");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role'      => ['sometimes', 'required', 'in:admin,manager,viewer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $old = $user->only(array_keys($data));
        $user->update($data);

        ActivityLog::record(
            userId:      auth()->id(),
            action:      'user.updated',
            modelType:   'User',
            modelId:     $user->id,
            description: "Updated user: {$user->name}",
            oldValues:   $old,
            newValues:   $user->fresh()->only(array_keys($data))
        );

        return back()->with('success', "User \"{$user->name}\" updated.");
    }
}
