<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed exactly 1 Admin User for login
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin SINTASI',
                'username' => 'admin',
                'email' => 'admin@medisin.id',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Seed Initial Patients (Matching Figma mockup data)
        $p1 = Patient::updateOrCreate(
            ['medical_record_number' => 'RM0001'],
            [
                'name' => 'NURFAZA M.',
                'national_id' => '321412412312412',
                'national_id_hash' => Patient::hashNationalId('321412412312412'),
                'address' => 'Jl Wisata 2 No 18',
            ]
        );

        $p2 = Patient::updateOrCreate(
            ['medical_record_number' => 'RM0002'],
            [
                'name' => 'NURFAZA D.',
                'national_id' => '321412412355553',
                'national_id_hash' => Patient::hashNationalId('321412412355553'),
                'address' => 'Jl Wisata 2 No 18',
            ]
        );

        // 3. Seed Visits (8 visits for RM0001, 0 visits for RM0002 to match Figma mockup)
        for ($i = 1; $i <= 8; $i++) {
            Visit::create([
                'patient_id' => $p1->id,
            ]);
        }
    }
}
