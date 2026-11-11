<?php
declare(strict_types=1);
session_start();
$page = $_GET['page'] ?? 'dashboard';
$paginas = ['dashboard', 'melhorias', 'configuracoes'];
if ($page === 'sair') { session_destroy(); header('Location: index.php?page=login'); exit; }
if ($page === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim((string)($_POST['email'] ?? ''));
        $senha = (string)($_POST['senha'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $senha !== '') {
            // Protótipo sem autenticação em banco: sessão local apenas para navegar nas telas.
            $_SESSION['usuario_nome'] = trim((string)($_POST['nome'] ?? '')) ?: explode('@', $email)[0];
            $_SESSION['usuario_email'] = $email;
            header('Location: index.php?page=dashboard'); exit;
        }
        $erroLogin = 'Informe um e-mail válido e uma senha.';
    }
    require __DIR__ . '/views/login.php'; exit;
}
if (empty($_SESSION['usuario_email'])) { header('Location: index.php?page=login'); exit; }
if (!in_array($page, $paginas, true)) $page = 'dashboard';
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Usuário';
$tituloPagina = ['dashboard'=>'Visão geral','melhorias'=>'Melhorias','configuracoes'=>'Configurações'][$page];
?><!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= htmlspecialchars($tituloPagina) ?> | Insight CX</title><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="assets/css/<?= $page ?>.css"></head>
<body class="app-body">
<aside class="sidebar">
  <a class="brand" href="index.php?page=dashboard"><span class="brand-mark">◉</span><span><b>INSIGHT <em>CX</em></b><small>Transformando experiências em inteligência.</small></span></a>
  <small class="nav-caption">MENU PRINCIPAL</small>
  <nav class="main-nav">
    <a class="<?= $page==='dashboard'?'active':'' ?>" href="index.php?page=dashboard"><span>▦</span>Visão geral</a>
    <a href="index.php?page=dashboard#clientes"><span>♙</span>Clientes</a>
    <a href="index.php?page=dashboard#feedbacks"><span>▤</span>Feedbacks</a>
    <a href="index.php?page=dashboard#analises"><span>⌁</span>Análises</a>
    <a class="<?= $page==='melhorias'?'active':'' ?>" href="index.php?page=melhorias"><span>↗</span>Melhorias</a>
    <a href="index.php?page=dashboard#relatorios"><span>▧</span>Relatórios</a>
    <a class="<?= $page==='configuracoes'?'active':'' ?>" href="index.php?page=configuracoes"><span>⚙</span>Configurações</a>
  </nav>
  <div class="sidebar-bottom"><div class="help-card"><b>?</b><strong>Precisa de ajuda?</strong><small>Acesse a central de suporte</small><a href="#suporte">Ver central</a></div><a class="logout-link" href="index.php?page=sair">⇥ &nbsp; Sair do sistema</a><div class="sidebar-footer">Uma solução <strong>CTI</strong></div></div>
</aside>
<div class="app-main"><header class="topbar"><div class="breadcrumbs">Insight CX <span>/</span> <?= htmlspecialchars($tituloPagina) ?></div><div class="topbar-right"><label class="global-search">⌕ <input type="search" placeholder="Buscar no sistema..."></label><button class="icon-button" aria-label="Notificações">♧</button><div class="user-chip"><span><?= htmlspecialchars(mb_strtoupper(mb_substr($nomeUsuario,0,2))) ?></span><div><b><?= htmlspecialchars($nomeUsuario) ?></b><small>Administrador</small></div></div></div></header>
<?php require __DIR__ . '/views/' . $page . '.php'; ?>
</div>
<script src="assets/js/app.js"></script>
</body></html>
