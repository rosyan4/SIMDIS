@extends('layouts.app')
@section('title', 'Import Data Pegawai')
@section('page-title', 'Import Data Pegawai')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink">Import Data Pegawai</h1>
</div>

<div class="card p-6 max-w-2xl">
    <p class="text-sm text-ink-soft mb-5">
        Upload file Excel (.xlsx/.xls) atau CSV berisi data pegawai. Setelah upload,
        Anda akan diminta memetakan kolom di file Anda ke field sistem sebelum data
        benar-benar disimpan — jadi tidak akan langsung masuk tanpa Anda cek dulu.
    </p>

    <form method="POST" action="{{ route('sdm.pegawai.import.preview') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-5">
            <label class="field-label" for="file">File Excel / CSV</label>
            <input type="file" id="file" name="file" class="field-input" accept=".xlsx,.xls,.csv" required>
            <p class="text-xs text-ink-soft mt-1.5">Maksimal 5 MB. Format: .xlsx, .xls, atau .csv.</p>
            @error('file') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="bg-canvas border border-line rounded-lg p-4 mb-5 text-sm text-ink-soft">
            <p class="font-semibold text-ink mb-1.5">Kolom yang dikenali sistem:</p>
            <ul class="list-disc list-inside space-y-0.5">
                <li>NIK <span class="text-ink-soft">(wajib)</span></li>
                <li>Nama Pegawai <span class="text-ink-soft">(wajib)</span></li>
                <li>Departemen <span class="text-ink-soft">(wajib)</span></li>
                <li>Jenis Pegawai, Jabatan, Subdepartemen, No. Telepon, Email <span class="text-ink-soft">(opsional)</span></li>
            </ul>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Lanjut ke Pemetaan Kolom</button>
            <a href="{{ route('sdm.pegawai.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection