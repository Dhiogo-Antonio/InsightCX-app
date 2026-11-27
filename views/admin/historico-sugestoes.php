<?php
session_start();

$paginaAtual = basename($_SERVER['PHP_SELF']);

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

// Requisição à API
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

// Consultar histórico de feedbacks respondidos
$resultado = requisicaoApi(
    $urlApi . '/feedbacks/historico-respondidos',
    $token
);

// Verificar autenticação
if ($resultado['status'] === 401) {
    $_SESSION = [];
    session_destroy();

    header('Location: ../../index.php');
    exit;
}

$erro = $resultado['status'] !== 200;

$feedbacks = !$erro && is_array($resultado['dados'])
    ? $resultado['dados']
    : [];

// Indicadores
$total = 0;
$automaticos = 0;
$manuais = 0;

foreach ($feedbacks as $feedback) {
    foreach (($feedback['analises_chatbot'] ?? []) as $analise) {

        $total++;

        if ($analise['tipo_atendimento'] === 'automatico') {
            $automaticos++;
        } elseif ($analise['tipo_atendimento'] === 'manual') {
            $manuais++;
        }
    }
}

// Ordenar feedbacks pela data de resposta
usort($feedbacks, function ($a, $b) {

    $dataA = $a['analises_chatbot'][0]['resposta_enviada_em'] ?? '';
    $dataB = $b['analises_chatbot'][0]['resposta_enviada_em'] ?? '';

    return strtotime($dataB) <=> strtotime($dataA);
});

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Histórico de feedbacks | Insight CX</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/historico-feedbacks.css">
</head>

<body>

    <aside class="sidebar">

        <a href="dashboard.php" class="logo">
            <span class="logo-icone"></span>
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

            <a href="dashboard.php"
                class="<?= $paginaAtual === 'dashboard.php' ? 'ativo' : '' ?>">
                <i data-lucide="layout-dashboard"></i>
                Dashboard
            </a>

            <a href="feedbacks.php"
                class="<?= $paginaAtual === 'feedbacks.php' ? 'ativo' : '' ?>">
                <i data-lucide="message-square-text"></i>
                Feedbacks
            </a>

            <a href="historico-sugestoes.php"
                class="<?= $paginaAtual === 'historico-sugestoes.php' ? 'ativo' : '' ?>">
                <i data-lucide="history"></i>
                Histórico de feedbacks
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
                <span class="subtitulo">GERENCIAMENTO</span>

                <h1>Histórico de feedbacks</h1>

                <p>
                    Acompanhe os feedbacks que já receberam respostas.
                </p>
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
                    <span>Total de respostas</span>
                    <h2><?= $erro ? '—' : $total ?></h2>
                    <small>Respostas enviadas aos clientes</small>
                </div>

            </article>

            <article class="card-indicador">

                <div class="indicador-icone azul">✦</div>

                <div>
                    <span>Automáticas</span>
                    <h2><?= $erro ? '—' : $automaticos ?></h2>
                    <small>Geradas pela inteligência artificial</small>
                </div>

            </article>

            <article class="card-indicador">

                <div class="indicador-icone verde">✓</div>

                <div>
                    <span>Manuais</span>
                    <h2><?= $erro ? '—' : $manuais ?></h2>
                    <small>Respondidas pelo administrador</small>
                </div>

            </article>

        </section>

        <!-- Histórico -->

        <section class="painel painel-recentes">

            <div class="painel-titulo">

                <div>
                    <h2>Feedbacks respondidos</h2>

                    <p>
                        Histórico organizado pela data de envio da resposta.
                    </p>
                </div>

            </div>

            <?php if ($erro): ?>

                <p class="mensagem-erro">
                    Não foi possível carregar o histórico de feedbacks.
                </p>

            <?php elseif (empty($feedbacks)): ?>

                <p class="sem-dados">
                    Nenhum feedback respondido foi encontrado.
                </p>

            <?php else: ?>

                <div class="tabela-container">

                    <table>

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Assunto</th>
                                <th>Categoria</th>
                                <th>Prioridade</th>
                                <th>Atendimento</th>
                                <th>Data da resposta</th>
                                <th>Resposta</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($feedbacks as $feedback): ?>

                                <?php foreach (($feedback['analises_chatbot'] ?? []) as $analise): ?>

                                    <?php

                                    $tipoAtendimento = $analise['tipo_atendimento'] ?? '';

                                    $nomeAtendimento = $tipoAtendimento === 'automatico'
                                        ? 'Automático'
                                        : 'Manual';

                                    $resposta = $tipoAtendimento === 'manual'
                                        ? ($analise['resposta_administrador'] ?? '')
                                        : ($analise['resposta_gerada'] ?? '');

                                    $cliente = $feedback['usuario']['nome'] ?? 'Não informado';

                                    $categoria = $feedback['categoria'] ?? '';

                                    $prioridade = $feedback['prioridade'] ?? '';

                                    $dataResposta = !empty($analise['resposta_enviada_em'])
                                        ? date('d/m/Y H:i', strtotime($analise['resposta_enviada_em']))
                                        : '—';

                                    ?>

                                    <tr>

                                        <td>
                                            #<?= escapar($feedback['id'] ?? '') ?>
                                        </td>

                                        <td>
                                            <?= escapar($cliente) ?>
                                        </td>

                                        <td>
                                            <?= escapar($feedback['assunto'] ?? 'Sem assunto') ?>
                                        </td>

                                        <td>
                                            <?= escapar(ucfirst($categoria)) ?>
                                        </td>

                                        <td>
                                            <span class="categoria <?= escapar($prioridade) ?>">
                                                <?= escapar(ucfirst($prioridade)) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="categoria <?= $tipoAtendimento === 'automatico' ? 'verde' : 'azul' ?>">
                                                <?= escapar($nomeAtendimento) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= escapar($dataResposta) ?>
                                        </td>

                                        <td>
                                            <button
                                                type="button"
                                                class="botao-resposta"
                                                onclick="visualizarResposta(this)"
                                                data-resposta="<?= escapar($resposta) ?>">
                                                Visualizar
                                            </button>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <!-- Modal de resposta -->

    <div id="modalResposta" class="modal-resposta">

        <div class="modal-conteudo">

            <button
                type="button"
                class="modal-fechar"
                onclick="fecharResposta()">
                &times;
            </button>

            <h2>Resposta enviada ao cliente</h2>

            <p id="textoResposta"></p>

        </div>

    </div>

    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        lucide.createIcons();

        function visualizarResposta(botao) {

            const resposta = botao.dataset.resposta;

            document.getElementById('textoResposta').textContent = resposta;

            document.getElementById('modalResposta').style.display = 'flex';
        }

        function fecharResposta() {

            document.getElementById('modalResposta').style.display = 'none';
        }

        document.getElementById('modalResposta').addEventListener('click', function(event) {

            if (event.target === this) {
                fecharResposta();
            }

        });
    </script>

</body>

</html>