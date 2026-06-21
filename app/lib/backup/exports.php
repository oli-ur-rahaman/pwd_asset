<?php
function export_excel_font_name(): string
{
    return 'Nirmala UI';
}

function export_pdf_font_family(): string
{
    return 'PwdPdfBangla';
}

function export_pdf_font_file_path(): string
{
    return dirname(__DIR__, 2) . '/public/assets/fonts/siyamrupali.ttf';
}

function export_pdf_font_face_css(): string
{
    static $css = null;
    if ($css !== null) {
        return $css;
    }

    $fontPath = export_pdf_font_file_path();
    if (!is_file($fontPath)) {
        $css = '';
        return $css;
    }

    $fontBinary = file_get_contents($fontPath);
    if ($fontBinary === false || $fontBinary === '') {
        $css = '';
        return $css;
    }

    $fontBase64 = base64_encode($fontBinary);
    $css = '@font-face{'
        . 'font-family:"' . export_pdf_font_family() . '";'
        . 'src:url(data:font/ttf;base64,' . $fontBase64 . ') format("truetype");'
        . 'font-weight:normal;'
        . 'font-style:normal;'
        . '}';
    return $css;
}

function export_apply_spreadsheet_font(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): void
{
    $spreadsheet->getDefaultStyle()->getFont()->setName(export_excel_font_name());
    $spreadsheet->getDefaultStyle()->getFont()->setSize(11);
}

function export_inject_pdf_font_css(string $html): string
{
    $font = export_pdf_font_family();
    $fontFace = export_pdf_font_face_css();
    $style = '<meta charset="utf-8"><style>'
        . $fontFace
        . '*{font-family:"' . $font . '", dejavusans, sans-serif !important;}'
        . 'html,body,table,thead,tbody,tr,td,th,div,span,p{font-family:"' . $font . '", dejavusans, sans-serif !important;}'
        . '</style>';
    if (stripos($html, '<head>') !== false) {
        $html = preg_replace('/<head>/i', '<head>' . $style, $html, 1) ?? ($style . $html);
    } else {
        $html = $style . $html;
    }
    if (stripos($html, '<html') !== false && stripos($html, 'lang=') === false) {
        $html = preg_replace('/<html([^>]*)>/i', '<html$1 lang="bn">', $html, 1) ?? $html;
    }
    if (stripos($html, '<body') !== false && stripos($html, 'lang=') === false) {
        $html = preg_replace('/<body([^>]*)>/i', '<body$1 lang="bn">', $html, 1) ?? $html;
    }
    return $html;
}

function export_browser_pdf_binary(): ?string
{
    $candidates = array_filter([
        getenv('CHROME_BIN') ?: null,
        getenv('GOOGLE_CHROME_BIN') ?: null,
        getenv('EDGE_BIN') ?: null,
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/snap/bin/chromium',
        '/usr/bin/microsoft-edge',
    ]);
    foreach ($candidates as $candidate) {
        if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
            return $candidate;
        }
    }
    return null;
}

function export_inject_browser_pdf_page_css(string $html, string $orientation): string
{
    $pageStyle = '<meta charset="utf-8"><style>'
        . export_pdf_font_face_css()
        . '@page{size:A4 ' . ($orientation === 'portrait' ? 'portrait' : 'landscape') . ';margin:12mm 10mm 12mm 10mm;}'
        . 'html,body,table,thead,tbody,tr,td,th,div,span,p{font-family:"' . export_pdf_font_family() . '", sans-serif !important;}'
        . '</style>';
    if (stripos($html, '<head>') !== false) {
        return preg_replace('/<head>/i', '<head>' . $pageStyle, $html, 1) ?? ($pageStyle . $html);
    }
    return $pageStyle . $html;
}

function export_pdf_with_browser(string $html, string $filename, string $orientation = 'landscape', ?string $destinationPath = null): ?string
{
    $engineOverride = strtolower(trim((string)getenv('PWD_PDF_ENGINE')));
    if ($engineOverride === 'mpdf') {
        return null;
    }

    $binary = export_browser_pdf_binary();
    if ($binary === null) {
        return null;
    }

    $runtimeDir = rtrim(sys_get_temp_dir(), '/\\') . '/pwd-asset-browser-pdf';
    if (!is_dir($runtimeDir)) {
        @mkdir($runtimeDir, 0777, true);
    }
    $runtimeDir = realpath($runtimeDir) ?: $runtimeDir;
    $homeDir = $runtimeDir . '/home';
    $xdgRuntimeDir = $runtimeDir . '/xdg-runtime';
    if (!is_dir($homeDir)) {
        @mkdir($homeDir, 0777, true);
    }
    if (!is_dir($xdgRuntimeDir)) {
        @mkdir($xdgRuntimeDir, 0700, true);
    }

    $token = bin2hex(random_bytes(8));
    $htmlPath = $runtimeDir . '/pdf-' . $token . '.html';
    $tempPdfPath = $runtimeDir . '/pdf-' . $token . '.pdf';
    $styledHtml = export_inject_browser_pdf_page_css($html, $orientation);
    file_put_contents($htmlPath, $styledHtml);

    $fileUrl = 'file:///' . str_replace(DIRECTORY_SEPARATOR, '/', ltrim($htmlPath, '\\/'));
    if (preg_match('/^[A-Za-z]:/', $htmlPath) === 1) {
        $fileUrl = 'file:///' . str_replace('\\', '/', $htmlPath);
    }

    $envPrefix = '';
    if (DIRECTORY_SEPARATOR === '/') {
        $envPrefix = 'HOME=' . escapeshellarg($homeDir)
            . ' XDG_RUNTIME_DIR=' . escapeshellarg($xdgRuntimeDir)
            . ' ';
    }

    $command = $envPrefix . '"' . $binary . '"'
        . ' --headless'
        . ' --disable-gpu'
        . ' --no-sandbox'
        . ' --disable-setuid-sandbox'
        . ' --disable-dev-shm-usage'
        . ' --user-data-dir="' . $homeDir . '/chromium-profile"'
        . ' --allow-file-access-from-files'
        . ' --run-all-compositor-stages-before-draw'
        . ' --virtual-time-budget=10000'
        . ' --print-to-pdf-no-header'
        . ' --no-pdf-header-footer'
        . ' --print-to-pdf="' . $tempPdfPath . '"'
        . ' "' . $fileUrl . '"';
    $output = [];
    $exitCode = 1;
    @exec($command . ' 2>&1', $output, $exitCode);
    @unlink($htmlPath);

    if ($exitCode !== 0 || !is_file($tempPdfPath) || filesize($tempPdfPath) === 0) {
        if (is_file($tempPdfPath)) {
            @unlink($tempPdfPath);
        }
        return null;
    }

    if ($destinationPath !== null) {
        @copy($tempPdfPath, $destinationPath);
        @unlink($tempPdfPath);
        return $destinationPath;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string)filesize($tempPdfPath));
    readfile($tempPdfPath);
    @unlink($tempPdfPath);
    exit;
}

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
    export_apply_spreadsheet_font($sheet);
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

function export_pdf(string $html, string $filename, string $orientation = 'landscape', ?string $destinationPath = null): ?string
{
    $browserResult = export_pdf_with_browser($html, $filename, $orientation, $destinationPath);
    if ($browserResult !== null) {
        return $browserResult;
    }

    $allowMpdfFallback = strtolower(trim((string)getenv('PWD_ALLOW_MPDF_FALLBACK'))) === '1';
    if (!$allowMpdfFallback) {
        http_response_code(500);
        $message = 'Browser PDF engine is unavailable. Install Chrome/Chromium/Edge or configure CHROME_BIN.';
        if ($destinationPath !== null) {
            throw new RuntimeException($message);
        }
        echo $message;
        exit;
    }

    ensure_library('Mpdf\\Mpdf', 'mPDF is not installed.');

    $orientation = strtolower(trim($orientation));
    if (!in_array($orientation, ['portrait', 'landscape'], true)) {
        $orientation = 'landscape';
    }

    $rootDir = dirname(__DIR__, 2);
    $fontDir = $rootDir . '/public/assets/fonts';
    $tempDir = $rootDir . '/storage/runtime/mpdf';
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0777, true);
    }

    $config = [
        'mode' => 'utf-8',
        'format' => 'A4-' . strtoupper($orientation === 'portrait' ? 'P' : 'L'),
        'tempDir' => $tempDir,
        'default_font' => 'siyamrupali',
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
        'fontDir' => array_merge((new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [$fontDir]),
        'fontdata' => (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
            'siyamrupali' => [
                'R' => 'siyamrupali.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ],
            'kalpurush' => [
                'R' => 'kalpurush.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ],
            'nikosh' => [
                'R' => 'Nikosh.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ],
            'lohitbengali' => [
                'R' => 'Lohit-Bengali.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ],
        ],
    ];

    $mpdf = new \Mpdf\Mpdf($config);
    $mpdf->SetHTMLFooter('<div style="text-align:right;font-size:9px;color:#444;">Page {PAGENO} of {nbpg}</div>');
    $mpdf->WriteHTML(export_inject_pdf_font_css($html));
    $pdfContent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    if ($destinationPath !== null) {
        file_put_contents($destinationPath, $pdfContent);
        return $destinationPath;
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (string)strlen($pdfContent));
    echo $pdfContent;
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
        body,table,thead,tbody,tr,td,th,div,span,p{font-family:' . export_pdf_font_family() . ', dejavusans, sans-serif;font-size:12px;}
        table{width:100%;border-collapse:collapse;}
        th,td{border:1px solid #444;padding:6px;text-align:center;}
        h2{text-align:center;}
    </style></head><body>'
        . '<h2>' . e($title) . '</h2>'
        . '<table><thead><tr>' . $thead . '</tr></thead><tbody>' . $tbody . '</tbody></table>'
        . '</body></html>';
}
