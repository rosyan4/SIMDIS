@php
    $departemensJson = $departemens->map(fn ($d) => [
        'id' => $d->id,
        'nama' => $d->nama_departemen,
        'subdepartemens' => $d->subdepartemens->map(fn ($s) => ['id' => $s->id, 'nama' => $s->nama_subdepartemen]),
    ])->values();
@endphp

<div x-data="{
    role: '{{ old('role', $user->role ?? '') }}',
    departemenId: '{{ old('departemen_id', $user->departemen_id ?? '') }}',
    subdepartemenId: '{{ old('subdepartemen_id', $user->subdepartemen_id ?? '') }}',
    statusManajer: '{{ old('status_manajer', $user->status_manajer ?? 'aktif') }}',
    departemens: {{ $departemensJson->toJson() }},
    get subdepartemenOptions() {
        const dep = this.departemens.find(d => d.id == this.departemenId);
        return dep ? dep.subdepartemens : [];
    }
}">
    @if ($errors->any())
    <div class="mb-6 border-l-4 border-red-500 pl-4 py-2">
        <p class="text-sm font-medium text-ink mb-1">Periksa kembali isian Anda:</p>
        <ul class="text-xs text-ink-soft list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="mb-4">
        <label class="text-xs text-ink-soft mb-1 block">Nama Lengkap <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="field-input" required>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="text-xs text-ink-soft mb-1 block">Username <span class="text-red-500">*</span></label>
            <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}" class="field-input" required>
        </div>
        <div>
            <label class="text-xs text-ink-soft mb-1 block">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="field-input" required>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="text-xs text-ink-soft mb-1 block">
                Password {{ $user ? '' : '*' }}
            </label>
            <input type="password" name="password" class="field-input" autocomplete="new-password" {{ $user ? '' : 'required' }}>
            @if ($user)
            <p class="text-xs text-ink-soft mt-1">Kosongkan jika tidak ingin mengubah password.</p>
            @endif
        </div>
        <div>
            <label class="text-xs text-ink-soft mb-1 block">Konfirmasi Password {{ $user ? '' : '*' }}</label>
            <input type="password" name="password_confirmation" class="field-input" autocomplete="new-password">
        </div>
    </div>

    <div class="mb-4">
        <label class="text-xs text-ink-soft mb-1 block">Role <span class="text-red-500">*</span></label>
        <select name="role" x-model="role" class="field-input" required>
            <option value="">-- Pilih Role --</option>
            <option value="admin_sdm">Admin SDM</option>
            <option value="admin_departemen">Admin Departemen</option>
            <option value="manajer_departemen">Manajer Departemen</option>
            <option value="asisten_manajer">Asisten Manajer</option>
        </select>
    </div>

    {{-- Departemen: untuk admin_departemen & manajer_departemen --}}
    <div class="mb-4" x-show="role === 'admin_departemen' || role === 'manajer_departemen'" x-cloak>
        <label class="text-xs text-ink-soft mb-1 block">Departemen <span class="text-red-500">*</span></label>
        <select name="departemen_id" x-model="departemenId" class="field-input">
            <option value="">-- Pilih Departemen --</option>
            <template x-for="d in departemens" :key="d.id">
                <option :value="d.id" x-text="d.nama" :selected="d.id == departemenId"></option>
            </template>
        </select>
    </div>

    {{-- Subdepartemen: hanya untuk asisten_manajer --}}
    <div class="mb-4" x-show="role === 'asisten_manajer'" x-cloak>
        <label class="text-xs text-ink-soft mb-1 block">Departemen (untuk memilih subdepartemen)</label>
        <select x-model="departemenId" class="field-input mb-2">
            <option value="">-- Pilih Departemen --</option>
            <template x-for="d in departemens" :key="d.id">
                <option :value="d.id" x-text="d.nama"></option>
            </template>
        </select>

        <label class="text-xs text-ink-soft mb-1 block">Subdepartemen <span class="text-red-500">*</span></label>
        <select name="subdepartemen_id" x-model="subdepartemenId" class="field-input">
            <option value="">-- Pilih Subdepartemen --</option>
            <template x-for="s in subdepartemenOptions" :key="s.id">
                <option :value="s.id" x-text="s.nama" :selected="s.id == subdepartemenId"></option>
            </template>
        </select>
        <p class="text-xs text-ink-soft mt-1" x-show="departemenId && subdepartemenOptions.length === 0" x-cloak>
            Departemen ini belum memiliki subdepartemen.
        </p>
    </div>

    <div class="flex items-center gap-2 mt-2">
        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }} class="rounded">
        <label for="is_active" class="text-sm text-ink">Akun aktif</label>
    </div>
</div>