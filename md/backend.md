# Backend Development Standard

Dokumen ini menjadi standar implementasi backend untuk project Laravel ini.

## 1. Arsitektur

Gunakan pola **MVC + Service** dengan pembagian tanggung jawab berikut:

- **Route**: mendefinisikan endpoint dan nama route.
- **Controller**: menerima request, memanggil service, lalu mengembalikan response.
- **Form Request**: menangani authorization, normalisasi input, dan validasi.
- **Service**: menyimpan business logic, transaksi, dan orkestrasi query.
- **Model**: mendefinisikan relasi, cast, fillable, scope, dan perilaku data.
- **View/Resource**: menyajikan response tanpa business logic.

Controller tidak boleh berisi query kompleks atau business rule.

## 2. Struktur Folder

```text
app/
├── Http/
│   ├── Controllers/
│   └── Requests/{Domain}/
├── Models/
└── Services/
```

Penamaan menggunakan bentuk tunggal untuk class, misalnya `MenuController`,
`MenuService`, dan `Menu`.

## 3. Primary Key

- Semua tabel domain baru wajib menggunakan **ULID**.
- Migration menggunakan `$table->ulid('id')->primary()`.
- Foreign key ULID menggunakan `$table->foreignUlid(...)`.
- Model menggunakan trait `Illuminate\Database\Eloquent\Concerns\HasUlids`.
- Jangan membuat ID secara manual di controller atau service.

## 4. Migration

- Migration hanya menangani perubahan schema.
- Selalu sediakan method `down()` yang dapat membatalkan perubahan.
- Tambahkan foreign key, index, nullable, default, dan panjang kolom secara eksplisit.
- Gunakan `cascadeOnDelete()`, `restrictOnDelete()`, atau `nullOnDelete()` sesuai aturan domain.
- Data awal aplikasi ditempatkan di seeder, bukan di migration.

## 5. Model

- Gunakan `$fillable` untuk field yang dapat diisi melalui service.
- Definisikan cast boolean, integer, datetime, enum, dan JSON.
- Definisikan relasi dengan return type.
- Hindari query bisnis besar di model; gunakan scope untuk filter yang reusable.
- Hindari akses langsung ke request/session di model, kecuali helper presentasi yang memang terkait konteks HTTP.

## 6. Form Request

- Setiap operasi create/update wajib menggunakan Form Request.
- Normalisasi checkbox, string kosong, dan nilai nullable di `prepareForValidation()`.
- Gunakan `Rule::in`, `Rule::exists`, `Rule::unique`, dan custom validation bila dibutuhkan.
- Authorization harus dipindahkan ke policy ketika autentikasi dan role telah diterapkan.
- Jangan melakukan proses penyimpanan data di Form Request.

## 7. Service

- Service menerima data yang sudah tervalidasi.
- Gunakan `DB::transaction()` untuk operasi tulis yang harus atomik.
- Service mengembalikan model, collection, paginator, atau value object yang jelas.
- Jangan mengembalikan redirect/view dari service.
- Hindari service generik besar; satu service berfokus pada satu domain.

## 8. Controller

- Gunakan constructor injection untuk service.
- Gunakan route model binding untuk mengambil model.
- Method controller dibuat singkat dan mengikuti resource convention:
  `index`, `create`, `store`, `edit`, `update`, `destroy`.
- Controller bertugas menentukan response dan flash message.
- Jangan menggunakan `request()->all()`; gunakan `$request->validated()`.

## 9. Route

- Semua route wajib memiliki nama.
- Gunakan `Route::resource()` untuk CRUD standar.
- Gunakan prefix dan name group untuk modul yang memiliki banyak endpoint.
- URL menggunakan kebab-case, sedangkan route name menggunakan dot notation.

## 10. Seeder

- Seeder harus idempotent bila mungkin, misalnya menggunakan `updateOrCreate()`.
- `DatabaseSeeder` memanggil seeder domain.
- Jalankan schema dan data awal dengan:

```bash
php artisan migrate --seed
```

## 11. Response dan Error

- Web menggunakan redirect dengan flash message yang informatif.
- API menggunakan format JSON yang konsisten dan HTTP status code yang tepat.
- Jangan membocorkan exception, query SQL, credential, atau stack trace ke user.
- Error validasi ditangani oleh Form Request.

## 12. Keamanan

- Semua form mutasi wajib memakai CSRF token.
- Escape output user di Blade dengan `{{ }}`.
- Raw HTML hanya boleh berasal dari daftar internal yang terkontrol.
- Validasi URL, enum, ownership, dan foreign key.
- Gunakan Policy/Gate untuk authorization setelah modul autentikasi tersedia.
- Jangan menyimpan secret di source code; gunakan environment variable.

## 13. Testing

Minimal test untuk setiap modul backend:

- halaman index dapat diakses;
- create berhasil dan validasi gagal untuk input invalid;
- update berhasil;
- delete berhasil;
- authorization diuji ketika policy tersedia;
- relasi dan business rule penting diuji.

Gunakan `RefreshDatabase` agar test independen.

## 14. Integrasi API Eksternal

Integrasi provider eksternal harus dipisahkan dari controller dan service domain:

```text
Controller
  -> DomainService
      -> ExternalProviderService
```

Aturan implementasi:

- base URL, grant type, timeout, dan konfigurasi non-user disimpan di
  `config/services.php` serta `.env`;
- credential milik user/customer diambil dari model, tidak ditulis di source code;
- credential, access token, dan URL yang memuat query rahasia tidak boleh masuk log,
  flash message, response JSON, atau exception yang tampil ke user;
- gunakan Laravel HTTP Client, bukan cURL manual;
- selalu tetapkan connect timeout dan request timeout;
- ubah kegagalan koneksi, HTTP, dan payload menjadi exception domain yang aman;
- test wajib memakai `Http::fake()`, bukan provider produksi.

Token eksternal yang dipakai berulang harus disimpan di cache:

- key cache mengandung identitas pemilik dan fingerprint credential;
- TTL mengikuti `expires_in` dengan buffer sebelum waktu kedaluwarsa;
- perubahan username/password otomatis menghasilkan key baru;
- jika provider menolak token, hapus cache dan refresh maksimal satu kali;
- jangan melakukan retry tanpa batas.

Data realtime untuk DataTable besar tidak boleh dipanggil saat query Yajra sedang
dibentuk. Gunakan enrichment request terpisah setelah row halaman aktif selesai
dirender:

1. DataTable mengambil master data dari database lokal;
2. browser mengirim maksimal row yang sedang terlihat;
3. backend memvalidasi HMAC reference, bukan menerima ULID mentah;
4. backend mengelompokkan fleet berdasarkan customer;
5. token diambil dari cache per customer;
6. request device customer yang sama dijalankan dengan HTTP pool;
7. hasil singkat disimpan di cache per customer dan device;
8. browser mengisi cell tanpa me-reload DataTable.

Konfigurasi jumlah concurrent request dan TTL posisi berada di
`config/services.php`. Kegagalan satu device menghasilkan status unavailable dan
tidak menggagalkan posisi device lain.

Mapping status GPS fleet:

- `0`: `INACTIVE`;
- `1`: `Running`;
- `2`: `Stop`;
- `3`: `Idle`.

Sinkronisasi data menggunakan unique business key yang eksplisit. Operasi upsert
berjalan dalam transaction dan harus membedakan jumlah record `created`,
`updated`, dan `unchanged`. Record soft-deleted boleh dipulihkan jika business key
yang sama kembali dari provider.

## 15. Penyimpanan Credential Provider

Project ini saat ini menyimpan password akun GPS customer sebagai plaintext
karena nilainya harus dikirim kembali ke provider dan keputusan implementasi
memang mensyaratkan tanpa hash maupun enkripsi.

Konsekuensinya:

- field password wajib masuk `$hidden`;
- password tidak boleh dipilih untuk DataTables atau dikirim ke Blade;
- password tidak boleh dicatat ke log;
- akses database harus dibatasi;
- perubahan ke hash tidak boleh dilakukan karena hash tidak dapat dikembalikan
  menjadi credential asli.

## 16. Quality Gate

Sebelum perubahan dianggap selesai, jalankan:

```bash
vendor/bin/pint --test
php artisan test
php artisan route:list
```

Pastikan migration dapat dijalankan dari database kosong dan seluruh test lulus.
