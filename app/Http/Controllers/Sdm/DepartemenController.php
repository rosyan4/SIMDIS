<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\Departemen;

class DepartemenController extends Controller
{
    public function index()
    {
        $departemens = Departemen::with(['manajer', 'subdepartemens.asistenManajer'])
            ->orderBy('nama_departemen')
            ->get();

        return view('sdm.departemen.index', compact('departemens'));
    }
}