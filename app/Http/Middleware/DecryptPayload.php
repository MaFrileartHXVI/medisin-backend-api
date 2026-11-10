<?php

namespace App\Http\Middleware;

use App\Services\PayloadCryptoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecryptPayload
{
    protected PayloadCryptoService $crypto;

    public function __construct(PayloadCryptoService $crypto)
    {
        $this->crypto = $crypto;
    }

    /**
     * Handle an incoming request.
     * Decrypts encrypted credentials in request payload.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $inputs = $request->all();

        // Standard decrypted fields
        $fieldsToDecrypt = ['username', 'email', 'password'];

        $modified = false;
        foreach ($fieldsToDecrypt as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $decrypted = $this->crypto->decrypt($request->input($field));
                $inputs[$field] = $decrypted;
                $modified = true;
            }
        }

        if ($modified) {
            $request->merge($inputs);
        }

        return $next($request);
    }
}
