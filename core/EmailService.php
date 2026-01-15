<?php
require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

class EmailService {
    private $pdo;
    private $config;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Configurações básicas
        $this->config = [
            'from_email' => 'equipe@supersites.com.br',
            'from_name'  => 'Supersites',
            'smtp' => [
                'host'     => 'mail.supersites.com.br',
                'port'     => 465,
                'username' => 'equipe@supersites.com.br',
                'password' => 'Pach1020$',
                'secure'   => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            ]
        ];
    }

    /**
     * Aplica o layout base em volta do conteúdo do template
     */
    private function applyBaseTemplate($content) {
        return "
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
                            <!-- Logo -->
                            <tr>
                                <td align='left' style='padding:0 0 20px;'>
                                    <img src='https://forms.supersites.com.br/serve_image.php?file=system/uploads/logo.png' alt='Supersites' style='max-width:180px; display:block;' />
                                </td>
                            </tr>
                            <!-- Caixa branca de conteúdo -->
                            <tr>
                                <td style='background-color:#ffffff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.08); padding:40px; font-size:15px; line-height:1.6; color:#333333;'>
                                    {$content}
                                </td>
                            </tr>
                            <!-- Footer profissional -->
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
    }
    
    /**
     * Cria versão texto limpa para AltBody
     */
    private function createTextVersion($content, $variables) {
        // Remove HTML tags
        $text = strip_tags($content);
        
        // Limpa espaços extras
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Adiciona assinatura texto
        $text .= "\n\n";
        $text .= "---\n";
        $text .= "Supersites\n";
        $text .= "Soluções em websites e sistemas\n";
        $text .= "E-mail: equipe@supersites.com.br\n";
        $text .= "Site: www.supersites.com.br\n";
        
        return $text;
    }
    
    public function sendTemplate($templateCode, $toEmail, $variables = []) {
        try {
            // Buscar template ativo no banco
            $stmt = $this->pdo->prepare("
                SELECT subject, body FROM email_templates 
                WHERE code = :code AND active = 1
            ");
            $stmt->execute([':code' => $templateCode]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                error_log("Template '$templateCode' não encontrado ou inativo");
                return false;
            }
            
            // Substituir variáveis no assunto e corpo
            $subject = $this->replaceVariables($template['subject'], $variables);
            $bodyRaw = $this->replaceVariables($template['body'], $variables);

            // Encapsular no layout base
            $body = $this->applyBaseTemplate($bodyRaw);
            
            // Enviar e-mail
            return $this->sendEmail($toEmail, $subject, $body, $bodyRaw, $variables);
            
        } catch (\Exception $e) {
            error_log("Erro ao enviar template '$templateCode': " . $e->getMessage());
            return false;
        }
    }
    
    private function replaceVariables($content, $variables) {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
    
    private function sendEmail($to, $subject, $body, $bodyRaw = '', $variables = []) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuração SMTP
            $mail->isSMTP();
            $mail->Host       = $this->config['smtp']['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['smtp']['username'];
            $mail->Password   = $this->config['smtp']['password'];
            $mail->SMTPSecure = $this->config['smtp']['secure'];
            $mail->Port       = $this->config['smtp']['port'];
            
            // 🔹 HEADERS ANTI-SPAM
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);
            $mail->addReplyTo($this->config['from_email'], $this->config['from_name']);
            
            // Remove identificação PHPMailer
            $mail->XMailer = ' ';
            
            // Headers adicionais de identificação
            $mail->addCustomHeader('Organization', 'Supersites');
            $mail->addCustomHeader('Return-Path', $this->config['from_email']);
            $mail->addCustomHeader('X-Priority', '3'); // Normal priority
            $mail->addCustomHeader('X-MSMail-Priority', 'Normal');
            $mail->addCustomHeader('Importance', 'Normal');
            
            // 🔹 Configurações de charset e encoding
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            // 🔹 Versão texto melhorada
            if (!empty($bodyRaw)) {
                $mail->AltBody = $this->createTextVersion($bodyRaw, $variables);
            } else {
                $mail->AltBody = $this->createTextVersion($body, []);
            }
            
            $mail->send();
            error_log("E-mail enviado com sucesso para: $to");
            return true;
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Erro PHPMailer: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    public function testConnection() {
        return $this->sendEmail(
            'equipe@supersites.com.br',
            'Teste Sistema Anti-Spam',
            $this->applyBaseTemplate('<h1>Teste funcionando!</h1><p>Sistema de e-mail com headers anti-spam configurados.</p>')
        );
    }
}