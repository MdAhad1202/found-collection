<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

$aid = $_SESSION['admin_id'];

// Get admin name
$adminName = $conn->query("SELECT name FROM admins WHERE id=$aid")->fetch_assoc()['name'];

// Get all students with my collection status
$res = $conn->query("
    SELECT s.roll, s.name,
           COALESCE(SUM(CASE WHEN c.admin_id = $aid THEN c.amount END), 0) AS my_amount,
           COUNT(CASE WHEN c.admin_id = $aid THEN 1 END) AS my_times,
           COUNT(c.id) AS total_entries
    FROM students s
    LEFT JOIN collections c ON c.student_id = s.id
    GROUP BY s.id
    ORDER BY s.roll ASC
");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Title
$sheet->setCellValue('A1', 'My Collection Report - ' . $adminName);
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Generated: ' . date('d-M-Y h:i A'));
$sheet->mergeCells('A2:E2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2')->getFont()->setItalic(true);

// Headers
$headers = ['#', 'Roll', 'Name', 'My Amount (৳)', 'Status'];
$sheet->fromArray($headers, null, 'A4');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2c3e50']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];
$sheet->getStyle('A4:E4')->applyFromArray($headerStyle);

// Data
$row = 5;
$i = 1;
$grandTotal = 0;
while ($r = $res->fetch_assoc()) {
    $status = 'Unpaid';
    if ($r['my_times'] > 0) {
        $status = 'Paid (You)';
        $grandTotal += $r['my_amount'];
    } elseif ($r['total_entries'] > 0) {
        $status = 'Paid by Other';
    }
    
    $sheet->setCellValue('A'.$row, $i++);
    $sheet->setCellValue('B'.$row, $r['roll']);
    $sheet->setCellValue('C'.$row, $r['name']);
    $sheet->setCellValue('D'.$row, $r['my_amount'] > 0 ? $r['my_amount'] : '—');
    $sheet->setCellValue('E'.$row, $status);
    
    if ($status === 'Paid (You)') {
        $sheet->getStyle('E'.$row)->getFont()->getColor()->setRGB('28a745');
        $sheet->getStyle('E'.$row)->getFont()->setBold(true);
    } elseif ($status === 'Unpaid') {
        $sheet->getStyle('E'.$row)->getFont()->getColor()->setRGB('dc3545');
    } else {
        $sheet->getStyle('E'.$row)->getFont()->getColor()->setRGB('ffc107');
    }
    $row++;
}

// Total
$sheet->setCellValue('C'.$row, 'TOTAL:');
$sheet->setCellValue('D'.$row, $grandTotal);
$sheet->getStyle('C'.$row.':D'.$row)->getFont()->setBold(true);
$sheet->getStyle('C'.$row.':D'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d4edda');

// Column widths
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(10);
$sheet->getColumnDimension('C')->setWidth(30);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(20);

// Download
$filename = 'My-Collection-' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>