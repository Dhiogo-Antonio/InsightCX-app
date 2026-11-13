
<?php

session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['token'])) {
    header('Location: ../../index.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$token = $_SESSION['token'];

$urlApi = 'http://127.0.0.1:8000/api';

$mensagem = '';
$tipoMensagem = '';
$servicos = [];

$dados = [
    'id_servico' => '',
    'categoria' => '',
    'assunto' => '',
    'comentario' => ''
];

// Função para realizar requisições à API.
function requisicaoApi($url, $token, $metodo = 'GET', $dados = null)
{
    $curl = curl_init();

    $cabecalhos = [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $cabecalhos,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_TIMEOUT => 15
    ]);

    if ($dados !== null) {
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            json_encode($dados)
        );
    }

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


$respostaServicos = requisicaoApi(
    $urlApi . '/servicos',
    $token
);

if ($respostaServicos['status'] >= 200 && $respostaServicos['status'] < 300) {

    $servicos = $respostaServicos['dados'] ?? [];

    if (!is_array($servicos)) {
        $servicos = [];
    }

} elseif ($respostaServicos['status'] === 401) {

    session_destroy();
    header('Location: ../../index.php');
    exit;

} else {

    $mensagem = 'Não foi possível carregar os serviços.';
    $tipoMensagem = 'erro';
}


// Recebe o formulário.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dados['id_servico'] = trim($_POST['id_servico'] ?? '');
    $dados['categoria'] = trim($_POST['categoria'] ?? '');
    $dados['assunto'] = trim($_POST['assunto'] ?? '');
    $dados['comentario'] = trim($_POST['comentario'] ?? '');

    if (
        empty($dados['id_servico']) ||
        empty($dados['categoria']) ||
        empty($dados['assunto']) ||
        empty($dados['comentario'])
    ) {
        $mensagem = 'Preencha todos os campos.';
        $tipoMensagem = 'erro';

    } elseif (!is_numeric($dados['id_servico'])) {
        $mensagem = 'Selecione um serviço válido.';
        $tipoMensagem = 'erro';

    } elseif (!in_array($dados['categoria'], ['elogio', 'reclamacao'])) {
        $mensagem = 'Selecione uma categoria válida.';
        $tipoMensagem = 'erro';

    } else {

        $resposta = requisicaoApi(
            $urlApi . '/feedbacks',
            $token,
            'POST',
            [
                'id_servico' => (int) $dados['id_servico'],
                'assunto' => $dados['assunto'],
                'comentario' => $dados['comentario'],
                'categoria' => $dados['categoria'],
                'prioridade' => 'media'
            ]
        );

        if (
            $resposta['status'] >= 200 &&
            $resposta['status'] < 300
        ) {
            $mensagem = 'Seu feedback foi enviado com sucesso!';
            $tipoMensagem = 'sucesso';

            $dados = [
                'id_servico' => '',
                'categoria' => '',
                'assunto' => '',
                'comentario' => ''
            ];

        } elseif ($resposta['status'] === 422) {

            $mensagem = 'Verifique os dados informados.';

            if (isset($resposta['dados']['errors'])) {
                $erros = [];

                foreach ($resposta['dados']['errors'] as $campo => $mensagens) {
                    $erros[] = implode(' ', $mensagens);
                }

                if (!empty($erros)) {
                    $mensagem = implode(' ', $erros);
                }
            }

            $tipoMensagem = 'erro';

        } elseif ($resposta['status'] === 401) {

            session_destroy();
            header('Location: ../../index.php');
            exit;

        } elseif ($resposta['status'] === 403) {

            $mensagem = $resposta['dados']['message']
                ?? 'Você não tem permissão para enviar feedback para este serviço.';

            $tipoMensagem = 'erro';

        } else {

            $mensagem = $resposta['dados']['message']
                ?? 'Não foi possível enviar seu feedback. Tente novamente.';

            $tipoMensagem = 'erro';
        }
    }
}

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Enviar feedback | Insight CX</title>

    <link rel="stylesheet" href="../../assets/css/enviar-feedback.css">
</head>

<body>

    <header class="navbar">

        <a href="index.php" class="logo">
            Insight <span>CX</span>
        </a>

        <nav>
            <a href="index.php">Início</a>
            <a href="enviar-feedback.php" class="ativo">Enviar feedback</a>
            <a href="meus-feedbacks.php">Meus feedbacks</a>
            <a href="sugestoes.php">Sugestões</a>
        </nav>

        <div class="usuario">
            <span>
                Olá, <?= escapar($usuario['nome'] ?? 'Cliente') ?>
            </span>
        </div>

    </header>

    <main class="container">

        <section class="cabecalho">

            <span class="subtitulo">SUA OPINIÃO IMPORTA</span>

            <h1>Compartilhe sua experiência</h1>

            <p>
                Conte para nós o que você achou dos nossos serviços.
                Seu feedback nos ajuda a melhorar.
            </p>

        </section>

        <section class="form-card">

            <h2>Enviar feedback</h2>

            <p class="descricao">
                Preencha as informações abaixo para registrar sua opinião.
            </p>

            <?php if (!empty($mensagem)): ?>

                <div class="mensagem <?= escapar($tipoMensagem) ?>">
                    <?= escapar($mensagem) ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="">

                <div class="campo">

                    <label for="id_servico">Serviço relacionado</label>

                    <select name="id_servico" id="id_servico" required>

                        <option value="">Selecione um serviço</option>

                        <?php foreach ($servicos as $servico): ?>

                            <option
                                value="<?= escapar($servico['id'] ?? '') ?>"
                                <?= (string) $dados['id_servico'] === (string) ($servico['id'] ?? '') ? 'selected' : '' ?>
                            >
                                <?= escapar($servico['nome'] ?? $servico['titulo'] ?? 'Serviço') ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <?php if (empty($servicos)): ?>
                        <small>Nenhum serviço disponível no momento.</small>
                    <?php endif; ?>

                </div>

                <div class="campo">

                    <label for="categoria">Categoria</label>

                    <select name="categoria" id="categoria" required>

                        <option value="">Selecione uma categoria</option>

                        <option
                            value="elogio"
                            <?= $dados['categoria'] === 'elogio' ? 'selected' : '' ?>
                        >
                            Elogio
                        </option>

                        <option
                            value="reclamacao"
                            <?= $dados['categoria'] === 'reclamacao' ? 'selected' : '' ?>
                        >
                            Reclamação
                        </option>

                    </select>

                </div>

                <div class="campo">

                    <label for="assunto">Assunto</label>

                    <input
                        type="text"
                        name="assunto"
                        id="assunto"
                        maxlength="255"
                        placeholder="Ex.: Qualidade do atendimento"
                        value="<?= escapar($dados['assunto']) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="comentario">Conte sua experiência</label>

                    <textarea
                        name="comentario"
                        id="comentario"
                        rows="6"
                        placeholder="Descreva sua experiência..."
                        required
                    ><?= escapar($dados['comentario']) ?></textarea>

                </div>

                <button type="submit" class="btn-enviar">
                    Enviar feedback
                </button>

            </form>

        </section>

    </main>

</body>
</html>
