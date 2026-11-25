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

// Registrar resposta do administrador.
$mensagem = '';
$tipoMensagem = '';

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

// Organizar as análises manuais.
$analises = [];

foreach ($feedbacks as $feedback) {

    foreach ($feedback['analises_chatbot'] ?? [] as $analise) {

        if (($analise['tipo_atendimento'] ?? '') !== 'manual') {
            continue;
        }

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
$emAtendimento = 0;
$finalizadas = 0;

foreach ($analises as $item) {

    $status = $item['analise']['status_atendimento'] ?? '';

    if ($status === 'aguardando_administrador') {
        $aguardando++;
    }

    if ($status === 'em_atendimento') {
        $emAtendimento++;
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
                    <span>Atendimentos manuais</span>
                    <h2><?= $erroApi ? '—' : $totalAnalises ?></h2>
                    <small>Feedbacks analisados</small>
                </div>
            </article>

            <article class="card-indicador">
                <div class="indicador-icone amarelo">◷</div>

                <div>
                    <span>Aguardando</span>
                    <h2><?= $erroApi ? '—' : $aguardando ?></h2>
                    <small>Precisam de resposta</small>
                </div>
            </article>

            <article class="card-indicador">
                <div class="indicador-icone azul">↻</div>

                <div>
                    <span>Em atendimento</span>
                    <h2><?= $erroApi ? '—' : $emAtendimento ?></h2>
                    <small>Em análise pelo administrador</small>
                </div>
            </article>

            <article class="card-indicador">
                <div class="indicador-icone verde">✓</div>

                <div>
                    <span>Finalizados</span>
                    <h2><?= $erroApi ? '—' : $finalizadas ?></h2>
                    <small>Respostas registradas</small>
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
                    Nenhum feedback encaminhado para atendimento manual.
                </p>

            <?php else: ?>

                <?php foreach ($analises as $item): ?>

                    <?php

                    $feedback = $item['feedback'];
                    $analise = $item['analise'];

                    $cliente = $feedback['usuario'] ?? [];

                    $status = $analise['status_atendimento'] ?? '';
                    $prioridade = $analise['prioridade_identificada'] ?? 'media';

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

                        <?php if ($status === 'finalizado'): ?>

                            <div class="resposta-registrada">

                                <strong>Resposta enviada ao cliente</strong>

                                <p>
                                    <?= nl2br(escapar(
                                        $analise['resposta_administrador'] ?? ''
                                    )) ?>
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