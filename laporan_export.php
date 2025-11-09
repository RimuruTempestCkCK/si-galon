<?php
// Set cookie session agar kompatibel dengan Cordova WebView
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '.depot-al-azzahra.site', // pastikan sesuai domain
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);
ob_start();
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// ===== Ambil data depot =====
$depot = $conn->query("SELECT * FROM depot LIMIT 1")->fetch_assoc();
$depot_name = $depot['nama_depot'] ?? 'Depot Tidak Diketahui';
$depot_address = $depot['alamat'] ?? '-';

// ===== Autoload Composer =====
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$type = $_GET['type'] ?? 'pdf';

// Ambil data pesanan selesai
$laporan = $conn->query("SELECT * FROM orders WHERE status='selesai' ORDER BY created_at DESC");

// ===== PDF =====
if ($type == 'pdf') {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Sistem Galon');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Laporan Pesanan Selesai');
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 15);

    $pdf->AddPage();

    // Header Laporan dengan data depot
    $html = '<h2 style="text-align:center;">Laporan Pesanan Selesai</h2>';
    $html .= '<h4 style="text-align:center;">'.$depot_name.'</h4>';
    $html .= '<p style="text-align:center;">'.$depot_address.'</p>';
    $html .= '<p>Tanggal Cetak: '.date('d M Y H:i').'</p>';
    $html .= '<hr>';

    // Tabel
    $html .= '<table border="1" cellpadding="5">
                <thead style="font-weight:bold; background-color:#f2f2f2;">
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';

    while($row = $laporan->fetch_assoc()){
        $html .= '<tr>
                    <td>#'.$row['id'].'</td>
                    <td>'.htmlspecialchars($row['recipient_name']).'</td>
                    <td>'.date('d M Y', strtotime($row['created_at'])).'</td>
                    <td>'.$row['quantity'].' galon</td>
                    <td>Rp '.number_format($row['total'],0,',','.').'</td>
                    <td>'.ucwords(str_replace('_',' ',$row['status'])).'</td>
                  </tr>';
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('laporan_pesanan.pdf', 'I');
    exit;
}

// ===== EXCEL =====
if ($type == 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header laporan
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', 'Laporan Pesanan Selesai');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A2:F2');
    $sheet->setCellValue('A2', $depot_name);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A3:F3');
    $sheet->setCellValue('A3', $depot_address);
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A4:F4');
    $sheet->setCellValue('A4', 'Tanggal Cetak: '.date('d M Y H:i'));
    $sheet->getStyle('A4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Tabel header mulai dari baris 6
    $sheet->setCellValue('A6', 'ID Pesanan')
          ->setCellValue('B6', 'Pelanggan')
          ->setCellValue('C6', 'Tanggal')
          ->setCellValue('D6', 'Jumlah')
          ->setCellValue('E6', 'Total')
          ->setCellValue('F6', 'Status');

    $sheet->getStyle('A6:F6')->getFont()->setBold(true);
    $sheet->getStyle('A6:F6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $rowNum = 7;
    $laporan->data_seek(0);
    while($row = $laporan->fetch_assoc()){
        $sheet->setCellValue('A'.$rowNum, '#'.$row['id'])
              ->setCellValue('B'.$rowNum, $row['recipient_name'])
              ->setCellValue('C'.$rowNum, date('d M Y', strtotime($row['created_at'])))
              ->setCellValue('D'.$rowNum, $row['quantity'].' galon')
              ->setCellValue('E'.$rowNum, $row['total'])
              ->setCellValue('F'.$rowNum, ucwords(str_replace('_',' ',$row['status'])));
        $rowNum++;
    }

    // Auto width kolom
    foreach(range('A','F') as $col){
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="laporan_pesanan.xlsx"');
    $writer->save('php://output');
    exit;
}

ob_end_flush();
