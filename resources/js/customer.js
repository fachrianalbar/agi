import $ from 'jquery';
import DataTable from 'datatables.net-dt';
import select2 from 'select2';
import Swal from 'sweetalert2';

import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'select2/dist/css/select2.css';
import 'sweetalert2/dist/sweetalert2.css';
import '../css/customer.css';

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

function initializeCustomerTable(page) {
    const tableElement = document.querySelector('#customerTable');

    if (!page || !tableElement) {
        return;
    }

    const table = new DataTable(tableElement, {
        processing: true,
        serverSide: true,
        ajax: page.dataset.tableUrl,
        order: [[1, 'asc']],
        pageLength: 10,
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'username', name: 'username' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'location', name: 'location', orderable: false },
            { data: 'status', name: 'is_active' },
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search customers...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ customers',
            infoEmpty: 'No customers available',
            zeroRecords: 'No matching customers found',
        },
    });

    tableElement.addEventListener('click', async (event) => {
        const button = event.target.closest('.js-delete-customer');

        if (!button) {
            return;
        }

        const result = await Swal.fire({
            ...swalTheme,
            icon: 'warning',
            title: 'Delete customer?',
            text: `Customer "${button.dataset.name}" will be permanently deleted.`,
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
                throw new Error(payload.message || 'The customer could not be deleted.');
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

function initializeSelect2() {
    $('.js-select2').each(function () {
        const select = $(this);

        select.select2({
            width: '100%',
            placeholder: select.data('placeholder') || null,
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const customerIndexPage = document.querySelector('#customerIndexPage');

    initializeSelect2();
    initializeCustomerTable(customerIndexPage);
    showFlashMessage(customerIndexPage);
});
