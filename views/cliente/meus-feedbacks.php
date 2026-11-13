
<?php

session_start();

if (empty($_SESSION['usuario']) || empty($_SESSION['token'])) {
    header('Location: ../../index.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$token = $_SESSION['token'];

$nome = is_array($usuario)
    ? ($usuario['nome'] ?? 'Cliente')
    : ($usuario->nome ?? 'Cliente');

$urlApi = 'http://127.0.0.1:8000/api';

$feedbacks = [];
$mensagem = '';
$tipoMensagem = '';

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function requisicaoApi($url, $token)
{
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 15
    ]);

    $resposta = curl_exec($curl);
    $erroCurl = curl_error($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    if ($resposta === false) {
        return [
            'status' => 0,
            'dados' => null,
            'erro' => $erroCurl
        ];
    }

    return [
        'status' => $status,
        'dados' => json_decode($resposta, true),
        'erro' => null
    ];
}

// Busca os feedbacks do cliente.
$resposta = requisicaoApi($urlApi . '/feedbacks', $token);

if ($resposta['status'] >= 200 && $resposta['status'] < 300) {

    $feedbacks = $resposta['dados'] ?? [];

    if (!is_array($feedbacks)) {
        $feedbacks = [];
    }

} elseif ($resposta['status'] === 401) {

    session_unset();
    session_destroy();

    header('Location: ../../index.php');
    exit;

} else {

    $mensagem = 'Não foi possível carregar seus feedbacks. Tente novamente.';
    $tipoMensagem = 'erro';
}

function nomeStatus($status)
{
    $nomes = [
        'pendente' => 'Pendente',
        'em analise' => 'Em análise',
        'resolvido' => 'Resolvido'
    ];

    return $nomes[$status] ?? ucfirst($status);
}

function classeStatus($status)
{
    $classes = [
        'pendente' => 'pendente',
        'em analise' => 'analise',
        'resolvido' => 'resolvido'
    ];

    return $classes[$status] ?? 'pendente';
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meus feedbacks | Insight CX</title>

    <link rel="stylesheet" href="../../assets/css/meus-feedbacks.css">
</head>

<body>

    <header class="navbar">

        <a href="index.php" class="logo">
            Insight <span>CX</span>
        </a>

        <nav>
            <a href="index.php">Início</a>
            <a href="enviar-feedback.php">Enviar feedback</a>
            <a href="meus-feedbacks.php" class="ativo">Meus feedbacks</a>
            <a href="sugestoes.php">Sugestões</a>
        </nav>

        <div class="usuario">
            Olá, <?= escapar($nome) ?>
        </div>

    </header>

    <main class="container">

        <section class="cabecalho">

            <span class="subtitulo">ACOMPANHAMENTO</span>

            <h1>Meus feedbacks</h1>

            <p>
                Acompanhe suas manifestações e consulte as atualizações
                realizadas pela empresa.
            </p>

        </section>

        <?php if (!empty($mensagem)): ?>

            <div class="mensagem erro">
                <?= escapar($mensagem) ?>
            </div>

        <?php elseif (empty($feedbacks)): ?>

            <section class="vazio">

                <div class="icone-vazio">☷</div>

                <h2>Você ainda não enviou feedbacks</h2>

                <p>
                    Quando você compartilhar sua experiência,
                    seus feedbacks aparecerão nesta página.
                </p>

                <a href="enviar-feedback.php" class="btn-enviar">
                    Enviar meu primeiro feedback
                </a>

            </section>

        <?php else: ?>

            <section class="lista-feedbacks">

                <div class="lista-cabecalho">
                    <h2>Histórico de feedbacks</h2>

                    <span>
                        <?= count($feedbacks) ?>
                        <?= count($feedbacks) === 1 ? 'registro' : 'registros' ?>
                    </span>
                </div>

                <?php foreach ($feedbacks as $feedback): ?>

                    <?php
                    $status = $feedback['status'] ?? 'pendente';
                    $servico = $feedback['servico']['nome'] ?? 'Serviço não informado';

                    $data = 'Data não disponível';

                    if (!empty($feedback['created_at'])) {
                        $timestamp = strtotime($feedback['created_at']);

                        if ($timestamp !== false) {
                            $data = date('d/m/Y', $timestamp);
                        }
                    }

                    $categoria = $feedback['categoria'] ?? '';
                    ?>

                    <article class="feedback-card">

                        <div class="feedback-topo">

                            <div>
                                <span class="categoria">
                                    <?= escapar($categoria === 'elogio' ? 'Elogio' : 'Reclamação') ?>
                                </span>

                                <h3>
                                    <?= escapar($feedback['assunto'] ?? 'Sem assunto') ?>
                                </h3>
                            </div>

                            <span class="status <?= escapar(classeStatus($status)) ?>">
                                <?= escapar(nomeStatus($status)) ?>
                            </span>

                        </div>

                        <p class="servico">
                            Serviço: <?= escapar($servico) ?>
                        </p>

                        <p class="comentario">
                            <?= nl2br(escapar($feedback['comentario'] ?? '')) ?>
                        </p>

                        <div class="feedback-rodape">
                            <span>Enviado em <?= escapar($data) ?></span>
                        </div>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

    </main>

    <footer class="footer">
        <p>
            © <?= date('Y') ?> Insight CX. Todos os direitos reservados.
        </p>

        <span>Sua experiência importa.</span>
    </footer>

</body>
</html>
