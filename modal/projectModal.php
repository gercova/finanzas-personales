<!-- ===== ADD PROJECT MODAL ===== -->
<div class="modal fade" id="addProjectModal" tabindex="-1" role="dialog" aria-labelledby="addProjectModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-plus"></i> Nuevo Proyecto</h4>
            </div>
            <form id="addProjectForm">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="success-messages"></div>
                    <div class="row">
                        <div class="col-sm-7">
                            <div class="form-group">
                                <label>Nombre del Proyecto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="project_name" id="project_name" placeholder="Ej: Software ERP IESTP..." required>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="form-group">
                                <label>Cliente</label>
                                <input type="text" class="form-control" name="client_name" id="client_name" placeholder="Nombre del cliente...">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Presupuesto S/. <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="budget" id="budget" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Fecha de Inicio</label>
                                <input type="date" class="form-control" name="start_date" id="start_date">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Fecha de Fin</label>
                                <input type="date" class="form-control" name="end_date" id="end_date">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control" name="status" id="project_status">
                            <option value="ACTIVE">Activo</option>
                            <option value="ON_HOLD">En Espera</option>
                            <option value="COMPLETED">Completado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" name="description" id="description" rows="3" placeholder="Descripción del proyecto..."></textarea>
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

<!-- ===== EDIT PROJECT MODAL ===== -->
<div class="modal fade" id="editProjectModal" tabindex="-1" role="dialog" aria-labelledby="editProjectModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-edit"></i> Editar Proyecto</h4>
            </div>
            <form id="editProjectForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="edit_project_id" id="edit_project_id">
                <div class="modal-body">
                    <div class="edit-success-messages"></div>
                    <div class="row">
                        <div class="col-sm-7">
                            <div class="form-group">
                                <label>Nombre del Proyecto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="edit_project_name" id="edit_project_name" required>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="form-group">
                                <label>Cliente</label>
                                <input type="text" class="form-control" name="edit_client_name" id="edit_client_name">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Presupuesto S/. <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="edit_budget" id="edit_budget" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Fecha de Inicio</label>
                                <input type="date" class="form-control" name="edit_start_date" id="edit_start_date">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Fecha de Fin</label>
                                <input type="date" class="form-control" name="edit_end_date" id="edit_end_date">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control" name="edit_status" id="edit_project_status">
                            <option value="ACTIVE">Activo</option>
                            <option value="ON_HOLD">En Espera</option>
                            <option value="COMPLETED">Completado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" name="edit_description" id="edit_description" rows="3"></textarea>
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
