<?php
// Limpa qualquer output anterior
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once(__DIR__ . "/../../../core/PlanService.php");
require_once(__DIR__ . "/../../../core/ImageProcessor.php");
require_once(__DIR__ . "/../../../core/phpmailer/src/Exception.php");
require_once(__DIR__ . "/../../../core/phpmailer/src/PHPMailer.php");
require_once(__DIR__ . "/../../../core/phpmailer/src/SMTP.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método não permitido";
    exit();
}

try {
    $form_id = trim($_POST['form_id'] ?? '');
    
    if (empty($form_id)) {
        http_response_code(400);
        echo "ID do formulário é obrigatório";
        exit();
    }
    
    // Verificar se o formulário existe e está ativo
    $formStmt = $pdo->prepare("SELECT * FROM forms WHERE id = :id AND status = 'ativo'");
    $formStmt->execute([':id' => $form_id]);
    $form = $formStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        http_response_code(404);
        echo "Formulário não encontrado ou inativo";
        exit();
    }

    // Verificar limite de respostas do dono do formulário
    $formOwnerId = $form['user_id'];

    // Precisamos temporariamente definir o user_id do dono para verificar o limite
    $currentUserId = $_SESSION['user_id'] ?? null;
    $_SESSION['user_id'] = $formOwnerId;

    // Contar respostas atuais do dono do formulário
    $responseCount = PlanService::getCount('responses', $formOwnerId);
    $canReceiveResponse = !PlanService::hasReachedLimit('responses', $responseCount);

    // Restaurar user_id original (se existia)
    if ($currentUserId !== null) {
        $_SESSION['user_id'] = $currentUserId;
    } else {
        unset($_SESSION['user_id']);
    }

    if (!$canReceiveResponse) {
        http_response_code(403);
        echo "Este formulário atingiu o limite máximo de respostas do plano atual.";
        exit();
    }

    // Buscar campos do formulário
    $fieldsStmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = :form_id ORDER BY order_index ASC");
    $fieldsStmt->execute([':form_id' => $form_id]);
    $fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($fields)) {
        http_response_code(400);
        echo "Formulário sem campos";
        exit();
    }
    
    // Validar campos obrigatórios e processar uploads
    $answers = [];
    $uploadedFiles = [];

    foreach ($fields as $field) {
        $fieldName = 'field_' . $field['id'];

        // Verificar se é campo de arquivo
        if ($field['type'] === 'file' && isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES[$fieldName];

            // Validar upload obrigatório
            if ($field['required'] && $file['error'] === UPLOAD_ERR_NO_FILE) {
                http_response_code(400);
                echo "O campo '{$field['label']}' é obrigatório";
                exit();
            }

            // Se tem arquivo, processar
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Criar diretório de upload
                $uploadDir = __DIR__ . '/../../../uploads/responses/' . $form_id . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Verificar se é imagem
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $isImage = in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);

                if ($isImage) {
                    // Se é imagem, usar ImageProcessor (converte para WebP)
                    $validation = ImageProcessor::validateUpload($file);
                    if (!$validation['success']) {
                        http_response_code(400);
                        echo "Erro no campo '{$field['label']}': " . $validation['error'];
                        exit();
                    }

                    $baseFilename = 'field-' . $field['id'] . '-' . pathinfo($file['name'], PATHINFO_FILENAME);
                    $result = ImageProcessor::processAndSave($file, $uploadDir, $baseFilename);

                    if (!$result['success']) {
                        http_response_code(500);
                        echo "Erro ao processar imagem do campo '{$field['label']}': " . $result['error'];
                        exit();
                    }

                    $uploadedFiles[$field['id']] = '/uploads/responses/' . $form_id . '/' . $result['filename'];
                } else {
                    // Se não é imagem, apenas mover arquivo
                    $sanitizedName = ImageProcessor::sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME));
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = $sanitizedName . '-' . date('YmdHis') . '.' . $extension;
                    $filepath = $uploadDir . $filename;

                    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                        http_response_code(500);
                        echo "Erro ao salvar arquivo do campo '{$field['label']}'";
                        exit();
                    }

                    $uploadedFiles[$field['id']] = '/uploads/responses/' . $form_id . '/' . $filename;
                }

                // Salvar caminho do arquivo como resposta
                $answers[$field['id']] = $uploadedFiles[$field['id']];
                continue;
            }
        }

        // Verificar se é array (múltiplas respostas ou campos compostos como RG)
        $isArray = isset($_POST[$fieldName]) && is_array($_POST[$fieldName]);

        if ($isArray) {
            // Verificar se é campo RG com subcampos (tem chave 'rg_number')
            if ($field['type'] === 'rg' && isset($_POST[$fieldName]['rg_number'])) {
                // Salvar como JSON completo para preservar todos os dados
                $answer = json_encode($_POST[$fieldName], JSON_UNESCAPED_UNICODE);
            } else {
                // Múltiplas respostas (checkbox) - juntar com vírgula
                $answer = array_filter(array_map('trim', $_POST[$fieldName]));
                $answer = !empty($answer) ? implode(', ', $answer) : '';
            }
        } else {
            // Resposta única
            $answer = trim($_POST[$fieldName] ?? '');
        }

        // Validar campo obrigatório (apenas se não for file)
        if ($field['type'] !== 'file' && $field['required'] && empty($answer)) {
            http_response_code(400);
            echo "O campo '{$field['label']}' é obrigatório";
            exit();
        }

        // Validar e-mail
        if ($field['type'] === 'email' && !empty($answer) && !filter_var($answer, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo "E-mail inválido no campo '{$field['label']}'";
            exit();
        }

        $answers[$field['id']] = $answer;
    }
    
    $pdo->beginTransaction();

    // Criar registro de resposta (score será atualizado depois)
    $responseStmt = $pdo->prepare("
        INSERT INTO form_responses (form_id, respondent_name, respondent_email, user_data, score, created_at)
        VALUES (:form_id, NULL, NULL, NULL, 0, NOW())
    ");
    $responseStmt->execute([':form_id' => $form_id]);
    $responseId = $pdo->lastInsertId();

    // Marcar resposta parcial como completa (se existir) - com tratamento de erro
    if (isset($_SESSION['partial_response_session_id'])) {
        try {
            $markCompleteStmt = $pdo->prepare("
                UPDATE partial_responses
                SET completed = 1
                WHERE form_id = :form_id AND session_id = :session_id
            ");
            $markCompleteStmt->execute([
                'form_id' => $form_id,
                'session_id' => $_SESSION['partial_response_session_id']
            ]);
        } catch (PDOException $e) {
            // Tabela não existe ainda - ignorar silenciosamente
            error_log('Tabela partial_responses não encontrada: ' . $e->getMessage());
        }
    }

    // Salvar cada resposta individual
    $answerStmt = $pdo->prepare("
        INSERT INTO response_answers (response_id, field_id, answer, score)
        VALUES (:response_id, :field_id, :answer, :score)
    ");

    $totalScore = 0; // Inicializar contador de pontuação total

    foreach ($answers as $fieldId => $answer) {
        if (!empty($answer)) { // Só salva se tiver resposta
            // Buscar o campo para verificar se tem pontuação
            $score = null;
            $field = array_filter($fields, function($f) use ($fieldId) {
                return $f['id'] == $fieldId;
            });
            $field = reset($field);

            // Verificar se é campo radio ou image_choice com scoring ativado
            if ($field && in_array($field['type'], ['radio', 'image_choice'])) {
                $config = json_decode($field['config'] ?? '{}', true);
                if (!empty($config['scoring_enabled'])) {
                    // Decodificar opções
                    $options = json_decode($field['options'] ?? '[]', true);

                    // Separar múltiplas respostas (caso seja checkbox)
                    $selectedAnswers = array_map('trim', explode(',', $answer));

                    // Procurar cada opção selecionada e somar os scores
                    foreach ($selectedAnswers as $selectedAnswer) {
                        foreach ($options as $option) {
                            if (is_array($option) && isset($option['label']) && $option['label'] === $selectedAnswer) {
                                $optionScore = isset($option['score']) ? (int)$option['score'] : 0;
                                $score = ($score ?? 0) + $optionScore;
                                $totalScore += $optionScore;
                                break;
                            }
                        }
                    }
                }
            }

            $answerStmt->execute([
                ':response_id' => $responseId,
                ':field_id' => $fieldId,
                ':answer' => $answer,
                ':score' => $score
            ]);
        }
    }

    // Atualizar score total na resposta
    $updateScoreStmt = $pdo->prepare("UPDATE form_responses SET score = :score WHERE id = :id");
    $updateScoreStmt->execute([
        ':score' => $totalScore,
        ':id' => $responseId
    ]);

    $pdo->commit();
    
    // ===== INTEGRAÇÕES =====
    // Buscar configurações de integração
    $integrationStmt = $pdo->prepare("SELECT * FROM form_integrations WHERE form_id = :form_id LIMIT 1");
    $integrationStmt->execute([':form_id' => $form_id]);
    $integration = $integrationStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($integration) {
        // Preparar dados para envio
        $emailData = [
            'form_title' => $form['title'],
            'form_description' => $form['description'] ?? '',
            'response_id' => $responseId,
            'submitted_at' => date('d/m/Y H:i:s'),
            'answers' => []
        ];
        
        // Montar array de respostas com labels
        foreach ($fields as $field) {
            if (isset($answers[$field['id']]) && !empty($answers[$field['id']])) {
                $emailData['answers'][] = [
                    'label' => $field['label'],
                    'answer' => $answers[$field['id']]
                ];
            }
        }
        
        // 1. ENVIAR E-MAIL
        if (!empty($integration['email_to'])) {
            try {
                $emailResult = sendFormResponseEmail(
                    $integration['email_to'], 
                    $integration['email_cc'] ?? '', 
                    $emailData
                );
                
                if ($emailResult) {
                    error_log("✓ E-mail enviado com sucesso para: " . $integration['email_to']);
                } else {
                    error_log("✗ Falha ao enviar e-mail para: " . $integration['email_to']);
                }
            } catch (Exception $e) {
                error_log("✗ Erro ao enviar e-mail: " . $e->getMessage());
            }
        }
        
        // 2. GOOGLE SHEETS
        if (!empty($integration['sheets_enabled']) && !empty($integration['sheets_url'])) {
            try {
                sendToGoogleSheets($integration['sheets_url'], $emailData);
                error_log("✓ Dados preparados para Google Sheets");
            } catch (Exception $e) {
                error_log("✗ Erro ao processar Google Sheets: " . $e->getMessage());
            }
        }
    }

    // Retornar sucesso com pontuação total
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'score' => $totalScore
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("✗ ERRO PDO: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo "Erro ao salvar resposta no banco de dados";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("✗ ERRO GERAL: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo "Erro interno no servidor";
}

ob_end_flush();
exit();

/**
 * Envia e-mail de resposta de formulário usando PHPMailer
 * @param string $emailTo - E-mail destinatário principal
 * @param string $emailCc - E-mails em cópia (separados por vírgula)
 * @param array $data - Dados do formulário e respostas
 * @return bool
 */
function sendFormResponseEmail($emailTo, $emailCc, $data) {
    // Validar e-mail principal
    if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
        error_log("E-mail destinatário inválido: " . $emailTo);
        return false;
    }
    
    $subject = "Nova resposta: " . $data['form_title'];
    
    // Montar corpo do e-mail em HTML
    $body = "
        <div style='margin-bottom: 20px;'>
            <h2 style='color: #4EA44B; margin: 0 0 20px 0; font-size: 24px;'>
                📋 Nova Resposta de Formulário
            </h2>
        </div>
        
        <div style='background: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 25px;'>
            <p style='margin: 5px 0;'><strong>Formulário:</strong> " . htmlspecialchars($data['form_title']) . "</p>
            <p style='margin: 5px 0;'><strong>Data/Hora:</strong> {$data['submitted_at']}</p>
            <p style='margin: 5px 0;'><strong>ID da Resposta:</strong> #{$data['response_id']}</p>
        </div>
        
        <h3 style='color: #555; font-size: 18px; margin: 0 0 15px 0;'>Respostas:</h3>
    ";
    
    foreach ($data['answers'] as $item) {
        $label = htmlspecialchars($item['label']);
        $answer = nl2br(htmlspecialchars($item['answer']));
        $body .= "
        <div style='background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4EA44B; border-radius: 4px;'>
            <div style='font-weight: bold; color: #555; margin-bottom: 5px;'>{$label}</div>
            <div style='color: #333;'>{$answer}</div>
        </div>
        ";
    }
    
    // Usar PHPMailer com as configurações do EmailService
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuração SMTP (mesma do EmailService)
        $mail->isSMTP();
        $mail->Host       = 'mail.supersites.com.br';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'equipe@supersites.com.br';
        $mail->Password   = 'Pach1020$';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        
        // Remetente
        $mail->setFrom('equipe@supersites.com.br', 'Supersites');
        $mail->addAddress($emailTo);
        $mail->addReplyTo('equipe@supersites.com.br', 'Supersites');
        
        // Adicionar CC se houver
        if (!empty($emailCc)) {
            $ccEmails = array_map('trim', explode(',', $emailCc));
            $ccEmails = array_filter($ccEmails, function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });
            
            foreach ($ccEmails as $cc) {
                $mail->addCC($cc);
            }
        }
        
        // Headers anti-spam
        $mail->XMailer = ' ';
        $mail->addCustomHeader('Organization', 'Supersites');
        $mail->addCustomHeader('Return-Path', 'equipe@supersites.com.br');
        $mail->addCustomHeader('X-Priority', '3');
        
        // Configurações de charset
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Aplicar o layout do EmailService
        $fullBody = "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Supersites</title>
        </head>
        <body style='margin:0; padding:0; background-color:#f4f4f4; font-family:Arial,sans-serif;'>
            <table role='presentation' cellpadding='0' cellspacing='0' border='0' width='100%'>
                <tr>
                    <td align='center' style='padding:30px 15px;'>
                        <table role='presentation' cellpadding='0' cellspacing='0' border='0' width='600' style='max-width:600px;'>
                            <tr>
                                <td align='left' style='padding:0 0 20px;'>
                                    <img src='https://painel.supersites.com.br/serve_image.php?file=system/logo_supersites.svg' alt='Supersites' style='max-width:180px; display:block;' />
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color:#ffffff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.08); padding:40px; font-size:15px; line-height:1.6; color:#333333;'>
                                    {$body}
                                </td>
                            </tr>
                            <tr>
                                <td style='padding:30px 0 0; font-size:12px; color:#666666; text-align:center;'>
                                    <p style='margin:0 0 10px;'>
                                        <strong>Supersites</strong><br>
                                        Soluções em websites e sistemas
                                    </p>
                                    <p style='margin:0 0 10px;'>
                                        📧 equipe@supersites.com.br | 🌐 www.supersites.com.br
                                    </p>
                                    <p style='margin:0; color:#999999;'>
                                        &copy; " . date('Y') . " Supersites. Todos os direitos reservados.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
        
        $mail->Body = $fullBody;
        
        // Versão texto
        $textBody = strip_tags($body);
        $textBody = preg_replace('/\s+/', ' ', $textBody);
        $textBody .= "\n\n---\nSupersites\nSoluções em websites e sistemas\nE-mail: equipe@supersites.com.br\nSite: www.supersites.com.br";
        $mail->AltBody = $textBody;
        
        $mail->send();
        return true;
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Erro PHPMailer ao enviar resposta: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envia dados para Google Sheets
 * @param string $sheetsUrl - URL da planilha do Google Sheets
 * @param array $data - Dados a serem enviados
 * @return bool
 */
function sendToGoogleSheets($sheetsUrl, $data) {
    // Extrair o ID da planilha da URL
    preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $sheetsUrl, $matches);
    
    if (empty($matches[1])) {
        throw new Exception("URL da planilha inválida");
    }
    
    $spreadsheetId = $matches[1];
    
    // Preparar linha para inserir
    $row = [
        $data['submitted_at'],
        $data['response_id']
    ];
    
    // Adicionar respostas
    foreach ($data['answers'] as $item) {
        $row[] = $item['answer'];
    }
    
    // Log dos dados preparados
    error_log("Google Sheets: Dados preparados para planilha {$spreadsheetId}");
    error_log("Linha a inserir: " . json_encode($row, JSON_UNESCAPED_UNICODE));
    
    // TODO: Implementar chamada à API do Google Sheets
    // Requer autenticação OAuth2 ou Service Account
    
    return true;
}