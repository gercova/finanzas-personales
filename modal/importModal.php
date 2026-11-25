<!-- ===== BULK IMPORT MODAL (shared) ===== -->
<!--
     Usage: include this file on any page that needs bulk import.
     Set the JS variable `importModalModule` BEFORE this modal is triggered.
     The trigger button should call openImportModal('income'|'expense'|'loan'|'project'|'asset')
-->
<div class="modal fade" id="bulkImportModal" tabindex="-1" role="dialog" aria-labelledby="bulkImportModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" id="importModalHeader" style="background:#34495e;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title" id="bulkImportModalLabel">
          <i class="glyphicon glyphicon-upload"></i> Importar Registros
        </h4>
      </div>

      <div class="modal-body">
        <!-- Step indicator -->
        <div class="import-steps" style="display:flex;gap:0;margin-bottom:20px;border:1px solid #ddd;border-radius:4px;overflow:hidden;">
          <div class="import-step active" id="istep1" style="flex:1;padding:10px;text-align:center;background:#34495e;color:#fff;font-size:13px;font-weight:600;">
            <i class="glyphicon glyphicon-cloud-upload"></i> 1. Subir archivo
          </div>
          <div class="import-step" id="istep2" style="flex:1;padding:10px;text-align:center;background:#ecf0f1;color:#999;font-size:13px;font-weight:600;">
            <i class="glyphicon glyphicon-list-alt"></i> 2. Resultado
          </div>
        </div>

        <!-- STEP 1: upload form -->
        <div id="importStep1">
          <div class="alert alert-info" style="font-size:13px;">
            <strong><i class="glyphicon glyphicon-info-sign"></i> Antes de importar:</strong>
            <ol style="margin:8px 0 0 0;padding-left:18px;">
              <li>Descargue la plantilla con el botón de abajo y llénela con sus datos.</li>
              <li>El archivo debe ser <strong>.xlsx, .xls o .csv</strong> (máx. 5 MB, máx. 500 filas).</li>
              <li>No modifique los encabezados de la plantilla.</li>
              <li>Los campos marcados con <strong>*</strong> son obligatorios.</li>
              <li>Los nombres de <strong>Cuenta, Categoría y Proyecto</strong> deben coincidir exactamente con los ya registrados en el sistema.</li>
            </ol>
          </div>

          <div class="text-center" style="margin-bottom:16px;">
            <a id="importDownloadTemplate" href="#" class="btn btn-default btn-sm" target="_blank">
              <i class="glyphicon glyphicon-download-alt"></i> Descargar Plantilla (.xlsx)
            </a>
          </div>

          <form id="bulkImportForm" enctype="multipart/form-data">
            <input type="hidden" name="module"      id="importModuleInput" value="">
            <input type="hidden" name="csrf_token"  id="importCsrfInput"   value="">

            <div class="form-group">
              <label><i class="glyphicon glyphicon-file"></i> Seleccionar archivo Excel o CSV</label>
              <div class="import-dropzone" id="importDropzone"
                   style="border:2px dashed #adb5bd;border-radius:6px;padding:30px 20px;text-align:center;cursor:pointer;background:#fafafa;transition:border-color .2s,background .2s;">
                <i class="glyphicon glyphicon-cloud-upload" style="font-size:36px;color:#aaa;display:block;margin-bottom:8px;"></i>
                <span id="importDropzoneText" style="color:#777;font-size:14px;">
                  Arrastre el archivo aquí o <strong>haga clic para seleccionar</strong>
                </span>
                <input type="file" name="file" id="importFileInput"
                       accept=".xlsx,.xls,.csv"
                       style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden;">
              </div>
              <div id="importFileInfo" style="margin-top:8px;display:none;">
                <div class="label label-success" style="font-size:12px;padding:4px 10px;">
                  <i class="glyphicon glyphicon-ok-sign"></i>
                  <span id="importFileName"></span>
                  <span id="importFileSize" style="margin-left:6px;opacity:.8;"></span>
                </div>
              </div>
            </div>

            <div id="importClientErrors" class="alert alert-danger" style="display:none;font-size:13px;"></div>

            <div class="text-right">
              <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary" id="importSubmitBtn" disabled>
                <i class="glyphicon glyphicon-upload"></i> Importar
              </button>
            </div>
          </form>

          <!-- Progress bar (shown during upload) -->
          <div id="importProgress" style="display:none;margin-top:16px;">
            <div class="progress">
              <div class="progress-bar progress-bar-striped active"
                   role="progressbar" style="width:100%;min-width:40px;">
                Procesando...
              </div>
            </div>
          </div>
        </div><!-- /importStep1 -->

        <!-- STEP 2: results -->
        <div id="importStep2" style="display:none;">
          <div id="importResultSummary"></div>

          <div id="importErrorsContainer" style="display:none;">
            <h5 style="font-weight:600;margin-top:16px;">
              <i class="glyphicon glyphicon-warning-sign text-warning"></i>
              Filas con errores (no importadas)
            </h5>
            <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
              <table class="table table-condensed table-bordered" style="font-size:12px;">
                <thead style="background:#f5f5f5;">
                  <tr>
                    <th style="width:60px;">Fila</th>
                    <th style="width:120px;">Campo</th>
                    <th>Detalle del error</th>
                  </tr>
                </thead>
                <tbody id="importErrorsBody"></tbody>
              </table>
            </div>
          </div>

          <div class="text-right" style="margin-top:16px;">
            <button type="button" class="btn btn-default" id="importTryAgainBtn">
              <i class="glyphicon glyphicon-repeat"></i> Importar otro archivo
            </button>
            <button type="button" class="btn btn-primary" data-dismiss="modal">
              <i class="glyphicon glyphicon-ok"></i> Cerrar
            </button>
          </div>
        </div><!-- /importStep2 -->

      </div><!-- /modal-body -->
    </div>
  </div>
</div>
