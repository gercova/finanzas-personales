<?php
/**
 * downloadTemplate.php — Generate and serve an .xlsx template file
 *
 * GET params:
 *   module : income | expense | loan | project | asset
 *   csrf_token (optional, but checked for authenticated sessions)
 */
require_once 'core.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    exit('Sesión no iniciada.');
}

$module  = strtolower(trim($_GET['module'] ?? ''));
$allowed = ['income', 'expense', 'loan', 'project', 'asset'];
if (!in_array($module, $allowed, true)) {
    http_response_code(400);
    exit('Módulo no reconocido.');
}

/* ── Template definitions ─────────────────────────────────────────
   Each entry: [ headerLabel, exampleValue, notes ]
   ─────────────────────────────────────────────────────────────────*/
$templates = [
    'income' => [
        'title'    => 'Plantilla de Ingresos',
        'filename' => 'plantilla_ingresos.xlsx',
        'color'    => '27AE60',
        'columns'  => [
            ['Concepto *',          'Mensualidad DRE - Octubre 2026',   'Texto descriptivo del ingreso. Requerido.'],
            ['Monto *',             '1500.00',                           'Número mayor a 0. Use punto como decimal.'],
            ['Cuenta Destino *',    'Cuenta Principal',                  'Nombre exacto de la cuenta (tal como la registró). Requerido.'],
            ['Categoría',           'Salario',                           'Nombre de la categoría de tipo INGRESO. Opcional.'],
            ['Proyecto',            'Proyecto Alpha',                    'Nombre exacto del proyecto. Opcional.'],
            ['Fecha *',             date('Y-m-d'),                       'Formato: YYYY-MM-DD o DD/MM/YYYY. Requerido.'],
            ['Estado',              'ACTIVO',                            'Valores: ACTIVO, PENDIENTE, CANCELADO. Por defecto: ACTIVO.'],
            ['¿Recurrente?',        'No',                                'Valores: Si/No, 1/0, Yes/No. Por defecto: No.'],
            ['Notas',               'Pago recibido vía transferencia',   'Observaciones adicionales. Opcional.'],
        ],
    ],
    'expense' => [
        'title'    => 'Plantilla de Gastos',
        'filename' => 'plantilla_gastos.xlsx',
        'color'    => 'C0392B',
        'columns'  => [
            ['Concepto *',          'Alquiler oficina - Julio 2026',      'Texto descriptivo del gasto. Requerido.'],
            ['Monto *',             '850.00',                              'Número mayor a 0. Use punto como decimal.'],
            ['Cuenta Origen *',     'Cuenta Principal',                    'Nombre exacto de la cuenta. Requerido.'],
            ['Categoría',           'Alquiler',                            'Nombre de la categoría de tipo GASTO. Opcional.'],
            ['Proyecto',            'Proyecto Alpha',                      'Nombre exacto del proyecto. Opcional.'],
            ['Fecha *',             date('Y-m-d'),                         'Formato: YYYY-MM-DD o DD/MM/YYYY. Requerido.'],
            ['Estado',              'PAGADO',                              'Valores: PAGADO, PENDIENTE. Por defecto: PAGADO.'],
            ['Notas',               'Factura N° 001-0045',                 'Observaciones adicionales. Opcional.'],
        ],
    ],
    'loan' => [
        'title'    => 'Plantilla de Préstamos',
        'filename' => 'plantilla_prestamos.xlsx',
        'color'    => 'E74C3C',
        'columns'  => [
            ['Prestatario *',       'Juan Pérez García',                   'Nombre de la persona que recibe el préstamo. Requerido.'],
            ['Concepto *',          'Préstamo personal',                   'Descripción del préstamo. Requerido.'],
            ['Cuenta Origen *',     'Cuenta Principal',                    'Cuenta de donde salen los fondos. Requerido.'],
            ['Fecha Préstamo *',    date('Y-m-d'),                         'Formato: YYYY-MM-DD o DD/MM/YYYY. Requerido.'],
            ['Fecha Vencimiento',   date('Y-m-d', strtotime('+6 months')), 'Formato: YYYY-MM-DD o DD/MM/YYYY. Opcional.'],
            ['Capital *',           '2000.00',                             'Monto principal prestado. Número mayor a 0.'],
            ['Tipo de Interés',     'NONE',                                'Valores: NONE, PERCENTAGE, FIXED (o NINGUNO, PORCENTAJE, FIJO). Por defecto: NONE.'],
            ['Valor Interés',       '',                                     'Si tipo es PERCENTAGE: porcentaje (ej. 5 para 5%). Si FIXED: monto fijo. Dejar vacío si NONE.'],
            ['Notas',               'Acuerdo verbal del 15/06/2026',       'Observaciones adicionales. Opcional.'],
        ],
    ],
    'project' => [
        'title'    => 'Plantilla de Proyectos',
        'filename' => 'plantilla_proyectos.xlsx',
        'color'    => '2980B9',
        'columns'  => [
            ['Nombre Proyecto *',   'Software ERP IESTP',                  'Nombre del proyecto. Requerido.'],
            ['Cliente',             'IESTP Cajamarca',                     'Nombre del cliente. Opcional.'],
            ['Presupuesto *',       '15000.00',                            'Número mayor a 0. Requerido.'],
            ['Fecha Inicio',        date('Y-m-d'),                         'Formato: YYYY-MM-DD o DD/MM/YYYY. Opcional.'],
            ['Fecha Fin',           date('Y-m-d', strtotime('+3 months')), 'Formato: YYYY-MM-DD o DD/MM/YYYY. Opcional.'],
            ['Estado',              'ACTIVE',                              'Valores: ACTIVE, ON_HOLD, COMPLETED (o ACTIVO, EN_ESPERA, COMPLETADO). Por defecto: ACTIVE.'],
            ['Descripción',         'Sistema de gestión académica',        'Descripción del proyecto. Opcional.'],
        ],
    ],
    'asset' => [
        'title'    => 'Plantilla de Activos Valiosos',
        'filename' => 'plantilla_activos.xlsx',
        'color'    => '8E44AD',
        'columns'  => [
            ['Nombre Activo *',     'Toyota Yaris 2022',                   'Nombre del activo. Requerido.'],
            ['Categoría *',         'VEHICULO',                            'Valores: VEHICULO, INMUEBLE, DOCUMENTO_FIDUCIARIO, EQUIPO_TECNOLOGICO, OTRO. Requerido.'],
            ['Valor Estimado *',    '45000.00',                            'Valor estimado actual. Número mayor a 0. Requerido.'],
            ['Precio de Compra',    '50000.00',                            'Precio original de compra. Opcional.'],
            ['Fecha Adquisición',   date('Y-m-d', strtotime('-1 year')),   'Formato: YYYY-MM-DD o DD/MM/YYYY. Opcional.'],
            ['N° Documento',        'PLACA-ABC-123',                       'Número de placa, escritura, serie, etc. Opcional.'],
            ['Ubicación/Custodia',  'Garaje casa principal',               'Lugar físico o responsable. Opcional.'],
            ['Estado',              'ACTIVO',                              'Valores: ACTIVO, EN_GARANTIA, VENDIDO, DADO_DE_BAJA. Por defecto: ACTIVO.'],
            ['Notas',               'Revisión técnica hasta dic 2026',     'Observaciones adicionales. Opcional.'],
        ],
    ],
];

$tpl = $templates[$module];

/* ── Build spreadsheet ───────────────────────────────────────────── */
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('myFin')
    ->setTitle($tpl['title'])
    ->setDescription('Plantilla de importación masiva para ' . $tpl['title']);

$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Datos');

$headerColor = $tpl['color'];
$cols        = $tpl['columns'];
$colCount    = count($cols);
$lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

/* ── Title row ───────────────────────────────────────────────────── */
$sheet->mergeCells("A1:{$lastColLetter}1");
$sheet->setCellValue('A1', $tpl['title'] . ' — Importación Masiva');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerColor]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(28);

/* ── Notes row ───────────────────────────────────────────────────── */
$sheet->mergeCells("A2:{$lastColLetter}2");
$sheet->setCellValue('A2', 'INSTRUCCIONES: Complete las filas desde la fila 4. Elimine las filas de ejemplo antes de importar si no las necesita. Campos marcados con * son requeridos. Máximo 500 filas.');
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
]);
$sheet->getRowDimension(2)->setRowHeight(32);

/* ── Header row ──────────────────────────────────────────────────── */
foreach ($cols as $ci => $col) {
    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
    $sheet->setCellValue("{$letter}3", $col[0]);
    $sheet->getStyle("{$letter}3")->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerColor]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ]);
    $sheet->getColumnDimensionByColumn($ci + 1)->setWidth(22);
}
$sheet->getRowDimension(3)->setRowHeight(30);

/* ── Example data row ────────────────────────────────────────────── */
foreach ($cols as $ci => $col) {
    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
    $sheet->setCellValue("{$letter}4", $col[1]);
    $sheet->getStyle("{$letter}4")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FFF0']],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        'font'      => ['italic' => true, 'color' => ['rgb' => '555555']],
    ]);
}

/* ── Notes sheet ─────────────────────────────────────────────────── */
$notesSheet = $spreadsheet->createSheet();
$notesSheet->setTitle('Instrucciones y Valores');

$notesSheet->mergeCells('A1:C1');
$notesSheet->setCellValue('A1', 'Descripción de Columnas — ' . $tpl['title']);
$notesSheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerColor]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$notesSheet->getRowDimension(1)->setRowHeight(24);

$notesSheet->setCellValue('A2', 'Columna');
$notesSheet->setCellValue('B2', 'Ejemplo');
$notesSheet->setCellValue('C2', 'Notas / Valores permitidos');
$notesSheet->getStyle('A2:C2')->applyFromArray([
    'font'      => ['bold' => true],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEEE']],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);
$notesSheet->getColumnDimension('A')->setWidth(28);
$notesSheet->getColumnDimension('B')->setWidth(32);
$notesSheet->getColumnDimension('C')->setWidth(60);

foreach ($cols as $i => $col) {
    $r = $i + 3;
    $notesSheet->setCellValue("A{$r}", $col[0]);
    $notesSheet->setCellValue("B{$r}", $col[1]);
    $notesSheet->setCellValue("C{$r}", $col[2]);
    $fill = ($i % 2 === 0) ? 'FFFFFF' : 'F9F9F9';
    $notesSheet->getStyle("A{$r}:C{$r}")->applyFromArray([
        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
    ]);
    $notesSheet->getRowDimension($r)->setRowHeight(20);
}

$spreadsheet->setActiveSheetIndex(0);

/* ── Output ──────────────────────────────────────────────────────── */
$filename = $tpl['filename'];
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
