<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medical_record_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $clean = strtoupper(trim($value));
                    if (!Patient::whereRaw('UPPER(medical_record_number) = ?', [$clean])->exists()) {
                        $fail("Nomor Rekam Medik '{$clean}' tidak terdaftar.");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'medical_record_number.required' => 'Nomor Rekam Medik wajib diisi.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $firstError = $validator->errors()->first();
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $firstError ?: 'Validasi data kunjungan gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
