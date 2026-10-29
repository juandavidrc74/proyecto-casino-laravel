<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class AutenticacionZeroKnowledge
{
    public function autenticar(Request $request, string $username, string $respuesta): ?User
    {
        $nonce = $request->session()->pull('login_nonce');

        if (! is_string($nonce) || $nonce === '') {
            return null;
        }

        $user = User::where('username', $username)->first();
        $hashGuardado = $user?->password ?? str_repeat('0', 64);
        $respuestaEsperada = hash('sha256', $hashGuardado.$nonce);
        $valida = hash_equals($respuestaEsperada, $respuesta);

        return $user !== null && $valida ? $user : null;
    }
}
