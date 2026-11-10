<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVisitRequest;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    /**
     * Display a listing of patient visits.
     */
    public function index(): JsonResponse
    {
        $visits = Visit::with('patient')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $visits,
        ]);
    }

    /**
     * Store a new patient visit.
     */
    public function store(StoreVisitRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $cleanMrn = strtoupper(trim($validated['medical_record_number']));
        $patient = Patient::whereRaw('UPPER(medical_record_number) = ?', [$cleanMrn])->firstOrFail();

        $visit = Visit::create([
            'patient_id' => $patient->id,
        ]);

        $visit->load('patient');

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan pasien berhasil dibuat.',
            'data' => $visit,
        ], 201);
    }
}
