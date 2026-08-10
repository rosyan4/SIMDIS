@extends('layouts.app')
@section('title', 'Login')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center -mt-10">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            @if (file_exists(public_path('images/logo1.png')))
                <img src="{{ asset('images/logo1.png') }}" alt="Logo Perumdam Tirta Mayang" class="h-16 w-auto mx-auto mb-4">
            @else
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" class="mx-auto mb-3">
                    <path d="M12 2C12 2 5 10.5 5 15C5 18.866 8.134 22 12 22C15.866 22 19 18.866 19 15C19 10.5 12 2 12 2Z" fill="#17B8A6"/>
                </svg>
            @endif
            <h1 class="font-display text-2xl text-ink">Perumdam Tirta Mayang</h1>
            <p class="text-ink-soft text-sm mt-1">Sistem Informasi Dispensasi</p>
        </div>

        <div class="card p-8">
            @if ($errors->any())
                <div class="mb-5 bg-[#FBE7E4] border border-[#C1483A]/30 text-[#8a352a] px-4 py-3 rounded-xl2 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-4">
                    <label class="field-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="field-input" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="field-label">Password</label>
                    <input type="password" name="password" class="field-input" required>
                </div>
                <label class="flex items-center gap-2 mb-6 text-sm text-ink-soft">
                    <input type="checkbox" name="remember" class="rounded border-line"> Ingat saya
                </label>
                <button type="submit" class="btn btn-primary w-full">Masuk</button>
            </form>
        </div>

        <p class="text-center text-xs text-ink-soft mt-6">
            Lupa password atau kendala akun? Hubungi Divisi SDM.
        </p>
    </div>
</div>
@endsection