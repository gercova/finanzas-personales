// accounts.js — Gestión de Cuentas y Categorías por Usuario
$(document).ready(function () {

    var accTable = $('#manageAccountsTable').DataTable({
        processing: true,
        ajax: { url: 'php_action/fetchAccounts.php', type: 'GET' },
        columns: [
            { data: 0, width: '4%', className: 'text-center' },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4, className: 'text-center' },
            { data: 5, className: 'text-right' },
            { data: 6, className: 'text-right' },
            { data: 7, className: 'text-center' },
            { data: 8, orderable: false, className: 'text-center' }
        ],
        language: { url: 'assests/plugins/datatables/Spanish.json' },
        responsive: false,
        autoWidth: false
    });

    var catTable = $('#manageFinCategoriesTable').DataTable({
        processing: true,
        ajax: { url: 'php_action/fetchFinCategories.php', type: 'GET' },
        columns: [
            { data: 0, width: '4%', className: 'text-center' },
            { data: 1 },
            { data: 2, className: 'text-center' },
            { data: 3, className: 'text-center' },
            { data: 4, orderable: false, className: 'text-center' }
        ],
        language: { url: 'assests/plugins/datatables/Spanish.json' },
        responsive: false,
        autoWidth: false
    });

    function showAccMsg(type, msg) {
        var html = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' + msg + '</div>';
        $('.accounts-messages').html(html);
        $('html,body').animate({ scrollTop: 0 }, 300);
        setTimeout(function () { $('.accounts-messages .alert').fadeOut(500, function () { $(this).remove(); }); }, 4500);
    }

    // Add Account
    $('#addAccountForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/createAccount.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#addAccountModal').modal('hide');
                    $('#addAccountForm')[0].reset();
                    accTable.ajax.reload();
                    showAccMsg('success', res.messages);
                } else {
                    showAccMsg('danger', res.messages);
                }
            },
            error: function () { showAccMsg('danger', 'Error de comunicación con el servidor.'); }
        });
    });

    // Edit Account - open
    $(document).on('click', '.edit-account', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.get('php_action/getAccountData.php', { id: id }, function (res) {
            if (!res.success) { showAccMsg('danger', res.messages); return; }
            var d = res.data;
            $('#edit_acc_id').val(d.id);
            $('#edit_acc_name').val(d.account_name);
            $('#edit_acc_type').val(d.account_type);
            $('#edit_acc_number').val(d.account_number || '');
            $('#edit_acc_currency').val(d.currency || 'PEN');
            $('#edit_acc_color').val(d.color || '#2e6da4');
            $('#edit_acc_status').val(d.status);
            $('#editAccountModal').modal('show');
        }, 'json');
    });

    // Edit Account - submit
    $('#editAccountForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/editAccount.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#editAccountModal').modal('hide');
                    accTable.ajax.reload();
                    showAccMsg('success', res.messages);
                } else {
                    showAccMsg('danger', res.messages);
                }
            },
            error: function () { showAccMsg('danger', 'Error de comunicación con el servidor.'); }
        });
    });

    // Remove Account
    $(document).on('click', '.remove-account', function (e) {
        e.preventDefault();
        if (!confirm('¿Está seguro de eliminar esta cuenta (borrado lógico)?')) return;
        var id = $(this).data('id');
        $.post('php_action/removeAccount.php', { account_id: id, csrf_token: systemCsrfToken }, function (res) {
            if (res.success) {
                accTable.ajax.reload();
                showAccMsg('success', res.messages);
            } else {
                showAccMsg('danger', res.messages);
            }
        }, 'json');
    });

    // Add Fin Category
    $('#addFinCategoryForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/createFinCategory.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#addFinCategoryModal').modal('hide');
                    $('#addFinCategoryForm')[0].reset();
                    catTable.ajax.reload();
                    showAccMsg('success', res.messages);
                } else {
                    showAccMsg('danger', res.messages);
                }
            },
            error: function () { showAccMsg('danger', 'Error de comunicación con el servidor.'); }
        });
    });

    // Edit Fin Category - open
    $(document).on('click', '.edit-fin-category', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.get('php_action/getFinCategoryData.php', { id: id }, function (res) {
            if (!res.success) { showAccMsg('danger', res.messages); return; }
            var d = res.data;
            $('#edit_cat_id').val(d.id);
            $('#edit_cat_name').val(d.category_name);
            $('#edit_cat_type').val(d.category_type);
            $('#edit_cat_color').val(d.color || '#5bc0de');
            $('#edit_cat_status').val(d.status);
            $('#editFinCategoryModal').modal('show');
        }, 'json');
    });

    // Edit Fin Category - submit
    $('#editFinCategoryForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/editFinCategory.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#editFinCategoryModal').modal('hide');
                    catTable.ajax.reload();
                    showAccMsg('success', res.messages);
                } else {
                    showAccMsg('danger', res.messages);
                }
            },
            error: function () { showAccMsg('danger', 'Error de comunicación con el servidor.'); }
        });
    });

    // Remove Fin Category
    $(document).on('click', '.remove-fin-category', function (e) {
        e.preventDefault();
        if (!confirm('¿Está seguro de eliminar esta categoría (borrado lógico)?')) return;
        var id = $(this).data('id');
        $.post('php_action/removeFinCategory.php', { category_id: id, csrf_token: systemCsrfToken }, function (res) {
            if (res.success) {
                catTable.ajax.reload();
                showAccMsg('success', res.messages);
            } else {
                showAccMsg('danger', res.messages);
            }
        }, 'json');
    });
});
