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

    public function editPassword(User $user)
    {
        return view('users.password', compact('user'));
    }

    public function updatePassword(Request $request, User $user)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data) {
            $user->forceFill([
                'password' => Hash::make($data['password']),
                'remember_token' => \Illuminate\Support\Str::random(60),
            ])->save();

            ActivityLog::record(
                userId: auth()->id(), action: 'user.password_changed',
                modelType: 'User', modelId: $user->id,
                description: "Password changed for user: {$user->name}"
            );
        });

        if ($user->id === $request->user()->id) {
            $request->session()->regenerate();
        }

        return redirect()->route('users.index')->with('success', "Password changed for {$user->name}.");
    }

}
