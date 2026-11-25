$(document).ready(function () {

    // ── DataTable ────────────────────────────────────────────────────────────
    var projectsTable = $('#projectsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: { url: 'php_action/fetchProjects.php', type: 'GET' },
        columns: [
            { data: 0,  width: '4%' },
            { data: 1 },
            { data: 2 },
            { data: 3,  className: 'text-right' },
            { data: 4 },
            { data: 5 },
            { data: 6,  orderable: false },
            { data: 7,  className: 'text-right' },
            { data: 8,  className: 'text-right' },
            { data: 9,  orderable: false },
            { data: 10, orderable: false }
        ],
        responsive: true,
        language: {
            url: 'assests/plugins/datatables/Spanish.json',
            emptyTable: 'No hay proyectos registrados.'
        }
    });
    // Expose reload callback for bulk import
    projectTableReload = function () { projectsTable.ajax.reload(); };

    // ── Add Project ───────────────────────────────────────────────────────────
    $('#addProjectForm').on('submit', function (e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&csrf_token=' + encodeURIComponent(systemCsrfToken);

        $.post('php_action/createProject.php', formData, function (res) {
            if (res.success) {
                $('#addProjectForm')[0].reset();
                $('#addProjectModal .success-messages').html(
                    '<div class="alert alert-success">' + res.messages + '</div>'
                );
                projectsTable.ajax.reload();
                setTimeout(function () { $('#addProjectModal').modal('hide'); }, 1200);
            } else {
                $('#addProjectModal .success-messages').html(
                    '<div class="alert alert-danger">' + res.messages + '</div>'
                );
            }
        });
    });

    // ── Edit Project: load ────────────────────────────────────────────────────
    window.editProject = function (id) {
        $.get('php_action/getProjectData.php', { id: id }, function (data) {
            if (!data) return;
            $('#edit_project_id').val(data.project_id);
            $('#edit_project_name').val(data.project_name);
            $('#edit_client_name').val(data.client_name || '');
            $('#edit_budget').val(data.budget);
            $('#edit_start_date').val(data.start_date || '');
            $('#edit_end_date').val(data.end_date || '');
            $('#edit_project_status').val(data.status);
            $('#edit_description').val(data.description || '');
            $('#editProjectModal').modal('show');
        });
    };

    // ── Edit Project: submit ──────────────────────────────────────────────────
    $('#editProjectForm').on('submit', function (e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&csrf_token=' + encodeURIComponent(systemCsrfToken);

        $.post('php_action/editProject.php', formData, function (res) {
            if (res.success) {
                $('#editProjectModal .edit-success-messages').html(
                    '<div class="alert alert-success">' + res.messages + '</div>'
                );
                projectsTable.ajax.reload();
                setTimeout(function () { $('#editProjectModal').modal('hide'); }, 1200);
            } else {
                $('#editProjectModal .edit-success-messages').html(
                    '<div class="alert alert-danger">' + res.messages + '</div>'
                );
            }
        });
    });

    // ── Remove Project ────────────────────────────────────────────────────────
    window.removeProject = function (id) {
        if (!confirm('¿Eliminar este proyecto y desvincular sus registros?')) return;
        $.post('php_action/removeProject.php', { project_id: id, csrf_token: systemCsrfToken }, function (res) {
            if (res.success) {
                projectsTable.ajax.reload();
            } else {
                alert(res.messages);
            }
        });
    };

    // ── Reset modals on close ─────────────────────────────────────────────────
    $('#addProjectModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $(this).find('.success-messages').html('');
    });
    $('#editProjectModal').on('hidden.bs.modal', function () {
        $(this).find('.edit-success-messages').html('');
    });
});
