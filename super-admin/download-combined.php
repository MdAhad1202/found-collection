<?php
require_once __DIR__ . '/../includes/auth.php';
requireSuperAdmin();
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();

// ==================== SHEET 1: All Students ====================
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('All Students');

// Query all students with payment summary
$allStudents = $conn->query("
    SELECT s.id, s.roll, s.name,
           COUNT(c.id) AS times,
           COALESCE(SUM(c.amount), 0) AS total
    FROM students s
    LEFT JOIN collections c ON c.student_id = s.id
    GROUP BY s.id
    ORDER BY s.roll ASC
");

// Count paid/unpaid
$totalStudents = 0;
$paidCount = 0;
$unpaidCount = 0;
$studentsData = [];

while ($r = $allStudents->fetch_assoc()) {
    $totalStudents++;
    if ($r['total'] > 0) $paidCount++;
    else $unpaidCount++;
    $studentsData[] = $r;
}

// Title
$sheet1->setCellValue('A1', 'Student Collection Report — All Students');
$sheet1->mergeCells('A1:F1');
$sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
$sheet1->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2c3e50');
$sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getRowDimension(1)->setRowHeight(28);

// Summary
$sheet1->setCellValue('A2', "Total Students: $totalStudents  |  Paid: $paidCount  |  Unpaid: $unpaidCount  |  Generated: " . date('d-M-Y h:i A'));
$sheet1->mergeCells('A2:F2');
$sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

// Headers
$headers = ['#', 'Roll', 'Name', 'Total Paid (৳)', 'Times', 'Status'];
$sheet1->fromArray($headers, null, 'A4');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34495e']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$sheet1->getStyle('A4:F4')->applyFromArray($headerStyle);
$sheet1->getRowDimension(4)->setRowHeight(22);

// Data rows
$row = 5;
$i = 1;
foreach ($studentsData as $s) {
    $sheet1->setCellValue('A'.$row, $i++);
    $sheet1->setCellValue('B'.$row, $s['roll']);
    $sheet1->setCellValue('C'.$row, $s['name']);
    $sheet1->setCellValue('D'.$row, $s['total'] > 0 ? $s['total'] : '—');
    $sheet1->setCellValue('E'.$row, $s['times'] > 0 ? $s['times'] : '—');
    $sheet1->setCellValue('F'.$row, $s['total'] > 0 ? 'Paid' : 'Unpaid');
    
    // Status color
    if ($s['total'] > 0) {
        $sheet1->getStyle('F'.$row)->getFont()->getColor()->setRGB('28a745');
        $sheet1->getStyle('F'.$row)->getFont()->setBold(true);
    } else {
        $sheet1->getStyle('F'.$row)->getFont()->getColor()->setRGB('dc3545');
        $sheet1->getStyle('F'.$row)->getFont()->setBold(true);
    }
    
    // Row borders
    $sheet1->getStyle('A'.$row.':F'.$row)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'dee2e6']]],
    ]);
    
    $row++;
}

// Grand total
$sheet1->setCellValue('C'.$row, 'GRAND TOTAL:');
$sheet1->setCellValue('D'.$row, "=SUM(D5:D" . ($row - 1) . ")");
$sheet1->getStyle('C'.$row.':F'.$row)->getFont()->setBold(true);
$sheet1->getStyle('C'.$row.':F'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d4edda');
$sheet1->getStyle('C'.$row.':F'.$row)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// Column widths
$sheet1->getColumnDimension('A')->setWidth(6);
$sheet1->getColumnDimension('B')->setWidth(12);
$sheet1->getColumnDimension('C')->setWidth(30);
$sheet1->getColumnDimension('D')->setWidth(18);
$sheet1->getColumnDimension('E')->setWidth(10);
$sheet1->getColumnDimension('F')->setWidth(12);

// ==================== SHEET 2: Unpaid Only ====================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Unpaid Only');

// Title
$sheet2->setCellValue('A1', 'Unpaid Students List');
$sheet2->mergeCells('A1:D1');
$sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
$sheet2->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('dc3545');
$sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet2->getRowDimension(1)->setRowHeight(28);

// Summary
$sheet2->setCellValue('A2', "Total Unpaid: $unpaidCount  |  Generated: " . date('d-M-Y h:i A'));
$sheet2->mergeCells('A2:D2');
$sheet2->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet2->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

// Headers
$sheet2->fromArray(['#', 'Roll', 'Name', 'Status'], null, 'A4');
$sheet2->getStyle('A4:D4')->applyFromArray($headerStyle);
$sheet2->getRowDimension(4)->setRowHeight(22);

// Data — only unpaid
$row = 5;
$i = 1;
foreach ($studentsData as $s) {
    if ($s['total'] > 0) continue; // skip paid
    
    $sheet2->setCellValue('A'.$row, $i++);
    $sheet2->setCellValue('B'.$row, $s['roll']);
    $sheet2->setCellValue('C'.$row, $s['name']);
    $sheet2->setCellValue('D'.$row, 'Unpaid');
    $sheet2->getStyle('D'.$row)->getFont()->getColor()->setRGB('dc3545');
    $sheet2->getStyle('D'.$row)->getFont()->setBold(true);
    
    $sheet2->getStyle('A'.$row.':D'.$row)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'dee2e6']]],
    ]);
    $row++;
}

if ($i === 1) {
    $sheet2->setCellValue('A5', '🎉 সব student payment করেছে!');
    $sheet2->mergeCells('A5:D5');
    $sheet2->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet2->getStyle('A5')->getFont()->setBold(true)->getColor()->setRGB('28a745');
}

// Column widths
$sheet2->getColumnDimension('A')->setWidth(6);
$sheet2->getColumnDimension('B')->setWidth(12);
$sheet2->getColumnDimension('C')->setWidth(30);
$sheet2->getColumnDimension('D')->setWidth(12);

// Set active sheet
$spreadsheet->setActiveSheetIndex(0);

// Download
$filename = 'Student-Report-' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>