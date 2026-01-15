<?php
return [
    'name' => 'leads',
    'label' => 'Meus Leads',
    'icon' => 'users',
    'url' => '/leads/list',
    'order' => 5,  // Ordem na sidebar (depois de Pastas)
    'roles' => ['admin', 'client']  // Quem pode acessar
];
