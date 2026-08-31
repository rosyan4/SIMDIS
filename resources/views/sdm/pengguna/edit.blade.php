@extends('layouts.app')
@section('title', 'Ubah Pengguna')
@section('page-title', 'Ubah Pengguna')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink">Ubah Pengguna — {{ $user->name }}</h1>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('sdm.pengguna.update', $user) }}">
        @csrf
        @method('PUT')
        @include('sdm.pengguna._form', ['user' => $user, 'departemens' => $departemens])

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check"></i> Simpan Perubahan
            </button>
            <a href="{{ route('sdm.pengguna.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection