@extends('layouts.app')

@section('title', 'Non Active Fleet')
@section('page-title', 'Non Active Fleet')
@section('crud-assets', 'true')

@push('styles')
<style>
  #inactiveFleetModal .modal,
  #inactiveSnapshotModal .modal {
    max-width: 960px !important;
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

    context.font = '26px Arial';

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
        return wrapText(context, value, columns[columnIndex].width - 28).length;
      });
      var height = Math.max(64, Math.max.apply(Math, lineCounts) * 30 + 28);

      return {
        values: row,
        height: height,
      };
    });
  }

  function drawSnapshotCanvas(customer, vehicles) {
    var canvas = document.createElement('canvas');
    var context = canvas.getContext('2d');
    var width = 1400;
    var margin = 58;
    var tableWidth = width - margin * 2;
    var columns = [
      { label: 'No', width: 80 },
      { label: 'Vehicle Name', width: 310 },
      { label: 'Datetime', width: 265 },
      { label: 'Latitude', width: 180 },
      { label: 'Longitude', width: 180 },
      { label: 'Location', width: 269 },
    ];
    var rows = measureSnapshotRows(context, vehicles, columns);
    var headerHeight = 220;
    var tableHeaderHeight = 62;
    var emptyHeight = vehicles.length === 0 ? 110 : 0;
    var tableHeight = tableHeaderHeight + rows.reduce(function (total, row) {
      return total + row.height;
    }, 0) + emptyHeight;
    var height = headerHeight + tableHeight + 96;
    var generatedAt = new Date().toLocaleString('id-ID', {
      dateStyle: 'medium',
      timeStyle: 'short',
    });

    canvas.width = width;
    canvas.height = height;

    context.fillStyle = '#FFF4EC';
    context.fillRect(0, 0, width, height);
    context.fillStyle = '#FFFFFF';
    drawRoundedRect(context, 36, 36, width - 72, height - 72, 22);
    context.fill();

    context.fillStyle = '#4E2C23';
    context.font = '700 42px Arial';
    context.fillText('Vehicle Non Active', margin, 98);
    context.font = '700 30px Arial';
    context.fillText(customer.name || 'Customer', margin, 145);
    context.fillStyle = '#72584F';
    context.font = '24px Arial';
    context.fillText('Generated ' + generatedAt, margin, 184);

    context.fillStyle = '#9CA3AF';
    drawRoundedRect(context, width - margin - 190, 86, 190, 48, 24);
    context.fill();
    context.fillStyle = '#FFFFFF';
    context.font = '700 22px Arial';
    context.textAlign = 'center';
    context.fillText(vehicles.length + ' INACTIVE', width - margin - 95, 117);
    context.textAlign = 'left';

    var x = margin;
    var y = headerHeight;

    context.fillStyle = '#4E2C23';
    drawRoundedRect(context, x, y, tableWidth, tableHeaderHeight, 12);
    context.fill();

    context.fillStyle = '#FFFFFF';
    context.font = '700 22px Arial';
    var columnX = x;
    columns.forEach(function (column) {
      context.fillText(column.label, columnX + 14, y + 39);
      columnX += column.width;
    });

    y += tableHeaderHeight;

    if (vehicles.length === 0) {
      context.fillStyle = '#FFF0E0';
      context.fillRect(x, y, tableWidth, emptyHeight);
      context.fillStyle = '#72584F';
      context.font = '26px Arial';
      context.fillText('Tidak ada mobil tidak aktif untuk customer ini.', x + 24, y + 64);
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
          context.font = columnIndex === 0 ? '700 24px Arial' : '24px Arial';
          wrapText(context, value, column.width - 28).forEach(function (line, lineIndex) {
            context.fillText(line, columnX + 14, y + 38 + lineIndex * 30);
          });

          columnX += column.width;
        });

        y += row.height;
      });
    }

    context.fillStyle = '#A08980';
    context.font = '20px Arial';
    context.fillText('Source: Total Kilat GPS inactive fleet data', margin, height - 54);

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

    activeSnapshot = {
      blob: blob,
      fileName: fileName,
      message: message,
      title: 'Vehicle Non Active',
    };

    snapshotElements.preview.src = canvas.toDataURL('image/png');
    setText(snapshotElements.total, vehicles.length + (vehicles.length === 1 ? ' Vehicle' : ' Vehicles'));
    setHidden(snapshotElements.loading, true);
    setHidden(snapshotElements.previewWrap, false);
    snapshotElements.copy.disabled = false;
    snapshotElements.share.disabled = false;
  }

  function shareSnapshot() {
    if (!activeSnapshot) {
      return;
    }

    window.open(
      'https://wa.me/?text=' + encodeURIComponent(activeSnapshot.message),
      '_blank',
      'noopener,noreferrer',
    );

    if (window.Swal) {
      window.Swal.fire({
        icon: 'info',
        title: 'WhatsApp opened',
        text: 'Kalau sudah klik Copy Image, paste snapshot langsung di chat WhatsApp.',
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

    await navigator.clipboard.write([
      new ClipboardItem({
        'image/png': activeSnapshot.blob,
      }),
    ]);

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
    shareSnapshot();
  });
});
</script>
@endpush
