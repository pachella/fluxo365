<?php
/**
 * Helper: Placeholders automáticos para campos
 *
 * Retorna placeholders inteligentes e contextuais baseados no tipo do campo
 */

function getFieldPlaceholder($type) {
    $placeholders = [
        'text' => 'Digite sua resposta...',
        'textarea' => 'Digite sua resposta aqui...',
        'email' => 'seu@email.com',
        'phone' => '(00) 00000-0000',
        'date' => 'Selecione uma data',
        'cpf' => '000.000.000-00',
        'cnpj' => '00.000.000/0000-00',
        'money' => 'R$ 0,00',
        'name' => 'Digite seu nome completo',
        'url' => 'https://exemplo.com',
        'number' => 'Digite um número',
        'address' => 'Digite seu endereço completo'
    ];

    return $placeholders[$type] ?? '';
}
