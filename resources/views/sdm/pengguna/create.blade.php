@extends('layouts.app')
@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna')

@section('content')
<div class="mb-8">
    <p class="text-xs font-semibold tracking-widest text-accent uppercase mb-1">Admin SDM</p>
    <h1 class="font-display text-3xl text-ink">Tambah Pengguna</h1>
</div>

<div class="card p-6 max-w-2xl">
    <form method="POST" action="{{ route('sdm.pengguna.store') }}">
        @csrf
        @include('sdm.pengguna._form', ['user' => null, 'departemens' => $departemens])

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check"></i> Simpan Pengguna
            </button>
            <a href="{{ route('sdm.pengguna.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection