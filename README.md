# MEDISIN - Backend REST API (SINTASI Fullstack Developer Test)

RESTful API backend sistem pendaftaran pasien dan manajemen kunjungan medis dengan enkripsi payload (client-to-server) dan database encryption (NIK & Email) berbasis **Laravel 11+** dan **PostgreSQL 18**.

---

## 🚀 Fitur Utama & Kepatuhan Kriteria Soal

1. **Autentikasi (Login)**:
   - Payload `user_name`/`email` dan `password` **terenkripsi secara end-to-end** dari Frontend ke Backend menggunakan AES-256-CBC (OpenSSL / CryptoJS compatible).
   - Backend memproses dekripsi otomatis sebelum autentikasi via middleware `DecryptPayload`.
   - Token-based API Authentication menggunakan **Laravel Sanctum**.

2. **Pendaftaran Pasien (Patient Registration)**:
   - **Auto-generate Nomor Rekam Medik (No RM)** secara berurutan dengan format: `RM0001`, `RM0002`, `RM0003`, dst.
   - **Database-Level Encryption**: Data **NIK** dan **Email** pasien dienkripsi penuh di database PostgreSQL menggunakan Eloquent encrypted casts (tersimpan dalam bentuk ciphertext Base64).
   - Validasi ketat (Negative Testing): pencegahan NIK ganda (blind index SHA-256).

3. **Buat Kunjungan (Patient Visits)**:
   - Pencarian otomatis (*auto-fetch*) data pasien terdaftar hanya dengan menginputkan **Nomor Rekam Medik (No RM)**.
   - **Jumlah Kunjungan Dinamis**: Dihitung otomatis secara akumulatif berdasarkan riwayat kunjungan yang telah dibuat pasien tersebut (`kunjungan_ke`).
   - Validasi ketat: proteksi No RM tidak terdaftar (Negative Test 422/404).

4. **Testing & Postman Collection**:
   - File Postman Collection lengkap disediakan pada path:  
     `postman/SINTASI_Medisin_API.postman_collection.json`
   - Dilengkapi pre-request script enkripsi otomatis dan assertions status code.

---

## 🛠️ Tech Stack & Requirements

- **PHP**: >= 8.2 (Teruji pada PHP 8.4)
- **Framework**: Laravel 11/12
- **Database**: PostgreSQL 18 (atau MySQL)
- **Authentication**: Laravel Sanctum
- **Kriptografi**: AES-256-CBC OpenSSL & SHA-256 Hash

---

## 📦 Instalasi & Konfigurasi

### 1. Masuk ke direktori backend
```bash
cd medisin-backend-api
```

### 2. Salin environment & Generate App Key
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Konfigurasi Database PostgreSQL di `.env`
Pastikan database `medisin_db` telah dibuat di PostgreSQL:
```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=medisin_db
DB_USERNAME=postgres
DB_PASSWORD=postgres

PAYLOAD_ENCRYPTION_KEY="SintasiSecurePayloadKey2026#X9!"
PAYLOAD_ENCRYPTION_IV="1234567890123456"
```

### 4. Jalankan Migrasi & Database Seeder
```bash
php artisan migrate:fresh --seed
```

### 5. Jalankan Server Pengembangan
```bash
php artisan serve --port=8000
```
Server REST API akan aktif di `http://localhost:8000/api`.

---

## 🔑 Data Akun Awal (Seeder)

| Nama Pengguna | Username | Email | Password | Role |
| :--- | :--- | :--- | :--- | :--- |
| **Admin SINTASI** | `admin` | `admin@medisin.id` | `password123` | Administrator |

---

## 📮 Postman Collection

File Postman Collection dapat diimpor langsung ke aplikasi Postman:
- File: [`postman/SINTASI_Medisin_API.postman_collection.json`](postman/SINTASI_Medisin_API.postman_collection.json)

### Koleksi mencakup:
1. **Authentication**:
   - `POST /api/auth/login` (Sukses dengan Pre-request CryptoJS Payload Encryption)
   - `POST /api/auth/login` (Negative Test: Wrong Password -> 401)
   - `POST /api/auth/login` (Negative Test: Empty Fields -> 422)
   - `GET /api/auth/me` (Profile Pengguna)
2. **Patients (Pendaftaran Pasien)**:
   - `GET /api/patients/next-mrn` (Preview sequence MRN berikutnya)
   - `GET /api/patients` (List data pasien)
   - `POST /api/patients` (Tambah pasien baru, simpan National ID & Email terenkripsi di DB)
   - `POST /api/patients` (Negative Test: Duplikasi National ID -> 422)
   - `GET /api/patients/by-mrn/{mrn}` (Pencarian via MRN untuk auto-fill data pasien)
   - `GET /api/patients/by-mrn/{mrn}` (Negative Test: MRN tidak ditemukan -> 404)
3. **Visits (Buat Kunjungan)**:
   - `GET /api/visits` (List riwayat kunjungan)
   - `POST /api/visits` (Buat kunjungan dengan MRN)
   - `POST /api/visits` (Negative Test: MRN fiktif -> 422)
4. **Crypto Utilities**:
   - `POST /api/crypto/encrypt` & `POST /api/crypto/decrypt`
5. **Logout**:
   - `POST /api/auth/logout`

---

## 🔒 Bukti Keamanan (Security Proof)

### 1. Enkripsi Kolom Database (NIK pada Pasien & Email pada Pengguna)
Sesuai syarat **Security Test** (*"Lakukan Encript data pada NIK dan email pada database"*):
- Kolom `national_id` (NIK) pada tabel `patients` disimpan dalam bentuk ciphertext terenkripsi AES-256-CBC.
- Kolom `email` pada tabel `users` disimpan dalam bentuk ciphertext terenkripsi AES-256-CBC.
- Kedua tabel dilengkapi kolom hash deterministik SHA-256 (`national_id_hash` & `email_hash`) sebagai *Blind Index* untuk validasi keunikan dan pencarian instan tanpa mendeskripsi seluruh database.

Jalankan query SQL berikut pada PostgreSQL:
```sql
SELECT medical_record_number, name, LEFT(national_id, 35) AS encrypted_nik FROM patients;
SELECT username, LEFT(email, 35) AS encrypted_email FROM users;
```
Hasil:
```text
 medical_record_number |    name    |            encrypted_nik            
-----------------------+------------+-------------------------------------
 RM0001                | NURFAZA M. | eyJpdiI6InlEaXQ5eUxPMXZ3RytEZEQ0... 
 RM0002                | NURFAZA D. | eyJpdiI6IlFvMkVVdk1wT3B4Y2s1NXQ2... 

 username |           encrypted_email           
----------+-------------------------------------
 admin    | eyJpdiI6Imx4QWIyS0FNZ3JWTXExQSs0... 
```

### 2. Payload Encryption (Client -> Server)
Frontend Quasar mengenkripsi kredensial sebelum transmisi jaringan:
```json
{
  "username": "U2FsdGVkX1+...encrypted...",
  "password": "U2FsdGVkX1+...encrypted..."
}
```
Backend Laravel menerima payload terenkripsi tersebut dan secara aman mendekripsinya menggunakan OpenSSL sebelum mencocokkan hash password `bcrypt`.

---

## 🧪 Menjalankan Automated Tests
```bash
php artisan test
```
Seluruh 7 skenario pengujian unit & integrasi (positive & negative cases) telah lulus 100%.

Juga dapat diuji secara otomatis via Postman / Newman:
```bash
npx --yes newman run postman/SINTASI_Medisin_API.postman_collection.json
```
(16 skenario request, 10 assertions otomatis, 100% lulus).
