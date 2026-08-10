@extends('layouts.app')
@section('title', 'Import Data Pegawai')

@section('content')
<div class="max-w-xl">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink mb-8">Import Data Pegawai</h1>

    <div class="card p-7">
        <p class="text-sm text-ink-soft mb-5">
            Upload file Excel dengan kolom apa saja — pada langkah berikutnya, Anda akan diminta memilih
            kolom mana yang berisi Nama, Email, Departemen, Subdepartemen, dan Role. Kolom lain (misalnya NIP,
            Alamat, Jenis Kelamin) akan otomatis diabaikan.
        </p>

        @if ($errors->any())
            <div class="mb-5 bg-[#FBE7E4] border border-[#C1483A]/30 text-[#8a352a] px-4 py-3 rounded-xl2 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('sdm.pegawai.import.preview') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="field-input mb-4">
            <button type="submit" class="btn btn-primary">Lanjut: Pilih Kolom</button>
        </form>
    </div>
</div>
@endsection