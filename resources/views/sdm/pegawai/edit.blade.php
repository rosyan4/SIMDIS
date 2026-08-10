@extends('layouts.app')
@section('title', 'Edit Pegawai')

@section('content')
<div class="max-w-xl">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink mb-8">Edit Pegawai</h1>

    <form method="POST" action="{{ route('sdm.pegawai.update', $pegawai) }}"
          class="card p-7 space-y-5"
          x-data="{
              departemens: {{ Js::from($departemens->map(fn($d) => [
                  'id' => $d->id,
                  'nama' => $d->nama_departemen,
                  'subdepartemens' => $d->subdepartemens->map(fn($s) => ['id' => $s->id, 'nama' => $s->nama_subdepartemen]),
              ])) }},
              selectedRole: '{{ old('role', $pegawai->role) }}',
              selectedDepartemen: {{ $currentDepartemenId ?? 'null' }},
              selectedSubdepartemen: {{ $pegawai->subdepartemen_id ?? 'null' }},
              get subdepartemenOptions() {
                  const dept = this.departemens.find(d => d.id == this.selectedDepartemen);
                  return dept ? dept.subdepartemens : [];
              }
          }">
        @csrf
        @method('PUT')

        <div>
            <label class="field-label">Nama</label>
            <input type="text" name="name" value="{{ old('name', $pegawai->name) }}" class="field-input">
        </div>

        <div>
            <label class="field-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $pegawai->email) }}" class="field-input">
        </div>

        <div>
            <label class="field-label">Password baru <span class="text-ink-soft font-normal">(kosongkan jika tidak diubah)</span></label>
            <input type="password" name="password" class="field-input">
        </div>

        <div>
            <label class="field-label">Role</label>
            <select name="role" x-model="selectedRole" @change="selectedDepartemen = ''; selectedSubdepartemen = ''" class="field-input">
                @foreach (['pegawai', 'manajer_departemen', 'asisten_manajer', 'admin_sdm'] as $role)
                <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
            @error('role') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div x-show="selectedRole !== 'admin_sdm'" x-cloak>
            <label class="field-label">Departemen</label>
            <select x-model="selectedDepartemen" @change="selectedSubdepartemen = ''"
                    :name="selectedRole === 'manajer_departemen' ? 'departemen_id' : '_departemen_filter'"
                    class="field-input">
                <option value="">- Pilih Departemen -</option>
                <template x-for="dept in departemens" :key="dept.id">
                    <option :value="dept.id" x-text="dept.nama"></option>
                </template>
            </select>
            @error('departemen_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div x-show="selectedRole === 'pegawai' || selectedRole === 'asisten_manajer'" x-cloak>
            <label class="field-label">Subdepartemen</label>
            <select name="subdepartemen_id" x-model="selectedSubdepartemen" class="field-input" :disabled="!selectedDepartemen">
                <option value="">- Pilih Subdepartemen -</option>
                <template x-for="sub in subdepartemenOptions" :key="sub.id">
                    <option :value="sub.id" x-text="sub.nama"></option>
                </template>
            </select>
            @error('subdepartemen_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input type="checkbox" name="is_active" value="1" @checked($pegawai->is_active) class="rounded border-line">
            Aktif
        </label>

        <button type="submit" class="btn btn-primary w-full">Simpan Perubahan</button>
    </form>
</div>
@endsection