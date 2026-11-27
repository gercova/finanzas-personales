import Swal from 'sweetalert2'

/**
 * Displays a consistent SweetAlert2 confirmation modal before deleting a record.
 * Returns true if the user confirms deletion, false otherwise.
 */
export async function confirmDelete(text, title = '¿Confirmar eliminación?') {
  const result = await Swal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#c8372d',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    focusCancel: true,
    buttonsStyling: true,
  })
  return Boolean(result.isConfirmed)
}
