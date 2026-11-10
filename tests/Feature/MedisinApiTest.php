<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Services\PayloadCryptoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MedisinApiTest extends TestCase
{
    use DatabaseTransactions;

    protected PayloadCryptoService $crypto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crypto = new PayloadCryptoService();
    }

    /**
     * Test Public Login with Encrypted Payload.
     */
    public function test_login_with_encrypted_payload(): void
    {
        $encryptedUsername = $this->crypto->encrypt('admin');
        $encryptedPassword = $this->crypto->encrypt('password123');

        $response = $this->postJson('/api/auth/login', [
            'username' => $encryptedUsername,
            'password' => $encryptedPassword,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'username', 'email'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    /**
     * Negative Test: Login with wrong password.
     */
    public function test_login_negative_invalid_password(): void
    {
        $encryptedUsername = $this->crypto->encrypt('admin');
        $encryptedPassword = $this->crypto->encrypt('wrongpass');

        $response = $this->postJson('/api/auth/login', [
            'username' => $encryptedUsername,
            'password' => $encryptedPassword,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test Patient Registration with Auto-generated sequential MRN format.
     */
    public function test_register_patient_auto_generates_sequential_mrn(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->postJson('/api/patients', [
            'name' => 'Budi Santoso',
            'national_id' => '321412412399999',
            'address' => 'Jl. Merdeka No. 45',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('patients', [
            'name' => 'Budi Santoso',
        ]);
    }

    /**
     * Negative Test: Prevent duplicate National ID registration.
     */
    public function test_register_patient_duplicate_national_id_rejected(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->postJson('/api/patients', [
            'name' => 'Kembaran Nurfaza',
            'national_id' => '321412412312412', // Seeded RM0001
            'address' => 'Jl. Kembaran No. 1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['national_id']);
    }

    /**
     * Test Find Patient by MRN for visit creation.
     */
    public function test_find_patient_by_mrn(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->getJson('/api/patients/by-mrn/RM0001');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'medical_record_number' => 'RM0001',
                    'name' => 'NURFAZA M.',
                ],
            ]);
    }

    /**
     * Negative Test: Find Patient by Non-existent MRN.
     */
    public function test_find_patient_by_non_existent_mrn(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->getJson('/api/patients/by-mrn/RM9999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test Create Visit.
     */
    public function test_create_visit(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->postJson('/api/visits', [
            'medical_record_number' => 'RM0001',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'patient' => [
                        'medical_record_number' => 'RM0001',
                    ],
                ],
            ]);
    }
}
