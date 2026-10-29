<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AutenticacionZeroKnowledge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        $nonce = bin2hex(random_bytes(32));
        session()->put('login_nonce', $nonce);

        return view('auth.login', compact('nonce'));
    }

    public function store(LoginRequest $request, AutenticacionZeroKnowledge $autenticacion): RedirectResponse
    {
        $user = $autenticacion->autenticar(
            $request,
            $request->string('username')->toString(),
            $request->string('respuesta')->toString(),
        );

        if ($user === null) {
            return redirect()->route('login')
                ->withErrors(['login' => 'Usuario o contraseña incorrectos.'])
                ->withInput(['username' => $request->input('username')]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('inicio');
    }
}
