@extends('layouts.app')
@section('title', 'Tambah Pegawai')

@section('content')
<div class="max-w-xl">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink mb-8">Tambah Pegawai</h1>

    <form method="POST" action="{{ route('sdm.pegawai.store') }}"
          class="card p-7 space-y-5"
          x-data="{
              departemens: {{ Js::from($departemens->map(fn($d) => [
                  'id' => $d->id,
                  'nama' => $d->nama_departemen,
                  'subdepartemens' => $d->subdepartemens->map(fn($s) => ['id' => $s->id, 'nama' => $s->nama_subdepartemen]),
              ])) }},
              selectedRole: '{{ old('role', 'pegawai') }}',
              selectedDepartemen: '',
              get subdepartemenOptions() {
                  const dept = this.departemens.find(d => d.id == this.selectedDepartemen);
                  return dept ? dept.subdepartemens : [];
              }
          }">
        @csrf

        <div>
            <label class="field-label">Nama</label>
            <input type="text" name="name" value="{{ old('name') }}" class="field-input">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="field-input">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Password</label>
            <input type="password" name="password" class="field-input">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Role</label>
            <select name="role" x-model="selectedRole" @change="selectedDepartemen = ''" class="field-input">
                <option value="pegawai">Pegawai</option>
                <option value="manajer_departemen">Manajer Departemen</option>
                <option value="asisten_manajer">Asisten Manajer</option>
                <option value="admin_sdm">Admin SDM</option>
            </select>
            @error('role') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div x-show="selectedRole !== 'admin_sdm'" x-cloak>
            <label class="field-label">Departemen</label>
            <select x-model="selectedDepartemen"
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
            <select name="subdepartemen_id" class="field-input" :disabled="!selectedDepartemen">
                <option value="">- Pilih Subdepartemen -</option>
                <template x-for="sub in subdepartemenOptions" :key="sub.id">
                    <option :value="sub.id" x-text="sub.nama"></option>
                </template>
            </select>
            <p class="text-xs text-ink-soft mt-1.5" x-show="!selectedDepartemen">Pilih departemen terlebih dahulu.</p>
            @error('subdepartemen_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <p class="text-xs text-ink-soft" x-show="selectedRole === 'manajer_departemen'" x-cloak>
            Jika departemen ini sudah punya Manajer, Manajer lama otomatis diturunkan jadi Pegawai.
        </p>
        <p class="text-xs text-ink-soft" x-show="selectedRole === 'asisten_manajer'" x-cloak>
            Jika subdepartemen ini sudah punya Asisten Manajer, yang lama otomatis diturunkan jadi Pegawai.
        </p>

        <button type="submit" class="btn btn-primary w-full">Simpan</button>
    </form>
</div>
@endsection