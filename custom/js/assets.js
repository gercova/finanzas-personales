$(document).ready(function () {

    // ── DataTable ────────────────────────────────────────────────────────────
    var assetsTable = $('#assetsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'php_action/fetchAssets.php',
            type: 'GET'
        },
        columns: [
            { data: 0, width: '4%' },
            { data: 1 },
            { data: 2 },
            { data: 3, className: 'text-right' },
            { data: 4, className: 'text-right' },
            { data: 5 },
            { data: 6 },
            { data: 7, orderable: false },
            { data: 8, orderable: false },
            { data: 9, orderable: false }
        ],
        responsive: true,
        language: {
            url: 'assests/plugins/datatables/Spanish.json',
            emptyTable: 'No hay activos registrados.'
        }
    });

    // ── Image preview – Add form ─────────────────────────────────────────────
    $('#asset_image').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#asset_image_preview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });

    $('#edit_asset_image').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#edit_asset_image_preview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });

    // ── Add Asset ────────────────────────────────────────────────────────────
    $('#addAssetForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('csrf_token', systemCsrfToken);

        $.ajax({
            url: 'php_action/createAsset.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#addAssetForm')[0].reset();
                    $('#asset_image_preview').hide();
                    $('#addAssetModal .success-messages').html(
                        '<div class="alert alert-success">' + res.messages + '</div>'
                    );
                    assetsTable.ajax.reload();
                    setTimeout(function () { $('#addAssetModal').modal('hide'); }, 1200);
                } else {
                    $('#addAssetModal .success-messages').html(
                        '<div class="alert alert-danger">' + res.messages + '</div>'
                    );
                }
            }
        });
    });

    // ── Edit Asset: load data ────────────────────────────────────────────────
    window.editAsset = function (id) {
        $.get('php_action/getAssetData.php', { id: id }, function (data) {
            if (!data) return;
            $('#edit_asset_id').val(data.asset_id);
            $('#edit_asset_name').val(data.asset_name);
            $('#edit_category').val(data.category);
            $('#edit_estimated_value').val(data.estimated_value);
            $('#edit_purchase_price').val(data.purchase_price || '');
            $('#edit_purchase_date').val(data.purchase_date || '');
            $('#edit_document_number').val(data.document_number || '');
            $('#edit_location_or_custody').val(data.location_or_custody || '');
            $('#edit_asset_status').val(data.status);
            $('#edit_notes').val(data.notes || '');
            if (data.image_path) {
                $('#edit_asset_image_preview').attr('src', data.image_path).show();
            } else {
                $('#edit_asset_image_preview').hide();
            }
            $('#editAssetModal').modal('show');
        });
    };

    // ── Edit Asset: submit ───────────────────────────────────────────────────
    $('#editAssetForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('csrf_token', systemCsrfToken);

        $.ajax({
            url: 'php_action/editAsset.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#editAssetModal .edit-success-messages').html(
                        '<div class="alert alert-success">' + res.messages + '</div>'
                    );
                    assetsTable.ajax.reload();
                    setTimeout(function () { $('#editAssetModal').modal('hide'); }, 1200);
                } else {
                    $('#editAssetModal .edit-success-messages').html(
                        '<div class="alert alert-danger">' + res.messages + '</div>'
                    );
                }
            }
        });
    });

    // ── Remove Asset ─────────────────────────────────────────────────────────
    window.removeAsset = function (id) {
        if (!confirm('¿Eliminar este activo permanentemente?')) return;
        $.post('php_action/removeAsset.php', { asset_id: id, csrf_token: systemCsrfToken }, function (res) {
            if (res.success) {
                assetsTable.ajax.reload();
            } else {
                alert(res.messages);
            }
        });
    };

    // ── Reset modals on close ────────────────────────────────────────────────
    $('#addAssetModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $(this).find('.success-messages').html('');
        $('#asset_image_preview').hide();
    });
    $('#editAssetModal').on('hidden.bs.modal', function () {
        $(this).find('.edit-success-messages').html('');
        $('#edit_asset_image_preview').hide();
    });
});
