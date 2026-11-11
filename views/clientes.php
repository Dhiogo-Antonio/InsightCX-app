<?php
declare(strict_types=1);

$e = static fn($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
$clientStats = $clientStats ?? ['active' => 0, 'contracted' => 0, 'satisfaction' => '—'];
$clients = $clients ?? [];
$clientServices = $clientServices ?? [];
$clientPagination = $clientPagination ?? ['current' => 1, 'last' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
$clientFilters = $clientFilters ?? ['search' => '', 'service' => '', 'status' => ''];
$clientUrl = static function(array $changes = []): string {
    $query = array_merge($_GET, ['page' => 'clientes'], $changes);
    foreach ($query as $key => $value) if ($value === '' || $value === null) unset($query[$key]);
    return 'index.php?' . http_build_query($query);
};
?>
<main class="clients-page">
    <section class="clients-heading">
        <div><span class="section-eyebrow">GESTÃO DA BASE</span><h1>Clientes</h1><p>Gerencie as empresas e acompanhe seu histórico de experiência.</p></div>
        <span class="clients-count"><?= number_format($clientPagination['total'], 0, ',', '.') ?> cadastrados</span>
    </section>

    <?php if (!empty($clientDataError)): ?>
        <div class="clients-notice" role="status"><?= $e($clientDataError) ?></div>
    <?php endif; ?>

    <section class="client-stats" aria-label="Indicadores de clientes">
        <article><span class="client-stat-icon blue">▦</span><div><small>Empresas ativas</small><strong><?= number_format($clientStats['active'], 0, ',', '.') ?></strong></div><span class="client-stat-note">Total cadastrado</span></article>
        <article><span class="client-stat-icon cyan">◇</span><div><small>Serviços contratados</small><strong><?= number_format($clientStats['contracted'], 0, ',', '.') ?></strong></div><span class="client-stat-note">Por empresa</span></article>
        <article><span class="client-stat-icon teal">↗</span><div><small>Satisfação média</small><strong><?= $e($clientStats['satisfaction']) ?></strong></div><span class="client-stat-note">Escala de 0 a 5</span></article>
    </section>

    <section class="clients-card">
        <form class="clients-filters" method="get" action="index.php">
            <input type="hidden" name="page" value="clientes">
            <label class="client-search"><span aria-hidden="true">⌕</span><input type="search" name="q" value="<?= $e($clientFilters['search']) ?>" placeholder="Buscar por empresa, CNPJ ou e-mail..." aria-label="Buscar clientes"></label>
            <select name="service" aria-label="Filtrar por serviço"><option value="">Todos os serviços</option><?php foreach ($clientServices as $service): ?><option value="<?= (int)$service['id'] ?>" <?= (string)$clientFilters['service'] === (string)$service['id'] ? 'selected' : '' ?>><?= $e($service['name']) ?></option><?php endforeach; ?></select>
            <select name="status" aria-label="Filtrar por status"><option value="">Todos os status</option><option value="active" <?= $clientFilters['status'] === 'active' ? 'selected' : '' ?>>Ativo</option><option value="inactive" <?= $clientFilters['status'] === 'inactive' ? 'selected' : '' ?>>Inativo</option></select>
            <button type="submit">Filtrar</button>
        </form>

        <div class="clients-table-wrap"><table class="clients-table"><thead><tr><th>EMPRESA</th><th>CNPJ</th><th>CONTATO</th><th>SERVIÇO CONTRATADO</th><th>STATUS</th><th>FEEDBACKS</th></tr></thead><tbody>
        <?php if (!$clients): ?><tr><td colspan="6" class="clients-empty"><span>▦</span><strong>Nenhum cliente encontrado</strong><small>Altere os filtros ou aguarde o cadastro de clientes.</small></td></tr>
        <?php else: foreach ($clients as $client): $initial = mb_strtoupper(mb_substr((string)$client['company_name'], 0, 1, 'UTF-8'), 'UTF-8'); $isActive = $client['status'] === 'active'; ?>
            <tr><td><div class="client-company"><span><?= $e($initial) ?></span><div><strong><?= $e($client['company_name']) ?></strong><small>Cliente desde <?= $e(date('Y', strtotime((string)$client['created_at']))) ?></small></div></div></td><td><?= $e($client['cnpj']) ?></td><td><div class="client-contact"><span><?= $e($client['email']) ?></span><small><?= $e($client['phone'] ?: '—') ?></small></div></td><td><span class="client-service"><?= $e($client['service_name'] ?: 'Não contratado') ?></span></td><td><span class="client-status <?= $isActive ? 'active' : 'inactive' ?>"><?= $isActive ? 'Ativo' : 'Inativo' ?></span></td><td><a class="client-feedback-link" href="index.php?page=feedbacks&client=<?= (int)$client['id'] ?>">Ver feedbacks <span>→</span></a></td></tr>
        <?php endforeach; endif; ?>
        </tbody></table></div>
        <footer class="clients-footer"><span>Mostrando <?= number_format($clientPagination['from'], 0, ',', '.') ?> a <?= number_format($clientPagination['to'], 0, ',', '.') ?> de <?= number_format($clientPagination['total'], 0, ',', '.') ?> clientes</span>
        <?php if ($clientPagination['last'] > 1): ?><nav class="clients-pagination" aria-label="Paginação de clientes"><?php if ($clientPagination['current'] > 1): ?><a href="<?= $e($clientUrl(['p' => $clientPagination['current'] - 1])) ?>" aria-label="Página anterior">‹</a><?php endif; ?><?php for ($p = 1; $p <= $clientPagination['last']; $p++): ?><a class="<?= $p === $clientPagination['current'] ? 'current' : '' ?>" href="<?= $e($clientUrl(['p' => $p])) ?>"><?= $p ?></a><?php endfor; ?><?php if ($clientPagination['current'] < $clientPagination['last']): ?><a href="<?= $e($clientUrl(['p' => $clientPagination['current'] + 1])) ?>" aria-label="Próxima página">›</a><?php endif; ?></nav><?php endif; ?>
        </footer>
    </section>
</main>
