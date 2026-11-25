<!-- ===== ADD ASSET MODAL ===== -->
<div class="modal fade" id="addAssetModal" tabindex="-1" role="dialog" aria-labelledby="addAssetModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title" id="addAssetModalLabel"><i class="glyphicon glyphicon-plus"></i> Registrar Activo Valioso</h4>
            </div>
            <form id="addAssetForm" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="success-messages"></div>
                    <div class="row">
                        <div class="col-sm-8">
                            <div class="form-group">
                                <label>Nombre del Activo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="asset_name" id="asset_name" placeholder="Ej: Toyota Yaris 2022, Laptop ASUS ROG..." required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Categoría <span class="text-danger">*</span></label>
                                <select class="form-control" name="category" id="category" required>
                                    <option value="">-- Seleccionar --</option>
                                    <option value="VEHICULO"> Vehículo</option>
                                    <option value="INMUEBLE"> Inmueble</option>
                                    <option value="DOCUMENTO_FIDUCIARIO"> Documento Fiduciario</option>
                                    <option value="EQUIPO_TECNOLOGICO"> Equipo Tecnológico</option>
                                    <option value="OTRO"> Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Valor Estimado Actual S/. <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="estimated_value" id="estimated_value" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Precio de Compra S/.</label>
                                <input type="number" class="form-control" name="purchase_price" id="purchase_price" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Fecha de Adquisición</label>
                                <input type="date" class="form-control" name="purchase_date" id="purchase_date">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>N° Documento / Título</label>
                                <input type="text" class="form-control" name="document_number" id="document_number" placeholder="Placa, Partida Registral, Serie...">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Ubicación / Custodia</label>
                                <input type="text" class="form-control" name="location_or_custody" id="location_or_custody" placeholder="Dirección, banco custodiante...">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Estado</label>
                                <select class="form-control" name="status" id="asset_status">
                                    <option value="ACTIVO">Activo</option>
                                    <option value="EN_GARANTIA">En Garantía</option>
                                    <option value="VENDIDO">Vendido</option>
                                    <option value="DADO_DE_BAJA">Dado de Baja</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-8">
                            <div class="form-group">
                                <label>Fotografía del Activo</label>
                                <input type="file" class="form-control" name="asset_image" id="asset_image" accept=".jpg,.jpeg,.png,.gif">
                                <img id="asset_image_preview" src="#" alt="Vista previa" style="display:none; max-height:80px; margin-top:6px; border-radius:4px;">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" name="notes" id="notes" rows="2" placeholder="Observaciones adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar Activo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== EDIT ASSET MODAL ===== -->
<div class="modal fade" id="editAssetModal" tabindex="-1" role="dialog" aria-labelledby="editAssetModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title" id="editAssetModalLabel"><i class="glyphicon glyphicon-edit"></i> Editar Activo</h4>
            </div>
            <form id="editAssetForm" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="edit_asset_id" id="edit_asset_id">
                <div class="modal-body">
                    <div class="edit-success-messages"></div>
                    <div class="row">
                        <div class="col-sm-8">
                            <div class="form-group">
                                <label>Nombre del Activo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="edit_asset_name" id="edit_asset_name" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Categoría <span class="text-danger">*</span></label>
                                <select class="form-control" name="edit_category" id="edit_category" required>
                                    <option value="VEHICULO">🚗 Vehículo</option>
                                    <option value="INMUEBLE">🏠 Inmueble</option>
                                    <option value="DOCUMENTO_FIDUCIARIO">📄 Documento Fiduciario</option>
                                    <option value="EQUIPO_TECNOLOGICO">💻 Equipo Tecnológico</option>
                                    <option value="OTRO">📦 Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Valor Estimado S/. <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="edit_estimated_value" id="edit_estimated_value" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Precio de Compra S/.</label>
                                <input type="number" class="form-control" name="edit_purchase_price" id="edit_purchase_price" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Fecha de Adquisición</label>
                                <input type="date" class="form-control" name="edit_purchase_date" id="edit_purchase_date">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>N° Documento / Título</label>
                                <input type="text" class="form-control" name="edit_document_number" id="edit_document_number">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Ubicación / Custodia</label>
                                <input type="text" class="form-control" name="edit_location_or_custody" id="edit_location_or_custody">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Estado</label>
                                <select class="form-control" name="edit_status" id="edit_asset_status">
                                    <option value="ACTIVO">Activo</option>
                                    <option value="EN_GARANTIA">En Garantía</option>
                                    <option value="VENDIDO">Vendido</option>
                                    <option value="DADO_DE_BAJA">Dado de Baja</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-8">
                            <div class="form-group">
                                <label>Nueva Fotografía (opcional)</label>
                                <input type="file" class="form-control" name="edit_asset_image" id="edit_asset_image" accept=".jpg,.jpeg,.png,.gif">
                                <img id="edit_asset_image_preview" src="#" alt="Vista previa" style="display:none; max-height:80px; margin-top:6px; border-radius:4px;">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" name="edit_notes" id="edit_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Actualizar Activo</button>
                </div>
            </form>
        </div>
    </div>
</div>
