<?php
return [
    'name' => 'ai-builder',
    'label' => 'Criar com IA',
    'icon' => 'zap',  // ícone de raio/energia para representar IA
    'url' => '/modules/ai-builder/',
    'order' => 2,
    'roles' => ['admin', 'client'],  // Todos os usuários logados podem acessar
    'badge' => 'BETA'  // Badge para recurso em teste
];
