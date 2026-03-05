<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Hard rule: web login hanya admin + active
        $ok = Auth::attempt([
            'username'  => $data['username'],
            'password'  => $data['password'],
            'role'      => 'admin',
            'is_active' => 1,
        ]);

        if (!$ok) {
            return back()
                ->withErrors(['username' => 'Login gagal.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }
}
