import $ from 'jquery';
import DataTable from 'datatables.net-dt';
import select2 from 'select2';
import Swal from 'sweetalert2';

import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'select2/dist/css/select2.css';
import 'sweetalert2/dist/sweetalert2.css';
import '../css/components.css';

window.$ = window.jQuery = $;
window.Swal = Swal;
select2(window, $);

const swalTheme = {
    confirmButtonColor: '#E2725B',
    cancelButtonColor: '#A08980',
};

function parseJson(value, fallback) {
    if (!value) {
        return fallback;
    }

    try {
        return JSON.parse(value);
    } catch {
        return fallback;
    }
}

function parseBoolean(value, fallback = true) {
    if (value === undefined) {
        return fallback;
    }

    return value !== 'false';
}

function showFlashMessage(page) {
    const message = page?.dataset.successMessage
        || page?.dataset.infoMessage
        || page?.dataset.errorMessage;

    if (!message) {
        return;
    }

    const isError = Boolean(page.dataset.errorMessage);
    const isSuccess = Boolean(page.dataset.successMessage);

    Swal.fire({
        ...swalTheme,
        icon: isError ? 'error' : (isSuccess ? 'success' : 'info'),
        title: isError ? 'Process failed' : (isSuccess ? 'Success' : 'Information'),
        text: message,
        timer: isError ? undefined : 2200,
        showConfirmButton: isError,
    });
}

function initializeSelect2() {
    $('.js-select2').each(function () {
        const select = $(this);

        if (select.hasClass('select2-hidden-accessible')) {
            return;
        }

        select.select2({
            width: '100%',
            placeholder: select.data('placeholder') || null,
            allowClear: select.data('allow-clear') === true,
        });
    });
}

function getColumns(tableElement) {
    return Array.from(tableElement.querySelectorAll('thead th')).map((header) => {
        if (header.dataset.column === 'row_number') {
            return {
                data: null,
                name: '',
                orderable: false,
                searchable: false,
                className: 'table-cell-center table-cell-number',
                render: (_data, _type, _row, meta) => (
                    meta.settings._iDisplayStart + meta.row + 1
                ),
            };
        }

        return {
            data: header.dataset.column,
            name: header.dataset.name || header.dataset.column,
            orderable: parseBoolean(header.dataset.orderable),
            searchable: parseBoolean(header.dataset.searchable),
            className: header.dataset.align ? `table-cell-${header.dataset.align}` : '',
        };
    });
}

function openModal(modal) {
    if (!modal) {
        return;
    }

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    if (!modal) {
        return;
    }

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function initializeModals(page) {
    page.querySelectorAll('[data-modal-target]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            openModal(document.getElementById(trigger.dataset.modalTarget));
        });
    });

    page.querySelectorAll('[data-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            closeModal(trigger.closest('[data-modal]'));
        });
    });

    page.querySelectorAll('[data-modal]').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal(page.querySelector('[data-modal].show'));
        }
    });
}

async function deleteRecord(button, page, table) {
    const recordLabel = button.dataset.recordLabel || 'record';
    const recordName = button.dataset.recordName || '';
    const displayName = recordName ? ` "${recordName}"` : '';
    const result = await Swal.fire({
        ...swalTheme,
        icon: 'warning',
        title: `Delete ${recordLabel}?`,
        text: `${recordLabel.charAt(0).toUpperCase() + recordLabel.slice(1)}${displayName} will be permanently deleted.`,
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(button.dataset.url, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': page.dataset.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || `The ${recordLabel} could not be deleted.`);
        }

        await Swal.fire({
            ...swalTheme,
            icon: 'success',
            title: 'Deleted',
            text: payload.message,
            timer: 1800,
            showConfirmButton: false,
        });

        table.ajax.reload(null, false);
    } catch (error) {
        Swal.fire({
            ...swalTheme,
            icon: 'error',
            title: 'Delete failed',
            text: error.message,
        });
    }
}

function initializeDataTable(tableElement, page) {
    const pluralLabel = tableElement.dataset.pluralLabel || 'records';
    const table = new DataTable(tableElement, {
        processing: true,
        serverSide: true,
        ajax: tableElement.dataset.url,
        order: parseJson(tableElement.dataset.order, [[1, 'asc']]),
        pageLength: Number(tableElement.dataset.pageLength || 10),
        columns: getColumns(tableElement),
        language: {
            search: '',
            searchPlaceholder: tableElement.dataset.searchPlaceholder || `Search ${pluralLabel}...`,
            lengthMenu: 'Show _MENU_ entries',
            info: `Showing _START_ to _END_ of _TOTAL_ ${pluralLabel}`,
            infoEmpty: `No ${pluralLabel} available`,
            zeroRecords: `No matching ${pluralLabel} found`,
        },
    });

    tableElement.addEventListener('click', (event) => {
        const button = event.target.closest('.js-delete-record');

        if (button) {
            deleteRecord(button, page, table);
        }
    });

    return table;
}

function firstValidationMessage(errors) {
    if (!errors || typeof errors !== 'object') {
        return null;
    }

    const messages = Object.values(errors).flat();

    return messages.find((message) => typeof message === 'string') || null;
}

async function submitAsyncForm(form, page, tables) {
    const submitButton = form.querySelector('[type="submit"]');
    const originalText = submitButton?.textContent;

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = submitButton.dataset.loadingText || 'Processing...';
    }

    try {
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': page.dataset.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                firstValidationMessage(payload.errors)
                || payload.message
                || 'The process could not be completed.',
            );
        }

        closeModal(form.closest('[data-modal]'));
        form.reset();
        $(form).find('.js-select2').val(null).trigger('change');
        tables.forEach((table) => table.ajax.reload(null, false));

        await Swal.fire({
            ...swalTheme,
            icon: 'success',
            title: form.dataset.successTitle || 'Process complete',
            text: payload.message,
        });
    } catch (error) {
        Swal.fire({
            ...swalTheme,
            icon: 'error',
            title: form.dataset.errorTitle || 'Process failed',
            text: error.message,
        });
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSelect2();

    document.querySelectorAll('.js-crud-page').forEach((page) => {
        showFlashMessage(page);
        initializeModals(page);

        const tables = Array.from(page.querySelectorAll('.js-data-table')).map((table) => (
            initializeDataTable(table, page)
        ));

        page.querySelectorAll('.js-async-form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                submitAsyncForm(form, page, tables);
            });
        });
    });
});
