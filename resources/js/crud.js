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
const enrichmentRequests = new WeakMap();
const badgeClasses = [
    'badge',
    'badge-success',
    'badge-warning',
    'badge-danger',
    'badge-info',
    'badge-neutral',
];

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

function replaceSelectOptions(select, items, placeholder) {
    select.innerHTML = '';
    select.append(new Option(placeholder, ''));

    items.forEach((item) => {
        select.append(new Option(item.label, item.value));
    });
}

function refreshSelect2(select) {
    if (window.jQuery && $(select).hasClass('select2-hidden-accessible')) {
        $(select)
            .prop('disabled', select.disabled)
            .trigger('change');
    }
}

function initializeDependentSelects(page) {
    page.querySelectorAll('[data-dependent-url][data-dependent-parent]').forEach((select) => {
        const parent = page.querySelector(select.dataset.dependentParent);

        if (!parent) {
            return;
        }

        const placeholder = select.dataset.placeholder || 'Select an option...';
        const emptyLabel = select.dataset.emptyLabel || placeholder;
        const param = select.dataset.dependentParam || parent.name || 'parent';

        const loadOptions = async () => {
            const parentValue = parent.value;

            if (!parentValue) {
                replaceSelectOptions(select, [], emptyLabel);
                select.value = '';
                select.disabled = true;
                refreshSelect2(select);

                return;
            }

            select.disabled = true;
            replaceSelectOptions(select, [], 'Loading...');
            refreshSelect2(select);

            try {
                const url = new URL(select.dataset.dependentUrl, window.location.origin);
                url.searchParams.set(param, parentValue);

                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || 'Options could not be loaded.');
                }

                const items = Array.isArray(payload.data) ? payload.data : [];
                const selectedValue = select.dataset.selectedValue || '';

                replaceSelectOptions(select, items, placeholder);
                select.disabled = false;

                if (selectedValue && items.some((item) => String(item.value) === selectedValue)) {
                    select.value = selectedValue;
                    delete select.dataset.selectedValue;
                } else {
                    select.value = '';
                }
            } catch (error) {
                replaceSelectOptions(select, [], 'Unable to load options');
                select.value = '';
                select.disabled = true;
            }

            refreshSelect2(select);
        };

        const handleParentChange = () => {
            delete select.dataset.selectedValue;
            loadOptions();
        };

        if (window.jQuery) {
            $(parent).on('change', handleParentChange);
        } else {
            parent.addEventListener('change', handleParentChange);
        }

        loadOptions();
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

    modal.querySelectorAll('[data-map-frame]').forEach((frame) => {
        frame.src = 'about:blank';
    });
}

function setModalText(modal, selector, value, fallback = 'Unavailable') {
    const element = modal?.querySelector(selector);

    if (!element) {
        return;
    }

    element.textContent = value || fallback;
}

function setModalBadge(modal, selector, value, variant = 'neutral') {
    const element = modal?.querySelector(selector);

    if (!element) {
        return;
    }

    element.textContent = value || 'Unavailable';
    element.classList.remove(...badgeClasses);
    element.classList.add('badge', `badge-${variant || 'neutral'}`);
}

function formatMapCoordinate(latitude, longitude) {
    if (latitude === null || latitude === undefined || longitude === null || longitude === undefined) {
        return 'Unavailable';
    }

    return `${latitude}, ${longitude}`;
}

function buildMapPayload(record, value) {
    return {
        url: value.url,
        latitude: value.latitude ?? null,
        longitude: value.longitude ?? null,
        address: record?.address?.text || null,
        mileage: record?.mileage?.text || null,
        vehicleStatus: record?.vehicle_status?.text || null,
        vehicleStatusBadge: record?.vehicle_status?.badge || 'neutral',
        engine: record?.engine?.text || null,
        engineBadge: record?.engine?.badge || 'neutral',
        lastUpdate: record?.last_update?.text || null,
    };
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

function initializeMapModals(page) {
    page.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-map-modal-target]');

        if (!trigger || trigger.disabled) {
            return;
        }

        const modal = document.getElementById(trigger.dataset.mapModalTarget);
        const frame = modal?.querySelector('[data-map-frame]');
        const payload = parseJson(trigger.dataset.mapPayload, {});
        const mapUrl = payload.url || trigger.dataset.mapUrl;

        if (!modal || !frame || !mapUrl) {
            return;
        }

        frame.src = mapUrl;
        setModalText(modal, '[data-map-vehicle-name]', trigger.dataset.mapTitle, 'Vehicle');
        setModalText(modal, '[data-map-address]', payload.address, 'Address unavailable');
        setModalText(modal, '[data-map-mileage]', payload.mileage);
        setModalText(modal, '[data-map-last-update]', payload.lastUpdate);
        setModalText(
            modal,
            '[data-map-coordinates]',
            formatMapCoordinate(payload.latitude, payload.longitude),
        );
        setModalBadge(modal, '[data-map-status]', payload.vehicleStatus, payload.vehicleStatusBadge);
        setModalBadge(modal, '[data-map-engine]', payload.engine ? `Engine ${payload.engine}` : null, payload.engineBadge);

        openModal(modal);
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

function setEnrichmentUnavailable(tableElement) {
    tableElement.querySelectorAll('[data-enrichment-field]').forEach((element) => {
        element.textContent = 'Unavailable';
        element.classList.remove('enrichment-loading', ...badgeClasses);
        element.classList.add('enrichment-error');
    });

    tableElement.querySelectorAll('[data-enrichment-link]').forEach((link) => {
        link.setAttribute('href', '#');
        link.setAttribute('aria-disabled', 'true');
        link.setAttribute('title', 'Position unavailable');
        link.classList.add('is-disabled');
    });

    tableElement.querySelectorAll('[data-enrichment-map]').forEach((button) => {
        button.disabled = true;
        button.dataset.mapUrl = '';
        delete button.dataset.mapPayload;
        button.setAttribute('aria-disabled', 'true');
        button.setAttribute('title', 'Position unavailable');
        button.classList.add('is-disabled');
    });
}

function applyTableEnrichment(tableElement, data) {
    tableElement.querySelectorAll('[data-enrichment-field]').forEach((element) => {
        const record = data[element.dataset.enrichmentRef];
        const value = record?.[element.dataset.enrichmentField];

        element.classList.remove('enrichment-loading', 'enrichment-error', ...badgeClasses);

        if (!value || typeof value.text !== 'string') {
            element.textContent = 'Unavailable';
            element.classList.add('enrichment-error');

            return;
        }

        element.textContent = value.text;

        if (value.badge) {
            element.classList.add('badge', `badge-${value.badge}`);
        }

        if (value.state === 'error') {
            element.classList.add('enrichment-error');
        }
    });

    tableElement.querySelectorAll('[data-enrichment-link]').forEach((link) => {
        const record = data[link.dataset.enrichmentRef];
        const value = record?.[link.dataset.enrichmentLink];

        if (!value?.url) {
            link.setAttribute('href', '#');
            link.setAttribute('aria-disabled', 'true');
            link.setAttribute('title', 'Position unavailable');
            link.classList.add('is-disabled');

            return;
        }

        link.setAttribute('href', value.url);
        link.setAttribute('aria-disabled', 'false');
        link.setAttribute('title', 'Open last position in Google Maps');
        link.classList.remove('is-disabled');
    });

    tableElement.querySelectorAll('[data-enrichment-map]').forEach((button) => {
        const record = data[button.dataset.enrichmentRef];
        const value = record?.[button.dataset.enrichmentMap];

        if (!value?.url) {
            button.disabled = true;
            button.dataset.mapUrl = '';
            delete button.dataset.mapPayload;
            button.setAttribute('aria-disabled', 'true');
            button.setAttribute('title', 'Position unavailable');
            button.classList.add('is-disabled');

            return;
        }

        button.disabled = false;
        button.dataset.mapUrl = value.url;
        button.dataset.mapPayload = JSON.stringify(buildMapPayload(record, value));
        button.setAttribute('aria-disabled', 'false');
        button.setAttribute('title', 'View last position on map');
        button.classList.remove('is-disabled');
    });
}

async function loadTableEnrichment(tableElement, page) {
    const url = tableElement.dataset.enrichmentUrl;

    if (!url) {
        return;
    }

    enrichmentRequests.get(tableElement)?.abort();

    const controller = new AbortController();
    enrichmentRequests.set(tableElement, controller);
    const devicesByReference = new Map();

    tableElement.querySelectorAll('[data-enrichment-ref][data-enrichment-source-key]').forEach((element) => {
        devicesByReference.set(element.dataset.enrichmentRef, {
            ref: element.dataset.enrichmentRef,
            device_name: element.dataset.enrichmentSourceKey,
        });
    });

    if (devicesByReference.size === 0) {
        return;
    }

    tableElement.querySelectorAll('[data-enrichment-field]').forEach((element) => {
        element.textContent = 'Loading...';
        element.classList.remove('enrichment-error', ...badgeClasses);
        element.classList.add('enrichment-loading');
    });

    try {
        const response = await fetch(url, {
            method: 'POST',
            signal: controller.signal,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.dataset.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                devices: Array.from(devicesByReference.values()),
            }),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || 'Latest positions could not be loaded.');
        }

        applyTableEnrichment(tableElement, payload.data || {});
    } catch (error) {
        if (error.name !== 'AbortError') {
            setEnrichmentUnavailable(tableElement);
        }
    } finally {
        if (enrichmentRequests.get(tableElement) === controller) {
            enrichmentRequests.delete(tableElement);
        }
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
        drawCallback: () => {
            loadTableEnrichment(tableElement, page);
        },
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
        initializeMapModals(page);
        initializeDependentSelects(page);

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
