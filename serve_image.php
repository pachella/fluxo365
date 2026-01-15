<?php
// serve_image.php - Colocar na raiz do projeto

$filename = $_GET['file'] ?? '';

// Validar nome do arquivo
if (empty($filename) || strpos($filename, '..') !== false) {
    http_response_code(404);
    exit;
}

$filepath = __DIR__ . '/uploads/' . $filename;

// Verificar se arquivo existe
if (!file_exists($filepath)) {
    http_response_code(404);
    exit;
}

// Obter tipo MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filepath);
finfo_close($finfo);

// Headers para servir a imagem
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, max-age=31536000'); // Cache por 1 ano

// Servir o arquivo
readfile($filepath);
?>