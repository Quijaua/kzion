<?php

namespace App\Http\Controllers;

use App\Support\JsChallenge;
use App\Support\TrustedDevices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class JsChallengeController
{
    public function verify(Request $request): JsonResponse
    {
        $token = (string) $request->input('token', '');
        $nonce = (string) $request->input('nonce', '');

        if (! JsChallenge::verifyToken($request, $token) || ! JsChallenge::verifyProof($token, $nonce)) {
            return response()->json(['ok' => false], 403)
                ->header('Cache-Control', 'no-store');
        }

        // Dispositivo confiável que caiu no desafio por mudança de rede:
        // depois de provar que é humano, passa a valer na rede nova.
        if (TrustedDevices::enabled() && $request->cookies->has(TrustedDevices::cookieName())) {
            TrustedDevices::rebind($request);
        }

        $ttl = (int) config('jschallenge.pass_ttl', 43200);

        $cookie = Cookie::create(
            name: config('jschallenge.cookie', '__jsc'),
            value: JsChallenge::issuePass($request),
            expire: time() + $ttl,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: true,
            sameSite: Cookie::SAMESITE_LAX,
        );

        return response()->json(['ok' => true])
            ->header('Cache-Control', 'no-store')
            ->withCookie($cookie);
    }
}
