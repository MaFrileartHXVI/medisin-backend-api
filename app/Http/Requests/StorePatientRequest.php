<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:150',
            'national_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $hash = Patient::hashNationalId($value);
                    if (Patient::where('national_id_hash', $hash)->exists()) {
                        $fail('NIK sudah terdaftar pada sistem. Silakan gunakan NIK lain.');
                    }
                },
            ],
            'address' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama pasien wajib diisi.',
            'name.min' => 'Nama pasien minimal 3 karakter.',
            'national_id.required' => 'NIK pasien wajib diisi.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $firstError = $validator->errors()->first();
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $firstError ?: 'Validasi data pasien gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
