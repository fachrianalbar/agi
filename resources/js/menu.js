import $ from 'jquery';
import DataTable from 'datatables.net-dt';
import select2 from 'select2';
import Swal from 'sweetalert2';

import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'select2/dist/css/select2.css';
import 'sweetalert2/dist/sweetalert2.css';
import '../css/menu.css';

window.$ = window.jQuery = $;
window.Swal = Swal;
select2(window, $);

const swalTheme = {
    confirmButtonColor: '#E2725B',
    cancelButtonColor: '#A08980',
};

function showFlashMessage(page) {
    const message = page?.dataset.successMessage || page?.dataset.infoMessage;

    if (!message) {
        return;
    }

    Swal.fire({
        ...swalTheme,
        icon: page.dataset.successMessage ? 'success' : 'info',
        title: page.dataset.successMessage ? 'Success' : 'Information',
        text: message,
        timer: 2200,
        showConfirmButton: false,
    });
}

function initializeSelect2() {
    $('.js-select2').each(function () {
        const select = $(this);

        select.select2({
            width: '100%',
            placeholder: select.data('placeholder') || null,
            allowClear: select.attr('id') === 'parent_id',
        });
    });
}

function initializeMenuTable(page) {
    const tableElement = document.querySelector('#menuTable');

    if (!page || !tableElement) {
        return;
    }

    const table = new DataTable(tableElement, {
        processing: true,
        serverSide: true,
        ajax: page.dataset.tableUrl,
        order: [
            [2, 'asc'],
            [5, 'asc'],
            [1, 'asc'],
        ],
        pageLength: 10,
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'menu', name: 'name' },
            { data: 'section', name: 'section' },
            { data: 'destination', name: 'destination', orderable: false },
            { data: 'parent_name', name: 'parent_name', orderable: false },
            { data: 'sort_order', name: 'sort_order' },
            { data: 'status', name: 'is_active' },
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search menus...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ menus',
            infoEmpty: 'No menus available',
            zeroRecords: 'No matching menus found',
        },
    });

    tableElement.addEventListener('click', async (event) => {
        const button = event.target.closest('.js-delete-menu');

        if (!button) {
            return;
        }

        const result = await Swal.fire({
            ...swalTheme,
            icon: 'warning',
            title: 'Delete menu?',
            text: `Menu "${button.dataset.name}" will be permanently deleted.`,
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
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'The menu could not be deleted.');
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
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const menuIndexPage = document.querySelector('#menuIndexPage');

    initializeSelect2();
    initializeMenuTable(menuIndexPage);
    showFlashMessage(menuIndexPage);
});
