# Frontend Development Standard

Dokumen ini menjadi standar implementasi frontend untuk project Laravel ini.

## 1. Prinsip Umum

- Gunakan Blade untuk struktur halaman dan Vite untuk asset JavaScript/CSS modular.
- Pertahankan konsistensi dengan design token di `public/assets/css/style.css`.
- Pisahkan markup, behavior, dan data source.
- Hindari inline style dan inline script untuk fitur baru.
- Setiap fitur harus dapat digunakan pada desktop dan mobile.

## 2. Struktur

```text
resources/
├── js/
│   ├── app.js
│   └── {feature}.js
└── views/
    ├── components/
    ├── layouts/
    ├── pages/{feature}/
    └── partials/
```

- Gunakan component untuk elemen reusable.
- Gunakan partial untuk potongan halaman atau kolom tabel yang memiliki markup khusus.
- JavaScript khusus modul ditempatkan di file terpisah, misalnya `resources/js/menu.js`.

## 3. Asset dan Dependency

- Install dependency frontend melalui NPM dan simpan versinya di `package.json`.
- Muat asset khusus halaman dengan `@vite()` melalui `@push('scripts')`.
- Jangan menambahkan CDN baru jika package dapat dikelola melalui NPM.
- Jangan menaruh credential, token privat, atau konfigurasi sensitif di bundle frontend.
- Jalankan `npm run build` setelah menambah atau mengubah entry Vite.

## 4. Blade

- Escape data dinamis dengan `{{ }}`.
- Gunakan `{!! !!}` hanya untuk HTML internal yang sudah dikontrol.
- Hindari query database dan business logic di Blade.
- Gunakan named route melalui `route()`, bukan URL hard-coded.
- Form mutasi wajib memakai `@csrf` dan method spoofing bila diperlukan.
- Gunakan stack `styles` dan `scripts` untuk kebutuhan khusus halaman.

## 5. Form

- Setiap input wajib memiliki `label` dan `id` yang sesuai.
- Tampilkan error validasi di dekat field terkait.
- Pertahankan input menggunakan `old()` setelah validasi gagal.
- Form utama menggunakan lebar penuh container.
- Grid form boleh dua kolom pada desktop dan wajib menjadi satu kolom pada mobile.
- Select dengan opsi dinamis atau panjang menggunakan **Select2**.
- Select2 diinisialisasi hanya pada class eksplisit seperti `.js-select2`.

## 6. DataTables

- Listing data yang berpotensi besar wajib menggunakan **Yajra DataTables server-side**.
- Blade hanya menyediakan header tabel dan endpoint AJAX.
- Query tetap berada di Service atau query object.
- Controller membentuk response Yajra dan partial Blade menangani kolom HTML.
- **Kolom action WAJIB berada di posisi pertama (kolom paling kiri).** Ini berlaku untuk semua tabel CRUD di seluruh aplikasi.
- Kolom action tidak boleh searchable atau orderable.
- Primary key internal seperti ULID tidak ditampilkan kecuali memang dibutuhkan user.
- Setelah mutasi AJAX, reload tabel tanpa mengubah halaman aktif:

```js
table.ajax.reload(null, false);
```

### Struktur Kolom Standar

```text
Kolom 0: Action (edit, delete, toggle — tidak searchable, tidak orderable)
Kolom 1: Nama / Label utama (searchable, orderable)
Kolom 2+: Data pendukung
Kolom N: Status (opsional, di urutan terakhir jika ada)
```

### Contoh DataTable Columns Definition

```js
columns: [
    { data: 'action', name: 'action', orderable: false, searchable: false },  // WAJIB index 0
    { data: 'name', name: 'name' },
    { data: 'type', name: 'type' },
    { data: 'status', name: 'status' },
],
```

## 7. SweetAlert

- Gunakan **SweetAlert2** untuk konfirmasi tindakan destruktif.
- Pesan harus menjelaskan objek dan dampak tindakan.
- Sediakan tombol batal untuk delete.
- Tampilkan success/error berdasarkan response server.
- Jangan mengandalkan alert browser native untuk fitur baru.

## 8. JavaScript

- Gunakan ES module dan import dependency secara eksplisit.
- Scope inisialisasi berdasarkan elemen root halaman.
- Pastikan script aman ketika elemen target tidak ada.
- Gunakan `data-*` untuk mengirim URL, CSRF token, atau konfigurasi non-sensitif dari Blade.
- Untuk request AJAX, kirim header `Accept: application/json` dan `X-CSRF-TOKEN`.
- Tangani response gagal dan tampilkan pesan yang dapat dipahami user.

## 9. CSS

- Gunakan CSS variable yang sudah tersedia untuk warna, radius, shadow, dan transition.
- Hindari nilai warna baru jika token yang sesuai sudah tersedia.
- Class diberi nama berdasarkan fungsi atau komponen, bukan posisi sementara.
- Style library pihak ketiga harus disesuaikan dengan tema aplikasi dalam selector yang scoped.
- Jangan mengubah style global library jika hanya satu modul yang menggunakannya.

## 10. Navbar dan Navigasi

- Navbar hanya memuat kontrol yang benar-benar digunakan.
- Sidebar dibentuk dari data backend dan active state berasal dari route.
- Link eksternal dengan target tab baru wajib menggunakan `rel="noopener noreferrer"`.
- Semua tombol ikon wajib memiliki `title` atau accessible name.

## 11. Responsivitas dan Aksesibilitas

- Breakpoint utama mengikuti CSS project, terutama `768px`.
- Pastikan tidak ada horizontal overflow pada viewport mobile.
- Gunakan elemen semantik: `button` untuk action dan `a` untuk navigasi.
- State focus harus terlihat.
- Jangan menyampaikan status hanya melalui warna; sertakan teks atau ikon.

## 12. Quality Gate

Sebelum frontend dianggap selesai, jalankan:

```bash
npm run build
node --check resources/js/{feature}.js
php artisan view:cache
php artisan test
```

Lakukan pemeriksaan browser untuk:

- desktop dan mobile;
- console error;
- loading DataTables;
- search, ordering, dan pagination;
- Select2;
- konfirmasi SweetAlert;
- submit form dan pesan feedback.
