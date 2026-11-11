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

// Dados de clientes compartilhados entre a listagem e os filtros de feedbacks.
$clients = [];
$clientServices = [];
$clientStats = ['active' => 0, 'contracted' => 0, 'satisfaction' => '—'];
$clientPagination = ['current' => 1, 'last' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
$clientFilters = [
    'search' => trim((string)($_GET['q'] ?? '')),
    'service' => trim((string)($_GET['service'] ?? '')),
    'status' => in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? (string)$_GET['status'] : '',
];
$clientDataError = null;

if (in_array($page, ['clientes', 'feedbacks'], true)) {
    try {
        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbName = getenv('DB_NAME') ?: 'insight_cx';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $clientServices = $pdo->query('SELECT id, name FROM services WHERE active = 1 ORDER BY name')->fetchAll();

        if ($page === 'clientes') {
            $filters = [];
            $params = [];
            if ($clientFilters['search'] !== '') {
                $filters[] = '(c.company_name LIKE :company OR c.cnpj LIKE :cnpj OR c.email LIKE :email)';
                $term = '%' . $clientFilters['search'] . '%';
                $params += ['company' => $term, 'cnpj' => $term, 'email' => $term];
            }
            if ($clientFilters['service'] !== '' && ctype_digit($clientFilters['service'])) {
                $filters[] = 'c.service_id = :service';
                $params['service'] = (int)$clientFilters['service'];
            }
            if ($clientFilters['status'] !== '') {
                $filters[] = 'c.status = :status';
                $params['status'] = $clientFilters['status'];
            }
            $where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
            $count = $pdo->prepare("SELECT COUNT(*) FROM clients c{$where}");
            $count->execute($params);
            $total = (int)$count->fetchColumn();
            $perPage = 5;
            $lastPage = max(1, (int)ceil($total / $perPage));
            $currentPage = min(max(1, (int)($_GET['p'] ?? 1)), $lastPage);
            $offset = ($currentPage - 1) * $perPage;
            $list = $pdo->prepare("SELECT c.id, c.company_name, c.cnpj, c.email, c.phone, c.status, c.created_at, s.name AS service_name FROM clients c LEFT JOIN services s ON s.id = c.service_id{$where} ORDER BY c.company_name LIMIT :limit OFFSET :offset");
            foreach ($params as $key => $value) $list->bindValue(':' . $key, $value);
            $list->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $list->bindValue(':offset', $offset, PDO::PARAM_INT);
            $list->execute();
            $clients = $list->fetchAll();
            $clientPagination = ['current' => $currentPage, 'last' => $lastPage, 'total' => $total, 'from' => $total ? $offset + 1 : 0, 'to' => min($offset + $perPage, $total)];
            $clientStats['active'] = (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE status = 'active'")->fetchColumn();
            $clientStats['contracted'] = (int)$pdo->query('SELECT COUNT(*) FROM clients WHERE service_id IS NOT NULL')->fetchColumn();
            $average = $pdo->query('SELECT AVG(score) FROM feedbacks')->fetchColumn();
            $clientStats['satisfaction'] = $average !== null ? number_format((float)$average, 1, ',', '.') : '—';
        } else {
            $clients = $pdo->query('SELECT id, company_name AS name FROM clients ORDER BY company_name')->fetchAll();
        }
    } catch (Throwable $exception) {
        // Mantém a página utilizável com estado vazio enquanto o banco não estiver configurado.
        $clientDataError = 'Não foi possível carregar os dados de clientes. Configure o banco MySQL (DB_HOST, DB_NAME, DB_USER e DB_PASS) e importe o schema do projeto.';
    }
}

// Exemplo de estrutura de conexão, configure as credenciais fora do repositório:
// $pdo = new PDO($dsn, $dbUser, $dbPassword, [
//     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
// ]);
