<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

class ShowLoginController
{
    public function __invoke(Request $request)
    {
        return view('auth.login');
    }
}
