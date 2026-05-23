<x-layouts.app title="Akun User">
    <div class="toolbar">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Akun User</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola akses superadmin, admin, supervisor NOC, dan teknisi.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <input class="form-control w-72" data-table-search="#users-table" placeholder="Cari user...">
            <button type="button" class="btn-primary" data-modal-open="#user-create-modal">Tambah User</button>
        </div>
    </div>

    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table id="users-table" class="data-table">
                <thead><tr><th>Nama</th><th>Email</th><th>No Telp</th><th>Role</th><th>Status</th><th>Dibuat</th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="font-bold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?: '-' }}</td>
                            <td><span class="badge">{{ $user->role }}</span></td>
                            <td>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td>{{ $user->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr data-empty-row="1"><td colspan="6" class="text-center text-slate-500">Belum ada user.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <dialog id="user-create-modal" class="modal-shell">
        <form method="post" action="{{ route('users.store') }}">
            @csrf
            <div class="modal-header">
                <div>
                    <h3 class="text-lg font-bold">Tambah User</h3>
                    <p class="mt-1 text-sm text-slate-500">Buat akun baru dan pilih role akses.</p>
                </div>
                <button type="button" class="btn" data-modal-close>Tutup</button>
            </div>
            <div class="modal-body grid gap-4 md:grid-cols-2">
                <label class="grid gap-2"><span class="form-label">Nama</span><input name="name" class="form-control" placeholder="Nama user" required></label>
                <label class="grid gap-2"><span class="form-label">Email</span><input name="email" type="email" class="form-control" placeholder="user@domain.com" required></label>
                <label class="grid gap-2"><span class="form-label">No Telp</span><input name="phone" class="form-control" placeholder="08xxxx / +62xxxx"></label>
                <label class="grid gap-2"><span class="form-label">Password</span><input name="password" type="password" class="form-control" placeholder="Minimal 6 karakter" required></label>
                <label class="grid gap-2"><span class="form-label">Role</span><select name="role" class="form-control" required><option value="teknisi">Teknisi</option><option value="supervisor_noc">Supervisor NOC</option><option value="admin">Admin</option><option value="superadmin">Superadmin</option></select></label>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 md:col-span-2">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300">
                    <span class="text-sm font-semibold text-slate-700">Akun aktif</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>Batal</button>
                <button class="btn-primary">Simpan User</button>
            </div>
        </form>
    </dialog>
</x-layouts.app>
