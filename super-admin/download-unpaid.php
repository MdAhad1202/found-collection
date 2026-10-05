<?php
require_once __DIR__ . '/../includes/auth.php';
requireSuperAdmin();
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$res = $conn->query("
    SELECT * FROM students 
    WHERE id NOT IN (SELECT DISTINCT student_id FROM collections)
    ORDER BY roll ASC
");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'Unpaid Students List');
$sheet->mergeCells('A1:C1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Generated: ' . date('d-M-Y h:i A'));
$sheet->mergeCells('A2:C2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->fromArray(['#', 'Roll', 'Name'], null, 'A4');
$sheet->getStyle('A4:C4')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dc3545']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$row = 5;
$i = 1;
while ($r = $res->fetch_assoc()) {
   $sheet->fromArray([
    $i++, $r['roll'], $r['name']
], null, 'A'.$row);
    $row++;
}

$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(30);


$filename = 'Unpaid-Students-' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
(new Xlsx($spreadsheet))->save('php://output');
exit;
?>