<?php
function ensure_library(string $class, string $message): void
{
    if (!class_exists($class)) {
        http_response_code(500);
        echo $message;
        exit;
    }
}

function export_excel(array $rows, array $headers, string $filename, ?string $sheet_title = null): void
{
    ensure_library('PhpOffice\\PhpSpreadsheet\\Spreadsheet', 'PhpSpreadsheet is not installed.');

    $sheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $active = $sheet->getActiveSheet();
    if ($sheet_title) {
        $safe = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $sheet_title);
        $safe = substr($safe, 0, 31);
        $active->setTitle($safe === '' ? 'Sheet1' : $safe);
    }

    $col = 1;
    foreach ($headers as $header) {
        $active->setCellValue([$col, 1], $header);
        $col++;
    }

    $rowNum = 2;
    foreach ($rows as $row) {
        $col = 1;
        foreach ($headers as $key => $header) {
            $value = $row[$key] ?? '';
            $active->setCellValue([$col, $rowNum], $value);
            $col++;
        }
        $rowNum++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet);
    $writer->save('php://output');
    exit;
}

function export_pdf(string $html, string $filename): void
{
    ensure_library('Dompdf\\Dompdf', 'dompdf is not installed.');

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

function render_table_html(string $title, array $headers, array $rows): string
{
    $thead = '';
    foreach ($headers as $header) {
        $thead .= '<th>' . e($header) . '</th>';
    }

    $tbody = '';
    foreach ($rows as $row) {
        $tbody .= '<tr>';
        foreach (array_keys($headers) as $key) {
            $tbody .= '<td>' . e((string)($row[$key] ?? '')) . '</td>';
        }
        $tbody .= '</tr>';
    }

    return '<html><head><style>
        body{font-family:Arial, sans-serif;font-size:12px;}
        table{width:100%;border-collapse:collapse;}
        th,td{border:1px solid #444;padding:6px;text-align:center;}
        h2{text-align:center;}
    </style></head><body>'
        . '<h2>' . e($title) . '</h2>'
        . '<table><thead><tr>' . $thead . '</tr></thead><tbody>' . $tbody . '</tbody></table>'
        . '</body></html>';
}
