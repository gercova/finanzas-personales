// expense.js — Gestión de Gastos
$(document).ready(function () {

    var today = new Date().toISOString().slice(0, 10);

    function populateExpenseSelects(prefix) {
        var accSel  = $('#' + prefix + '_exp_account');
        var catSel  = $('#' + prefix + '_exp_category');
        var projSel = $('#' + prefix + '_exp_project');
        accSel.find('option:not(:first)').remove();
        $.each(expenseAccounts, function (i, acc) {
            accSel.append('<option value="' + acc.id + '">' + acc.account_name + ' (' + acc.account_type + ')</option>');
        });
        catSel.find('option:not(:first)').remove();
        $.each(expenseCategories, function (i, cat) {
            catSel.append('<option value="' + cat.id + '">' + cat.category_name + '</option>');
        });
        projSel.find('option:not(:first)').remove();
        $.each(expenseProjects, function (i, proj) {
            projSel.append('<option value="' + proj.id + '">' + proj.project_name + '</option>');
        });
    }

    // Init DataTable
    var expTable = $('#manageExpenseTable').DataTable({
        processing: true,
        ajax: { url: 'php_action/fetchExpenses.php', type: 'GET' },
        columns: [
            { data: 0, width: '4%' },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, orderable: false },
            { data: 7 },
            { data: 8, orderable: false }
        ],
        order: [[4, 'desc']],
        language: { url: 'assests/plugins/datatables/Spanish.json' },
        responsive: true,
        createdRow: function (row, data) {
            if (data[7] && data[7].indexOf('PAGADO') !== -1) {
                $(row).addClass('expense-pagado');
            } else {
                $(row).addClass('expense-pendiente');
            }
        }
    });

    // ADD - open modal
    $('[data-target="#addExpenseModal"]').on('click', function () {
        $('#addExpenseForm')[0].reset();
        $('#add_exp_date').val(today);
        $('#add_exp_preview').html('');
        populateExpenseSelects('add');
    });

    // Receipt preview - add
    $('#add_exp_receipt').on('change', function () {
        var file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#add_exp_preview').html('<img src="' + e.target.result + '" style="max-height:120px;border-radius:4px;border:1px solid #ddd;">');
            };
            reader.readAsDataURL(file);
        }
    });

    // Receipt preview - edit
    $('#edit_exp_receipt').on('change', function () {
        var file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#edit_exp_preview').html('<img src="' + e.target.result + '" style="max-height:120px;border-radius:4px;border:1px solid #ddd;">');
            };
            reader.readAsDataURL(file);
        }
    });

    // ADD - submit
    $('#addExpenseForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/createExpense.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#addExpenseModal').modal('hide');
                    expTable.ajax.reload();
                    showMessage('success', res.messages);
                } else {
                    showMessage('danger', res.messages);
                }
            },
            error: function () { showMessage('danger', 'Error de comunicación con el servidor.'); }
        });
    });

    // EDIT - open
    $(document).on('click', '.edit-expense', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        populateExpenseSelects('edit');
        $.get('php_action/getExpenseData.php', { id: id }, function (res) {
            if (!res.success) { alert(res.messages); return; }
            var d = res.data;
            $('#edit_exp_id').val(d.id);
            $('#edit_exp_concept').val(d.concept);
            $('#edit_exp_account').val(d.account_id);
            $('#edit_exp_category').val(d.category_id || '');
            $('#edit_exp_project').val(d.project_id || '');
            $('#edit_exp_date').val(d.expense_date);
            $('#edit_exp_amount').val(d.amount);
            $('#edit_exp_status').val(d.status);
            $('#edit_exp_notes').val(d.notes || '');
            $('#edit_exp_preview').html('');
            if (d.receipt_image) {
                $('#edit_exp_current_img').html('<img src="../' + d.receipt_image + '" style="max-height:60px;border-radius:3px;border:1px solid #ddd;">');
            } else {
                $('#edit_exp_current_img').html('<span class="text-muted">Sin comprobante</span>');
            }
            $('#editExpenseModal').modal('show');
        }, 'json');
    });

    // EDIT - submit
    $('#editExpenseForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/editExpense.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#editExpenseModal').modal('hide');
                    expTable.ajax.reload();
                    showMessage('success', res.messages);
                } else {
                    showMessage('danger', res.messages);
                }
            },
            error: function () { showMessage('danger', 'Error de comunicación con el servidor.'); }
        });
    });

    // REMOVE
    $(document).on('click', '.remove-expense', function (e) {
        e.preventDefault();
        if (!confirm('¿Está seguro de eliminar este gasto? Esta acción no se puede deshacer.')) return;
        var id = $(this).data('id');
        $.ajax({
            url: 'php_action/removeExpense.php',
            type: 'POST',
            data: { expense_id: id, csrf_token: systemCsrfToken },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    expTable.ajax.reload();
                    showMessage('success', res.messages);
                } else {
                    showMessage('danger', res.messages);
                }
            },
            error: function () { showMessage('danger', 'Error de comunicación con el servidor.'); }
        });
    });

    function showMessage(type, msg) {
        var html = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' + msg + '</div>';
        $('.remove-messages').html(html);
        $('html,body').animate({ scrollTop: 0 }, 400);
        setTimeout(function () { $('.remove-messages .alert').fadeOut(500, function () { $(this).remove(); }); }, 4000);
    }
});
