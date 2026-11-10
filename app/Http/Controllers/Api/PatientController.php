<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    /**
     * Display a listing of patients matching Figma dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Patient::withCount('visits')->orderBy('medical_record_number', 'asc');

        $search = $request->input('search');
        if (is_string($search) && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ILIKE', "%{$term}%")
                  ->orWhere('medical_record_number', 'ILIKE', "%{$term}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * Register a new patient with auto-generated sequential MRN and encrypted National ID.
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $patient = DB::transaction(function () use ($validated) {
            $mrn = Patient::generateMedicalRecordNumber();
            $nationalId = trim($validated['national_id']);

            return Patient::create([
                'medical_record_number' => $mrn,
                'name' => trim($validated['name']),
                'national_id' => $nationalId,
                'national_id_hash' => Patient::hashNationalId($nationalId),
                'address' => !empty($validated['address']) ? trim($validated['address']) : null,
            ]);
        });

        $patient->visits_count = 0;

        return response()->json([
            'success' => true,
            'message' => 'Pasien berhasil didaftarkan.',
            'data' => $patient,
        ], 201);
    }

    /**
     * Find patient by Medical Record Number (MRN) for auto-fill in modal Buat Kunjungan.
     */
    public function findByMedicalRecordNumber(string $mrn): JsonResponse
    {
        $clean = strtoupper(trim($mrn));

        $patient = Patient::whereRaw('UPPER(medical_record_number) = ?', [$clean])->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => "Pasien dengan Nomor Rekam Medik '{$clean}' tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $patient,
        ]);
    }

    /**
     * Preview the next available sequential Medical Record Number.
     */
    public function getNextMedicalRecordNumber(): JsonResponse
    {
        $next = Patient::generateMedicalRecordNumber();

        return response()->json([
            'success' => true,
            'data' => [
                'medical_record_number' => $next,
            ],
        ]);
    }
}
