@extends('layouts.app')
@section('title', 'Ganti Password')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center -mt-10">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="font-display text-2xl text-ink">Ganti Password</h1>
            <p class="text-ink-soft text-sm mt-2">Ini login pertama Anda. Buat password baru sebelum melanjutkan.</p>
        </div>

        <div class="card p-8">
            @if ($errors->any())
                <div class="mb-5 bg-[#FBE7E4] border border-[#C1483A]/30 text-[#8a352a] px-4 py-3 rounded-xl2 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.change.update') }}">
                @csrf
                <div class="mb-4">
                    <label class="field-label">Password Baru</label>
                    <input type="password" name="password" class="field-input" required>
                </div>
                <div class="mb-6">
                    <label class="field-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" class="field-input" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Simpan Password</button>
            </form>
        </div>
    </div>
</div>
@endsection