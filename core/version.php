<?php
/**
 * Versão do Sistema FormTalk
 *
 * Regras de versionamento:
 * - Formato: X.Y (duas casas decimais)
 * - Incrementar .1 a cada feature ou fix importante
 * - Quando chegar em .9, pular para próximo major (11.9 → 12.0)
 * - Mesma versão usada em commits e cache de assets
 *
 * Histórico recente:
 * - 11.7: Sistema de pontuação + Melhorias no módulo Leads
 * - 12.2: Ajustes no campo VSL - Correção do bloqueio do botão + Autoplay
 * - 12.3: Correções completas no VSL - Autoplay via API + Bloqueio robusto + Esconder controles
 * - 12.4: Novo campo Mensagem de Áudio com player customizado
 * - 12.5: Sistema anti-cache melhorado + Fix upload de áudio
 * - 12.6: Campo Mensagem de Áudio recriado do zero + Autoplay + Upload via upload_image.php
 * - 13.0: Redesign modal de personalização + Sistema de alinhamento de conteúdo (left/center/right)
 * - 13.1: Fix alinhamento correto - container centralizado, conteúdo alinhado
 * - 13.2: Player de áudio - solução definitiva para instabilidade (cache busting + preload auto)
 * - 13.3: Barra de progresso estilo Stories do Instagram (segmentada com animação)
 * - 13.4: Fix alinhamento de botões (OK, Voltar, Enviar) + Fix campos flex/grid
 * - 13.5: Debug logs detalhados para envio de formulário + Melhorias gerais
 */

define('APP_VERSION', '13.5');
