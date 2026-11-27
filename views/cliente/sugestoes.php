<?php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['token'])) {
    header('Location: ../../index.php');
    exit;
}

$usuario = $_SESSION['usuario'];

$nome = is_array($usuario)
    ? ($usuario['nome'] ?? 'Cliente')
    : ($usuario->nome ?? 'Cliente');

$token = $_SESSION['token'];

$urlApi = 'http://127.0.0.1:8000/api';

$sugestoes = [];
$retornos = [];

$erro = '';
$erroFeedbacks = '';

function requisicaoApi($url, $token)
{
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
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

function formatarData($data)
{
    if (!$data) {
        return 'Data não disponível';
    }

    return date('d/m/Y', strtotime($data));
}

function nomeStatus($status)
{
    $status = strtolower($status);

    $nomes = [
        'pendente' => 'Pendente',
        'em andamento' => 'Em andamento',
        'concluida' => 'Concluída'
    ];

    return $nomes[$status] ?? 'Não informado';
}

function nomeStatusAtendimento($status)
{
    $nomes = [
        'aguardando_analise' => 'Aguardando análise',
        'respondido_automaticamente' => 'Respondido automaticamente',
        'aguardando_administrador' => 'Aguardando administrador',
        'em_atendimento' => 'Em atendimento',
        'finalizado' => 'Finalizado'
    ];

    return $nomes[$status] ?? 'Não informado';
}


// Buscar sugestões de melhoria

$resultado = requisicaoApi(
    $urlApi . '/sugestoes',
    $token
);

if ($resultado['status'] === 401) {
    $_SESSION = [];
    session_destroy();

    header('Location: ../../index.php');
    exit;
}

if ($resultado['status'] === 200) {
    $sugestoes = $resultado['dados'] ?? [];
} else {
    $erro = 'Não foi possível carregar as sugestões. Tente novamente.';
}


// Buscar feedbacks e respostas do chatbot

$resultadoFeedbacks = requisicaoApi(
    $urlApi . '/feedbacks',
    $token
);

if ($resultadoFeedbacks['status'] === 401) {
    $_SESSION = [];
    session_destroy();

    header('Location: ../../index.php');
    exit;
}

if ($resultadoFeedbacks['status'] === 200) {

    $feedbacks = $resultadoFeedbacks['dados'] ?? [];

    foreach ($feedbacks as $feedback) {

        $analises = $feedback['analises_chatbot'] ?? [];

        foreach ($analises as $analise) {

            $retornos[] = [
                'feedback' => $feedback,
                'analise' => $analise
            ];
        }
    }
} else {
    $erroFeedbacks = 'Não foi possível carregar as respostas do chatbot.';
}


// Ordenar retornos do mais recente para o mais antigo

usort($retornos, function ($a, $b) {
    return strtotime($b['analise']['created_at'] ?? '')
        <=> strtotime($a['analise']['created_at'] ?? '');
});

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Respostas e sugestões | Insight CX</title>

    <link rel="stylesheet" href="../../assets/css/sugestoes.css">
</head>

<body>

    <header class="navbar">

        <div class="logo">
            Insight CX
        </div>

        <nav>
            <a href="index.php">Início</a>
            <a href="enviar-feedback.php">Enviar feedback</a>
            <a href="meus-feedbacks.php">Meus feedbacks</a>
            <a href="sugestoes.php" class="ativo">Sugestões</a>
        </nav>

        <div class="usuario">
            <span><?= escapar($nome) ?></span>

            <form method="POST" action="index.php">
                <button type="submit" name="sair">Sair</button>
            </form>
        </div>

    </header>

    <main class="container">

        <section class="cabecalho">

            <div>
                <span class="subtitulo">EVOLUÇÃO E MELHORIAS</span>

                <h1>Respostas e sugestões</h1>

                <p>
                    Acompanhe as respostas dos seus feedbacks e as melhorias
                    propostas para os serviços da sua empresa.
                </p>
            </div>

        </section>


        <!-- Respostas dos feedbacks -->

        <section class="lista-sugestoes">

            <div class="resumo">

                <div>
                    <h2>Respostas dos seus feedbacks</h2>

                    <p>
                        Acompanhe os retornos sobre suas manifestações.
                    </p>
                </div>

                <span class="contador">
                    <?= count($retornos) ?>
                    <?= count($retornos) === 1 ? 'retorno' : 'retornos' ?>
                </span>

            </div>

            <?php if ($erroFeedbacks): ?>

                <div class="mensagem erro">
                    <?= escapar($erroFeedbacks) ?>
                </div>

            <?php elseif (empty($retornos)): ?>

                <section class="vazio">

                    <div class="icone-vazio">
                        <span>✦</span>
                    </div>

                    <h2>Nenhum retorno disponível</h2>

                    <p>
                        Quando seus feedbacks forem analisados,
                        os retornos aparecerão nesta área.
                    </p>

                </section>

            <?php else: ?>

                <div class="cards-sugestoes">

                    <?php foreach ($retornos as $retorno): ?>

                        <?php

                        $feedback = $retorno['feedback'];
                        $analise = $retorno['analise'];

                        $statusAtendimento = $analise['status_atendimento'] ?? '';

                        $resposta = $analise['resposta_gerada'] ?? null;

                        $respostaAdministrador =
                            $analise['resposta_administrador'] ?? null;

                        $tipoAtendimento =
                            $analise['tipo_atendimento'] ?? '';

                        $prioridade =
                            $analise['prioridade_identificada'] ?? 'Não informada';

                        $servico = $feedback['servico']
                            ?? 'Serviço não informado';

                        $data = formatarData(
                            $analise['created_at'] ?? null
                        );

                        ?>

                        <article class="card-sugestao">

                            <div class="card-topo">

                                <span class="categoria">
                                    RETORNO DO CHATBOT
                                </span>

                                <span class="status status-<?= escapar(str_replace('_', '-', $statusAtendimento)) ?>">
                                    <?= escapar(nomeStatusAtendimento($statusAtendimento)) ?>
                                </span>

                            </div>

                            <h3>
                                <?= escapar($feedback['assunto'] ?? 'Feedback') ?>
                            </h3>

                            <div class="informacoes">

                                <div>
                                    <span class="info-label">
                                        Serviço
                                    </span>

                                    <strong>
                                        <?= escapar($servico) ?>
                                    </strong>
                                </div>

                                <div>
                                    <span class="info-label">
                                        Prioridade
                                    </span>

                                    <strong>
                                        <?= escapar(ucfirst($prioridade)) ?>
                                    </strong>
                                </div>

                                <div>
                                    <span class="info-label">
                                        Data da análise
                                    </span>

                                    <strong>
                                        <?= escapar($data) ?>
                                    </strong>
                                </div>

                            </div>

                            <div class="descricao">

                                <?php if (!empty($resposta)): ?>

                                    <strong>
                                        Orientação do assistente virtual
                                    </strong>

                                    <p>
                                        <?= nl2br(escapar($resposta)) ?>
                                    </p>

                                <?php endif; ?>


                                <?php if ($tipoAtendimento === 'manual'): ?>

                                    <strong>
                                        Acompanhamento da equipe
                                    </strong>

                                    <p>
                                        Seu feedback também foi encaminhado
                                        ao administrador para acompanhamento.
                                    </p>

                                <?php endif; ?>

                                <div class="resposta">
                                    <?php if (!empty($respostaAdministrador)): ?>

                                        <strong>
                                            Resposta da equipe responsável
                                        </strong>
                                        <br>

                                        <p>
                                            <?= nl2br(escapar($respostaAdministrador)) ?>
                                        </p>

                                    <?php elseif ($statusAtendimento === 'aguardando_administrador'): ?>


                                        <strong>
                                            Aguardando retorno da equipe
                                        </strong>
                                        <br>

                                        <p>
                                            Seu feedback foi encaminhado ao administrador.
                                            Assim que houver uma resposta,
                                            ela aparecerá aqui.
                                        </p>

                                    <?php elseif ($statusAtendimento === 'em_atendimento'): ?>

                                        <strong>
                                            Atendimento em andamento
                                        </strong>
                                        <br>

                                        <p>
                                            A equipe responsável está trabalhando
                                            na sua solicitação.
                                        </p>

                                    <?php elseif ($statusAtendimento === 'aguardando_analise'): ?>

                                        <strong>
                                            Feedback em análise
                                        </strong>
                                        <br>

                                        <p>
                                            Seu feedback está aguardando análise.
                                        </p>

                                    <?php elseif (
                                        empty($resposta)
                                        && empty($respostaAdministrador)
                                        && $statusAtendimento === 'finalizado'
                                    ): ?>

                                        <strong>
                                            Atendimento finalizado
                                        </strong>
                                        <br>

                                        <p>
                                            O atendimento deste feedback foi finalizado.
                                        </p>

                                    <?php elseif (
                                        empty($resposta)
                                        && empty($respostaAdministrador)
                                        && $tipoAtendimento !== 'manual'
                                    ): ?>

                                        <strong>
                                            Análise registrada
                                        </strong>
                                        <br>

                                        <p>
                                            Seu feedback possui uma análise registrada.
                                        </p>

                                    <?php endif; ?>
                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


        <br>





        <section class="lista-sugestoes">

            <div class="resumo">

                <div>
                    <h2>Sugestões de melhoria</h2>

                    <p>
                        Confira as melhorias propostas pela equipe responsável.
                    </p>
                </div>

                <span class="contador">
                    <?= count($sugestoes) ?>
                    <?= count($sugestoes) === 1 ? 'sugestão' : 'sugestões' ?>
                </span>

            </div>

            <?php if ($erro): ?>

                <div class="mensagem erro">
                    <?= escapar($erro) ?>
                </div>

            <?php elseif (empty($sugestoes)): ?>

                <section class="vazio">

                    <div class="icone-vazio">
                        <span>✦</span>
                    </div>

                    <h2>Nenhuma sugestão disponível</h2>

                    <p>
                        Quando a equipe cadastrar sugestões de melhoria,
                        elas aparecerão nesta área.
                    </p>

                </section>

            <?php else: ?>

                <div class="cards-sugestoes">

                    <?php foreach ($sugestoes as $sugestao): ?>

                        <?php
                        $status = strtolower($sugestao['status'] ?? '');
                        ?>

                        <article class="card-sugestao">

                            <div class="card-topo">

                                <span class="categoria">
                                    MELHORIA PROPOSTA
                                </span>

                                <span class="status status-<?= escapar(str_replace(' ', '-', $status)) ?>">
                                    <?= escapar(nomeStatus($status)) ?>
                                </span>

                            </div>

                            <h3>
                                <?= escapar($sugestao['titulo'] ?? 'Sem título') ?>
                            </h3>

                            <p class="descricao">
                                <?= nl2br(escapar($sugestao['descricao'] ?? '')) ?>
                            </p>

                            <div class="informacoes">

                                <div>
                                    <span class="info-label">
                                        Data da publicação
                                    </span>

                                    <strong>
                                        <?= escapar(formatarData($sugestao['created_at'] ?? null)) ?>
                                    </strong>
                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


    </main>

</body>

</html>