<!-- ===== ADD LOAN MODAL ===== -->
<div class="modal fade" id="addLoanModal" tabindex="-1" role="dialog" aria-labelledby="addLoanModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#c0392b;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-plus-sign"></i> Registrar Préstamo</h4>
      </div>
      <form id="addLoanForm" autocomplete="off">
        <div class="modal-body">
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Nombre del Prestatario <span class="text-danger">*</span></label>
                <input type="text" name="borrower_name" id="add_loan_borrower" class="form-control" placeholder="Ej: Daniel García" required>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Concepto / Descripción <span class="text-danger">*</span></label>
                <input type="text" name="concept" id="add_loan_concept" class="form-control" placeholder="Ej: Préstamo personal" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Cuenta de Origen (Fondos) <span class="text-danger">*</span></label>
                <select name="account_id" id="add_loan_account" class="form-control" required>
                  <option value="">-- Seleccione cuenta --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label>Fecha de Préstamo <span class="text-danger">*</span></label>
                <input type="date" name="loan_date" id="add_loan_date" class="form-control" required>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label>Fecha de Vencimiento</label>
                <input type="date" name="due_date" id="add_loan_due_date" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label>Capital Prestado (S/) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="principal_amount" id="add_loan_principal" class="form-control" placeholder="0.00" required>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Tipo de Interés</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="interest_type" value="NINGUNO" checked> Sin interés</label>
                  <label class="radio-inline"><input type="radio" name="interest_type" value="PORCENTAJE"> %</label>
                  <label class="radio-inline"><input type="radio" name="interest_type" value="FIJO"> Fijo S/</label>
                </div>
              </div>
            </div>
            <div class="col-sm-4" id="add_loan_interest_val_div" style="display:none;">
              <div class="form-group">
                <label id="add_loan_interest_label">Valor de Interés</label>
                <input type="number" step="0.01" min="0" name="interest_value" id="add_loan_interest_val" class="form-control" placeholder="0.00">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Total a Cobrar (S/)</label>
                <input type="number" step="0.01" name="total_receivable" id="add_loan_total" class="form-control" placeholder="Auto-calculado" readonly style="background:#f5f5f5;">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Notas</label>
            <textarea name="notes" id="add_loan_notes" class="form-control" rows="2" placeholder="Observaciones adicionales..."></textarea>
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

<!-- ===== EDIT LOAN MODAL ===== -->
<div class="modal fade" id="editLoanModal" tabindex="-1" role="dialog" aria-labelledby="editLoanModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#8e44ad;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-edit"></i> Editar Préstamo</h4>
      </div>
      <form id="editLoanForm" autocomplete="off">
        <input type="hidden" name="loan_id" id="edit_loan_id">
        <div class="modal-body">
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Prestatario <span class="text-danger">*</span></label>
                <input type="text" name="borrower_name" id="edit_loan_borrower" class="form-control" required>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Concepto <span class="text-danger">*</span></label>
                <input type="text" name="concept" id="edit_loan_concept" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Cuenta de Origen <span class="text-danger">*</span></label>
                <select name="account_id" id="edit_loan_account" class="form-control" required>
                  <option value="">-- Seleccione cuenta --</option>
                </select>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label>Fecha Préstamo <span class="text-danger">*</span></label>
                <input type="date" name="loan_date" id="edit_loan_date" class="form-control" required>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label>Vencimiento</label>
                <input type="date" name="due_date" id="edit_loan_due_date" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label>Capital (S/) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="principal_amount" id="edit_loan_principal" class="form-control" required>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Tipo de Interés</label>
                <div>
                  <label class="radio-inline"><input type="radio" name="interest_type" id="edit_int_none" value="NINGUNO"> Sin interés</label>
                  <label class="radio-inline"><input type="radio" name="interest_type" id="edit_int_pct" value="PORCENTAJE"> %</label>
                  <label class="radio-inline"><input type="radio" name="interest_type" id="edit_int_fixed" value="FIJO"> Fijo</label>
                </div>
              </div>
            </div>
            <div class="col-sm-4" id="edit_loan_interest_val_div" style="display:none;">
              <div class="form-group">
                <label id="edit_loan_interest_label">Valor Interés</label>
                <input type="number" step="0.01" min="0" name="interest_value" id="edit_loan_interest_val" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label>Total a Cobrar (S/)</label>
                <input type="number" step="0.01" name="total_receivable" id="edit_loan_total" class="form-control" readonly style="background:#f5f5f5;">
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label>Estado</label>
                <select name="status" id="edit_loan_status" class="form-control">
                  <option value="PENDIENTE">PENDIENTE</option>
                  <option value="PARCIAL">PARCIAL</option>
                  <option value="PAGADO">PAGADO</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Notas</label>
            <textarea name="notes" id="edit_loan_notes" class="form-control" rows="2"></textarea>
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

<!-- ===== LOAN PAYMENT MODAL ===== -->
<div class="modal fade" id="loanPaymentModal" tabindex="-1" role="dialog" aria-labelledby="loanPaymentModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background:#27ae60;color:#fff;">
        <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-usd"></i> Registrar Pago de Préstamo</h4>
      </div>
      <form id="loanPaymentForm" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="loan_id" id="pay_loan_id">
        <div class="modal-body">
          <div id="pay_loan_info" class="alert alert-info" style="display:none;padding:8px 12px;font-size:13px;"></div>
          <div class="form-group">
            <label>Cuenta Destino (donde ingresan los fondos) <span class="text-danger">*</span></label>
            <select name="destination_account_id" id="pay_dest_account" class="form-control" required>
              <option value="">-- Seleccione cuenta --</option>
            </select>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label>Monto del Pago (S/) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" id="pay_amount" class="form-control" placeholder="0.00" required>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label>Fecha de Pago <span class="text-danger">*</span></label>
                <input type="date" name="payment_date" id="pay_date" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Comprobante (opcional)</label>
            <input type="file" name="receipt_image" id="pay_receipt" accept="image/*" class="form-control">
          </div>
          <div class="form-group">
            <label>Notas</label>
            <textarea name="notes" id="pay_notes" class="form-control" rows="2" placeholder="Observaciones del pago..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-ok"></i> Registrar Pago</button>
        </div>
      </form>
    </div>
  </div>
</div>
