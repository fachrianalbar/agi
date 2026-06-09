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
├── css/
│   └── {feature}.css
└── views/
    ├── components/
    ├── layouts/
    ├── pages/{feature}/
    │   ├── index.blade.php
    │   ├── create.blade.php
    │   ├── edit.blade.php
    │   ├── _form.blade.php
    │   └── columns/
    │       ├── action.blade.php
    │       ├── name.blade.php
    │       └── status.blade.php
    └── partials/
```

- Gunakan component untuk elemen reusable.
- Gunakan partial untuk potongan halaman atau kolom tabel yang memiliki markup khusus.
- JavaScript khusus modul ditempatkan di file terpisah, misalnya `resources/js/menu.js`.
- CSS khusus modul ditempatkan di `resources/css/{feature}.css` dan di-import dari JS module-nya.

## 3. Asset dan Dependency

- Install dependency frontend melalui NPM dan simpan versinya di `package.json`.
- Muat asset khusus halaman dengan `@vite()` melalui `@push('scripts')`.
- Jangan menambahkan CDN baru jika package dapat dikelola melalui NPM.
- Jangan menaruh credential, token privat, atau konfigurasi sensitif di bundle frontend.
- Jalankan `npm run build` setelah menambah atau mengubah entry Vite.
- Daftarkan setiap JS module baru di `vite.config.js` dalam array `input`.

## 4. Blade

- Escape data dinamis dengan `{{ }}`.
- Gunakan `{!! !!}` hanya untuk HTML internal yang sudah dikontrol.
- Hindari query database dan business logic di Blade.
- Gunakan named route melalui `route()`, bukan URL hard-coded.
- Form mutasi wajib memakai `@csrf` dan method spoofing bila diperlukan.
- Gunakan stack `styles` dan `scripts` untuk kebutuhan khusus halaman.
- Halaman index dan form yang menggunakan JS module wajib menambahkan `@push('scripts')`:
  ```blade
  @push('scripts')
    @vite('resources/js/{feature}.js')
  @endpush
  ```

### Pola Halaman Index

```blade
@extends('layouts.app')

@section('title', '{Feature Label}')
@section('page-title', '{Feature Label}')
@section('sweetalert-feedback', 'true')

@section('content')
<div
  class="page-section active"
  id="{feature}IndexPage"
  data-table-url="{{ route('{feature}.data') }}"
  data-csrf-token="{{ csrf_token() }}"
  data-success-message="{{ session('success') }}"
  data-info-message="{{ session('info') }}"
>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">{Feature Label}</h1>
        <p class="page-header-subtitle">{Subtitle description}</p>
      </div>
      <a href="{{ route('{feature}.create') }}" class="btn btn-primary btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New {Feature}
      </a>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">All {Features}</h3>
          <p class="card-subtitle">{Card subtitle}</p>
        </div>
      </div>

      <div class="data-table-container">
        <table class="table" id="{feature}Table">
          <thead>
            <tr>
              <th></th>  {{-- Kolom Action — selalu pertama --}}
              <th>Name</th>
              <th>...</th>
              <th>Status</th>  {{-- Kolom Status — selalu terakhir --}}
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/{feature}.js')
@endpush
```

**Atribut `data-*` pada container page:**
- `data-table-url` — endpoint DataTables JSON (wajib)
- `data-csrf-token` — CSRF token untuk AJAX delete (wajib)
- `data-success-message` — flash message success (opsional)
- `data-info-message` — flash message info (opsional)

### Pola Halaman Create

```blade
@extends('layouts.app')

@section('title', 'Create {Feature}')
@section('page-title', 'Create {Feature}')
@section('sweetalert-feedback', 'true')

@section('content')
<div class="page-section active">
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Create {Feature}</h1>
        <p class="page-header-subtitle">{Subtitle}</p>
      </div>
      <a href="{{ route('{feature}.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    <form method="POST" action="{{ route('{feature}.store') }}" class="card {feature}-form-card" id="{feature}Form">
      @csrf
      @include('pages.{feature}._form', ['{featureVar}' => null])
      <div class="form-actions">
        <a href="{{ route('{feature}.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Create {Feature}</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/{feature}.js')
@endpush
```

### Pola Halaman Edit

```blade
@extends('layouts.app')

@section('title', 'Edit {Feature}')
@section('page-title', 'Edit {Feature}')
@section('sweetalert-feedback', 'true')

@section('content')
<div class="page-section active">
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Edit {Feature}</h1>
        <p class="page-header-subtitle">Update {feature} details for {{ ${featureVar}->name }}</p>
      </div>
      <a href="{{ route('{feature}.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    <form method="POST" action="{{ route('{feature}.update', ${featureVar}) }}" class="card {feature}-form-card" id="{feature}Form">
      @csrf
      @method('PUT')
      @include('pages.{feature}._form')
      <div class="form-actions">
        <a href="{{ route('{feature}.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/{feature}.js')
@endpush
```

## 5. Form

- Setiap input wajib memiliki `label` dan `id` yang sesuai.
- Tampilkan error validasi di dekat field terkait menggunakan `<div class="form-error">`.
- Input dengan error wajib diberi class `form-input-error`:
  ```blade
  <input ... class="form-input @error('field') form-input-error @enderror" ...>
  @error('field') <div class="form-error">{{ $message }}</div> @enderror
  ```
- Hint text menggunakan `<div class="form-hint">`, bukan `<span>`.
- Pertahankan input menggunakan `old()` setelah validasi gagal.
- Form utama menggunakan lebar penuh container.
- Grid form menggunakan class `form-grid` (2 kolom desktop, 1 kolom mobile).
- Field full-width (seperti address, notes) ditempatkan di luar `form-grid` dalam `<div class="form-group">` standalone.
- Select dengan opsi dinamis atau panjang menggunakan **Select2**.
- Select2 diinisialisasi hanya pada class eksplisit seperti `.js-select2`.

### Struktur Form (`_form.blade.php`)

Partial `_form.blade.php` HANYA berisi field-field form (tidak ada `<form>` tag, `@csrf`, atau form actions). Form tag, CSRF, method spoofing, dan tombol actions diletakkan di create/edit blade.

```blade
@php
  $isEdit = isset(${featureVar}) && ${featureVar}->exists;
  $isActive = old('is_active', ${featureVar}->is_active ?? true);
@endphp

<div class="form-grid">
  <div class="form-group">
    <label for="name" class="form-label">Full Name</label>
    <input ... class="form-input @error('name') form-input-error @enderror" ...>
    @error('name') <div class="form-error">{{ $message }}</div> @enderror
  </div>
  ...
</div>

{{-- Full-width fields di luar form-grid --}}
<div class="form-group">
  <label for="notes" class="form-label">Notes</label>
  <textarea ...></textarea>
</div>

{{-- Boolean toggle menggunakan pola check-control --}}
<div class="form-group form-group-switch">
  <label class="form-label">Account Status</label>
  <label class="check-control">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked($isActive)>
    <span>
      <strong>Active</strong>
      <small>Description of what this toggle does.</small>
    </span>
  </label>
</div>
```

### Boolean Toggle

Untuk field boolean (is_active, dll), gunakan **pola `check-control`** — BUKAN `toggle toggle-labeled`:

```blade
<div class="form-group form-group-switch">
  <label class="form-label">Label</label>
  <label class="check-control">
    <input type="hidden" name="field" value="0">
    <input type="checkbox" name="field" value="1" @checked($isActive)>
    <span>
      <strong>Label Aktif</strong>
      <small>Deskripsi.</small>
    </span>
  </label>
</div>
```

### CSS untuk Form Card

Setiap modul wajib memiliki class `.${feature}-form-card` di `style.css`. **Form harus full width** mengikuti container. Tidak boleh ada batasan `max-width`:

```css
.{feature}-form-card {
  width: 100%;
}
```

## 6. DataTables

- Listing data yang berpotensi besar wajib menggunakan **Yajra DataTables server-side**.
- Blade hanya menyediakan header tabel dan endpoint AJAX.
- Query berada di Service layer melalui method `getDataTableQuery()`.
- Controller membentuk response Yajra melalui method `data()` dan partial Blade menangani kolom HTML.
- **Wrapper tabel WAJIB menggunakan class `data-table-container`**, BUKAN `table-wrapper`.
- Class `table-wrapper` hanya digunakan untuk tabel HTML statis (non-DataTables).

### Struktur Kolom Standar

```text
Kolom 0: Action (edit, delete, toggle — tidak searchable, tidak orderable)
Kolom 1: Nama / Label utama (searchable, orderable)
Kolom 2+: Data pendukung
Kolom N: Status (opsional, di urutan terakhir jika ada)
```

- **Kolom action WAJIB berada di posisi pertama (kolom paling kiri).** Ini berlaku untuk semua tabel CRUD di seluruh aplikasi.
- Kolom action tidak boleh searchable atau orderable.
- Primary key internal seperti ULID tidak ditampilkan kecuali memang dibutuhkan user.
- Setelah mutasi AJAX, reload tabel tanpa mengubah halaman aktif:

```js
table.ajax.reload(null, false);
```

### Contoh DataTable Columns Definition

```js
columns: [
    { data: 'action', name: 'action', orderable: false, searchable: false },  // WAJIB index 0
    { data: 'name', name: 'name' },
    { data: 'email', name: 'email' },
    { data: 'location', name: 'location', orderable: false },  // computed field
    { data: 'status', name: 'is_active' },  // WAJIB di urutan terakhir
],
```

### Controller DataTables Endpoint

```php
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\Facades\DataTables;

public function data(): JsonResponse
{
    return DataTables::eloquent($this->service->getDataTableQuery())
        ->addColumn('name', fn (Model $m) => view('pages.{feature}.columns.name', [/* vars */])->render())
        ->addColumn('status', fn (Model $m) => view('pages.{feature}.columns.status', [/* vars */])->render())
        ->addColumn('action', fn (Model $m) => view('pages.{feature}.columns.action', [/* vars */])->render())
        ->filterColumn('computed_field', function (Builder $q, string $kw): void {
            // custom filter logic
        })
        ->only(['action', 'name', ..., 'status'])
        ->rawColumns(['action', 'name', 'status'])
        ->toJson();
}
```

### Route DataTables

Route `data` WAJIB didefinisikan SEBELUM resource route agar tidak tertangkap route parameter:

```php
Route::get('{feature}/data', [Controller::class, 'data'])->name('{feature}.data');
Route::resource('{feature}', Controller::class)->except('show');
```

### Service getDataTableQuery

```php
public function getDataTableQuery(): Builder
{
    return Model::query()->select([
        'id',
        'name',
        'email',
        // ... hanya kolom yang dibutuhkan DataTable
        'is_active',
    ]);
}
```

### CSS DataTables — `data-table-container`

Wrapper `data-table-container` memiliki styling khusus untuk elemen DataTables (search input, pagination, layout, border). Styling ini SUDAH tersedia di `style.css` dengan selector `.data-table-container`. Setiap modul DataTables cukup menggunakan class wrapper ini tanpa perlu menambah CSS tambahan.

## 7. SweetAlert

- Gunakan **SweetAlert2** untuk konfirmasi tindakan destruktif.
- Pesan harus menjelaskan objek dan dampak tindakan.
- Sediakan tombol batal untuk delete.
- Tampilkan success/error berdasarkan response server.
- Jangan mengandalkan alert browser native untuk fitur baru.

### Inisialisasi di JS Module

```js
import Swal from 'sweetalert2';
window.Swal = Swal;

const swalTheme = {
    confirmButtonColor: '#E2725B',
    cancelButtonColor: '#A08980',
};
```

### Konfirmasi Delete via AJAX

```js
tableElement.addEventListener('click', async (event) => {
    const button = event.target.closest('.js-delete-{feature}');
    if (!button) return;

    const result = await Swal.fire({
        ...swalTheme,
        icon: 'warning',
        title: 'Delete {feature}?',
        text: `{Feature} "${button.dataset.name}" will be permanently deleted.`,
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch(button.dataset.url, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': page.dataset.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Failed.');

        await Swal.fire({ ...swalTheme, icon: 'success', title: 'Deleted', text: payload.message, timer: 1800, showConfirmButton: false });
        table.ajax.reload(null, false);
    } catch (error) {
        Swal.fire({ ...swalTheme, icon: 'error', title: 'Delete failed', text: error.message });
    }
});
```

### Flash Messages

Halaman index yang menggunakan SweetAlert wajib:
1. Menambahkan `@section('sweetalert-feedback', 'true')` — ini menyembunyikan `<x-toast />` component
2. Menyediakan `data-success-message` dan `data-info-message` pada container page
3. Memanggil `showFlashMessage(page)` di JS:

```js
function showFlashMessage(page) {
    const message = page?.dataset.successMessage || page?.dataset.infoMessage;
    if (!message) return;

    Swal.fire({
        ...swalTheme,
        icon: page.dataset.successMessage ? 'success' : 'info',
        title: page.dataset.successMessage ? 'Success' : 'Information',
        text: message,
        timer: 2200,
        showConfirmButton: false,
    });
}
```

### Column Action Partial

Delete button menggunakan `<button>` dengan class `js-delete-{feature}`, BUKAN `<form>` dengan inline `onsubmit`:

```blade
<div class="table-actions">
  <a href="{{ route('{feature}.edit', $record) }}" class="table-action-btn" title="Edit">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">...</svg>
  </a>
  <button type="button"
    class="table-action-btn table-action-danger js-delete-{feature}"
    title="Delete"
    data-name="{{ $record->name }}"
    data-url="{{ route('{feature}.destroy', $record) }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">...</svg>
  </button>
</div>
```

## 8. JavaScript

- Gunakan ES module dan import dependency secara eksplisit.
- Scope inisialisasi berdasarkan elemen root halaman.
- Pastikan script aman ketika elemen target tidak ada.
- Gunakan `data-*` untuk mengirim URL, CSRF token, atau konfigurasi non-sensitif dari Blade.
- Untuk request AJAX, kirim header `Accept: application/json` dan `X-CSRF-TOKEN`.
- Tangani response gagal dan tampilkan pesan yang dapat dipahami user.

### Struktur JS Module

Setiap modul JavaScript wajib mengikuti struktur:

```js
import $ from 'jquery';
import DataTable from 'datatables.net-dt';
import select2 from 'select2';
import Swal from 'sweetalert2';

import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'select2/dist/css/select2.css';
import 'sweetalert2/dist/sweetalert2.css';
import '../css/{feature}.css';

window.$ = window.jQuery = $;
window.Swal = Swal;
select2(window, $);

// ... theme, helpers, init functions ...

document.addEventListener('DOMContentLoaded', () => {
    const indexPage = document.querySelector('#{feature}IndexPage');

    initializeSelect2();
    initialize{Feature}Table(indexPage);
    showFlashMessage(indexPage);
});
```

- `select2(window, $)` dipanggil secara global untuk mengaktifkan Select2 jQuery plugin.
- `initializeSelect2()` dijalankan di semua halaman (index, create, edit).
- `initialize{Feature}Table()` hanya berjalan jika elemen tabel ada.
- `showFlashMessage()` hanya berjalan jika elemen page dengan data message ada.

## 9. CSS

- Gunakan CSS variable yang sudah tersedia untuk warna, radius, shadow, dan transition.
- Hindari nilai warna baru jika token yang sesuai sudah tersedia.
- Class diberi nama berdasarkan fungsi atau komponen, bukan posisi sementara.
- Style library pihak ketiga harus disesuaikan dengan tema aplikasi dalam selector yang scoped.
- Jangan mengubah style global library jika hanya satu modul yang menggunakannya.

### CSS Module

Setiap modul memiliki file CSS terpisah yang di-import dari JS module. CSS module minimal berisi:

```css
/* Select2 theming — konsisten di seluruh aplikasi */
.select2-container { width: 100% !important; font-family: inherit; }
.select2-container--default .select2-selection--single {
    height: 42px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    background: var(--color-bg-white);
}
/* ... (copy from existing customer.css or menu.css) ... */

/* SweetAlert2 theming */
.swal2-popup { border-radius: var(--radius-lg); font-family: inherit; }
.swal2-title { color: var(--color-text-primary); }
.swal2-html-container { color: var(--color-text-secondary); }
```

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

## 12. Module Checklist

Setiap modul baru wajib memiliki file-file berikut:

```
app/Http/Controllers/{Feature}Controller.php    # index, data, create, store, edit, update, destroy
app/Services/{Feature}Service.php                # getDataTableQuery, getPaginated, create, update, delete
app/Models/{Feature}.php                         # Model dengan HasUlids, SoftDeletes
app/Http/Requests/{Feature}/Store{Feature}Request.php
app/Http/Requests/{Feature}/Update{Feature}Request.php
routes/web.php                                   # Route data + resource
resources/views/pages/{feature}/index.blade.php
resources/views/pages/{feature}/create.blade.php
resources/views/pages/{feature}/edit.blade.php
resources/views/pages/{feature}/_form.blade.php
resources/views/pages/{feature}/columns/action.blade.php
resources/views/pages/{feature}/columns/name.blade.php
resources/views/pages/{feature}/columns/status.blade.php
resources/js/{feature}.js
resources/css/{feature}.css
public/assets/css/style.css                      # .{feature}-form-card class
vite.config.js                                   # entry JS
```

## 13. Quality Gate

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
