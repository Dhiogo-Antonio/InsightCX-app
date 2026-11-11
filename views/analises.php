<?php
/**
 * View: Análises
 *
 * O controller deve fornecer os dados agregados do banco:
 * $metrics = [
 *   'satisfaction' => ['value' => 0, 'scale' => 5, 'change' => 0],
 *   'evaluations' => ['value' => 0, 'change' => 0],
 *   'response_rate' => ['value' => 0, 'change' => 0],
 *   'resolved_complaints' => ['value' => 0, 'change' => 0],
 * ];
 * $evolution = [
 *   'labels' => [], 'current' => [], 'previous' => []
 * ];
 * $categories = [
 *   ['label' => '...', 'value' => 0, 'percentage' => 0, 'color' => '#...']
 * ];
 * $dissatisfactionServices = [
 *   ['name' => '...', 'percentage' => 0]
 * ];
 * $frequentComplaints = [
 *   ['reason' => '...', 'count' => 0, 'change' => 0]
 * ];
 * $periodOptions = [['value'=>'6m', 'label'=>'Últimos 6 meses']];
 * $selectedPeriod = '6m';
 * $user = ['name'=>'', 'role'=>''];
 *
 * Também são aceitas URLs opcionais para os links da navegação:
 * $dashboardUrl, $clientsUrl, $feedbacksUrl, $analyticsUrl,
 * $improvementsUrl, $reportsUrl, $settingsUrl, $helpUrl, $logoutUrl.
 */

$e = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
$metrics = $metrics ?? [];
$evolution = $evolution ?? ['labels' => [], 'current' => [], 'previous' => []];
$categories = $categories ?? [];
$dissatisfactionServices = $dissatisfactionServices ?? [];
$frequentComplaints = $frequentComplaints ?? [];
$periodOptions = $periodOptions ?? [];
$selectedPeriod = $selectedPeriod ?? ($_GET['period'] ?? '');
$user = $user ?? ['name' => '', 'role' => ''];

$metricValue = static function (string $key) use ($metrics): string {
    $value = $metrics[$key]['value'] ?? null;
    if ($value === null || $value === '') return '—';
    return is_numeric($value) ? number_format((float)$value, 0, ',', '.') : (string)$value;
};
$metricChange = static function (string $key) use ($metrics): ?float {
    $value = $metrics[$key]['change'] ?? null;
    return is_numeric($value) ? (float)$value : null;
};
$changeLabel = static function (?float $value): string {
    if ($value === null) return '—';
    return ($value > 0 ? '+' : '') . number_format($value, 1, ',', '.') . '%';
};
$changeClass = static fn(?float $value) => $value === null ? 'neutral' : ($value >= 0 ? 'positive' : 'negative');

$chartPayload = [
    'labels' => array_values($evolution['labels'] ?? []),
    'current' => array_values($evolution['current'] ?? []),
    'previous' => array_values($evolution['previous'] ?? []),
    'categories' => array_map(static fn($item) => [
        'label' => $item['label'] ?? '',
        'value' => (float)($item['value'] ?? 0),
        'color' => $item['color'] ?? null,
    ], $categories),
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análises | Insight CX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/analises.css">
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
            <a href="<?= $e($analyticsUrl ?? '#') ?>" class="nav-link is-active" aria-current="page"><span class="nav-icon">⌁</span><span>Análises</span></a>
            <a href="<?= $e($improvementsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">↗</span><span>Melhorias</span></a>
            <a href="<?= $e($reportsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">▧</span><span>Relatórios</span></a>
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
            <div class="breadcrumb"><a href="<?= $e($dashboardUrl ?? '#') ?>">Insight CX</a><span>/</span><span>Análises</span></div>
            <div class="topbar-actions">
                <form class="global-search" action="<?= $e($analyticsUrl ?? '') ?>" method="get">
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
                <div><h1>Análises</h1><p>Transforme dados de experiência em decisões mais consistentes.</p></div>
                <form method="get" action="<?= $e($analyticsUrl ?? '') ?>" class="period-form">
                    <label class="period-select"><span>▣</span>
                        <select name="period" aria-label="Período" onchange="this.form.submit()">
                            <?php foreach ($periodOptions as $option): ?>
                                <option value="<?= $e($option['value'] ?? '') ?>" <?= (string)$selectedPeriod === (string)($option['value'] ?? '') ? 'selected' : '' ?>><?= $e($option['label'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            </div>

            <section class="summary-grid" aria-label="Indicadores principais">
                <article class="card satisfaction-card">
                    <span class="eyebrow">SATISFAÇÃO GERAL</span>
                    <div class="satisfaction-value">
                        <strong><?= isset($metrics['satisfaction']['value']) ? $e(number_format((float)$metrics['satisfaction']['value'], 1, ',', '.')) : '—' ?></strong>
                        <span>/ <?= $e($metrics['satisfaction']['scale'] ?? 5) ?></span>
                    </div>
                    <?php if (isset($metrics['satisfaction']['value'])): ?>
                        <?php $score = max(0, min(5, (float)$metrics['satisfaction']['value'])); ?>
                        <div class="score-stars" aria-label="Satisfação <?= $e($score) ?> de 5">
                            <?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i <= round($score) ? 'filled' : '' ?>">★</span><?php endfor; ?>
                        </div>
                    <?php endif; ?>
                    <p class="metric-change <?= $changeClass($metricChange('satisfaction')) ?>">
                        <?= $e($changeLabel($metricChange('satisfaction'))) ?> <span>em relação ao período anterior</span>
                    </p>
                </article>

                <article class="card kpi-card">
                    <?php
                    $kpis = [
                        ['key' => 'evaluations', 'label' => 'Total de avaliações', 'suffix' => ''],
                        ['key' => 'response_rate', 'label' => 'Taxa de resposta', 'suffix' => '%'],
                        ['key' => 'resolved_complaints', 'label' => 'Reclamações resolvidas', 'suffix' => '%'],
                    ];
                    ?>
                    <?php foreach ($kpis as $index => $kpi): $change = $metricChange($kpi['key']); ?>
                        <div class="kpi-item">
                            <span class="kpi-label"><?= $e($kpi['label']) ?></span>
                            <strong><?= $e($metricValue($kpi['key'])) ?><?= $e($kpi['suffix']) ?></strong>
                            <small class="metric-change <?= $changeClass($change) ?>"><?= $e($changeLabel($change)) ?></small>
                        </div>
                    <?php endforeach; ?>
                </article>
            </section>

            <section class="charts-grid">
                <article class="card chart-card evolution-card">
                    <div class="card-heading">
                        <div><h2>Evolução das avaliações</h2><p>Comparativo entre os períodos selecionados</p></div>
                        <div class="legend"><span><i class="legend-current"></i>Atual</span><span><i class="legend-previous"></i>Anterior</span></div>
                    </div>
                    <div class="line-chart-wrap"><canvas id="evolutionChart" aria-label="Gráfico de evolução das avaliações"></canvas></div>
                    <div class="chart-empty" id="evolutionEmpty" hidden>Sem dados de evolução para o período selecionado.</div>
                </article>

                <article class="card chart-card categories-card">
                    <div class="card-heading"><div><h2>Categorias mais registradas</h2><p>Distribuição das manifestações</p></div></div>
                    <div class="category-chart-content">
                        <div class="donut-wrap"><canvas id="categoriesChart" aria-label="Gráfico de categorias"></canvas><div class="donut-center"><strong id="categoriesTotal">—</strong><span>Total</span></div></div>
                        <ul class="category-legend" id="categoryLegend">
                            <?php foreach ($categories as $category): ?>
                                <li><span class="legend-dot" style="--item-color: <?= $e($category['color'] ?? '#aab4c3') ?>"></span><span><?= $e($category['label'] ?? '') ?></span><strong><?= $e($category['percentage'] ?? '—') ?>%</strong></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!$categories): ?><div class="chart-empty">Sem categorias para exibir.</div><?php endif; ?>
                    </div>
                </article>
            </section>

            <section class="details-grid">
                <article class="card services-card">
                    <div class="card-heading"><div><h2>Serviços com maior insatisfação</h2><p>Percentual de notas abaixo de 3</p></div></div>
                    <?php if (!$dissatisfactionServices): ?>
                        <div class="list-empty">Sem dados de insatisfação para o período selecionado.</div>
                    <?php else: ?>
                        <div class="bar-list">
                            <?php foreach ($dissatisfactionServices as $service): $percentage = max(0, min(100, (float)($service['percentage'] ?? 0))); ?>
                                <div class="bar-row">
                                    <span class="bar-label" title="<?= $e($service['name'] ?? '') ?>"><?= $e($service['name'] ?? '') ?></span>
                                    <div class="bar-track"><span style="width: <?= $e($percentage) ?>%"></span></div>
                                    <strong><?= $e(number_format($percentage, 0, ',', '.')) ?>%</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="card complaints-card">
                    <div class="card-heading"><div><h2>Reclamações mais frequentes</h2><p>Principais motivos no período</p></div></div>
                    <?php if (!$frequentComplaints): ?>
                        <div class="list-empty">Nenhuma reclamação registrada no período.</div>
                    <?php else: ?>
                        <ol class="complaint-list">
                            <?php foreach ($frequentComplaints as $index => $complaint): $change = isset($complaint['change']) && is_numeric($complaint['change']) ? (float)$complaint['change'] : null; ?>
                                <li>
                                    <span class="rank"><?= $e($index + 1) ?></span>
                                    <div class="complaint-info"><strong><?= $e($complaint['reason'] ?? '') ?></strong><small><?= $e($complaint['count'] ?? 0) ?> registros</small></div>
                                    <span class="complaint-change <?= $changeClass($change) ?>"><?= $change === null ? '—' : $e($changeLabel($change)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </article>
            </section>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const data = <?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const evolutionCanvas = document.getElementById('evolutionChart');
    const evolutionEmpty = document.getElementById('evolutionEmpty');

    if (evolutionCanvas && data.labels.length && data.current.length) {
        new Chart(evolutionCanvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Atual', data: data.current, borderColor: '#326de5', backgroundColor: 'rgba(50,109,229,.13)', fill: true, tension: .38, borderWidth: 2.5, pointRadius: 4, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderColor: '#326de5', pointBorderWidth: 2 },
                    { label: 'Anterior', data: data.previous, borderColor: '#b8c4d5', backgroundColor: 'transparent', fill: false, tension: .38, borderWidth: 1.5, borderDash: [4, 4], pointRadius: 2, pointHoverRadius: 4 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { min: 0, max: 5, ticks: { stepSize: 1, color: '#8c97a7', font: { size: 9 } }, grid: { color: '#edf1f6', borderDash: [4, 5] }, border: { display: false } },
                    x: { ticks: { color: '#8c97a7', font: { size: 9 } }, grid: { display: false }, border: { display: false } }
                }
            }
        });
    } else if (evolutionEmpty) {
        evolutionEmpty.hidden = false;
        evolutionCanvas.hidden = true;
    }

    const categoryCanvas = document.getElementById('categoriesChart');
    const totalElement = document.getElementById('categoriesTotal');
    const legend = document.getElementById('categoryLegend');
    if (categoryCanvas && data.categories.length) {
        const total = data.categories.reduce((sum, item) => sum + Number(item.value || 0), 0);
        if (totalElement) totalElement.textContent = total.toLocaleString('pt-BR');
        new Chart(categoryCanvas, {
            type: 'doughnut',
            data: {
                labels: data.categories.map(item => item.label),
                datasets: [{ data: data.categories.map(item => item.value), backgroundColor: data.categories.map(item => item.color || '#aab4c3'), borderWidth: 0, hoverOffset: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: context => `${context.label}: ${Number(context.raw).toLocaleString('pt-BR')}` } } } }
        });
    } else if (categoryCanvas) {
        categoryCanvas.hidden = true;
        if (legend) legend.hidden = true;
        if (totalElement) totalElement.textContent = '—';
    }
})();
</script>
</body>
</html>
