
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

/**
 * Comunicação com a API.
 */
function requisicaoApi($url, $token, $metodo = 'GET', $corpo = null)
{
    $curl = curl_init();

    $cabecalhos = [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ];

    if ($corpo !== null) {
        $cabecalhos[] = 'Content-Type: application/json';
    }

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => $cabecalhos,
        CURLOPT_POSTFIELDS => $corpo !== null
            ? json_encode($corpo)
            : null,
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

/**
 * Proteger a saída de dados.
 */
function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// Encerrar sessão.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sair'])) {

    requisicaoApi($urlApi . '/logout', $token, 'POST');

    $_SESSION = [];
    session_destroy();

    header('Location: ../../index.php');
    exit;
}

// Mensagens da página.
$mensagem = '';
$tipoMensagem = '';

// Aprovar resposta gerada pela IA.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aprovar'])) {

    $idAnalise = filter_input(INPUT_POST, 'id_analise', FILTER_VALIDATE_INT);

    if (!$idAnalise) {

        $mensagem = 'Análise inválida.';
        $tipoMensagem = 'erro';

    } else {

        $resultadoAprovacao = requisicaoApi(
            $urlApi . '/analises-chatbot/' . $idAnalise . '/aprovar',
            $token,
            'PUT'
        );

        if ($resultadoAprovacao['status'] === 200) {

            $_SESSION['mensagem_feedback'] = 'Resposta da IA aprovada com sucesso!';
            $_SESSION['tipo_mensagem_feedback'] = 'sucesso';

            header('Location: feedbacks.php');
            exit;
        }

        $mensagem = $resultadoAprovacao['dados']['message']
            ?? 'Não foi possível aprovar a resposta.';

        $tipoMensagem = 'erro';
    }
}

// Recusar resposta gerada pela IA.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recusar'])) {

    $idAnalise = filter_input(INPUT_POST, 'id_analise', FILTER_VALIDATE_INT);
    $motivo = trim($_POST['motivo_recusa'] ?? '');

    if (!$idAnalise) {

        $mensagem = 'Análise inválida.';
        $tipoMensagem = 'erro';

    } else {

        $resultadoRecusa = requisicaoApi(
            $urlApi . '/analises-chatbot/' . $idAnalise . '/recusar',
            $token,
            'PUT',
            [
                'motivo_recusa' => $motivo
            ]
        );

        if ($resultadoRecusa['status'] === 200) {

            $_SESSION['mensagem_feedback'] = 'Resposta da IA recusada. Escreva uma resposta manual.';
            $_SESSION['tipo_mensagem_feedback'] = 'sucesso';

            header('Location: feedbacks.php');
            exit;
        }

        $mensagem = $resultadoRecusa['dados']['message']
            ?? 'Não foi possível recusar a resposta.';

        $tipoMensagem = 'erro';
    }
}

// Registrar resposta manual do administrador.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder'])) {

    $idAnalise = filter_input(INPUT_POST, 'id_analise', FILTER_VALIDATE_INT);
    $resposta = trim($_POST['resposta_administrador'] ?? '');

    if (!$idAnalise || strlen($resposta) < 3) {

        $mensagem = 'Informe uma resposta válida.';
        $tipoMensagem = 'erro';

    } else {

        $resultadoResposta = requisicaoApi(
            $urlApi . '/analises-chatbot/' . $idAnalise . '/responder',
            $token,
            'PUT',
            [
                'resposta_administrador' => $resposta
            ]
        );

        if ($resultadoResposta['status'] === 200) {

            $_SESSION['mensagem_feedback'] = 'Resposta registrada com sucesso!';
            $_SESSION['tipo_mensagem_feedback'] = 'sucesso';

            header('Location: feedbacks.php');
            exit;

        } else {

            $mensagem = $resultadoResposta['dados']['message']
                ?? 'Não foi possível registrar a resposta.';

            $tipoMensagem = 'erro';
        }
    }
}

// Recuperar mensagem após redirecionamento.
if (isset($_SESSION['mensagem_feedback'])) {

    $mensagem = $_SESSION['mensagem_feedback'];
    $tipoMensagem = $_SESSION['tipo_mensagem_feedback'] ?? 'sucesso';

    unset($_SESSION['mensagem_feedback']);
    unset($_SESSION['tipo_mensagem_feedback']);
}

// Consultar feedbacks.
$resultado = requisicaoApi($urlApi . '/feedbacks', $token);

if ($resultado['status'] === 401) {

    $_SESSION = [];
    session_destroy();

    header('Location: ../../index.php');
    exit;
}

$erroApi = $resultado['status'] !== 200;

$feedbacks = !$erroApi && is_array($resultado['dados'])
    ? $resultado['dados']
    : [];

// Organizar todas as análises do chatbot.
$analises = [];

foreach ($feedbacks as $feedback) {

    foreach ($feedback['analises_chatbot'] ?? [] as $analise) {

        $analises[] = [
            'feedback' => $feedback,
            'analise' => $analise
        ];
    }
}

// Ordenar análises mais recentes primeiro.
usort($analises, function ($a, $b) {

    return strtotime($b['analise']['created_at'] ?? '')
        <=> strtotime($a['analise']['created_at'] ?? '');
});

// Indicadores.
$totalAnalises = count($analises);

$aguardando = 0;
$aprovadas = 0;
$recusadas = 0;
$finalizadas = 0;

foreach ($analises as $item) {

    $analise = $item['analise'];

    $statusAprovacao = $analise['status_aprovacao'] ?? '';
    $status = $analise['status_atendimento'] ?? '';

    if ($statusAprovacao === 'aguardando') {
        $aguardando++;
    }

    if ($statusAprovacao === 'aprovada') {
        $aprovadas++;
    }

    if ($statusAprovacao === 'recusada') {
        $recusadas++;
    }

    if ($status === 'finalizado') {
        $finalizadas++;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Feedbacks | Insight CX</title>

    <link rel="stylesheet" href="../../assets/css/feedbacks.css">
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

            <a href="dashboard.php" class="<?= $paginaAtual === 'dashboard.php' ? 'ativo' : '' ?>">
                <i data-lucide="layout-dashboard"></i>
                Dashboard
            </a>

            <a href="sugestoes.php" class="<?= $paginaAtual === 'sugestoes.php' ? 'ativo' : '' ?>">
                <i data-lucide="lightbulb"></i>
                Sugestões
            </a>

            <a href="feedbacks.php" class="<?= $paginaAtual === 'feedbacks.php' ? 'ativo' : '' ?>">
                <i data-lucide="message-square-text"></i>
                Feedbacks
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
                <span class="subtitulo">ATENDIMENTO AO CLIENTE</span>

                <h1>Feedbacks</h1>

                <p>
                    Analise os feedbacks encaminhados pelo chatbot
                    e responda aos clientes.
                </p>
            </div>

            <div class="data-atual">
                <span>◷</span>
                <?= date('d/m/Y') ?>
            </div>

        </header>

        <?php if ($mensagem): ?>

            <div class="mensagem-feedback <?= escapar($tipoMensagem) ?>">
                <?= escapar($mensagem) ?>
            </div>

        <?php endif; ?>

        <section class="indicadores">

            <article class="card-indicador">

                <div class="indicador-icone roxo">▤</div>

                <div>
                    <span>Total de análises</span>
                    <h2><?= $erroApi ? '—' : $totalAnalises ?></h2>
                    <small>Feedbacks analisados</small>
                </div>

            </article>

            <article class="card-indicador">

                <div class="indicador-icone amarelo">◷</div>

                <div>
                    <span>Aguardando aprovação</span>
                    <h2><?= $erroApi ? '—' : $aguardando ?></h2>
                    <small>Precisam de avaliação</small>
                </div>

            </article>

            <article class="card-indicador">

                <div class="indicador-icone azul">↻</div>

                <div>
                    <span>Respostas recusadas</span>
                    <h2><?= $erroApi ? '—' : $recusadas ?></h2>
                    <small>Aguardando resposta manual</small>
                </div>

            </article>

            <article class="card-indicador">

                <div class="indicador-icone verde">✓</div>

                <div>
                    <span>Respostas aprovadas</span>
                    <h2><?= $erroApi ? '—' : $aprovadas ?></h2>
                    <small>Liberadas aos clientes</small>
                </div>

            </article>

        </section>

        <section class="lista-feedbacks">

            <div class="painel-titulo">

                <div>
                    <h2>Fila de atendimento</h2>
                    <p>Feedbacks que precisam de análise humana.</p>
                </div>

            </div>

            <?php if ($erroApi): ?>

                <p class="mensagem-erro">
                    Não foi possível carregar os feedbacks.
                    Verifique a conexão com a API.
                </p>

            <?php elseif (empty($analises)): ?>

                <p class="sem-dados">
                    Nenhum feedback encontrado.
                </p>

            <?php else: ?>

                <?php foreach ($analises as $item): ?>

                    <?php

                    $feedback = $item['feedback'];
                    $analise = $item['analise'];

                    $cliente = $feedback['usuario'] ?? [];

                    $status = $analise['status_atendimento'] ?? '';
                    $statusAprovacao = $analise['status_aprovacao'] ?? 'nao_gerada';

                    $prioridade = $analise['prioridade_identificada'] ?? 'media';

                    $respostaIA = $analise['resposta_gerada'] ?? '';
                    $respostaManual = $analise['resposta_administrador'] ?? '';

                    $palavras = $analise['palavras_chave'] ?? [];

                    ?>

                    <article class="card-feedback">

                        <div class="feedback-cabecalho">

                            <div>

                                <span class="feedback-cliente">
                                    <?= escapar($cliente['nome'] ?? 'Cliente não identificado') ?>
                                </span>

                                <span class="feedback-email">
                                    <?= escapar($cliente['email'] ?? '') ?>
                                </span>

                            </div>

                            <div class="feedback-etiquetas">

                                <span class="etiqueta prioridade-<?= escapar($prioridade) ?>">
                                    Prioridade <?= escapar(ucfirst($prioridade)) ?>
                                </span>

                                <span class="etiqueta status-<?= escapar($status) ?>">
                                    <?= escapar(ucfirst(str_replace('_', ' ', $status))) ?>
                                </span>

                            </div>

                        </div>

                        <div class="feedback-informacoes">

                            <div>
                                <span>Serviço</span>

                                <strong>
                                    <?= escapar($feedback['servico']['nome'] ?? 'Não informado') ?>
                                </strong>
                            </div>

                            <div>
                                <span>Categoria</span>

                                <strong>
                                    <?= escapar(ucfirst($feedback['categoria'] ?? '')) ?>
                                </strong>
                            </div>

                            <div>
                                <span>Data</span>

                                <strong>
                                    <?= !empty($feedback['created_at'])
                                        ? date('d/m/Y H:i', strtotime($feedback['created_at']))
                                        : 'Não informada' ?>
                                </strong>
                            </div>

                        </div>

                        <div class="feedback-conteudo">

                            <h3>
                                <?= escapar($feedback['assunto'] ?? 'Sem assunto') ?>
                            </h3>

                            <p>
                                <?= nl2br(escapar($feedback['comentario'] ?? '')) ?>
                            </p>

                        </div>

                        <div class="analise-chatbot">

                            <strong>Análise do chatbot</strong>

                            <p>
                                <?= escapar($analise['analise'] ?? 'Análise não disponível.') ?>
                            </p>

                            <?php if (!empty($palavras)): ?>

                                <div class="palavras-chave">

                                    <span>Palavras-chave:</span>

                                    <?php foreach ($palavras as $palavra): ?>

                                        <span class="palavra">
                                            <?= escapar($palavra) ?>
                                        </span>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                        <?php if ($statusAprovacao === 'aprovada'): ?>

                            <div class="resposta-registrada">

                                <strong>Resposta da IA aprovada e enviada ao cliente</strong>

                                <p>
                                    <?= nl2br(escapar($respostaIA)) ?>
                                </p>

                                <small>
                                    <?php if (!empty($analise['resposta_enviada_em'])): ?>

                                        Aprovada em
                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime($analise['resposta_enviada_em'])
                                        ) ?>

                                    <?php endif; ?>
                                </small>

                            </div>

                        <?php elseif (
                            $statusAprovacao === 'aguardando' &&
                            !empty($respostaIA)
                        ): ?>

                            <div class="resposta-registrada">

                                <strong>Resposta sugerida pela IA</strong>

                                <p>
                                    <?= nl2br(escapar($respostaIA)) ?>
                                </p>

                            </div>

                            <div class="acoes-resposta">

                                <form method="POST" class="form-resposta">

                                    <input
                                        type="hidden"
                                        name="id_analise"
                                        value="<?= (int) $analise['id'] ?>">

                                    <button
                                        type="submit"
                                        name="aprovar"
                                        class="botao-responder">
                                        Aprovar resposta
                                    </button>

                                </form>

                                <form method="POST" class="form-resposta">

                                    <input
                                        type="hidden"
                                        name="id_analise"
                                        value="<?= (int) $analise['id'] ?>">

                                    <input
                                        type="hidden"
                                        name="motivo_recusa"
                                        value="Resposta da IA recusada pelo administrador.">

                                    <button
                                        type="submit"
                                        name="recusar"
                                        class="botao-responder">
                                        Recusar resposta
                                    </button>

                                </form>

                            </div>

                        <?php elseif (
                            $statusAprovacao === 'recusada' ||
                            $statusAprovacao === 'nao_gerada'
                        ): ?>

                            <?php if (!empty($respostaManual)): ?>

                                <div class="resposta-registrada">

                                    <strong>Resposta manual enviada ao cliente</strong>

                                    <p>
                                        <?= nl2br(escapar($respostaManual)) ?>
                                    </p>

                                    <small>
                                        <?php if (!empty($analise['respondido_administrador_em'])): ?>

                                            Respondido em
                                            <?= date(
                                                'd/m/Y H:i',
                                                strtotime($analise['respondido_administrador_em'])
                                            ) ?>

                                        <?php endif; ?>
                                    </small>

                                </div>

                            <?php else: ?>

                                <form method="POST" class="form-resposta">

                                    <input
                                        type="hidden"
                                        name="id_analise"
                                        value="<?= (int) $analise['id'] ?>">

                                    <label for="resposta-<?= (int) $analise['id'] ?>">
                                        Resposta ao cliente
                                    </label>

                                    <textarea
                                        id="resposta-<?= (int) $analise['id'] ?>"
                                        name="resposta_administrador"
                                        rows="4"
                                        placeholder="Escreva uma resposta para o cliente..."
                                        required
                                        minlength="3"
                                        maxlength="5000"></textarea>

                                    <button
                                        type="submit"
                                        name="responder"
                                        class="botao-responder">
                                        Registrar resposta
                                    </button>

                                </form>

                            <?php endif; ?>

                        <?php else: ?>

                            <div class="resposta-registrada">

                                <strong>Resposta não disponível</strong>

                                <p>
                                    Verifique a situação desta análise.
                                </p>

                            </div>

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        lucide.createIcons();
    </script>

</body>

</html>
