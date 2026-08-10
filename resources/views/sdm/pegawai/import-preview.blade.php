@extends('layouts.app')
@section('title', 'Pemetaan Kolom Import')

@section('content')
<div class="max-w-4xl">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM · Langkah 2 dari 2</p>
    <h1 class="font-display text-3xl text-ink mb-2">Pilih Kolom yang Sesuai</h1>
    <p class="text-ink-soft text-sm mb-8">
        Ditemukan {{ count($headers) }} kolom, {{ $totalRows }} baris data. Cocokkan kolom di file Excel Anda
        dengan field yang dibutuhkan sistem — kolom yang tidak dipilih di bawah ini akan diabaikan.
    </p>

    <form method="POST" action="{{ route('sdm.pegawai.import.confirm') }}">
        @csrf
        <input type="hidden" name="path" value="{{ $path }}">

        <div class="card p-7 mb-6">
            <h2 class="font-semibold text-ink mb-4">Pemetaan Kolom</h2>
            <div class="space-y-4">
                @foreach ($targetFields as $field => $label)
                <div class="grid grid-cols-3 items-center gap-4">
                    <label class="text-sm font-medium text-ink">
                        {{ $label }}
                        @if (in_array($field, ['nama', 'email']))
                            <span class="text-[#C1483A]">*</span>
                        @endif
                    </label>
                    <select name="mapping[{{ $field }}]" class="field-input col-span-2">
                        <option value="">— Tidak digunakan —</option>
                        @foreach ($headers as $index => $header)
                        <option value="{{ $index }}" @selected(($suggestedMapping[$field] ?? null) === $index)>
                            {{ $header ?: '(kolom ' . ($index + 1) . ')' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endforeach
            </div>
            <p class="text-xs text-ink-soft mt-4">
                <span class="text-[#C1483A]">*</span> Nama dan Email wajib dipetakan. Departemen/Subdepartemen/Role
                opsional — jika tidak dipilih, Role default "Pegawai" dan tanpa penempatan subdepartemen.
            </p>
        </div>

        <div class="card overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-line">
                <h2 class="font-semibold text-ink">Pratinjau Data</h2>
                <p class="text-xs text-ink-soft mt-1">Menampilkan 5 baris pertama dari file yang diupload.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table-pro">
                    <thead>
                        <tr>
                            @foreach ($headers as $header)
                            <th>{{ $header ?: '-' }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previewRows as $row)
                        <tr>
                            @foreach ($headers as $index => $header)
                            <td class="text-ink-soft">{{ $row[$index] ?? '-' }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('sdm.pegawai.import.form') }}" class="btn btn-outline">Batal, Upload Ulang</a>
            <button type="submit" class="btn btn-primary">Import {{ $totalRows }} Data Pegawai</button>
        </div>
    </form>
</div>
@endsection