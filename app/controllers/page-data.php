<?php
/**
 * Camada de integração de dados.
 *
 * Implemente consultas ao banco e atribua aqui as variáveis esperadas pela view.
 * Use PDO preparado e valide autorização/CSRF nas ações POST.
 *
 * Exemplos de variáveis:
 *
 * page=feedbacks:
 *   $feedbacks, $counts, $pagination, $filters, $clients, $services, $activeTab
 *
 * page=analises:
 *   $metrics, $evolution, $categories, $dissatisfactionServices,
 *   $frequentComplaints, $periodOptions, $selectedPeriod
 *
 * page=relatorios:
 *   $reportCards, $dateFilters, $quickPeriods, $selectedQuickPeriod
 *
 * page=feedback-detalhe:
 *   $feedback, $history, $currentOwner, $internalNotes, $noteMaxLength
 *
 * IDs:
 *   $_GET['id'] identifica o feedback aberto na tela de detalhe.
 *
 * Não inicialize arrays com registros demonstrativos. Quando não houver dados,
 * deixe as variáveis sem definição: as views exibem estados vazios.
 */

// Contexto compartilhado por todas as páginas autenticadas.
$dashboardUrl = 'index.php?page=dashboard';
$clientsUrl = 'index.php?page=dashboard#clientes';
$feedbacksUrl = 'index.php?page=feedbacks';
$analyticsUrl = 'index.php?page=analises';
$improvementsUrl = 'index.php?page=melhorias';
$reportsUrl = 'index.php?page=relatorios';
$settingsUrl = 'index.php?page=configuracoes';
$helpUrl = '#suporte';
$logoutUrl = 'index.php?page=sair';
$searchUrl = 'index.php';
$feedbackDetailUrl = 'index.php?page=feedback-detalhe';
$createFeedbackUrl = 'index.php?page=feedbacks&action=create';
$exportUrl = 'index.php?page=relatorios&action=export';
$forwardUrl = 'index.php?page=feedback-detalhe&action=forward';
$saveNoteUrl = 'index.php?page=feedback-detalhe&action=save-note';
$editOwnerUrl = 'index.php?page=feedback-detalhe&action=edit-owner';
$clientUrl = $clientsUrl;
$userName = $_SESSION['usuario_nome'] ?? '';
$user = [
    'name' => $userName,
    'role' => 'Administrador',
    'initials' => mb_strtoupper(mb_substr($userName, 0, 2)),
];
$csrfToken = $_SESSION['csrf_token'] ?? null;

// Exemplo de estrutura de conexão, configure as credenciais fora do repositório:
// $pdo = new PDO($dsn, $dbUser, $dbPassword, [
//     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
// ]);
