@extends('layouts.app')

@section('title', 'Non Active Fleet')
@section('page-title', 'Non Active Fleet')
@section('crud-assets', 'true')

@push('styles')
<style>
  #inactiveFleetModal .modal,
  #inactiveSnapshotModal .modal {
    width: 95vw;
    max-width: 1320px !important;
    max-height: 92vh;
  }
</style>
@endpush

@section('content')
<div
  class="page-section active js-crud-page"
  id="inactiveFleetPage"
  data-csrf-token="{{ csrf_token() }}"
  data-success-message="{{ session('success') }}"
  data-info-message="{{ session('info') }}"
  data-error-message="{{ session('error') }}"
>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Non Active Fleet</h1>
        <p class="page-header-subtitle">Pilih customer untuk melihat daftar mobil yang tidak aktif dari Total Kilat GPS.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Customers</h3>
          <p class="card-subtitle">List utama berdasarkan customer yang punya credential GPS aktif.</p>
        </div>
      </div>

      <div class="data-table-container">
        <table
          class="table js-data-table"
          id="inactiveCustomerTable"
          data-url="{{ route('inactive.data') }}"
          data-order='[[2,"asc"]]'
          data-plural-label="customers"
          data-search-placeholder="Search customers..."
        >
          <thead>
            <tr>
              <th data-column="row_number" data-orderable="false" data-searchable="false">No</th>
              <th data-column="action" data-orderable="false" data-searchable="false"></th>
              <th data-column="name">Name</th>
              <th data-column="username">Username</th>
              <th data-column="email">Email</th>
              <th data-column="phone">Phone</th>
              <th data-column="location" data-orderable="false">Location</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <x-modal id="inactiveFleetModal" title="Inactive Fleets" size="xl">
    <div class="inactive-modal-shell">
      <div class="inactive-modal-header">
        <div>
          <span class="inactive-modal-eyebrow">Customer</span>
          <h3 class="inactive-modal-title" data-inactive-customer-name>Customer</h3>
          <p class="inactive-modal-subtitle" data-inactive-customer-username>Username</p>
        </div>
        <span class="badge badge-neutral" data-inactive-total>0 Vehicles</span>
      </div>

      <div class="inactive-state" data-inactive-loading hidden>
        Loading inactive fleets...
      </div>

      <div class="inactive-state inactive-state-error" data-inactive-error hidden>
        Data could not be loaded.
      </div>

      <div class="inactive-state" data-inactive-empty hidden>
        Tidak ada mobil tidak aktif untuk customer ini.
      </div>

      <div class="inactive-table-wrap" data-inactive-table-wrap hidden>
        <table class="table inactive-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Vehicle Name</th>
              <th>Datetime</th>
              <th>Latitude</th>
              <th>Longitude</th>
              <th>Location</th>
            </tr>
          </thead>
          <tbody data-inactive-rows></tbody>
        </table>
      </div>
    </div>
  </x-modal>

  <x-modal id="inactiveSnapshotModal" title="Vehicle Non Active" size="xl">
    <div class="inactive-modal-shell">
      <div class="inactive-modal-header">
        <div>
          <span class="inactive-modal-eyebrow">Snapshot</span>
          <h3 class="inactive-modal-title" data-snapshot-customer-name>Customer</h3>
        </div>
        <div class="inactive-snapshot-header-actions">
          <span class="badge badge-neutral" data-snapshot-total>0 Vehicles</span>
          <button type="button" class="btn btn-secondary btn-sm" data-modal-close>Close</button>
          <button type="button" class="btn btn-secondary btn-sm" data-snapshot-copy disabled>Copy Image</button>
          <button type="button" class="btn btn-primary btn-sm" data-snapshot-share disabled>Share WhatsApp</button>
        </div>
      </div>

      <div class="inactive-state" data-snapshot-loading hidden>
        Creating snapshot...
      </div>

      <div class="inactive-state inactive-state-error" data-snapshot-error hidden>
        Snapshot could not be created.
      </div>

      <div class="inactive-snapshot-preview" data-snapshot-preview-wrap hidden>
        <img src="" alt="Inactive fleet snapshot preview" data-snapshot-preview>
      </div>
    </div>
  </x-modal>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var page = document.getElementById('inactiveFleetPage');
  var modal = document.getElementById('inactiveFleetModal');
  var snapshotModal = document.getElementById('inactiveSnapshotModal');

  if (!page || !modal || !snapshotModal) {
    return;
  }

  var elements = {
    customerName: modal.querySelector('[data-inactive-customer-name]'),
    customerUsername: modal.querySelector('[data-inactive-customer-username]'),
    total: modal.querySelector('[data-inactive-total]'),
    loading: modal.querySelector('[data-inactive-loading]'),
    error: modal.querySelector('[data-inactive-error]'),
    empty: modal.querySelector('[data-inactive-empty]'),
    tableWrap: modal.querySelector('[data-inactive-table-wrap]'),
    rows: modal.querySelector('[data-inactive-rows]'),
  };
  var snapshotElements = {
    customerName: snapshotModal.querySelector('[data-snapshot-customer-name]'),
    total: snapshotModal.querySelector('[data-snapshot-total]'),
    loading: snapshotModal.querySelector('[data-snapshot-loading]'),
    error: snapshotModal.querySelector('[data-snapshot-error]'),
    previewWrap: snapshotModal.querySelector('[data-snapshot-preview-wrap]'),
    preview: snapshotModal.querySelector('[data-snapshot-preview]'),
    copy: snapshotModal.querySelector('[data-snapshot-copy]'),
    share: snapshotModal.querySelector('[data-snapshot-share]'),
  };
  var activeSnapshot = null;

  function openModal(targetModal) {
    targetModal.classList.add('show');
    targetModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function setHidden(element, hidden) {
    if (element) {
      element.hidden = hidden;
    }
  }

  function setText(element, value) {
    if (element) {
      element.textContent = value || '-';
    }
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (character) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
      }[character];
    });
  }

  function resetModal(trigger) {
    setText(elements.customerName, trigger.dataset.customerName || 'Customer');
    setText(elements.customerUsername, trigger.dataset.customerUsername || 'Username');
    setText(elements.total, 'Loading...');
    setHidden(elements.loading, false);
    setHidden(elements.error, true);
    setHidden(elements.empty, true);
    setHidden(elements.tableWrap, true);

    if (elements.rows) {
      elements.rows.innerHTML = '';
    }
  }

  function appendCell(row, value) {
    var cell = document.createElement('td');
    cell.textContent = value || '-';
    row.appendChild(cell);
  }

  function renderRows(vehicles) {
    if (!elements.rows) {
      return;
    }

    elements.rows.innerHTML = '';
    vehicles.forEach(function (vehicle, index) {
      var row = document.createElement('tr');
      appendCell(row, index + 1);
      appendCell(row, vehicle.vehicle_name);
      appendCell(row, vehicle.datetime);
      appendCell(row, vehicle.latitude);
      appendCell(row, vehicle.longitude);
      appendCell(row, vehicle.location);
      elements.rows.appendChild(row);
    });
  }

  async function loadInactiveVehicles(url) {
    var response = await fetch(url, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    var payload = await response.json().catch(function () {
      return {};
    });

    if (!response.ok) {
      throw new Error(payload.message || 'Inactive fleet data could not be loaded.');
    }

    return Array.isArray(payload.data) ? payload.data : [];
  }

  function sanitizeFileName(value) {
    return String(value || 'customer')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '')
      || 'customer';
  }

  function breakLongWord(context, word, maxWidth) {
    var parts = [];
    var part = '';
    for (var i = 0; i < word.length; i++) {
      var test = part + word[i];
      if (context.measureText(test).width > maxWidth && part) {
        parts.push(part);
        part = word[i];
      } else {
        part = test;
      }
    }
    if (part) {
      parts.push(part);
    }
    return parts;
  }

  function wrapText(context, text, maxWidth) {
    var words = String(text || '-').split(/\s+/);
    var lines = [];
    var line = '';

    words.forEach(function (word) {
      var testLine = line ? line + ' ' + word : word;

      if (context.measureText(testLine).width > maxWidth && line) {
        lines.push(line);
        line = '';
      }

      if (context.measureText(word).width > maxWidth) {
        var broken = breakLongWord(context, word, maxWidth);
        broken.forEach(function (part, idx) {
          if (idx === 0 && line) {
            var combined = line + ' ' + part;
            if (context.measureText(combined).width <= maxWidth) {
              line = combined;
            } else {
              lines.push(line);
              line = part;
            }
          } else if (idx === broken.length - 1) {
            line = part;
          } else {
            lines.push(part);
          }
        });
      } else {
        line = line ? line + ' ' + word : word;
      }
    });

    if (line) {
      lines.push(line);
    }

    return lines.length ? lines : ['-'];
  }

  function drawRoundedRect(context, x, y, width, height, radius) {
    context.beginPath();
    context.moveTo(x + radius, y);
    context.lineTo(x + width - radius, y);
    context.quadraticCurveTo(x + width, y, x + width, y + radius);
    context.lineTo(x + width, y + height - radius);
    context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    context.lineTo(x + radius, y + height);
    context.quadraticCurveTo(x, y + height, x, y + height - radius);
    context.lineTo(x, y + radius);
    context.quadraticCurveTo(x, y, x + radius, y);
    context.closePath();
  }

  function measureSnapshotRows(context, vehicles, columns) {
    if (vehicles.length === 0) {
      return [];
    }

    context.font = '15px Arial';

    return vehicles.map(function (vehicle, index) {
      var row = [
        String(index + 1),
        vehicle.vehicle_name || '-',
        vehicle.datetime || '-',
        vehicle.latitude ?? '-',
        vehicle.longitude ?? '-',
        vehicle.location || '-',
      ];
      var lineCounts = row.map(function (value, columnIndex) {
        return wrapText(context, value, columns[columnIndex].width - 16).length;
      });
      var height = Math.max(36, Math.max.apply(Math, lineCounts) * 18 + 14);

      return {
        values: row,
        height: height,
      };
    });
  }

  function drawSnapshotCanvas(customer, vehicles) {
    var canvas = document.createElement('canvas');
    var context = canvas.getContext('2d');
    var width = 1000;
    var margin = 32;
    var tableWidth = width - margin * 2;
    var columns = [
      { label: 'No', width: 42 },
      { label: 'Vehicle Name', width: 175 },
      { label: 'Datetime', width: 174 },
      { label: 'Latitude', width: 110 },
      { label: 'Longitude', width: 120 },
      { label: 'Location', width: 315 },
    ];
    var rows = measureSnapshotRows(context, vehicles, columns);
    var headerHeight = 124;
    var tableHeaderHeight = 36;
    var emptyHeight = vehicles.length === 0 ? 64 : 0;
    var tableHeight = tableHeaderHeight + rows.reduce(function (total, row) {
      return total + row.height;
    }, 0) + emptyHeight;
    var height = headerHeight + tableHeight + 52;
    var generatedAt = new Date().toLocaleString('id-ID', {
      dateStyle: 'medium',
      timeStyle: 'short',
    });

    canvas.width = width;
    canvas.height = height;

    context.fillStyle = '#FFF4EC';
    context.fillRect(0, 0, width, height);
    context.fillStyle = '#FFFFFF';
    drawRoundedRect(context, 18, 18, width - 36, height - 36, 14);
    context.fill();

    context.fillStyle = '#4E2C23';
    context.font = '700 22px Arial';
    context.fillText('Vehicle Non Active', margin, 54);
    context.font = '700 16px Arial';
    context.fillText(customer.name || 'Customer', margin, 82);
    context.fillStyle = '#72584F';
    context.font = '13px Arial';
    context.fillText('Generated ' + generatedAt, margin, 104);

    context.fillStyle = '#9CA3AF';
    drawRoundedRect(context, width - margin - 118, 48, 118, 30, 15);
    context.fill();
    context.fillStyle = '#FFFFFF';
    context.font = '700 13px Arial';
    context.textAlign = 'center';
    context.fillText(vehicles.length + ' INACTIVE', width - margin - 59, 68);
    context.textAlign = 'left';

    var x = margin;
    var y = headerHeight;

    context.fillStyle = '#4E2C23';
    drawRoundedRect(context, x, y, tableWidth, tableHeaderHeight, 8);
    context.fill();

    context.fillStyle = '#FFFFFF';
    context.font = '700 13px Arial';
    var columnX = x;
    columns.forEach(function (column) {
      context.fillText(column.label, columnX + 8, y + 23);
      columnX += column.width;
    });

    y += tableHeaderHeight;

    if (vehicles.length === 0) {
      context.fillStyle = '#FFF0E0';
      context.fillRect(x, y, tableWidth, emptyHeight);
      context.fillStyle = '#72584F';
      context.font = '15px Arial';
      context.fillText('Tidak ada mobil tidak aktif untuk customer ini.', x + 14, y + 40);
    } else {
      rows.forEach(function (row, rowIndex) {
        context.fillStyle = rowIndex % 2 === 0 ? '#FFFFFF' : '#FFF8F3';
        context.fillRect(x, y, tableWidth, row.height);
        context.strokeStyle = '#F0DDD0';
        context.beginPath();
        context.moveTo(x, y + row.height);
        context.lineTo(x + tableWidth, y + row.height);
        context.stroke();

        columnX = x;
        row.values.forEach(function (value, columnIndex) {
          var column = columns[columnIndex];

          context.fillStyle = '#4E2C23';
          context.font = columnIndex === 0 ? '700 15px Arial' : '15px Arial';
          wrapText(context, value, column.width - 16).forEach(function (line, lineIndex) {
            context.fillText(line, columnX + 8, y + 23 + lineIndex * 18);
          });

          columnX += column.width;
        });

        y += row.height;
      });
    }

    context.fillStyle = '#A08980';
    context.font = '12px Arial';
    context.fillText('Source: Total Kilat GPS inactive fleet data', margin, height - 28);

    return canvas;
  }

  function canvasToBlob(canvas) {
    return new Promise(function (resolve) {
      canvas.toBlob(function (blob) {
        resolve(blob);
      }, 'image/png');
    });
  }

  async function createSnapshot(trigger) {
    var customer = {
      name: trigger.dataset.customerName || 'Customer',
    };
    setText(snapshotElements.customerName, customer.name);
    setText(snapshotElements.total, 'Creating...');
    setHidden(snapshotElements.loading, false);
    setHidden(snapshotElements.error, true);
    setHidden(snapshotElements.previewWrap, true);
    snapshotElements.copy.disabled = true;
    snapshotElements.share.disabled = true;
    activeSnapshot = null;
    openModal(snapshotModal);

    var vehicles = await loadInactiveVehicles(trigger.dataset.url);
    var canvas = drawSnapshotCanvas(customer, vehicles);
    var blob = await canvasToBlob(canvas);

    if (!blob) {
      throw new Error('Snapshot image could not be created.');
    }

    var fileName = 'inactive-fleet-' + sanitizeFileName(customer.name) + '.png';
    var message = 'Informasi : Mobil tidak aktif ' + vehicles.length + ' Unit. ' + customer.name;
    var dataUrl = canvas.toDataURL('image/png');

    activeSnapshot = {
      blob: blob,
      dataUrl: dataUrl,
      fileName: fileName,
      message: message,
      title: 'Vehicle Non Active',
    };

    snapshotElements.preview.src = dataUrl;
    setText(snapshotElements.total, vehicles.length + (vehicles.length === 1 ? ' Vehicle' : ' Vehicles'));
    setHidden(snapshotElements.loading, true);
    setHidden(snapshotElements.previewWrap, false);
    snapshotElements.copy.disabled = false;
    snapshotElements.share.disabled = false;
  }

  async function writeSnapshotClipboard(includeCaption) {
    if (!activeSnapshot) {
      return false;
    }

    if (!navigator.clipboard || typeof ClipboardItem === 'undefined') {
      return false;
    }

    var clipboardData = {
      'image/png': activeSnapshot.blob,
    };

    if (includeCaption) {
      clipboardData['text/plain'] = new Blob([activeSnapshot.message], {
        type: 'text/plain',
      });
      clipboardData['text/html'] = new Blob([
        '<p>' + escapeHtml(activeSnapshot.message) + '</p>' +
          '<img src="' + activeSnapshot.dataUrl + '" alt="' + escapeHtml(activeSnapshot.title) + '">',
      ], {
        type: 'text/html',
      });
    }

    await navigator.clipboard.write([
      new ClipboardItem(clipboardData),
    ]);

    return true;
  }

  function openWhatsAppApp(message) {
    window.location.href = 'whatsapp://send?text=' + encodeURIComponent(message);
  }

  async function shareSnapshot() {
    if (!activeSnapshot) {
      return;
    }

    var copiedImage = false;

    try {
      copiedImage = await writeSnapshotClipboard(true);
    } catch {
      try {
        copiedImage = await writeSnapshotClipboard(false);
      } catch {
        copiedImage = false;
      }
    }

    if (!copiedImage && navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(activeSnapshot.message).catch(function () {});
    }

    openWhatsAppApp(activeSnapshot.message);

    if (window.Swal) {
      window.Swal.fire({
        icon: copiedImage ? 'success' : 'info',
        title: copiedImage ? 'Image copied' : 'Open WhatsApp',
        text: copiedImage
          ? 'Snapshot dan caption sudah dicopy. Paste di aplikasi WhatsApp.'
          : 'Caption sudah dicopy. Jika aplikasi WhatsApp tidak terbuka, buka WhatsApp lalu paste manual.',
        confirmButtonColor: '#E2725B',
      });
    }
  }

  async function copySnapshotImage() {
    if (!activeSnapshot) {
      return;
    }

    if (!navigator.clipboard || typeof ClipboardItem === 'undefined') {
      if (window.Swal) {
        window.Swal.fire({
          icon: 'info',
          title: 'Copy image unavailable',
          text: 'Copy gambar butuh HTTPS atau localhost dan browser yang support Clipboard Image. Coba buka via http://127.0.0.1:8000.',
          confirmButtonColor: '#E2725B',
        });
      }

      return;
    }

    await writeSnapshotClipboard(false);

    if (window.Swal) {
      window.Swal.fire({
        icon: 'success',
        title: 'Image copied',
        text: 'Snapshot sudah dicopy. Buka WhatsApp lalu paste di chat.',
        timer: 2200,
        showConfirmButton: false,
        confirmButtonColor: '#E2725B',
      });
    }
  }

  page.addEventListener('click', async function (event) {
    var trigger = event.target.closest('.js-load-inactive-fleets');

    if (!trigger || trigger.disabled) {
      return;
    }

    resetModal(trigger);
    openModal(modal);
    trigger.disabled = true;

    try {
      var vehicles = await loadInactiveVehicles(trigger.dataset.url);
      renderRows(vehicles);
      setText(elements.total, vehicles.length + (vehicles.length === 1 ? ' Vehicle' : ' Vehicles'));
      setHidden(elements.loading, true);
      setHidden(elements.empty, vehicles.length !== 0);
      setHidden(elements.tableWrap, vehicles.length === 0);
    } catch (error) {
      setText(elements.total, 'Failed');
      setText(elements.error, error.message);
      setHidden(elements.loading, true);
      setHidden(elements.error, false);
      setHidden(elements.empty, true);
      setHidden(elements.tableWrap, true);

      if (window.Swal) {
        window.Swal.fire({
          icon: 'error',
          title: 'Load failed',
          text: error.message,
          confirmButtonColor: '#E2725B',
        });
      }
    } finally {
      trigger.disabled = false;
    }
  });

  page.addEventListener('click', async function (event) {
    var trigger = event.target.closest('.js-create-inactive-snapshot');

    if (!trigger || trigger.disabled) {
      return;
    }

    trigger.disabled = true;

    try {
      await createSnapshot(trigger);
    } catch (error) {
      setText(snapshotElements.total, 'Failed');
      setText(snapshotElements.error, error.message);
      setHidden(snapshotElements.loading, true);
      setHidden(snapshotElements.error, false);
      setHidden(snapshotElements.previewWrap, true);

      if (window.Swal) {
        window.Swal.fire({
          icon: 'error',
          title: 'Snapshot failed',
          text: error.message,
          confirmButtonColor: '#E2725B',
        });
      }
    } finally {
      trigger.disabled = false;
    }
  });

  snapshotElements.copy.addEventListener('click', function () {
    copySnapshotImage().catch(function (error) {
      if (window.Swal) {
        window.Swal.fire({
          icon: 'error',
          title: 'Copy failed',
          text: error.message || 'Snapshot image could not be copied.',
          confirmButtonColor: '#E2725B',
        });
      }
    });
  });
  snapshotElements.share.addEventListener('click', function () {
    shareSnapshot().catch(function (error) {
      if (window.Swal) {
        window.Swal.fire({
          icon: 'error',
          title: 'Share failed',
          text: error.message || 'Snapshot could not be copied for WhatsApp.',
          confirmButtonColor: '#E2725B',
        });
      }
    });
  });
});
</script>
@endpush
