<?php
return [
    'name' => 'dashboard',
    'label' => 'Dashboard',
    'icon' => 'home',
    'url' => '/dashboard',  // URL diferente dos outros módulos
    'order' => 1,
    'roles' => ['admin', 'client']  // Todos os usuários logados podem acessar
];