<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayloadCryptoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptoController extends Controller
{
    protected PayloadCryptoService $crypto;

    public function __construct(PayloadCryptoService $crypto)
    {
        $this->crypto = $crypto;
    }

    /**
     * Encrypt a string utility.
     */
    public function encrypt(Request $request): JsonResponse
    {
        $text = $request->input('text', '');

        return response()->json([
            'original' => $text,
            'encrypted' => $this->crypto->encrypt($text),
        ]);
    }

    /**
     * Decrypt a string utility.
     */
    public function decrypt(Request $request): JsonResponse
    {
        $text = $request->input('text', '');

        return response()->json([
            'ciphertext' => $text,
            'decrypted' => $this->crypto->decrypt($text),
        ]);
    }
}

