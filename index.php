<?php
/**
 * Redirecionamento automático para o sistema ConsultaProd
 * 
 * Este arquivo redireciona automaticamente para a página de login
 * quando o usuário acessa a raiz do projeto via XAMPP
 */

// Verificar se já estamos na pasta public
if (basename(__DIR__) === 'public') {
    // Se já estamos em public, incluir o index.php do Laravel
    require_once __DIR__ . '/index.php';
    exit;
}

// Obter a URL base correta
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$requestUri = $_SERVER['REQUEST_URI'];

// Extrair o caminho base (remover trailing slash)
$baseUri = rtrim($requestUri, '/');

// Construir URL correta para o login
$loginUrl = $protocol . '://' . $host . $baseUri . '/public/login';

// Redirecionar para a página de login
header('Location: ' . $loginUrl);
exit;
?>