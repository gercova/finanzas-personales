<!-- ===== ADD EXPENSE MODAL ===== -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" role="dialog" aria-labelledby="addExpenseModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#c0392b;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title" id="addExpenseModalLabel">
          <i class="glyphicon glyphicon-plus-sign"></i> Registrar Gasto
        </h4>
      </div>
      <form id="addExpenseForm" enctype="multipart/form-data" autocomplete="off">
        <div class="modal-body">
          <div class="row">
            <div class="col-sm-8">
              <div class="form-group">
                <label>Concepto <span class="text-danger">*</span></label>
                <input type="text" name="concept" id="add_exp_concept" class="form-control" placeholder="Ej: Pago cuarto - Mayo 2026" required>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Monto (S/) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="amount" id="add_exp_amount" class="form-control" placeholder="0.00" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Cuenta Origen <span class="text-danger">*</span></label>
                <select name="account_id" id="add_exp_account" class="form-control" required>
                  <option value="">-- Seleccione cuenta --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Categoría</label>
                <select name="category_id" id="add_exp_category" class="form-control">
                  <option value="">-- Seleccione categoría --</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Proyecto (opcional)</label>
                <select name="project_id" id="add_exp_project" class="form-control">
                  <option value="">-- Sin proyecto --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label>Fecha <span class="text-danger">*</span></label>
                <input type="date" name="expense_date" id="add_exp_date" class="form-control" required>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label>Estado</label>
                <select name="status" id="add_exp_status" class="form-control">
                  <option value="PAGADO">PAGADO</option>
                  <option value="PENDIENTE">PENDIENTE</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Comprobante (foto/imagen)</label>
                <input type="file" name="receipt_image" id="add_exp_receipt" accept="image/*" class="form-control">
                <div id="add_exp_preview" style="margin-top:8px;"></div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Notas</label>
                <textarea name="notes" id="add_exp_notes" class="form-control" rows="3" placeholder="Observaciones adicionales..."></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== EDIT EXPENSE MODAL ===== -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" role="dialog" aria-labelledby="editExpenseModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#e67e22;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title" id="editExpenseModalLabel">
          <i class="glyphicon glyphicon-edit"></i> Editar Gasto
        </h4>
      </div>
      <form id="editExpenseForm" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="expense_id" id="edit_exp_id">
        <div class="modal-body">
          <div class="row">
            <div class="col-sm-8">
              <div class="form-group">
                <label>Concepto <span class="text-danger">*</span></label>
                <input type="text" name="concept" id="edit_exp_concept" class="form-control" required>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Monto (S/) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="amount" id="edit_exp_amount" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Cuenta Origen <span class="text-danger">*</span></label>
                <select name="account_id" id="edit_exp_account" class="form-control" required>
                  <option value="">-- Seleccione cuenta --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Categoría</label>
                <select name="category_id" id="edit_exp_category" class="form-control">
                  <option value="">-- Seleccione categoría --</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Proyecto (opcional)</label>
                <select name="project_id" id="edit_exp_project" class="form-control">
                  <option value="">-- Sin proyecto --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label>Fecha <span class="text-danger">*</span></label>
                <input type="date" name="expense_date" id="edit_exp_date" class="form-control" required>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label>Estado</label>
                <select name="status" id="edit_exp_status" class="form-control">
                  <option value="PAGADO">PAGADO</option>
                  <option value="PENDIENTE">PENDIENTE</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Comprobante actual</label>
                <div id="edit_exp_current_img" style="margin-bottom:6px;"></div>
                <input type="file" name="receipt_image" id="edit_exp_receipt" accept="image/*" class="form-control">
                <small class="text-muted">Dejar vacío para conservar el actual</small>
                <div id="edit_exp_preview" style="margin-top:6px;"></div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Notas</label>
                <textarea name="notes" id="edit_exp_notes" class="form-control" rows="3"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning"><i class="glyphicon glyphicon-floppy-disk"></i> Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
// Accounts, categories, and projects for JS injection (strictly scoped to current user)
$expUserId = (int)$_SESSION['userId'];

$expAccounts = [];
$stmtExpAcc = $connect->prepare("SELECT account_id AS id, account_name, account_type FROM accounts WHERE created_by=? AND status=1 AND is_deleted=0 ORDER BY account_name");
if ($stmtExpAcc) { $stmtExpAcc->bind_param("i", $expUserId); $stmtExpAcc->execute(); $r=$stmtExpAcc->get_result(); while($row=$r->fetch_assoc()) $expAccounts[]=$row; $stmtExpAcc->close(); }

$expCategories = [];
$stmtExpCat = $connect->prepare("SELECT category_id AS id, category_name FROM financial_categories WHERE created_by=? AND category_type='EXPENSE' AND status=1 AND is_deleted=0 ORDER BY category_name");
if ($stmtExpCat) { $stmtExpCat->bind_param("i", $expUserId); $stmtExpCat->execute(); $r=$stmtExpCat->get_result(); while($row=$r->fetch_assoc()) $expCategories[]=$row; $stmtExpCat->close(); }

$expProjects = [];
$stmtExpProj = $connect->prepare("SELECT project_id AS id, project_name FROM projects WHERE created_by=? AND status='ACTIVE' AND is_deleted=0 ORDER BY project_name");
if ($stmtExpProj) { $stmtExpProj->bind_param("i", $expUserId); $stmtExpProj->execute(); $r=$stmtExpProj->get_result(); while($row=$r->fetch_assoc()) $expProjects[]=$row; $stmtExpProj->close(); }
?>
<script>
var expenseAccounts   = <?php echo json_encode($expAccounts); ?>;
var expenseCategories = <?php echo json_encode($expCategories); ?>;
var expenseProjects   = <?php echo json_encode($expProjects); ?>;
</script>
