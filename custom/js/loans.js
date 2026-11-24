// loans.js — Gestión de Préstamos
$(document).ready(function () {

    var today = new Date().toISOString().slice(0, 10);

    function populateLoanAccountSelects() {
        ['add_loan_account','edit_loan_account','pay_dest_account'].forEach(function(selId) {
            var sel = $('#' + selId);
            sel.find('option:not(:first)').remove();
            $.each(loanAccounts, function(i, acc) {
                sel.append('<option value="' + acc.id + '">' + acc.account_name + ' (' + acc.account_type + ')</option>');
            });
        });
    }

    // Interest calculator — ADD
    function calcInterestAdd() {
        var principal = parseFloat($('#add_loan_principal').val()) || 0;
        var type = $('input[name="interest_type"]:checked').val();
        var val  = parseFloat($('#add_loan_interest_val').val()) || 0;
        var total = principal;
        if (type === 'PORCENTAJE') total = principal + (principal * val / 100);
        else if (type === 'FIJO')  total = principal + val;
        $('#add_loan_total').val(total.toFixed(2));
    }

    $('input[name="interest_type"]').on('change', function() {
        var type = $(this).val();
        if (type === 'NINGUNO') {
            $('#add_loan_interest_val_div').hide();
            $('#add_loan_interest_val').val('');
        } else {
            $('#add_loan_interest_val_div').show();
            $('#add_loan_interest_label').text(type === 'PORCENTAJE' ? 'Porcentaje (%)' : 'Monto Fijo (S/)');
        }
        calcInterestAdd();
    });
    $('#add_loan_principal, #add_loan_interest_val').on('input', calcInterestAdd);

    // Interest calculator — EDIT
    function calcInterestEdit() {
        var principal = parseFloat($('#edit_loan_principal').val()) || 0;
        var type = $('input[name="interest_type"]:checked', '#editLoanForm').val();
        var val  = parseFloat($('#edit_loan_interest_val').val()) || 0;
        var total = principal;
        if (type === 'PORCENTAJE') total = principal + (principal * val / 100);
        else if (type === 'FIJO')  total = principal + val;
        $('#edit_loan_total').val(total.toFixed(2));
    }
    $('#editLoanModal').on('change', 'input[name="interest_type"]', function() {
        var type = $(this).val();
        if (type === 'NINGUNO') {
            $('#edit_loan_interest_val_div').hide();
            $('#edit_loan_interest_val').val('');
        } else {
            $('#edit_loan_interest_val_div').show();
            $('#edit_loan_interest_label').text(type === 'PORCENTAJE' ? 'Porcentaje (%)' : 'Monto Fijo (S/)');
        }
        calcInterestEdit();
    });
    $('#edit_loan_principal, #edit_loan_interest_val').on('input', calcInterestEdit);

    // Init DataTable
    var loanTable = $('#manageLoanTable').DataTable({
        processing: true,
        ajax: { url: 'php_action/fetchLoans.php', type: 'GET' },
        columns: [
            { data: 0, width: '3%' },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7 },
            { data: 8 },
            { data: 9 },
            { data: 10 },
            { data: 11 },
            { data: 12, orderable: false }
        ],
        order: [[4, 'desc']],
        language: { url: 'assests/plugins/datatables/Spanish.json' },
        responsive: true,
        createdRow: function(row, data) {
            if (data[11]) {
                if (data[11].indexOf('PAGADO') !== -1) $(row).css('background-color','#e8d5f5');
                else if (data[11].indexOf('PARCIAL') !== -1) $(row).css('background-color','#fff3cd');
                else $(row).css('background-color','#ffcccc');
            }
        }
    });

    // ADD - open
    $('[data-target="#addLoanModal"]').on('click', function() {
        $('#addLoanForm')[0].reset();
        $('#add_loan_date').val(today);
        $('#add_loan_interest_val_div').hide();
        populateLoanAccountSelects();
        $('input[name="interest_type"][value="NINGUNO"]').prop('checked', true);
        $('#add_loan_total').val('');
    });

    // ADD - submit
    $('#addLoanForm').on('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/createLoan.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    $('#addLoanModal').modal('hide');
                    loanTable.ajax.reload();
                    showMessage('success', res.messages);
                } else {
                    showMessage('danger', res.messages);
                }
            },
            error: function() { showMessage('danger', 'Error de comunicación.'); }
        });
    });

    // EDIT - open
    $(document).on('click', '.edit-loan', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        populateLoanAccountSelects();
        $.get('php_action/getLoanData.php', { id: id }, function(res) {
            if (!res.success) { alert(res.messages); return; }
            var d = res.data;
            $('#edit_loan_id').val(d.id);
            $('#edit_loan_borrower').val(d.borrower_name);
            $('#edit_loan_concept').val(d.concept);
            $('#edit_loan_account').val(d.account_id);
            $('#edit_loan_date').val(d.loan_date);
            $('#edit_loan_due_date').val(d.due_date || '');
            $('#edit_loan_principal').val(d.principal_amount);
            $('#edit_loan_total').val(d.total_receivable);
            $('#edit_loan_status').val(d.status);
            $('#edit_loan_notes').val(d.notes || '');

            var intType = d.interest_type || 'NINGUNO';
            $('input[name="interest_type"][value="' + intType + '"]', '#editLoanForm').prop('checked', true);
            if (intType !== 'NINGUNO') {
                $('#edit_loan_interest_val_div').show();
                $('#edit_loan_interest_label').text(intType === 'PORCENTAJE' ? 'Porcentaje (%)' : 'Monto Fijo (S/)');
                $('#edit_loan_interest_val').val(d.interest_value || '');
            } else {
                $('#edit_loan_interest_val_div').hide();
            }
            $('#editLoanModal').modal('show');
        }, 'json');
    });

    // EDIT - submit
    $('#editLoanForm').on('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/editLoan.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    $('#editLoanModal').modal('hide');
                    loanTable.ajax.reload();
                    showMessage('success', res.messages);
                } else {
                    showMessage('danger', res.messages);
                }
            },
            error: function() { showMessage('danger', 'Error de comunicación.'); }
        });
    });

    // PAYMENT - open
    $(document).on('click', '.pay-loan', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var borrower = $(this).data('borrower');
        var pending  = $(this).data('pending');
        populateLoanAccountSelects();
        $('#pay_loan_id').val(id);
        $('#pay_date').val(today);
        $('#pay_amount').val('');
        $('#pay_notes').val('');
        $('#pay_receipt').val('');
        $('#pay_loan_info').show().html(
            '<strong>Prestatario:</strong> ' + borrower + ' &mdash; <strong>Pendiente: S/ ' + pending + '</strong>'
        );
        $('#loanPaymentModal').modal('show');
    });

    // PAYMENT - submit
    $('#loanPaymentForm').on('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('csrf_token', systemCsrfToken);
        $.ajax({
            url: 'php_action/addLoanPayment.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    $('#loanPaymentModal').modal('hide');
                    loanTable.ajax.reload();
                    showMessage('success', res.messages);
                } else {
                    showMessage('danger', res.messages);
                }
            },
            error: function() { showMessage('danger', 'Error de comunicación.'); }
        });
    });

    // REMOVE
    $(document).on('click', '.remove-loan', function(e) {
        e.preventDefault();
        if (!confirm('¿Está seguro de eliminar este préstamo y todos sus pagos? Esta acción no se puede deshacer.')) return;
        var id = $(this).data('id');
        $.ajax({
            url: 'php_action/removeLoan.php',
            type: 'POST',
            data: { loan_id: id, csrf_token: systemCsrfToken },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    loanTable.ajax.reload();
                    showMessage('success', res.messages);
                } else {
                    showMessage('danger', res.messages);
                }
            },
            error: function() { showMessage('danger', 'Error de comunicación.'); }
        });
    });

    function showMessage(type, msg) {
        var html = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' + msg + '</div>';
        $('.remove-messages').html(html);
        $('html,body').animate({ scrollTop: 0 }, 400);
        setTimeout(function() { $('.remove-messages .alert').fadeOut(500, function() { $(this).remove(); }); }, 5000);
    }
});
