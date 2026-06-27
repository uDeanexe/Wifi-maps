<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserManagement($request, $user);

        if ($request->user()->is($user)) {
            return back()->withErrors([
                'is_active' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan.',
            ]);
        }

        if ($this->isLastActiveSuperadmin($user)) {
            return back()->withErrors([
                'is_active' => 'Minimal satu superadmin aktif harus tetap tersedia.',
            ]);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('status', $user->is_active
            ? 'User berhasil diaktifkan.'
            : 'User berhasil dinonaktifkan.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUserManagement($request, $user);

        if ($request->user()->is($user)) {
            return back()->withErrors([
                'user' => 'Akun yang sedang digunakan tidak dapat dihapus.',
            ]);
        }

        if ($this->isLastActiveSuperadmin($user)) {
            return back()->withErrors([
                'user' => 'Minimal satu superadmin aktif harus tetap tersedia.',
            ]);
        }

        $name = $user->name;
        $user->delete();

        return back()->with('status', "User {$name} berhasil dihapus.");
    }

    private function authorizeUserManagement(Request $request, User $target): void
    {
        $actor = $request->user();

        abort_unless(in_array($actor?->role, ['superadmin', 'admin'], true), 403);
        abort_if($actor->role !== 'superadmin' && $target->role === 'superadmin', 403);
    }

    private function isLastActiveSuperadmin(User $user): bool
    {
        return $user->role === 'superadmin'
            && $user->is_active
            && User::where('role', 'superadmin')->where('is_active', true)->count() <= 1;
    }
}
