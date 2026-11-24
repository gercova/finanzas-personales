<!-- ===== ADD INCOME MODAL ===== -->
<div class="modal fade" id="addIncomeModal" tabindex="-1" role="dialog" aria-labelledby="addIncomeModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#27ae60;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title" id="addIncomeModalLabel">
          <i class="glyphicon glyphicon-plus-sign"></i> Registrar Ingreso
        </h4>
      </div>
      <form id="addIncomeForm" autocomplete="off">
        <div class="modal-body">
          <div class="row">
            <div class="col-sm-8">
              <div class="form-group">
                <label>Concepto <span class="text-danger">*</span></label>
                <input type="text" name="concept" id="add_income_concept" class="form-control" placeholder="Ej: Mensualidad DRE - Octubre 2026" required>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Monto (S/) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="amount" id="add_income_amount" class="form-control" placeholder="0.00" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Cuenta Destino <span class="text-danger">*</span></label>
                <select name="account_id" id="add_income_account" class="form-control" required>
                  <option value="">-- Seleccione cuenta --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Categoría</label>
                <select name="category_id" id="add_income_category" class="form-control">
                  <option value="">-- Seleccione categoría --</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Proyecto (opcional)</label>
                <select name="project_id" id="add_income_project" class="form-control">
                  <option value="">-- Sin proyecto --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Fecha <span class="text-danger">*</span></label>
                <input type="date" name="income_date" id="add_income_date" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label>Estado</label>
                <select name="status" id="add_income_status" class="form-control">
                  <option value="ACTIVO">ACTIVO</option>
                  <option value="PENDIENTE">PENDIENTE</option>
                  <option value="CANCELADO">CANCELADO</option>
                </select>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>&nbsp;</label><br>
                <label class="checkbox-inline">
                  <input type="checkbox" name="is_recurring" id="add_income_recurring" value="1"> ¿Es recurrente?
                </label>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Notas</label>
            <textarea name="notes" id="add_income_notes" class="form-control" rows="2" placeholder="Observaciones adicionales..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== EDIT INCOME MODAL ===== -->
<div class="modal fade" id="editIncomeModal" tabindex="-1" role="dialog" aria-labelledby="editIncomeModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#2980b9;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title" id="editIncomeModalLabel">
          <i class="glyphicon glyphicon-edit"></i> Editar Ingreso
        </h4>
      </div>
      <form id="editIncomeForm" autocomplete="off">
        <input type="hidden" name="income_id" id="edit_income_id">
        <div class="modal-body">
          <div class="row">
            <div class="col-sm-8">
              <div class="form-group">
                <label>Concepto <span class="text-danger">*</span></label>
                <input type="text" name="concept" id="edit_income_concept" class="form-control" required>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Monto (S/) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="amount" id="edit_income_amount" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Cuenta Destino <span class="text-danger">*</span></label>
                <select name="account_id" id="edit_income_account" class="form-control" required>
                  <option value="">-- Seleccione cuenta --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Categoría</label>
                <select name="category_id" id="edit_income_category" class="form-control">
                  <option value="">-- Seleccione categoría --</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Proyecto (opcional)</label>
                <select name="project_id" id="edit_income_project" class="form-control">
                  <option value="">-- Sin proyecto --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Fecha <span class="text-danger">*</span></label>
                <input type="date" name="income_date" id="edit_income_date" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label>Estado</label>
                <select name="status" id="edit_income_status" class="form-control">
                  <option value="ACTIVO">ACTIVO</option>
                  <option value="PENDIENTE">PENDIENTE</option>
                  <option value="CANCELADO">CANCELADO</option>
                </select>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>&nbsp;</label><br>
                <label class="checkbox-inline">
                  <input type="checkbox" name="is_recurring" id="edit_income_recurring" value="1"> ¿Es recurrente?
                </label>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Notas</label>
            <textarea name="notes" id="edit_income_notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>
