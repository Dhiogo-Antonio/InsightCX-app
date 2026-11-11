<?php
/**
 * Detalhe do feedback — Insight CX
 *
 * Dados fornecidos pelo controller:
 * $feedback = [
 *   'code' => '...', 'created_at' => '...',
 *   'status' => ['label' => '...', 'class' => 'pending'],
 *   'company' => ['initials'=>'', 'name'=>'', 'service'=>'', 'plan'=>''],
 *   'category' => '', 'rating' => null, 'manifestation_date' => '',
 *   'channel' => '', 'description' => ''
 * ];
 * $history = [['title'=>'', 'description'=>'', 'date'=>'', 'icon'=>'message|eye|clock', 'done'=>true]];
 * $currentOwner = ['name'=>'', 'role'=>'', 'initials'=>''];
 * $internalNotes = [['author'=>'', 'content'=>'', 'created_at'=>'']];
 *
 * A view não define dados de negócio padrão. Arrays vazios exibem estados vazios.
 */
$e = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');

$feedback = $feedback ?? [];
$history = $history ?? [];
$currentOwner = $currentOwner ?? [];
$internalNotes = $internalNotes ?? [];
$noteMaxLength = (int)($noteMaxLength ?? 500);

$status = $feedback['status'] ?? [];
$statusClass = preg_replace('/[^a-z0-9_-]/i', '', (string)($status['class'] ?? ''));
$rating = isset($feedback['rating']) && is_numeric($feedback['rating']) ? max(0, min(5, (int)round($feedback['rating']))) : null;
$companyInitials = $feedback['company']['initials'] ?? '';
$ownerInitials = $currentOwner['initials'] ?? '';

$historyIcon = static function (string $icon): string {
    $icons = [
        'message' => '<path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-5.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
    return $icons[$icon] ?? $icons['clock'];
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e(isset($feedback['code']) ? 'Feedback ' . $feedback['code'] : 'Detalhes do feedback') ?> | Insight CX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feedback-detalhe.css">
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
            <a href="<?= $e($feedbacksUrl ?? '#') ?>" class="nav-link is-active" aria-current="page"><span class="nav-icon">▤</span><span>Feedbacks</span><?php if (isset($pendingFeedbacksCount)): ?><span class="nav-badge"><?= $e($pendingFeedbacksCount) ?></span><?php endif; ?></a>
            <a href="<?= $e($analyticsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">⌁</span><span>Análises</span></a>
            <a href="<?= $e($improvementsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">↗</span><span>Melhorias</span></a>
            <a href="<?= $e($reportsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">▧</span><span>Relatórios</span></a>
            <a href="<?= $e($settingsUrl ?? '#') ?>" class="nav-link"><span class="nav-icon">⚙</span><span>Configurações</span></a>
        </nav>
        <div class="sidebar-bottom">
            <a href="<?= $e($helpUrl ?? '#') ?>" class="help-card"><span class="help-symbol">?</span><strong>Precisa de ajuda?</strong><small>Acesse a central de suporte</small><span class="help-link">Ver central</span></a>
            <a href="<?= $e($logoutUrl ?? '#') ?>" class="logout-link"><span>⇥</span> Sair do sistema</a>
            <div class="sidebar-footer"><span>Uma solução</span><strong>CTI</strong></div>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div class="breadcrumb"><a href="<?= $e($dashboardUrl ?? '#') ?>">Insight CX</a><span>/</span><span>Detalhes do feedback</span></div>
            <div class="topbar-actions">
                <form class="global-search" action="<?= $e($searchUrl ?? '#') ?>" method="get"><span aria-hidden="true">⌕</span><input type="search" name="search" placeholder="Buscar no sistema..." aria-label="Buscar no sistema"><kbd>⌘ K</kbd></form>
                <button class="icon-button" type="button" aria-label="Notificações">♧<?php if (!empty($unreadNotifications)): ?><i></i><?php endif; ?></button>
                <div class="profile"><div class="profile-avatar"><?= $e(mb_strtoupper(mb_substr($user['initials'] ?? '', 0, 2))) ?></div><div class="profile-copy"><strong><?= $e($user['name'] ?? '') ?></strong><small><?= $e($user['role'] ?? '') ?></small></div><span class="profile-chevron">⌄</span></div>
            </div>
        </header>

        <section class="content">
            <a class="back-link" href="<?= $e($feedbacksUrl ?? '#') ?>">← Voltar para feedbacks</a>
            <div class="page-heading">
                <div>
                    <h1><?= isset($feedback['code']) && $feedback['code'] !== '' ? 'Feedback #' . $e($feedback['code']) : 'Detalhes do feedback' ?></h1>
                    <?php if (!empty($feedback['created_at'])): ?><p>Registrado em <?= $e($feedback['created_at']) ?></p><?php endif; ?>
                </div>
                <div class="heading-actions">
                    <?php if (!empty($status['label'])): ?><span class="status-pill <?= $e($statusClass) ?>"><?= $e($status['label']) ?></span><?php endif; ?>
                    <form action="<?= $e($forwardUrl ?? '#') ?>" method="post">
                        <?php if (!empty($csrfToken)): ?><input type="hidden" name="_token" value="<?= $e($csrfToken) ?>"><?php endif; ?>
                        <input type="hidden" name="feedback_id" value="<?= $e($feedback['id'] ?? '') ?>">
                        <button type="submit" class="primary-button"><span>→</span> Encaminhar</button>
                    </form>
                </div>
            </div>

            <div class="detail-layout">
                <div class="left-column">
                    <section class="card feedback-card">
                        <div class="company-header">
                            <div class="company-avatar"><?= $e($companyInitials) ?></div>
                            <div class="company-copy">
                                <span class="eyebrow">EMPRESA</span>
                                <h2><?= $e($feedback['company']['name'] ?? '') ?></h2>
                                <p><?= $e($feedback['company']['service'] ?? '') ?><?= !empty($feedback['company']['plan']) ? ' · ' . $e($feedback['company']['plan']) : '' ?></p>
                            </div>
                            <a class="outline-button" href="<?= $e($clientUrl ?? '#') ?>">Ver cliente <span>→</span></a>
                        </div>

                        <div class="metadata-grid">
                            <div class="metadata-item"><span>Categoria</span><strong><?php if (!empty($feedback['category'])): ?><span class="category-pill"><?= $e($feedback['category']) ?></span><?php endif; ?></strong></div>
                            <div class="metadata-item"><span>Avaliação</span><strong class="rating-value"><?php if ($rating !== null): ?><?= $e(number_format((float)($feedback['rating']), 1, ',', '')) ?> <span class="stars" aria-label="<?= $e($rating) ?> de 5 estrelas"><?php for ($i=1; $i<=5; $i++): ?><span class="<?= $i <= $rating ? 'filled' : '' ?>">★</span><?php endfor; ?></span><?php endif; ?></strong></div>
                            <div class="metadata-item"><span>Data de manifestação</span><strong><?= $e($feedback['manifestation_date'] ?? '') ?></strong></div>
                            <div class="metadata-item"><span>Canal de entrada</span><strong><?= $e($feedback['channel'] ?? '') ?></strong></div>
                        </div>

                        <div class="description-block">
                            <h3 class="eyebrow">DESCRIÇÃO DA MANIFESTAÇÃO</h3>
                            <?php if (!empty($feedback['description'])): ?><p><?= nl2br($e($feedback['description'])) ?></p><?php else: ?><p class="muted-empty">Nenhuma descrição informada.</p><?php endif; ?>
                        </div>
                    </section>

                    <section class="card notes-card">
                        <div class="notes-heading"><div><h2>Observações internas</h2><p>Visível apenas para usuários autorizados</p></div><span class="lock-icon" aria-label="Conteúdo privado">♙</span></div>
                        <form action="<?= $e($saveNoteUrl ?? '#') ?>" method="post" class="note-form">
                            <?php if (!empty($csrfToken)): ?><input type="hidden" name="_token" value="<?= $e($csrfToken) ?>"><?php endif; ?>
                            <input type="hidden" name="feedback_id" value="<?= $e($feedback['id'] ?? '') ?>">
                            <textarea name="content" maxlength="<?= $e($noteMaxLength) ?>" placeholder="Adicione uma observação sobre este feedback..." required data-note-input></textarea>
                            <div class="note-form-footer"><span><b data-note-count>0</b>/<?= $e($noteMaxLength) ?> caracteres</span><button type="submit" class="save-button" disabled>Salvar observação</button></div>
                        </form>
                        <?php if ($internalNotes): ?>
                            <div class="notes-list">
                                <?php foreach ($internalNotes as $note): ?>
                                    <article class="note-item"><div class="note-item-heading"><strong><?= $e($note['author'] ?? '') ?></strong><time><?= $e($note['created_at'] ?? '') ?></time></div><p><?= nl2br($e($note['content'] ?? '')) ?></p></article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

                <aside class="card history-card">
                    <div class="history-content">
                        <h2>Histórico de atualizações</h2>
                        <p class="section-subtitle">Acompanhamento da manifestação</p>
                        <?php if ($history): ?>
                            <ol class="timeline">
                                <?php foreach ($history as $event): ?>
                                    <li class="timeline-item <?= !empty($event['done']) ? 'is-done' : '' ?>">
                                        <span class="timeline-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $historyIcon((string)($event['icon'] ?? 'clock')) ?></svg></span>
                                        <div class="timeline-copy"><strong><?= $e($event['title'] ?? '') ?></strong><p><?= $e($event['description'] ?? '') ?></p><?php if (!empty($event['date'])): ?><time><?= $e($event['date']) ?></time><?php endif; ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <div class="history-empty">Nenhuma atualização registrada.</div>
                        <?php endif; ?>
                    </div>
                    <div class="owner-section">
                        <span class="eyebrow">RESPONSÁVEL ATUAL</span>
                        <?php if (!empty($currentOwner['name'])): ?>
                            <div class="owner-row"><div class="owner-avatar"><?= $e($ownerInitials) ?></div><div class="owner-copy"><strong><?= $e($currentOwner['name']) ?></strong><small><?= $e($currentOwner['role'] ?? '') ?></small></div><a href="<?= $e($editOwnerUrl ?? '#') ?>" class="edit-owner" aria-label="Editar responsável">／</a></div>
                        <?php else: ?>
                            <p class="muted-empty">Nenhum responsável atribuído.</p>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </section>
    </main>
</div>
<script>
(() => {
    const field = document.querySelector('[data-note-input]');
    const count = document.querySelector('[data-note-count]');
    const save = document.querySelector('.save-button');
    if (!field || !count || !save) return;
    const update = () => {
        count.textContent = field.value.length;
        save.disabled = field.value.trim().length === 0;
    };
    field.addEventListener('input', update);
    update();
})();
</script>
</body>
</html>
