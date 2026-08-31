@extends('layouts.app')
@section('title', 'Pemetaan Kolom Import')
@section('page-title', 'Pemetaan Kolom Import')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink">Pemetaan Kolom</h1>
    <p class="text-sm text-ink-soft mt-1">
        Ditemukan <strong>{{ $totalRows }}</strong> baris data. Cocokkan kolom di file Anda dengan field sistem di bawah ini.
    </p>
</div>

{{-- Pratinjau 5 baris pertama --}}
<div class="card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-line">
        <h3 class="font-semibold text-ink">Pratinjau Data (5 baris pertama)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="table-pro">
            <thead>
                <tr>
                    @foreach ($headers as $h)
                    <th>{{ $h ?: '(kosong)' }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($previewRows as $row)
                <tr>
                    @foreach ($headers as $i => $h)
                    <td class="text-ink-soft">{{ $row[$i] ?? '-' }}</td>
                    @endforeach
                </tr>
                @empty
                <tr><td colspan="{{ count($headers) }}" class="text-center text-ink-soft py-6">Tidak ada baris data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Form pemetaan --}}
<form method="POST" action="{{ route('sdm.pegawai.import.confirm') }}" class="card p-6 max-w-2xl">
    @csrf
    <input type="hidden" name="path" value="{{ $path }}">

    @php
        $wajib = ['nik', 'nama', 'departemen'];
    @endphp

    <div class="space-y-4 mb-6">
        @foreach ($targetFields as $field => $label)
        <div class="grid grid-cols-2 gap-4 items-center">
            <label class="field-label mb-0" for="mapping_{{ $field }}">
                {{ $label }}
                @if (in_array($field, $wajib))
                <span class="text-[#C1483A]">*</span>
                @endif
            </label>
            <select id="mapping_{{ $field }}" name="mapping[{{ $field }}]" class="field-input"
                    @if (in_array($field, $wajib)) required @endif>
                <option value="">— Tidak dipetakan —</option>
                @foreach ($headers as $index => $h)
                <option value="{{ $index }}" @selected(($suggestedMapping[$field] ?? null) === $index)>
                    {{ $h ?: '(kolom ' . ($index + 1) . ')' }}
                </option>
                @endforeach
            </select>
        </div>
        @error('mapping.' . $field) <p class="field-error text-right">{{ $message }}</p> @enderror
        @endforeach
    </div>

    <div class="bg-canvas border border-line rounded-lg p-4 mb-6 text-xs text-ink-soft">
        <i class="fas fa-circle-info mr-1"></i>
        Kolom yang ditandai sistem otomatis (kalau ada) sudah dipilihkan berdasarkan
        kemiripan nama kolom. Periksa kembali sebelum lanjut — data yang gagal
        dipetakan (misal departemen tidak dikenali) akan dilewati dan dilaporkan
        setelah proses selesai, baris lain tetap masuk.
    </div>

    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary">Proses Import</button>
        <a href="{{ route('sdm.pegawai.import.form') }}" class="btn btn-outline">Upload Ulang</a>
    </div>
</form>
@endsection