/**
 * bulkImport.js — Shared import modal logic
 *
 * Exposes:  openImportModal(module, headerColor, reloadFn)
 *
 * Depends on:
 *   - #bulkImportModal (importModal.php)
 *   - systemCsrfToken  (set in header.php)
 */
(function ($) {
    'use strict';

    var _reloadFn    = null;
    var _module      = '';
    var MAX_FILE_MB  = 5;
    var MAX_FILE_B   = MAX_FILE_MB * 1024 * 1024;

    var moduleLabels = {
        income:  'Ingresos',
        expense: 'Gastos',
        loan:    'Préstamos',
        project: 'Proyectos',
        asset:   'Activos Valiosos'
    };

    var moduleColors = {
        income:  '27AE60',
        expense: 'C0392B',
        loan:    'E74C3C',
        project: '2980B9',
        asset:   '8E44AD'
    };

    /* ── Public entry point ──────────────────────────────────────── */
    window.openImportModal = function (module, reloadFn) {
        _module   = module;
        _reloadFn = reloadFn || null;

        var color = moduleColors[module] || '34495E';
        var label = moduleLabels[module] || 'Registros';

        // Update header colour
        $('#importModalHeader').css('background', '#' + color);
        $('#bulkImportModalLabel').html(
            '<i class="glyphicon glyphicon-upload"></i> Importar ' + label
        );

        // Set hidden fields
        $('#importModuleInput').val(module);
        $('#importCsrfInput').val(systemCsrfToken);

        // Update template download link
        $('#importDownloadTemplate')
            .attr('href', 'php_action/downloadTemplate.php?module=' + module + '&csrf_token=' + encodeURIComponent(systemCsrfToken));

        // Reset to step 1
        resetImportModal();
        $('#bulkImportModal').modal('show');
    };

    /* ── Reset to step 1 ─────────────────────────────────────────── */
    function resetImportModal() {
        // Steps UI
        $('#istep1').css({ background: '#34495e', color: '#fff' });
        $('#istep2').css({ background: '#ecf0f1', color: '#999' });
        $('#importStep1').show();
        $('#importStep2').hide();

        // Form state
        $('#bulkImportForm')[0].reset();
        $('#importFileInput').val('');
        $('#importFileInfo').hide();
        $('#importFileName').text('');
        $('#importFileSize').text('');
        $('#importClientErrors').hide().html('');
        $('#importSubmitBtn').prop('disabled', true);
        $('#importProgress').hide();
        $('#importDropzoneText').html(
            'Arrastre el archivo aquí o <strong>haga clic para seleccionar</strong>'
        );
        $('#importDropzone').css({ borderColor: '#adb5bd', background: '#fafafa' });
    }

    /* ── Dropzone click → file input ─────────────────────────────── */
    $(document).on('click', '#importDropzone', function () {
        $('#importFileInput').trigger('click');
    });

    /* ── Drag & drop visual feedback ─────────────────────────────── */
    $(document).on('dragover dragenter', '#importDropzone', function (e) {
        e.preventDefault();
        $(this).css({ borderColor: '#3498db', background: '#EBF5FB' });
    }).on('dragleave drop', '#importDropzone', function (e) {
        e.preventDefault();
        if (e.type === 'drop') {
            handleFileSelected(e.originalEvent.dataTransfer.files[0]);
        } else {
            $(this).css({ borderColor: '#adb5bd', background: '#fafafa' });
        }
    });

    /* ── File input change ───────────────────────────────────────── */
    $(document).on('change', '#importFileInput', function () {
        handleFileSelected(this.files[0]);
    });

    function handleFileSelected(file) {
        $('#importClientErrors').hide().html('');
        if (!file) return;

        var allowed = ['.xlsx', '.xls', '.csv'];
        var ext = '.' + file.name.split('.').pop().toLowerCase();
        var errors = [];

        if (allowed.indexOf(ext) === -1) {
            errors.push('Formato no permitido. Solo se aceptan archivos .xlsx, .xls o .csv.');
        }
        if (file.size > MAX_FILE_B) {
            errors.push('El archivo supera el tamaño máximo de ' + MAX_FILE_MB + ' MB.');
        }

        if (errors.length) {
            $('#importClientErrors')
                .html('<strong>Error:</strong> ' + errors.join('<br>'))
                .show();
            $('#importSubmitBtn').prop('disabled', true);
            $('#importFileInfo').hide();
            $('#importDropzone').css({ borderColor: '#e74c3c', background: '#fdf2f2' });
            return;
        }

        // Show file info
        var sizeLabel = file.size < 1024 * 1024
            ? (file.size / 1024).toFixed(1) + ' KB'
            : (file.size / (1024 * 1024)).toFixed(2) + ' MB';

        $('#importFileName').text(file.name);
        $('#importFileSize').text('(' + sizeLabel + ')');
        $('#importFileInfo').show();
        $('#importSubmitBtn').prop('disabled', false);
        $('#importDropzone').css({ borderColor: '#27ae60', background: '#f0fff4' });
        $('#importDropzoneText').html('<strong>' + $('<span>').text(file.name).html() + '</strong> seleccionado');
    }

    /* ── Form submit ─────────────────────────────────────────────── */
    $(document).on('submit', '#bulkImportForm', function (e) {
        e.preventDefault();

        var file = $('#importFileInput')[0].files[0];
        if (!file) {
            $('#importClientErrors').html('Por favor seleccione un archivo.').show();
            return;
        }

        var fd = new FormData(this);
        // Ensure the file is attached (drag-drop may not bind via form)
        fd.set('file', file);
        fd.set('csrf_token', systemCsrfToken);

        $('#importSubmitBtn').prop('disabled', true);
        $('#importProgress').show();
        $('#importClientErrors').hide();

        $.ajax({
            url:         'php_action/bulkImport.php',
            type:        'POST',
            data:        fd,
            processData: false,
            contentType: false,
            dataType:    'json',
            success: function (res) {
                $('#importProgress').hide();
                showResults(res);
            },
            error: function (xhr) {
                $('#importProgress').hide();
                var msg = 'Error de comunicación con el servidor.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r && r.messages) msg = r.messages;
                } catch (ex) {}
                showResults({ success: false, messages: msg, inserted: 0, errors: [] });
            }
        });
    });

    /* ── Render results (step 2) ─────────────────────────────────── */
    function showResults(res) {
        // Activate step 2 UI
        $('#istep1').css({ background: '#ecf0f1', color: '#999' });
        $('#istep2').css({ background: '#34495e', color: '#fff' });
        $('#importStep1').hide();
        $('#importStep2').show();

        var alertClass = res.success ? 'success' : 'danger';
        var iconClass  = res.success ? 'ok-sign' : 'remove-sign';
        var inserted   = res.inserted || 0;
        var errorCount = (res.errors || []).length;

        var summaryHtml =
            '<div class="alert alert-' + alertClass + '" style="font-size:14px;">' +
            '<strong><i class="glyphicon glyphicon-' + iconClass + '"></i> ' +
            (res.success ? 'Importación completada' : 'Importación fallida') + '</strong><br>' +
            $('<span>').text(res.messages).html() +
            '</div>';

        if (res.success && inserted > 0) {
            summaryHtml +=
                '<div class="well well-sm text-center" style="font-size:15px;">' +
                '<span class="text-success" style="font-weight:700;font-size:22px;">' + inserted + '</span>' +
                ' registro(s) importado(s) correctamente.' +
                '</div>';
        }

        $('#importResultSummary').html(summaryHtml);

        // Error table
        if (res.errors && res.errors.length > 0) {
            var tbody = '';
            $.each(res.errors, function (i, err) {
                tbody +=
                    '<tr>' +
                    '<td class="text-center"><strong>' + err.row + '</strong></td>' +
                    '<td>' + $('<span>').text(err.field || '-').html() + '</td>' +
                    '<td>' + $('<span>').text(err.message || '-').html() + '</td>' +
                    '</tr>';
            });
            $('#importErrorsBody').html(tbody);
            $('#importErrorsContainer').show();
        } else {
            $('#importErrorsContainer').hide();
        }

        // Reload table if import succeeded
        if (res.success && inserted > 0 && typeof _reloadFn === 'function') {
            _reloadFn();
        }
    }

    /* ── "Try again" button ──────────────────────────────────────── */
    $(document).on('click', '#importTryAgainBtn', function () {
        resetImportModal();
    });

    /* ── Reset on modal hidden ───────────────────────────────────── */
    $('#bulkImportModal').on('hidden.bs.modal', function () {
        resetImportModal();
    });

}(jQuery));
