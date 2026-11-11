<?php
/**
 * View: Feedbacks
 *
 * O controller deve disponibilizar:
 * $feedbacks = [
 *   ['id'=>1, 'client_name'=>'...', 'client_code'=>'...', 'service'=>'...',
 *    'category'=>'complaint|praise|suggestion', 'rating'=>4,
 *    'date'=>'2025-05-24', 'status'=>'pending|completed|analysis|in_progress',
 *    'details_url'=>'/feedbacks/1']
 * ];
 * $counts = ['all'=>0, 'praise'=>0, 'complaint'=>0, 'suggestion'=>0];
 * $pagination = ['current'=>1, 'last'=>1, 'total'=>0, 'from'=>0, 'to'=>0];
 * $filters = ['search'=>'', 'client'=>'', 'service'=>'', 'month'=>''];
 * $clients = [['id'=>..., 'name'=>...]];
 * $services = [['id'=>..., 'name'=>...]];
 * $user = ['name'=>'', 'role'=>''];
 */

$e = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');

$feedbacks = $feedbacks ?? [];
$feedbackDetailUrl = $feedbackDetailUrl ?? 'index.php?page=feedback-detalhe';
$counts = $counts ?? ['all' => 0, 'praise' => 0, 'complaint' => 0, 'suggestion' => 0];
$pagination = $pagination ?? ['current' => 1, 'last' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
$filters = $filters ?? [];
$clients = $clients ?? [];
$services = $services ?? [];
$user = $user ?? ['name' => '', 'role' => ''];

$activeTab = $activeTab ?? ($_GET['tab'] ?? 'all');
$tabLabels = [
    'all' => 'Todos',
    'praise' => 'Elogios',
    'complaint' => 'Reclamações',
    'suggestion' => 'Sugestões',
];
$statusLabels = [
    'pending' => 'Pendente',
    'completed' => 'Concluído',
    'analysis' => 'Em análise',
    'in_progress' => 'Em andamento',
];
$categoryLabels = [
    'praise' => 'Elogio',
    'complaint' => 'Reclamação',
    'suggestion' => 'Sugestão',
];

function feedback_date(?string $date): string {
    if (!$date) return '—';
    try {
        return (new DateTime($date))->format('d M, Y');
    } catch (Throwable $exception) {
        return $date;
    }
}

$query = $_GET;
$buildPageUrl = static function (int $page) use ($query): string {
    $query['p'] = $page;
    return '?' . http_build_query($query);
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedbacks | Insight CX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feedbacks.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= $e($dashboardUrl ?? 'index.php?page=dashboard') ?>" aria-label="Insight CX - início">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 40 40" fill="none"><path d="M5 20C5 11.7 11.7 5 20 5s15 6.7 15 15-6.7 15-15 15S5 28.3 5 20Z" stroke="currentColor" stroke-width="2"/><path d="M12 23V18m8 9V13m8 10v-7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
            </span>
            <span class="brand-copy"><strong>INSIGHT CX</strong><small>Transformando experiências em inteligência.</small></span>
        </a>

        <div class="sidebar-caption">MENU PRINCIPAL</div>
        <nav class="main-nav" aria-label="Menu principal">
            <a href="<?= $e($dashboardUrl ?? '#') ?>" class="nav-link">
                <span class="nav-icon">◫</span><span>Visão geral</span>
            </a>
            <a href="<?= $e($clientsUrl ?? '#') ?>" class="nav-link">
                <span class="nav-icon">♧</span><span>Clientes</span>
            </a>
            <a href="<?= $e($feedbacksUrl ?? '#') ?>" class="nav-link is-active" aria-current="page">
                <span class="nav-icon">▤</span><span>Feedbacks</span>
                <?php if (!empty($pendingFeedbacksCount)): ?><span class="nav-badge"><?= $e($pendingFeedbacksCount) ?></span><?php endif; ?>
            </a>
            <a href="<?= $e($analyticsUrl ?? '#') ?>" class="nav-link">
                <span class="nav-icon">⌁</span><span>Análises</span>
            </a>
            <a href="<?= $e($improvementsUrl ?? '#') ?>" class="nav-link">
                <span class="nav-icon">↗</span><span>Melhorias</span>
            </a>
            <a href="<?= $e($reportsUrl ?? '#') ?>" class="nav-link">
                <span class="nav-icon">▧</span><span>Relatórios</span>
            </a>
            <a href="<?= $e($settingsUrl ?? '#') ?>" class="nav-link">
                <span class="nav-icon">⚙</span><span>Configurações</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="<?= $e($helpUrl ?? '#') ?>" class="help-card">
                <span class="help-symbol">?</span>
                <strong>Precisa de ajuda?</strong>
                <small>Acesse a central de suporte</small>
                <span class="help-link">Ver central</span>
            </a>
            <a href="<?= $e($logoutUrl ?? '#') ?>" class="logout-link"><span>⇥</span> Sair do sistema</a>
            <div class="sidebar-footer"><span>Uma solução</span><strong>CTI</strong></div>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div class="breadcrumb"><a href="<?= $e($dashboardUrl ?? '#') ?>">Insight CX</a><span>/</span><span>Feedbacks</span></div>
            <div class="topbar-actions">
                <form class="global-search" action="<?= $e($feedbacksUrl ?? '') ?>" method="get">
                    <span aria-hidden="true">⌕</span>
                    <input type="search" name="search" placeholder="Buscar no sistema..." value="<?= $e($filters['search'] ?? '') ?>" aria-label="Buscar no sistema">
                    <kbd>⌘ K</kbd>
                </form>
                <button class="icon-button notification-button" type="button" aria-label="Notificações">
                    <span>♧</span>
                    <?php if (!empty($unreadNotifications)): ?><i></i><?php endif; ?>
                </button>
                <div class="profile">
                    <div class="profile-avatar"><?= $e(mb_strtoupper(mb_substr($user['name'] ?? '', 0, 2))) ?></div>
                    <div class="profile-copy"><strong><?= $e($user['name'] ?? '') ?></strong><small><?= $e($user['role'] ?? '') ?></small></div>
                    <span class="profile-chevron">⌄</span>
                </div>
            </div>
        </header>

        <section class="content">
            <div class="page-heading">
                <div>
                    <h1>Feedbacks</h1>
                    <p>Centralize e acompanhe todas as manifestações dos clientes.</p>
                </div>
                <a class="primary-button" href="<?= $e($createFeedbackUrl ?? '#') ?>"><span>＋</span> Registrar feedback</a>
            </div>

            <nav class="tabs" aria-label="Categorias de feedback">
                <?php foreach ($tabLabels as $key => $label): ?>
                    <a class="tab <?= $activeTab === $key ? 'is-selected' : '' ?>"
                       href="?<?= $e(http_build_query(array_merge($_GET, ['tab' => $key, 'p' => 1]))) ?>"
                       <?= $activeTab === $key ? 'aria-current="page"' : '' ?>>
                        <?= $e($label) ?>
                        <span class="tab-count"><?= $e($counts[$key] ?? 0) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <section class="feedback-panel">
                <form class="filters" method="get" action="<?= $e($feedbacksUrl ?? '') ?>">
                    <input type="hidden" name="tab" value="<?= $e($activeTab) ?>">
                    <label class="filter-search">
                        <span aria-hidden="true">⌕</span>
                        <input type="search" name="search" placeholder="Buscar por cliente ou descrição..." value="<?= $e($filters['search'] ?? '') ?>">
                    </label>
                    <select name="client" aria-label="Filtrar por cliente">
                        <option value="">Todos os clientes</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $e($client['id'] ?? '') ?>" <?= (string)($filters['client'] ?? '') === (string)($client['id'] ?? '') ? 'selected' : '' ?>><?= $e($client['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="service" aria-label="Filtrar por serviço">
                        <option value="">Todos os serviços</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= $e($service['id'] ?? '') ?>" <?= (string)($filters['service'] ?? '') === (string)($service['id'] ?? '') ? 'selected' : '' ?>><?= $e($service['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="date-filter">
                        <span aria-hidden="true">▣</span>
                        <input type="month" name="month" value="<?= $e($filters['month'] ?? '') ?>" aria-label="Filtrar por mês">
                    </label>
                    <button class="more-filters" type="button" aria-expanded="false" aria-controls="extra-filters" id="toggle-extra-filters">
                        <span>☷</span> Mais filtros
                        <?php if (!empty($activeFiltersCount)): ?><b><?= $e($activeFiltersCount) ?></b><?php endif; ?>
                    </button>
                    <div class="extra-filters" id="extra-filters" hidden>
                        <select name="status" aria-label="Filtrar por status">
                            <option value="">Todos os status</option>
                            <?php foreach ($statusLabels as $key => $label): ?>
                                <option value="<?= $e($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="rating" aria-label="Filtrar por nota">
                            <option value="">Todas as notas</option>
                            <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                <option value="<?= $rating ?>" <?= (string)($filters['rating'] ?? '') === (string)$rating ? 'selected' : '' ?>><?= $rating ?> estrelas</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button class="apply-filters" type="submit">Aplicar</button>
                </form>

                <div class="table-scroll">
                    <table class="feedback-table">
                        <thead>
                        <tr>
                            <th>CLIENTE</th><th>SERVIÇO</th><th>CATEGORIA</th><th>NOTA</th><th>DATA</th><th>STATUS</th><th><span class="sr-only">Ações</span></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$feedbacks): ?>
                            <tr><td class="empty-state" colspan="7">
                                <span class="empty-icon">▤</span>
                                <strong>Nenhum feedback encontrado</strong>
                                <small>Quando houver feedbacks correspondentes aos filtros, eles aparecerão aqui.</small>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <?php
                                $category = $feedback['category'] ?? '';
                                $status = $feedback['status'] ?? '';
                                $rating = max(0, min(5, (int)($feedback['rating'] ?? 0)));
                                $clientName = $feedback['client_name'] ?? '';
                                $initials = mb_strtoupper(mb_substr($clientName, 0, 1) . (preg_match('/\s+(\S)/u', $clientName, $m) ? $m[1] : ''));
                                ?>
                                <tr>
                                    <td>
                                        <div class="client-cell">
                                            <span class="client-avatar"><?= $e($feedback['client_initials'] ?? $initials) ?></span>
                                            <span class="client-info"><strong><?= $e($clientName) ?></strong><small>#<?= $e($feedback['client_code'] ?? $feedback['id'] ?? '') ?></small></span>
                                        </div>
                                    </td>
                                    <td><?= $e($feedback['service'] ?? '—') ?></td>
                                    <td><span class="category-pill category-<?= $e($category) ?>"><?= $e($categoryLabels[$category] ?? $feedback['category_label'] ?? '—') ?></span></td>
                                    <td><span class="rating"><strong><?= number_format((float)($feedback['rating'] ?? 0), 1, ',', '.') ?></strong><span class="stars" aria-label="<?= $rating ?> de 5 estrelas"><?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i <= $rating ? 'filled' : '' ?>">★</span><?php endfor; ?></span></span></td>
                                    <td><?= $e(feedback_date($feedback['date'] ?? null)) ?></td>
                                    <td><span class="status-pill status-<?= $e($status) ?>"><?= $e($statusLabels[$status] ?? $feedback['status_label'] ?? '—') ?></span></td>
                                    <td><a class="details-link" href="<?= $e($feedback['details_url'] ?? ($feedbackDetailUrl . '&id=' . rawurlencode((string)($feedback['id'] ?? '')))) ?>"><span>◎</span> Ver detalhes</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <footer class="table-footer">
                    <span>Mostrando <?= $e($pagination['from'] ?? 0) ?> a <?= $e($pagination['to'] ?? 0) ?> de <?= $e($pagination['total'] ?? 0) ?> feedbacks</span>
                    <?php if (($pagination['last'] ?? 1) > 1): ?>
                        <nav class="pagination" aria-label="Paginação">
                            <a class="page-button <?= ($pagination['current'] ?? 1) <= 1 ? 'is-disabled' : '' ?>" href="<?= $e($buildPageUrl(max(1, (int)($pagination['current'] ?? 1) - 1))) ?>" aria-label="Página anterior">‹</a>
                            <?php
                            $current = (int)($pagination['current'] ?? 1);
                            $last = (int)($pagination['last'] ?? 1);
                            $pages = array_unique(array_filter([1, $current - 1, $current, $current + 1, $last], fn($p) => $p >= 1 && $p <= $last));
                            sort($pages);
                            $previous = 0;
                            foreach ($pages as $page):
                                if ($previous && $page > $previous + 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
                                <a class="page-button <?= $page === $current ? 'is-current' : '' ?>" href="<?= $e($buildPageUrl($page)) ?>"><?= $page ?></a>
                                <?php $previous = $page; endforeach; ?>
                            <a class="page-button <?= $current >= $last ? 'is-disabled' : '' ?>" href="<?= $e($buildPageUrl(min($last, $current + 1))) ?>" aria-label="Próxima página">›</a>
                        </nav>
                    <?php endif; ?>
                </footer>
            </section>
        </section>
    </main>
</div>
<script>
(() => {
    const button = document.getElementById('toggle-extra-filters');
    const panel = document.getElementById('extra-filters');
    if (!button || !panel) return;
    button.addEventListener('click', () => {
        const isOpen = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!isOpen));
        panel.hidden = isOpen;
    });
})();
</script>
</body>
</html>
