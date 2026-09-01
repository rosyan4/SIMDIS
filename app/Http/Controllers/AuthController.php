<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Login pakai email + password. NIK tidak dipakai untuk login akun
     * sistem (User) — NIK adalah atribut Pegawai (data master terpisah,
     * tidak login), bukan atribut akun User.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Akun Anda tidak aktif. Hubungi Admin SDM.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($user->must_change_password) {
            return redirect()->route('password.change.form');
        }

        return redirect()->intended($user->dashboardRoute());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}