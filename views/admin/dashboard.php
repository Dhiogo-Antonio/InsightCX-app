
<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['token'])) {
    header('Location: ../../index.php');
    exit;
}

$usuario = $_SESSION['usuario'];

$nome = is_array($usuario)
    ? ($usuario['nome'] ?? 'Administrador')
    : ($usuario->nome ?? 'Administrador');

$tipo = is_array($usuario)
    ? ($usuario['tipo'] ?? '')
    : ($usuario->tipo ?? '');

$token = $_SESSION['token'];
$urlApi = 'http://127.0.0.1:8000/api';

if ($tipo !== 'admin') {
    header('Location: ../cliente/index.php');
    exit;
}

function requisicaoApi($url, $token, $metodo = 'GET')
{
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_TIMEOUT => 15
    ]);

    $resposta = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    return [
        'status' => $status,
        'dados' => json_decode($resposta ?: '', true)
    ];
}

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// Encerrar sessão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sair'])) {
    requisicaoApi($urlApi . '/logout', $token, 'POST');

    $_SESSION = [];
    session_destroy();

    header('Location: ../../index.php');
    exit;
}

// Consultar dados da API
$resultadoFeedbacks = requisicaoApi($urlApi . '/feedbacks', $token);
$resultadoSugestoes = requisicaoApi($urlApi . '/sugestoes', $token);

// Verificar autenticação
if (
    $resultadoFeedbacks['status'] === 401 ||
    $resultadoSugestoes['status'] === 401
) {
    $_SESSION = [];
    session_destroy();

    header('Location: ../../index.php');
    exit;
}

$erroFeedbacks = $resultadoFeedbacks['status'] !== 200;
$erroSugestoes = $resultadoSugestoes['status'] !== 200;

$feedbacks = !$erroFeedbacks && is_array($resultadoFeedbacks['dados'])
    ? $resultadoFeedbacks['dados']
    : [];

$sugestoes = !$erroSugestoes && is_array($resultadoSugestoes['dados'])
    ? $resultadoSugestoes['dados']
    : [];

// Indicadores
$totalFeedbacks = count($feedbacks);
$totalSugestoes = count($sugestoes);

$elogios = 0;
$reclamacoes = 0;
$pendentes = 0;

foreach ($feedbacks as $feedback) {
    $categoria = strtolower($feedback['categoria'] ?? '');
    $status = strtolower($feedback['status'] ?? '');

    if ($categoria === 'elogio') {
        $elogios++;
    }

    if ($categoria === 'reclamacao') {
        $reclamacoes++;
    }

    if ($status === 'pendente') {
        $pendentes++;
    }
}

// Dados mensais dos últimos seis meses
$meses = [];
$inicio = new DateTime('first day of this month');
$inicio->modify('-5 months');

$nomesMeses = [
    1 => 'Jan',
    2 => 'Fev',
    3 => 'Mar',
    4 => 'Abr',
    5 => 'Mai',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Ago',
    9 => 'Set',
    10 => 'Out',
    11 => 'Nov',
    12 => 'Dez'
];

for ($i = 0; $i < 6; $i++) {
    $dataMes = clone $inicio;
    $dataMes->modify("+{$i} months");

    $chave = $dataMes->format('Y-m');

    $meses[$chave] = [
        'nome' => $nomesMeses[(int) $dataMes->format('n')],
        'elogios' => 0,
        'reclamacoes' => 0
    ];
}

foreach ($feedbacks as $feedback) {
    if (empty($feedback['created_at'])) {
        continue;
    }

    $data = strtotime($feedback['created_at']);

    if (!$data) {
        continue;
    }

    $chave = date('Y-m', $data);
    $categoria = strtolower($feedback['categoria'] ?? '');

    if (!isset($meses[$chave])) {
        continue;
    }

    if ($categoria === 'elogio') {
        $meses[$chave]['elogios']++;
    }

    if ($categoria === 'reclamacao') {
        $meses[$chave]['reclamacoes']++;
    }
}

$maiorValor = 1;

foreach ($meses as $mes) {
    $maiorValor = max(
        $maiorValor,
        $mes['elogios'],
        $mes['reclamacoes']
    );
}

// Situação das sugestões
$statusSugestoes = [
    'pendente' => 0,
    'em andamento' => 0,
    'concluida' => 0
];

foreach ($sugestoes as $sugestao) {
    $status = strtolower($sugestao['status'] ?? '');

    if (isset($statusSugestoes[$status])) {
        $statusSugestoes[$status]++;
    }
}

$percentualPendente = $totalSugestoes > 0
    ? ($statusSugestoes['pendente'] / $totalSugestoes) * 100
    : 0;

$percentualAndamento = $totalSugestoes > 0
    ? ($statusSugestoes['em andamento'] / $totalSugestoes) * 100
    : 0;

$percentualConcluida = $totalSugestoes > 0
    ? ($statusSugestoes['concluida'] / $totalSugestoes) * 100
    : 0;

$graficoRosca = "conic-gradient(
    #f5b942 0% {$percentualPendente}%,
    #438cf5 {$percentualPendente}% " .
    ($percentualPendente + $percentualAndamento) . "%,
    #35c99a " .
    ($percentualPendente + $percentualAndamento) . "% 100%
)";

// Ordenar feedbacks por data
usort($feedbacks, function ($a, $b) {
    return strtotime($b['created_at'] ?? '') <=> strtotime($a['created_at'] ?? '');
});

$feedbacksRecentes = array_slice($feedbacks, 0, 5);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Insight CX</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>

<body>

    <aside class="sidebar">

        <a href="dashboard.php" class="logo">
            <span class="logo-icone">▥</span>
            Insight <span class="logo-destaque">CX</span>
        </a>

        <div class="perfil">
            <div class="avatar">
                <?= escapar(strtoupper(substr($nome, 0, 1))) ?>
            </div>

            <div class="perfil-info">
                <strong><?= escapar($nome) ?></strong>
                <span>Administrador</span>
            </div>
        </div>

        <span class="menu-titulo">MENU PRINCIPAL</span>

        <nav class="menu">
            <a href="dashboard.php" class="ativo">
                <span>▦</span>
                Dashboard
            </a>

            <a href="sugestoes.php">
                <span>✧</span>
                Sugestões
            </a>
        </nav>

        <form method="POST" class="sair">
            <button type="submit" name="sair">
                <span>↪</span>
                Sair do sistema
            </button>
        </form>

    </aside>

    <main class="conteudo">

        <header class="topo">

            <div>
                <span class="subtitulo">PAINEL ADMINISTRATIVO</span>
                <h1>Dashboard</h1>
                <p>Acompanhe a experiência dos clientes e os resultados do CRM.</p>
            </div>

            <div class="data-atual">
                <span>◷</span>
                <?= date('d/m/Y') ?>
            </div>

        </header>

        <!-- Indicadores -->

        <section class="indicadores">

            <article class="card-indicador">
                <div class="indicador-icone roxo">▤</div>

                <div>
                    <span>Total de feedbacks</span>
                    <h2><?= $erroFeedbacks ? '—' : $totalFeedbacks ?></h2>
                    <small>Registros recebidos</small>
                </div>
            </article>

            <article class="card-indicador">
                <div class="indicador-icone verde">↑</div>

                <div>
                    <span>Elogios</span>
                    <h2><?= $erroFeedbacks ? '—' : $elogios ?></h2>
                    <small>Feedbacks positivos</small>
                </div>
            </article>

            <article class="card-indicador">
                <div class="indicador-icone vermelho">↓</div>

                <div>
                    <span>Reclamações</span>
                    <h2><?= $erroFeedbacks ? '—' : $reclamacoes ?></h2>
                    <small>Feedbacks negativos</small>
                </div>
            </article>

            <article class="card-indicador">
                <div class="indicador-icone azul">✧</div>

                <div>
                    <span>Sugestões</span>
                    <h2><?= $erroSugestoes ? '—' : $totalSugestoes ?></h2>
                    <small>Melhorias cadastradas</small>
                </div>
            </article>

        </section>

        <!-- Gráficos -->

        <section class="graficos">

            <article class="painel painel-barras">

                <div class="painel-titulo">
                    <div>
                        <h2>Comparativo de feedbacks</h2>
                        <p>Elogios e reclamações nos últimos seis meses.</p>
                    </div>

                    <div class="legenda">
                        <span><i class="ponto verde"></i> Elogios</span>
                        <span><i class="ponto vermelho"></i> Reclamações</span>
                    </div>
                </div>

                <?php if ($erroFeedbacks): ?>

                    <p class="mensagem-erro">
                        Não foi possível carregar os dados dos feedbacks.
                    </p>

                <?php else: ?>

                    <div class="grafico-barras">

                        <?php foreach ($meses as $mes): ?>

                            <?php
                            $alturaElogios = ($mes['elogios'] / $maiorValor) * 100;
                            $alturaReclamacoes = ($mes['reclamacoes'] / $maiorValor) * 100;
                            ?>

                            <div class="grupo-mes">

                                <div class="barras">

                                    <div class="barra-coluna">
                                        <span class="valor-barra">
                                            <?= $mes['elogios'] ?>
                                        </span>

                                        <div
                                            class="barra verde"
                                            style="height: <?= max(2, $alturaElogios) ?>%">
                                        </div>
                                    </div>

                                    <div class="barra-coluna">
                                        <span class="valor-barra">
                                            <?= $mes['reclamacoes'] ?>
                                        </span>

                                        <div
                                            class="barra vermelho"
                                            style="height: <?= max(2, $alturaReclamacoes) ?>%">
                                        </div>
                                    </div>

                                </div>

                                <span class="nome-mes"><?= escapar($mes['nome']) ?></span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <?php if ($totalFeedbacks === 0): ?>
                        <p class="sem-dados">
                            Ainda não existem feedbacks cadastrados.
                        </p>
                    <?php endif; ?>

                <?php endif; ?>

            </article>

            <article class="painel painel-sugestoes">

                <div class="painel-titulo">
                    <div>
                        <h2>Status das sugestões</h2>
                        <p>Acompanhamento das melhorias.</p>
                    </div>
                </div>

                <?php if ($erroSugestoes): ?>

                    <p class="mensagem-erro">
                        Não foi possível carregar as sugestões.
                    </p>

                <?php else: ?>

                    <div class="grafico-rosca-area">

                        <div class="grafico-rosca"
                            style="background: <?= escapar($graficoRosca) ?>">

                            <div class="rosca-centro">
                                <strong><?= $totalSugestoes ?></strong>
                                <span>Sugestões</span>
                            </div>

                        </div>

                        <div class="legenda-rosca">

                            <div>
                                <i class="ponto amarelo"></i>
                                <span>
                                    Pendente
                                    <strong><?= $statusSugestoes['pendente'] ?></strong>
                                </span>
                            </div>

                            <div>
                                <i class="ponto azul"></i>
                                <span>
                                    Em andamento
                                    <strong><?= $statusSugestoes['em andamento'] ?></strong>
                                </span>
                            </div>

                            <div>
                                <i class="ponto verde"></i>
                                <span>
                                    Concluída
                                    <strong><?= $statusSugestoes['concluida'] ?></strong>
                                </span>
                            </div>

                        </div>

                    </div>

                    <?php if ($totalSugestoes === 0): ?>
                        <p class="sem-dados">
                            Nenhuma sugestão cadastrada.
                        </p>
                    <?php endif; ?>

                <?php endif; ?>

            </article>

        </section>

        <!-- Feedbacks recentes -->

        <section class="painel painel-recentes">

            <div class="painel-titulo">
                <div>
                    <h2>Feedbacks recentes</h2>
                    <p>Últimos registros recebidos pelo sistema.</p>
                </div>
            </div>

            <?php if ($erroFeedbacks): ?>

                <p class="mensagem-erro">
                    Não foi possível carregar os feedbacks.
                </p>

            <?php elseif (empty($feedbacksRecentes)): ?>

                <p class="sem-dados">
                    Nenhum feedback foi cadastrado até o momento.
                </p>

            <?php else: ?>

                <div class="tabela-container">

                    <table>
                        <thead>
                            <tr>
                                <th>Assunto</th>
                                <th>Serviço</th>
                                <th>Categoria</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($feedbacksRecentes as $feedback): ?>

                                <?php
                                $categoria = strtolower($feedback['categoria'] ?? '');
                                $status = strtolower($feedback['status'] ?? '');

                                $nomeCategoria = $categoria === 'elogio'
                                    ? 'Elogio'
                                    : ($categoria === 'reclamacao' ? 'Reclamação' : 'Não informado');

                                $nomeStatus = [
                                    'pendente' => 'Pendente',
                                    'em analise' => 'Em análise',
                                    'resolvido' => 'Resolvido'
                                ][$status] ?? 'Não informado';

                                $data = !empty($feedback['created_at'])
                                    ? date('d/m/Y', strtotime($feedback['created_at']))
                                    : '—';
                                ?>

                                <tr>
                                    <td><?= escapar($feedback['assunto'] ?? 'Sem assunto') ?></td>

                                    <td>
                                        <?= escapar($feedback['servico']['nome'] ?? 'Não informado') ?>
                                    </td>

                                    <td>
                                        <span class="categoria <?= escapar($categoria) ?>">
                                            <?= escapar($nomeCategoria) ?>
                                        </span>
                                    </td>

                                    <td><?= escapar($nomeStatus) ?></td>

                                    <td><?= escapar($data) ?></td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

</body>
</html>
