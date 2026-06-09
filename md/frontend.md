# Frontend Development Standard

Dokumen ini adalah sumber aturan frontend untuk seluruh modul Laravel pada project
ini. Tujuan utamanya adalah konsistensi, reuse, dan mencegah CSS/JavaScript yang
disalin ke setiap domain.

## 1. Aturan Utama

- Satu pola UI yang sama harus memiliki satu implementasi global.
- Jangan membuat `menu.css`, `customer.css`, `fleet.css`, atau file sejenis hanya
  untuk menyalin style Select2, DataTables, form, alert, button, atau card.
- Jangan membuat JavaScript CRUD per modul jika perbedaannya hanya URL, nama
  kolom, urutan tabel, label, atau pesan.
- Perbedaan konfigurasi dideklarasikan melalui HTML `data-*`; behavior tetap
  berada di JavaScript global.
- Class CSS reusable menggunakan nama fungsi seperti `.form-card`,
  `.record-name`, atau `.data-table-container`, bukan nama domain.
- File khusus modul hanya diperbolehkan untuk behavior atau visual yang benar-
  benar unik dan tidak masuk akal dipakai modul lain.

## 2. Ownership Asset

```text
public/assets/css/style.css
resources/css/components.css
resources/js/crud.js
resources/js/app.js
resources/views/components/
resources/views/pages/{module}/
```

### `public/assets/css/style.css`

Berisi design token dan komponen native aplikasi:

- layout, sidebar, navbar;
- typography;
- button, card, badge;
- table HTML dasar;
- form input, textarea, grid, switch, action;
- modal, toast, pagination, empty state;
- utility visual yang reusable.

Jangan menaruh selector khusus library pihak ketiga di file ini.

### `resources/css/components.css`

Berisi adapter tema untuk library pihak ketiga yang digunakan oleh CRUD:

- DataTables;
- Select2;
- SweetAlert2.

Style library ditulis satu kali di file ini dan di-import oleh `crud.js`.

### `resources/js/crud.js`

Berisi behavior umum seluruh modul CRUD:

- inisialisasi DataTables server-side;
- pembacaan konfigurasi kolom dari `<th data-*>`;
- inisialisasi Select2;
- flash message SweetAlert;
- konfirmasi dan request delete AJAX;
- reload DataTables setelah delete.

### `resources/js/app.js`

Berisi behavior shell aplikasi yang tidak spesifik CRUD, misalnya sidebar,
navbar, dropdown, atau shortcut global.

## 3. Larangan Duplikasi

Jangan membuat file berikut untuk modul CRUD biasa:

```text
resources/css/{module}.css
resources/js/{module}.js
```

Contoh yang dilarang:

- `fleet.css`, `customer.css`, dan `menu.css` berisi style Select2 yang sama;
- tiga fungsi `initializeSelect2()` pada tiga file;
- tiga implementasi delete AJAX yang hanya berbeda kata `fleet/customer/menu`;
- tiga konfigurasi SweetAlert dengan warna dan tombol yang sama;
- class `.menu-form-card`, `.fleet-form-card`, `.customer-form-card` yang semuanya
  hanya berisi `width: 100%`.

Aktifkan asset shared melalui marker layout:

```blade
@section('crud-assets', 'true')
```

File khusus modul baru boleh dibuat jika memenuhi semua kondisi:

1. behavior tidak dapat dinyatakan lewat konfigurasi `data-*`;
2. behavior tidak cocok menjadi komponen global;
3. implementasinya benar-benar berbeda, bukan hanya beda label atau endpoint;
4. alasan pengecualian dicatat dalam code review.

## 4. Struktur Modul CRUD

```text
resources/views/pages/{module}/
├── index.blade.php
├── create.blade.php
├── edit.blade.php
├── _form.blade.php
└── columns/
    ├── action.blade.php
    ├── name.blade.php
    └── status.blade.php
```

Folder `columns` hanya diperlukan untuk kolom dengan HTML. Kolom teks biasa
tidak memerlukan partial.

Komponen global yang tersedia:

- `<x-crud-actions>` untuk edit/delete;
- `<x-status-badge>` untuk status boolean;
- `<x-badge>` untuk badge umum;
- `<x-modal>` untuk modal;
- `<x-toast>` untuk halaman non-CRUD.

## 5. Halaman Index

Root halaman index wajib memakai `.js-crud-page`. Root menyimpan token dan flash
message, bukan konfigurasi kolom.

```blade
<div
  class="page-section active js-crud-page"
  data-csrf-token="{{ csrf_token() }}"
  data-success-message="{{ session('success') }}"
  data-info-message="{{ session('info') }}"
>
```

Tabel memakai `.js-data-table` dan mendeklarasikan konfigurasi dasar:

```blade
<table
  class="table js-data-table"
  id="customerTable"
  data-url="{{ route('customers.data') }}"
  data-order='[[1,"asc"]]'
  data-plural-label="customers"
  data-page-length="10"
>
```

### Atribut Tabel

- `data-url`: endpoint JSON Yajra, wajib.
- `data-order`: JSON array urutan DataTables, opsional.
- `data-plural-label`: label jamak untuk info/search/empty message, wajib.
- `data-page-length`: jumlah row per halaman, default `10`.
- `data-search-placeholder`: placeholder custom, opsional.

### Atribut Header Kolom

Setiap `<th>` wajib mendefinisikan `data-column`.

```blade
<th data-column="row_number" data-orderable="false" data-searchable="false">No</th>
<th data-column="action" data-orderable="false" data-searchable="false"></th>
<th data-column="name">Name</th>
<th data-column="location" data-orderable="false">Location</th>
<th data-column="status" data-name="is_active" data-align="center">Status</th>
```

- `data-column`: key pada response JSON Yajra.
- `data-name`: nama field database jika berbeda dari key response.
- `data-orderable="false"`: menonaktifkan sorting.
- `data-searchable="false"`: menonaktifkan pencarian.
- `data-align="center"`: membuat header dan isi kolom rata tengah.
- Nilai `orderable` dan `searchable` default adalah `true`.
- `row_number` dihitung oleh `crud.js`, tidak perlu dikirim oleh backend.
- Nomor mengikuti offset pagination server-side, sehingga halaman kedua melanjutkan
  nomor setelah halaman pertama.

Urutan kolom standar:

1. nomor;
2. action;
3. nama/label utama;
4. data pendukung;
5. status di kolom terakhir jika tersedia.

ULID dan primary key internal tidak ditampilkan.

## 6. Action Tabel

Partial action tidak boleh menyalin SVG dan markup tombol. Gunakan komponen:

```blade
<x-crud-actions
  :edit-url="route('customers.edit', $customer)"
  :delete-url="route('customers.destroy', $customer)"
  record-label="customer"
  :record-name="$customer->name"
/>
```

Komponen menghasilkan:

- link edit;
- tombol delete `.js-delete-record`;
- URL delete;
- label dan nama record untuk SweetAlert.

Tidak boleh menggunakan:

- `onclick`;
- `onsubmit`;
- `confirm()` browser;
- class domain seperti `.js-delete-customer`.

## 7. Status Tabel

Status boolean menggunakan:

```blade
<x-status-badge :active="$record->is_active" />
```

Label dapat diubah tanpa membuat component baru:

```blade
<x-status-badge
  :active="$record->is_active"
  active-label="Enabled"
  inactive-label="Disabled"
/>
```

## 8. Backend DataTables

Listing CRUD menggunakan Yajra server-side.

Service menyediakan query:

```php
public function getDataTableQuery(): Builder
{
    return Model::query()
        ->with('relation')
        ->select('models.*');
}
```

Controller membentuk response:

```php
return DataTables::eloquent($this->service->getDataTableQuery())
    ->addColumn('name', fn (Model $record) => view(...)->render())
    ->addColumn('status', fn (Model $record) => view(...)->render())
    ->addColumn('action', fn (Model $record) => view(...)->render())
    ->only(['action', 'name', 'email', 'status'])
    ->rawColumns(['action', 'name', 'status'])
    ->toJson();
```

Aturan:

- gunakan `only()` agar ULID dan field internal tidak bocor;
- gunakan `rawColumns()` hanya untuk kolom HTML internal;
- computed column harus memiliki `filterColumn()` jika searchable;
- kolom relation harus eager-loaded;
- endpoint `data` didefinisikan sebelum resource route.

## 9. Form Create dan Edit

Form utama selalu:

```blade
<form method="POST" action="..." class="card form-card">
```

`.form-card` sudah global dan full width. Jangan membuat class form per modul.

Partial `_form.blade.php` hanya berisi field:

- tidak memiliki tag `<form>`;
- tidak memiliki `@csrf`;
- tidak memiliki `@method`;
- tidak memiliki tombol save/cancel.

Create/edit bertanggung jawab atas wrapper dan action:

```blade
@csrf
@include('pages.customers._form')

<div class="form-actions">
  <a href="..." class="btn btn-secondary">Cancel</a>
  <button type="submit" class="btn btn-primary">Save Changes</button>
</div>
```

## 10. Field Form

Setiap field wajib memiliki:

- `label` dengan `for`;
- input dengan `id` yang sama;
- `old()` untuk mempertahankan nilai;
- aturan `required`, `maxlength`, dan type HTML yang sesuai;
- error di dekat field.

```blade
<div class="form-group">
  <label for="name" class="form-label">Name</label>
  <input
    id="name"
    name="name"
    class="form-input @error('name') form-input-error @enderror"
    value="{{ old('name', $record->name ?? '') }}"
    required
  >
  @error('name')
    <div class="form-error">{{ $message }}</div>
  @enderror
</div>
```

Gunakan:

- `.form-grid` untuk dua kolom desktop dan satu kolom mobile;
- `.form-group` standalone untuk field full width;
- `.form-hint` untuk bantuan;
- `.form-error` untuk error;
- `.form-input-error` untuk state invalid;
- `.form-textarea` untuk teks panjang.

Jangan membuat CSS baru hanya untuk mengubah lebar sebuah field. Gunakan layout
grid atau utility global yang sudah tersedia.

## 11. Boolean Field

Boolean wajib mengirim `0` saat unchecked:

```blade
<label class="check-control">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" @checked($isActive)>
  <span>
    <strong>Active</strong>
    <small>Show this record in active listings.</small>
  </span>
</label>
```

Gunakan pola `.check-control`. Jangan membuat toggle baru per modul.

## 12. Select2

Select yang membutuhkan pencarian memakai:

```blade
<select
  class="form-select js-select2"
  data-placeholder="Select a customer..."
>
```

Untuk select nullable:

```blade
data-allow-clear="true"
```

Aturan:

- tidak perlu fungsi `initializeSelect2()` per modul;
- tidak perlu import Select2 per modul;
- tidak perlu CSS Select2 per modul;
- state error ditangani global oleh `components.css`.

## 13. SweetAlert dan Feedback

Halaman CRUD memakai SweetAlert untuk:

- flash success/info;
- konfirmasi delete;
- success delete;
- error request.

Semua halaman index/create/edit CRUD menambahkan:

```blade
@section('crud-assets', 'true')
```

Marker ini membuat layout:

- memuat `resources/js/crud.js`;
- tidak merender toast lama;
- menggunakan SweetAlert untuk feedback.

Index juga menyediakan flash message pada `.js-crud-page`.

Delete endpoint harus mengembalikan JSON jika request mengharapkan JSON:

```json
{
  "message": "Record deleted successfully."
}
```

Jangan membuat konfigurasi warna SweetAlert per modul. Tema berada di `crud.js`
dan `components.css`.

## 14. CSS Reusable

Nama class harus berdasarkan fungsi:

```text
form-card
form-grid
form-actions
record-name
record-name-cell
record-icon
data-table-container
table-actions
table-action-btn
code-label
```

Nama berikut tidak diperbolehkan untuk style umum:

```text
menu-form-card
customer-table-wrapper
fleet-select
menu-alert
```

Sebelum menambah CSS:

1. cari class serupa dengan `rg`;
2. periksa apakah design token sudah ada;
3. tentukan apakah style native atau adapter library;
4. perluas class global bila behavior sama;
5. buat class khusus hanya jika visualnya unik.

## 15. JavaScript Reusable

`crud.js` membaca konfigurasi dari DOM. Modul baru tidak mengubah file ini jika
hanya memiliki:

- endpoint berbeda;
- kolom berbeda;
- order berbeda;
- label singular/plural berbeda;
- select berbeda;
- nama record berbeda.

Ubah `crud.js` hanya ketika behavior umum seluruh CRUD bertambah.

Jika ada behavior unik:

- buat file kecil khusus behavior tersebut;
- jangan import ulang DataTables, Select2, SweetAlert, atau `components.css`;
- muat file setelah `crud.js`;
- dokumentasikan alasan behavior tidak dapat digeneralisasi.

## 16. Vite

Entry shared terdaftar satu kali:

```js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/crud.js',
]
```

Jangan menambahkan entry Vite untuk setiap modul CRUD.

Halaman CRUD tidak memanggil `@vite()` sendiri. Cukup tambahkan:

```blade
@section('crud-assets', 'true')
```

Layout adalah satu-satunya tempat yang memuat entry `crud.js`.

## 17. Inline Style dan Script

Fitur baru tidak boleh menggunakan:

- atribut `style`;
- atribut `onclick`/`onsubmit`;
- tag `<script>` di Blade;
- URL hard-coded;
- warna hard-coded jika token tersedia.

Gunakan class, component, named route, dan ES module.

Kode lama yang masih inline dirapikan saat area tersebut disentuh, tanpa
melakukan refactor massal yang tidak terkait.

## 18. Checklist Modul Baru

Frontend modul CRUD baru hanya memerlukan:

```text
resources/views/pages/{module}/index.blade.php
resources/views/pages/{module}/create.blade.php
resources/views/pages/{module}/edit.blade.php
resources/views/pages/{module}/_form.blade.php
resources/views/pages/{module}/columns/*.blade.php
```

Tidak membuat CSS atau JavaScript module secara default.

Checklist implementasi:

1. root index memakai `.js-crud-page`;
2. table memakai `.js-data-table`;
3. setiap `<th>` memiliki `data-column`;
4. action memakai `<x-crud-actions>`;
5. status boolean memakai `<x-status-badge>`;
6. form memakai `.form-card`;
7. select searchable memakai `.js-select2`;
8. index/create/edit memiliki section `crud-assets`;
9. ULID tidak tampil dan tidak ada di JSON;
10. desktop, mobile, dan console sudah diperiksa.

## 19. Modal dan Form Async

### Modal Reusable

Modal memakai component `<x-modal>` dan dibuka secara deklaratif:

```blade
<button type="button" data-modal-target="exampleModal">Open</button>

<x-modal id="exampleModal" title="Example">
  <button type="button" data-modal-close>Cancel</button>
</x-modal>
```

Jangan menambahkan `onclick`, script Blade, atau JavaScript khusus modul untuk
open/close modal. Runtime modal berada di `crud.js`.

### Form Async Reusable

Form proses non-CRUD yang perlu memperbarui DataTable dapat memakai:

```blade
<form
  method="POST"
  action="{{ route('example.process') }}"
  class="js-async-form"
  data-success-title="Process complete"
>
  @csrf
  <button type="submit" data-loading-text="Processing...">Process</button>
</form>
```

Kontrak endpoint:

- sukses mengembalikan HTTP `200` dan JSON `message`;
- validasi mengembalikan HTTP `422` dengan object `errors`;
- kegagalan provider menggunakan status `502` dan JSON `message` yang aman;
- response tidak boleh memuat token, password, ULID internal yang tidak diperlukan,
  exception, atau stack trace.

Runtime global akan:

- mencegah submit normal;
- menonaktifkan tombol selama request;
- menampilkan error validasi atau provider lewat SweetAlert;
- menutup modal setelah sukses;
- mereset Select2;
- me-refresh semua `.js-data-table` pada halaman.

### DataTable Enrichment

Data eksternal atau realtime tidak boleh memperlambat response Yajra utama.
Tambahkan endpoint enrichment pada table:

```blade
<table
  class="table js-data-table"
  data-url="{{ route('records.data') }}"
  data-enrichment-url="{{ route('records.latest-values') }}"
>
```

Cell yang akan diisi setelah draw menggunakan kontrak berikut:

```blade
<span
  class="enrichment-value enrichment-loading"
  data-enrichment-ref="{{ $reference }}"
  data-enrichment-source-key="{{ $externalKey }}"
  data-enrichment-field="status"
>
  Loading...
</span>
```

Action peta yang baru aktif setelah enrichment berhasil:

```blade
<button
  type="button"
  class="table-action-btn is-disabled"
  data-enrichment-ref="{{ $reference }}"
  data-enrichment-source-key="{{ $externalKey }}"
  data-enrichment-map="map"
  data-map-modal-target="recordMapModal"
  aria-disabled="true"
  disabled
>
```

Runtime `crud.js` akan:

- mengirim hanya row pada halaman aktif;
- membatalkan request halaman sebelumnya ketika pagination berubah;
- memperbarui text, badge, dan action peta dari response JSON;
- menampilkan `Unavailable` tanpa menggagalkan DataTable;
- menjalankan proses kembali pada pagination, search, order, dan page length.

Reference harus berupa nilai opaque/HMAC. Jangan menaruh ULID dalam atribut DOM
atau payload enrichment.

Endpoint mengembalikan data berdasarkan reference:

```json
{
  "data": {
    "opaque-reference": {
      "status": {
        "text": "Running",
        "badge": "success"
      },
      "map": {
        "url": "https://maps.google.com/maps?q=-6.2,106.8&z=16&output=embed"
      }
    }
  }
}
```

Nilai `badge` mengikuti variant global: `success`, `warning`, `danger`, `info`,
atau `neutral`. Action tanpa URL tetap disabled. Action peta harus membuka
`<x-modal size="lg">` yang berisi iframe `[data-map-frame]`, bukan tab baru.
Gunakan URL Google Maps `output=embed` agar tidak memerlukan API key. Runtime
global mengosongkan `src` iframe saat modal ditutup.

## 20. Quality Gate

Jalankan:

```bash
npm run build
node --check resources/js/crud.js
php artisan view:cache
php artisan test
vendor/bin/pint --test
git diff --check
```

Browser verification wajib mencakup:

- DataTables memuat data server-side;
- search, order, pagination;
- action edit dan dialog delete;
- delete batal dan delete berhasil;
- Select2 tampil dan dapat dipilih;
- error form terlihat;
- flash SweetAlert hanya muncul satu kali;
- tidak ada horizontal overflow halaman mobile;
- console tidak memiliki error aplikasi.
