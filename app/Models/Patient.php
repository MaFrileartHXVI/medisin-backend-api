<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Patient extends Model
{
    use HasFactory;

    protected $table = 'patients';

    protected $fillable = [
        'medical_record_number',
        'name',
        'national_id',
        'national_id_hash',
        'address',
    ];

    /**
     * Database-level automatic AES-256 encryption for sensitive patient identity data (NIK).
     */
    protected function casts(): array
    {
        return [
            'national_id' => 'encrypted',
        ];
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'patient_id');
    }

    /**
     * Generate sequential Medical Record Number (MRN) format: RM0001, RM0002, etc.
     */
    public static function generateMedicalRecordNumber(): string
    {
        return DB::transaction(function () {
            $last = DB::table('patients')
                ->where('medical_record_number', 'LIKE', 'RM%')
                ->orderByRaw("CAST(SUBSTRING(medical_record_number FROM 3) AS INTEGER) DESC")
                ->lockForUpdate()
                ->value('medical_record_number');

            if (!$last) {
                return 'RM0001';
            }

            $num = (int) substr($last, 2);
            return sprintf('RM%04d', $num + 1);
        });
    }

    /**
     * Deterministic SHA-256 hashing helper for blind index lookup.
     */
    public static function hashNationalId(string $id): string
    {
        return hash('sha256', trim($id));
    }
}
