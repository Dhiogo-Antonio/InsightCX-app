<?php
/**
 * View: Relatórios
 *
 * Dados esperados do controller:
 * $reportCards = [
 *   [
 *     'key' => 'satisfaction',
 *     'title' => 'Satisfação geral',
 *     'description' => '...',
 *     'icon' => 'chart|alert|briefcase|building|trend',
 *     'tone' => 'blue|red|cyan|green',
 *     'updated_at' => '...',
 *     'values' => [], // valores do gráfico, sem rótulos obrigatórios
 *     'details_url' => '/relatorios/satisfacao'
 *   ]
 * ];
 * $dateFilters = ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD'];
 * $quickPeriods = [['value'=>'30d','label'=>'30 dias'], ...];
 * $selectedQuickPeriod = '6m';
 * $user = ['name'=>'', 'role'=>''];
 *
 * O controller também pode fornecer $exportUrl, URLs de navegação e
 * $pendingFeedbacksCount. Nenhum relatório ou métrica é criado nesta view.
 */

$e = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
$reportCards = $reportCards ?? [];
$dateFilters = $dateFilters ?? ['start' => '', 'end' => ''];
$quickPeriods = $quickPeriods ?? [];
$selectedQuickPeriod = $selectedQuickPeriod ?? ($_GET['period'] ?? '');
$user = $user ?? ['name' => '', 'role' => ''];

$reportIcon = static function (string $icon): string {
    $icons = [
        'chart' => '<path d="M4 18V5m0 13h17M7 14l4-4 3 3 6-7"/>',
        'alert' => '<path d="M10.3 4.3 2.7 18a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L11.7 4.3a.8.8 0 0 0-1.4 0Z"/><path d="M11 9v4m0 3h.01"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18m-11 0v2h4v-2"/>',
        'building' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h2m2 0h2M9 11h2m2 0h2M9 15h2m2 0h2M11 21v-3h2v3"/>',
        'trend' => '<path d="m3 17 6-6 4 4 8-9"/><path d="M15 6h6v6"/>',
    ];
    return $icons[$icon] ?? $icons['chart'];
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios | Insight CX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/relatorios.css">
    <link rel="stylesheet" href="assets/css/typography.css">
    <link rel="stylesheet" href="assets/css/navigation.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= $e($dashboardUrl ?? 'index.php?page=dashboard') ?>" aria-label="Insight CX - início"><img class="brand-logo" src="assets/img/logo-insight-cx.png" alt="Insight CX — Transformando experiências em inteligência"></a>
        <div class="sidebar-caption">MENU PRINCIPAL</div>
        <nav class="main-nav" aria-label="Menu principal">
            <a href="<?= $e($dashboardUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">▦</span><span>Visão geral</span></a>
            <a href="<?= $e($clientsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">♙</span><span>Clientes</span></a>
            <a href="<?= $e($feedbacksUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">▤</span><span>Feedbacks</span><?php if (isset($pendingFeedbacksCount)): ?><span class="nav-badge"><?= $e($pendingFeedbacksCount) ?></span><?php endif; ?></a>
            <a href="<?= $e($analyticsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">⌁</span><span>Análises</span></a>
            <a href="<?= $e($improvementsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">↗</span><span>Melhorias</span></a>
            <a href="<?= $e($reportsUrl ?? '#') ?>" class="nav-link is-active" aria-current="page"><span class="nav-icon">▧</span><span>Relatórios</span></a>
            <a href="<?= $e($settingsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">⚙</span><span>Configurações</span></a>
        </nav>
        <div class="sidebar-bottom">
            <a href="<?= $e($helpUrl ?? '#') ?>" class="help-card">
                <span class="help-symbol">?</span><strong>Precisa de ajuda?</strong>
                <small>Acesse a central de suporte</small><span class="help-link">Ver central</span>
            </a>
            <a href="<?= $e($logoutUrl ?? '#') ?>" class="logout-link"><span>⇥</span> Sair do sistema</a>
            <div class="sidebar-footer"><span>Uma solução</span><strong>CTI</strong></div>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div class="breadcrumb"><a href="<?= $e($dashboardUrl ?? '#') ?>">Insight CX</a><span>/</span><span>Relatórios</span></div>
            <div class="topbar-actions">
                <form class="global-search" action="<?= $e($reportsUrl ?? '') ?>" method="get">
                    <span aria-hidden="true">⌕</span><input type="search" name="search" placeholder="Buscar no sistema..." aria-label="Buscar no sistema"><kbd>⌘ K</kbd>
                </form>
                <button class="icon-button" type="button" aria-label="Notificações">♧<?php if (!empty($unreadNotifications)): ?><i></i><?php endif; ?></button>
                <div class="profile">
                    <div class="profile-avatar"><?= $e(mb_strtoupper(mb_substr($user['name'] ?? '', 0, 2))) ?></div>
                    <div class="profile-copy"><strong><?= $e($user['name'] ?? '') ?></strong><small><?= $e($user['role'] ?? '') ?></small></div>
                    <span class="profile-chevron">⌄</span>
                </div>
            </div>
        </header>

        <section class="content">
            <div class="page-heading">
                <div><h1>Relatórios</h1><p>Explore, filtre e exporte indicadores da experiência dos clientes.</p></div>
                <a class="export-button" href="<?= $e($exportUrl ?? '#') ?>"><span aria-hidden="true">↓</span> Exportar relatório</a>
            </div>

            <section class="card period-card" aria-labelledby="period-title">
                <div class="period-title" id="period-title">PERÍODO DE ANÁLISE</div>
                <form class="period-form" method="get" action="<?= $e($reportsUrl ?? '') ?>">
                    <label class="date-field">
                        <span>Data inicial</span>
                        <input type="date" name="start_date" value="<?= $e($dateFilters['start'] ?? '') ?>">
                    </label>
                    <span class="date-separator">até</span>
                    <label class="date-field">
                        <span>Data final</span>
                        <input type="date" name="end_date" value="<?= $e($dateFilters['end'] ?? '') ?>">
                    </label>
                    <button type="submit" class="apply-button">Aplicar período</button>
                    <div class="quick-periods">
                        <span>Período rápido</span>
                        <?php foreach ($quickPeriods as $period): ?>
                            <button type="submit" name="period" value="<?= $e($period['value'] ?? '') ?>" class="quick-button <?= (string)$selectedQuickPeriod === (string)($period['value'] ?? '') ? 'is-selected' : '' ?>">
                                <?= $e($period['label'] ?? '') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </form>
            </section>

            <section class="reports-grid" aria-label="Relatórios disponíveis">
                <?php if (!$reportCards): ?>
                    <div class="card empty-reports">
                        <span class="empty-icon">▧</span>
                        <strong>Nenhum relatório disponível</strong>
                        <p>Os relatórios aparecerão aqui quando houver dados para o período selecionado.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reportCards as $report): ?>
                        <?php
                        $tone = $report['tone'] ?? 'blue';
                        $values = array_values(array_filter($report['values'] ?? [], 'is_numeric'));
                        $maxValue = $values ? max($values) : 0;
                        ?>
                        <article class="card report-card">
                            <div class="report-heading">
                                <span class="report-icon tone-<?= $e($tone) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $reportIcon((string)($report['icon'] ?? 'chart')) ?></svg>
                                </span>
                                <div class="report-title">
                                    <h2><?= $e($report['title'] ?? '') ?></h2>
                                    <p><?= $e($report['description'] ?? '') ?></p>
                                </div>
                            </div>
                            <?php if ($values && $maxValue > 0): ?>
                                <div class="mini-chart" role="img" aria-label="<?= $e($report['title'] ?? 'Relatório') ?>: gráfico resumido">
                                    <?php foreach ($values as $value): $height = max(5, min(100, ((float)$value / $maxValue) * 100)); ?>
                                        <span style="height: <?= $e($height) ?>%"></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="mini-chart mini-chart-empty"><span class="empty-chart-message">Sem dados para exibir</span></div>
                            <?php endif; ?>
                            <div class="report-footer">
                                <span><?= $e($report['updated_at'] ?? 'Atualização indisponível') ?></span>
                                <a href="<?= $e($report['details_url'] ?? '#') ?>">Visualizar <span aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </section>
    </main>
</div>
</body>
</html>
