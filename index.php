<?php
declare(strict_types=1);
session_start();
$page = $_GET['page'] ?? 'dashboard';
$paginas = ['dashboard', 'melhorias', 'configuracoes', 'clientes'];
if ($page === 'sair') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}
if ($page === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $senha !== '') {
            // Protótipo sem autenticação em banco: sessão local apenas para navegar nas telas.
            $_SESSION['usuario_nome'] = trim((string) ($_POST['nome'] ?? '')) ?: explode('@', $email)[0];
            $_SESSION['usuario_email'] = $email;
            header('Location: index.php?page=dashboard');
            exit;
        }
        $erroLogin = 'Informe um e-mail válido e uma senha.';
    }
    require __DIR__ . '/views/login.php';
    exit;
}
if (empty($_SESSION['usuario_email'])) {
    header('Location: index.php?page=login');
    exit;
}
$integratedPages = [
    'feedbacks' => __DIR__ . '/views/feedbacks.php',
    'analises' => __DIR__ . '/views/analises.php',
    'relatorios' => __DIR__ . '/views/relatorios.php',
    'feedback-detalhe' => __DIR__ . '/views/feedback-detalhe.php',
];
require __DIR__ . '/app/controllers/page-data.php';
if (isset($integratedPages[$page])) {
    require $integratedPages[$page];
    exit;
}
if (!in_array($page, $paginas, true))
    $page = 'dashboard';
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Usuário';
$tituloPagina = ['dashboard' => 'Visão geral', 'melhorias' => 'Melhorias', 'configuracoes' => 'Configurações', 'clientes' => 'Clientes'][$page];
?><!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($tituloPagina) ?> | Insight CX</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/<?= $page ?>.css">
    <link rel="stylesheet" href="assets/css/typography.css">
    <link rel="stylesheet" href="assets/css/navigation.css">
</head>

<body class="app-body">
    <aside class="sidebar">
        <a class="brand" href="index.php?page=dashboard" aria-label="Insight CX - início"><img class="brand-logo" src="assets/img/logo-insight-cx.png" alt="Insight CX — Transformando experiências em inteligência"></a>
        <small class="nav-caption">MENU PRINCIPAL</small>
        <nav class="main-nav">
            <a class="<?= $page === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard"><span>▦</span>Visão
                geral</a>
            <a class="<?= $page === 'clientes' ? 'active' : '' ?>" href="index.php?page=clientes"><span>♙</span>Clientes</a>
            <a href="<?= htmlspecialchars($feedbacksUrl, ENT_QUOTES, 'UTF-8') ?>"><span>▤</span>Feedbacks</a>
            <a href="<?= htmlspecialchars($analyticsUrl, ENT_QUOTES, 'UTF-8') ?>"><span>⌁</span>Análises</a>
            <a class="<?= $page === 'melhorias' ? 'active' : '' ?>"
                href="index.php?page=melhorias"><span>↗</span>Melhorias</a>
            <a href="<?= htmlspecialchars($reportsUrl, ENT_QUOTES, 'UTF-8') ?>"><span>▧</span>Relatórios</a>
            <a class="<?= $page === 'configuracoes' ? 'active' : '' ?>"
                href="index.php?page=configuracoes"><span>⚙</span>Configurações</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="help-card"><b>?</b><strong>Precisa de ajuda?</strong><small>Acesse a central de
                    suporte</small><a href="#suporte">Ver central</a></div><a class="logout-link"
                href="index.php?page=sair">⇥ &nbsp; Sair do sistema</a>
            <div class="sidebar-footer">Uma solução <strong>CTI</strong></div>
        </div>
    </aside>
    <div class="app-main">
        <header class="topbar">
            <div class="breadcrumbs">Insight CX <span>/</span> <?= htmlspecialchars($tituloPagina) ?></div>
            <div class="topbar-right"><label class="global-search">⌕ <input type="search"
                        placeholder="Buscar no sistema..."></label><button class="icon-button"
                    aria-label="Notificações">♧</button>
                <div class="user-chip"><span><?= htmlspecialchars(mb_strtoupper(mb_substr($nomeUsuario, 0, 2))) ?></span>
                    <div><b><?= htmlspecialchars($nomeUsuario) ?></b><small>Administrador</small></div>
                </div>
            </div>
        </header>
        <?php require __DIR__ . '/views/' . $page . '.php'; ?>
    </div>
    <script src="assets/js/app.js"></script>
</body>

</html>
