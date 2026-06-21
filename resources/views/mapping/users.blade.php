<x-layouts.app title="Akun User">
    <section class="data-page-hero mb-5"><div><div class="data-page-eyebrow">Manajemen akses</div><h2 class="data-page-title">Akun User</h2><p class="data-page-description">Kelola akses superadmin, admin, supervisor NOC, dan teknisi.</p></div><button type="button" class="btn-primary" data-modal-open="#user-create-modal" data-primary-create>+ Tambah User</button></section>
    <section class="panel mb-5 p-4"><div class="flex gap-2"><label class="relative min-w-0 flex-1"><span class="sr-only">Cari user</span><input class="form-control !pl-10" data-table-search="#users-table" data-search-summary="#users-search-summary" placeholder="Cari nama, email, telepon, atau role..."><span class="pointer-events-none absolute left-3 top-2.5 text-slate-400">⌕</span></label><button type="button" class="btn-compact hidden" data-clear-search="#users-table">Bersihkan</button></div></section>

    <div class="panel overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-black text-slate-900">Daftar User</h3><p id="users-search-summary" class="text-xs text-slate-500">{{ $users->count() }} akun tersimpan</p></div>
        <div class="overflow-x-auto">
            <table id="users-table" class="data-table responsive-data-table">
                <thead><tr><th><button type="button" class="table-sort" data-sort-table="#users-table" data-sort-column="0">Nama <span>↕</span></button></th><th><button type="button" class="table-sort" data-sort-table="#users-table" data-sort-column="1">Email <span>↕</span></button></th><th>No Telp</th><th><button type="button" class="table-sort" data-sort-table="#users-table" data-sort-column="3">Role <span>↕</span></button></th><th>Status</th><th>Dibuat</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td data-label="Nama" class="font-bold">{{ $user->name }}</td><td data-label="Email">{{ $user->email }}</td><td data-label="No Telp">{{ $user->phone ?: '-' }}</td><td data-label="Role"><span class="badge">{{ str($user->role)->replace('_', ' ')->title() }}</span></td><td data-label="Status"><span class="badge {{ $user->is_active ? '!bg-emerald-50 !text-emerald-700' : '!bg-rose-50 !text-rose-700' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td data-label="Dibuat">{{ $user->created_at?->format('d M Y H:i') }}</td>
                            <td data-label="Aksi" class="text-right">
                                @if(auth()->user()->role === 'superadmin' || $user->role !== 'superadmin')
                                    <button type="button" class="btn-compact" data-modal-open="#user-edit-{{ $user->id }}">Edit</button>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">Dilindungi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row="1"><td colspan="7" class="text-center text-slate-500">Belum ada user.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-pagination" data-table-pagination="#users-table"></div>
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
                <label class="grid gap-2"><span class="form-label">Role</span><select name="role" class="form-control" required><option value="teknisi">Teknisi</option><option value="supervisor_noc">Supervisor NOC</option><option value="admin">Admin</option>@if(auth()->user()->role === 'superadmin')<option value="superadmin">Superadmin</option>@endif</select></label>
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

    @foreach ($users as $user)
        @if(auth()->user()->role === 'superadmin' || $user->role !== 'superadmin')
            <dialog id="user-edit-{{ $user->id }}" class="modal-shell">
                <form method="post" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('put')
                    <div class="modal-header">
                        <div><h3 class="text-lg font-bold">Edit User</h3><p class="mt-1 text-sm text-slate-500">Perbarui profil, role, status, atau password {{ $user->name }}.</p></div>
                        <button type="button" class="btn" data-modal-close>Tutup</button>
                    </div>
                    <div class="modal-body grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2"><span class="form-label">Nama</span><input name="name" value="{{ $user->name }}" class="form-control" required></label>
                        <label class="grid gap-2"><span class="form-label">Email</span><input name="email" type="email" value="{{ $user->email }}" class="form-control" required></label>
                        <label class="grid gap-2"><span class="form-label">No Telp</span><input name="phone" value="{{ $user->phone }}" class="form-control" placeholder="08xxxx / +62xxxx"></label>
                        <label class="grid gap-2"><span class="form-label">Password baru</span><input name="password" type="password" class="form-control" placeholder="Kosongkan jika tidak diubah"></label>
                        <label class="grid gap-2"><span class="form-label">Role</span><select name="role" class="form-control" required><option value="teknisi" @selected($user->role === 'teknisi')>Teknisi</option><option value="supervisor_noc" @selected($user->role === 'supervisor_noc')>Supervisor NOC</option><option value="admin" @selected($user->role === 'admin')>Admin</option>@if(auth()->user()->role === 'superadmin')<option value="superadmin" @selected($user->role === 'superadmin')>Superadmin</option>@endif</select></label>
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <input type="checkbox" name="is_active" value="1" @checked($user->is_active) class="h-4 w-4 rounded border-slate-300">
                            <span class="text-sm font-semibold text-slate-700">Akun aktif</span>
                        </label>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn" data-modal-close>Batal</button><button class="btn-primary">Simpan Perubahan</button></div>
                </form>
            </dialog>
        @endif
    @endforeach
</x-layouts.app>
