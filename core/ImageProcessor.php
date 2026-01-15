<?php
/**
 * ImageProcessor - Classe centralizada para processamento de imagens
 *
 * Responsável por:
 * - Conversão de imagens para WebP
 * - Redimensionamento automático
 * - Sanitização de nomes de arquivo
 * - Validação de tipos e tamanhos
 *
 * Usa GD Library para processamento de imagens
 */

class ImageProcessor {

    /**
     * Tipos MIME permitidos
     */
    const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    /**
     * Extensões permitidas
     */
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Tamanho máximo padrão: 10MB
     */
    const MAX_SIZE_DEFAULT = 10485760; // 10 * 1024 * 1024

    /**
     * Largura máxima padrão: 1920px
     */
    const MAX_WIDTH_DEFAULT = 1920;

    /**
     * Qualidade WebP padrão: 85
     */
    const WEBP_QUALITY_DEFAULT = 85;

    /**
     * Validar arquivo de upload
     *
     * @param array $file Array $_FILES
     * @param int $maxSize Tamanho máximo em bytes
     * @return array ['success' => bool, 'error' => string]
     */
    public static function validateUpload($file, $maxSize = self::MAX_SIZE_DEFAULT) {
        // Verificar se recebeu arquivo
        if (!isset($file) || !is_array($file)) {
            return ['success' => false, 'error' => 'Nenhum arquivo enviado'];
        }

        // Verificar erros no upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Erro no upload do arquivo'];
        }

        // Validar tipo de arquivo via MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            return ['success' => false, 'error' => 'Tipo de arquivo não permitido. Use JPG, PNG ou WebP'];
        }

        // Validar tamanho
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024));
            return ['success' => false, 'error' => "Arquivo muito grande. Máximo {$maxSizeMB}MB"];
        }

        return ['success' => true];
    }

    /**
     * Processar e salvar imagem
     *
     * Converte para WebP, redimensiona e salva no diretório especificado
     *
     * @param array $file Array $_FILES
     * @param string $uploadDir Diretório de destino (com barra final)
     * @param string $baseFilename Nome base do arquivo (sem extensão)
     * @param int $maxWidth Largura máxima
     * @param int $quality Qualidade WebP (0-100)
     * @return array ['success' => bool, 'url' => string, 'filename' => string, 'error' => string]
     */
    public static function processAndSave($file, $uploadDir, $baseFilename, $maxWidth = self::MAX_WIDTH_DEFAULT, $quality = self::WEBP_QUALITY_DEFAULT) {
        // Criar diretório se não existir
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Sanitizar nome do arquivo
        $sanitizedFilename = self::sanitizeFilename($baseFilename);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Nome temporário
        $tempFilename = $sanitizedFilename . '-' . date('YmdHis') . '.' . $extension;
        $tempPath = $uploadDir . $tempFilename;

        // Mover arquivo para diretório temporário
        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            return ['success' => false, 'error' => 'Erro ao salvar arquivo'];
        }

        // Processar imagem: redimensionar e converter para WebP
        try {
            $webpPath = self::convertToWebP($tempPath, $uploadDir, $sanitizedFilename, $maxWidth, $quality);

            if (!$webpPath) {
                throw new Exception('Erro ao processar imagem');
            }

            // Remover arquivo original se não for WebP
            if ($extension !== 'webp' && file_exists($tempPath)) {
                unlink($tempPath);
            }

            return [
                'success' => true,
                'path' => $webpPath,
                'filename' => basename($webpPath)
            ];

        } catch (Exception $e) {
            // Limpar arquivo temporário em caso de erro
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sanitizar nome do arquivo
     * Remove acentos, espaços e caracteres especiais
     *
     * @param string $filename Nome do arquivo
     * @return string Nome sanitizado
     */
    public static function sanitizeFilename($filename) {
        $filename = strtolower($filename);

        $replacements = [
            '/[áàâãäå]/u' => 'a',
            '/[éèêë]/u' => 'e',
            '/[íìîï]/u' => 'i',
            '/[óòôõö]/u' => 'o',
            '/[úùûü]/u' => 'u',
            '/[ç]/u' => 'c',
            '/[ñ]/u' => 'n',
            '/\s+/' => '-',
            '/[^a-z0-9\-_]/' => '',
            '/\-+/' => '-'
        ];

        foreach ($replacements as $pattern => $replacement) {
            $filename = preg_replace($pattern, $replacement, $filename);
        }

        return trim($filename, '-');
    }

    /**
     * Converter imagem para WebP
     *
     * @param string $imagePath Caminho da imagem original
     * @param string $outputDir Diretório de saída
     * @param string $baseFilename Nome base do arquivo
     * @param int $maxWidth Largura máxima
     * @param int $quality Qualidade WebP (0-100)
     * @return string|false Caminho do arquivo WebP ou false em caso de erro
     */
    private static function convertToWebP($imagePath, $outputDir, $baseFilename, $maxWidth = self::MAX_WIDTH_DEFAULT, $quality = self::WEBP_QUALITY_DEFAULT) {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        // Se já for WebP, só redimensiona
        if ($extension === 'webp') {
            self::resizeImage($imagePath, $imagePath, $maxWidth);
            return $imagePath;
        }

        // Carregar imagem conforme o tipo
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'png':
                $image = imagecreatefrompng($imagePath);
                if ($image !== false) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
            default:
                throw new Exception('Tipo de imagem não suportado');
        }

        if (!$image) {
            throw new Exception('Erro ao carregar imagem');
        }

        // Redimensionar se necessário
        $image = self::resizeImageResource($image, $maxWidth);

        // Gerar nome do arquivo WebP
        $webpFilename = $baseFilename . '-' . date('YmdHis') . '.webp';
        $webpPath = $outputDir . $webpFilename;

        // Converter para WebP
        $success = imagewebp($image, $webpPath, $quality);

        imagedestroy($image);

        if (!$success || !file_exists($webpPath) || filesize($webpPath) === 0) {
            throw new Exception('Erro ao converter imagem para WebP');
        }

        return $webpPath;
    }

    /**
     * Redimensionar imagem mantendo proporção
     *
     * @param string $imagePath Caminho da imagem
     * @param string $outputPath Caminho de saída
     * @param int $maxWidth Largura máxima
     * @return bool Sucesso da operação
     */
    private static function resizeImage($imagePath, $outputPath, $maxWidth = self::MAX_WIDTH_DEFAULT) {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        // Carregar imagem
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'webp':
                $image = imagecreatefromwebp($imagePath);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        $resizedImage = self::resizeImageResource($image, $maxWidth);

        // Salvar imagem redimensionada
        $success = false;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $success = imagejpeg($resizedImage, $outputPath, 90);
                break;
            case 'png':
                $success = imagepng($resizedImage, $outputPath, 9);
                break;
            case 'webp':
                $success = imagewebp($resizedImage, $outputPath, self::WEBP_QUALITY_DEFAULT);
                break;
        }

        imagedestroy($image);
        imagedestroy($resizedImage);

        return $success;
    }

    /**
     * Redimensionar resource de imagem
     *
     * @param resource $image Resource GD da imagem
     * @param int $maxWidth Largura máxima
     * @return resource Resource GD redimensionado
     */
    private static function resizeImageResource($image, $maxWidth = self::MAX_WIDTH_DEFAULT) {
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Se a imagem já é menor que o máximo, retorna a original
        if ($originalWidth <= $maxWidth) {
            return $image;
        }

        // Calcular novas dimensões mantendo proporção
        $newWidth = $maxWidth;
        $newHeight = intval(($originalHeight / $originalWidth) * $newWidth);

        // Criar nova imagem redimensionada
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparência para PNG/WebP
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
        imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);

        // Redimensionar
        imagecopyresampled(
            $resizedImage,
            $image,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        return $resizedImage;
    }
}
