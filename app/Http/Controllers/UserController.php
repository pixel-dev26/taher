<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Admin-only user management (see the 'admin' middleware on this resource's
 * routes). Two roles: admin (full access, including this screen and the
 * Activity Log) and staff (day-to-day data entry — everything else).
 *
 * No destroy — accounts are deactivated, never deleted, the same way SKUs
 * and Godowns are. UpdateUserRequest blocks an admin from demoting or
 * deactivating their own account, and from demoting or deactivating another
 * admin who is the last other one, so there is always at least one admin
 * left who can undo a mistake.
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
            // Forces the new user to set their own password on first login —
            // the same way the original admin account was handed over.
            'must_change_password' => true,
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            $data['must_change_password'] = true;
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }
}
