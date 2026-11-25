<!-- ===== ADD ACCOUNT MODAL ===== -->
<div class="modal fade" id="addAccountModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#2e6da4;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-plus-sign"></i> Nueva Cuenta Financiera</h4>
      </div>
      <form id="addAccountForm" autocomplete="off">
        <div class="modal-body">
          <div class="form-group">
            <label>Nombre / Institución <span class="text-danger">*</span></label>
            <input type="text" name="account_name" id="add_acc_name" class="form-control" placeholder="Ej: Banco de Crédito (BCP), Efectivo..." required>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Tipo de Cuenta</label>
                <select name="account_type" id="add_acc_type" class="form-control">
                  <option value="BANK">Banco</option>
                  <option value="DIGITAL_WALLET">Billetera Digital (Yape/Plin)</option>
                  <option value="CASH">Efectivo en Mano</option>
                  <option value="SAVINGS">Cuenta de Ahorros</option>
                  <option value="OTHER">Otro</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>N° Cuenta / Referencia</label>
                <input type="text" name="account_number" id="add_acc_number" class="form-control" placeholder="Opcional">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label>Saldo Inicial (S/)</label>
                <input type="number" step="0.01" name="initial_balance" id="add_acc_balance" class="form-control" value="0.00" required>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Moneda</label>
                <input type="text" name="currency" id="add_acc_currency" class="form-control" value="PEN" maxlength="10">
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Color</label>
                <input type="color" name="color" id="add_acc_color" class="form-control" value="#2e6da4">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== EDIT ACCOUNT MODAL ===== -->
<div class="modal fade" id="editAccountModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#2980b9;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-edit"></i> Editar Cuenta Financiera</h4>
      </div>
      <form id="editAccountForm" autocomplete="off">
        <input type="hidden" name="account_id" id="edit_acc_id">
        <div class="modal-body">
          <div class="form-group">
            <label>Nombre / Institución <span class="text-danger">*</span></label>
            <input type="text" name="account_name" id="edit_acc_name" class="form-control" required>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Tipo de Cuenta</label>
                <select name="account_type" id="edit_acc_type" class="form-control">
                  <option value="BANK">Banco</option>
                  <option value="DIGITAL_WALLET">Billetera Digital (Yape/Plin)</option>
                  <option value="CASH">Efectivo en Mano</option>
                  <option value="SAVINGS">Cuenta de Ahorros</option>
                  <option value="OTHER">Otro</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>N° Cuenta / Referencia</label>
                <input type="text" name="account_number" id="edit_acc_number" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label>Moneda</label>
                <input type="text" name="currency" id="edit_acc_currency" class="form-control" maxlength="10">
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Color</label>
                <input type="color" name="color" id="edit_acc_color" class="form-control">
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Estado</label>
                <select name="status" id="edit_acc_status" class="form-control">
                  <option value="1">Activa</option>
                  <option value="0">Inactiva</option>
                </select>
              </div>
            </div>
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

<!-- ===== ADD FINANCIAL CATEGORY MODAL ===== -->
<div class="modal fade" id="addFinCategoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#31b0d5;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-tag"></i> Nueva Categoría</h4>
      </div>
      <form id="addFinCategoryForm" autocomplete="off">
        <div class="modal-body">
          <div class="form-group">
            <label>Nombre <span class="text-danger">*</span></label>
            <input type="text" name="category_name" id="add_cat_name" class="form-control" placeholder="Ej: Freelance, Alimentación..." required>
          </div>
          <div class="form-group">
            <label>Tipo</label>
            <select name="category_type" id="add_cat_type" class="form-control">
              <option value="INCOME">Ingreso</option>
              <option value="EXPENSE" selected>Gasto</option>
            </select>
          </div>
          <div class="form-group">
            <label>Color</label>
            <input type="color" name="color" id="add_cat_color" class="form-control" value="#5bc0de">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== EDIT FINANCIAL CATEGORY MODAL ===== -->
<div class="modal fade" id="editFinCategoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#31b0d5;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-edit"></i> Editar Categoría</h4>
      </div>
      <form id="editFinCategoryForm" autocomplete="off">
        <input type="hidden" name="category_id" id="edit_cat_id">
        <div class="modal-body">
          <div class="form-group">
            <label>Nombre <span class="text-danger">*</span></label>
            <input type="text" name="category_name" id="edit_cat_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Tipo</label>
            <select name="category_type" id="edit_cat_type" class="form-control">
              <option value="INCOME">Ingreso</option>
              <option value="EXPENSE">Gasto</option>
            </select>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <div class="form-group">
                <label>Color</label>
                <input type="color" name="color" id="edit_cat_color" class="form-control">
              </div>
            </div>
            <div class="col-xs-6">
              <div class="form-group">
                <label>Estado</label>
                <select name="status" id="edit_cat_status" class="form-control">
                  <option value="1">Activa</option>
                  <option value="0">Inactiva</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info"><i class="glyphicon glyphicon-floppy-disk"></i> Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>
