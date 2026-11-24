// income.js — Gestión de Ingresos
$(document).ready(function () {

    // Populate selects
    function populateSelects(prefix) {
        var accSel = $('#' + prefix + '_income_account');
        var catSel = $('#' + prefix + '_income_category');
        var projSel = $('#' + prefix + '_income_project');

        accSel.find('option:not(:first)').remove();
        $.each(incomeAccounts, function (i, acc) {
            accSel.append('<option value="' + acc.id + '">' + acc.account_name + ' (' + acc.account_type + ')</option>');
        });
        catSel.find('option:not(:first)').remove();
        $.each(incomeCategories, function (i, cat) {
            catSel.append('<option value="' + cat.id + '">' + cat.category_name + '</option>');
        });
        projSel.find('option:not(:first)').remove();
        $.each(incomeProjects, function (i, proj) {
            projSel.append('<option value="' + proj.id + '">' + proj.project_name + '</option>');
        });
    }

    // Set today's date as default
    var today = new Date().toISOString().slice(0, 10);
    $('#add_income_date').val(today);

    // Init DataTable
    var incomeTable = $('#manageIncomeTable').DataTable({
        processing: true,
        ajax: { url: 'php_action/fetchIncomes.php', type: 'GET' },
        columns: [
            { data: 0, width: '4%' },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7 },
            { data: 8, orderable: false }
        ],
        order: [[5, 'desc']],
        language: { url: 'assests/plugins/datatables/Spanish.json' },
        responsive: true,
        createdRow: function (row, data) {
            if (data[7] && data[7].indexOf('ACTIVO') !== -1) {
                $(row).addClass('income-activo');
            } else if (data[7] && data[7].indexOf('PENDIENTE') !== -1) {
                $(row).addClass('income-pendiente');
            }
        }
    });

    // ADD - open modal
    $('[data-target="#addIncomeModal"]').on('click', function () {
        $('#addIncomeForm')[0].reset();
        $('#add_income_date').val(today);
        populateSelects('add');
    });

    // ADD - submit
    $('#addIncomeForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/createIncome.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#addIncomeModal').modal('hide');
                    incomeTable.ajax.reload();
                    showMessage('success', res.messages);
                } else {
                    showMessage('danger', res.messages);
                }
            },
            error: function () { showMessage('danger', 'Error de comunicación con el servidor.'); }
        });
    });

    // EDIT - open modal
    $(document).on('click', '.edit-income', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        populateSelects('edit');
        $.get('php_action/getIncomeData.php', { id: id }, function (res) {
            if (!res.success) { alert(res.messages); return; }
            var d = res.data;
            $('#edit_income_id').val(d.id);
            $('#edit_income_concept').val(d.concept);
            $('#edit_income_account').val(d.account_id);
            $('#edit_income_category').val(d.category_id || '');
            $('#edit_income_project').val(d.project_id || '');
            $('#edit_income_date').val(d.income_date);
            $('#edit_income_amount').val(d.amount);
            $('#edit_income_status').val(d.status);
            $('#edit_income_notes').val(d.notes || '');
            $('#edit_income_recurring').prop('checked', d.is_recurring == 1);
            $('#editIncomeModal').modal('show');
        }, 'json');
    });

    // EDIT - submit
    $('#editIncomeForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/editIncome.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#editIncomeModal').modal('hide');
                    incomeTable.ajax.reload();
                    showMessage('success', res.messages);
                } else {
                    showMessage('danger', res.messages);
                }
            },
            error: function () { showMessage('danger', 'Error de comunicación con el servidor.'); }
        });
    });

    // REMOVE
    $(document).on('click', '.remove-income', function (e) {
        e.preventDefault();
        if (!confirm('¿Está seguro de eliminar este ingreso? Esta acción no se puede deshacer.')) return;
        var id = $(this).data('id');
        $.ajax({
            url: 'php_action/removeIncome.php',
            type: 'POST',
            data: { income_id: id, csrf_token: systemCsrfToken },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    incomeTable.ajax.reload();
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
