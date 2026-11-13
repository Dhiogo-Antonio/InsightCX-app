
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

function requisicaoApi($url, $token, $metodo = 'GET', $dados = null)
{
    $curl = curl_init();

    $cabecalhos = [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ];

    if ($dados !== null) {
        $cabecalhos[] = 'Content-Type: application/json';
    }

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => $cabecalhos,
        CURLOPT_TIMEOUT => 15
    ]);

    if ($dados !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dados));
    }

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

// Mensagens da página
$mensagem = $_SESSION['mensagem'] ?? '';
$tipoMensagem = $_SESSION['tipo_mensagem'] ?? '';

unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);

// Cadastrar, atualizar ou excluir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastrar') {

        $dados = [
            'id_empresa' => $_POST['id_empresa'] ?? '',
            'id_servico' => $_POST['id_servico'] ?? '',
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'status' => $_POST['status'] ?? 'pendente'
        ];

        $resposta = requisicaoApi(
            $urlApi . '/sugestoes',
            $token,
            'POST',
            $dados
        );

    } elseif ($acao === 'atualizar') {

        $id = (int) ($_POST['id'] ?? 0);

        $dados = [
            'status' => $_POST['status'] ?? ''
        ];

        $resposta = requisicaoApi(
            $urlApi . '/sugestoes/' . $id,
            $token,
            'PUT',
            $dados
        );

    } elseif ($acao === 'excluir') {

        $id = (int) ($_POST['id'] ?? 0);

        $resposta = requisicaoApi(
            $urlApi . '/sugestoes/' . $id,
            $token,
            'DELETE'
        );

    } elseif ($acao === 'sair') {

        requisicaoApi($urlApi . '/logout', $token, 'POST');

        $_SESSION = [];
        session_destroy();

        header('Location: ../../index.php');
        exit;

    } else {
        $resposta = [
            'status' => 400,
            'dados' => ['mensagem' => 'Ação inválida.']
        ];
    }

    if ($resposta['status'] === 401) {
        $_SESSION = [];
        session_destroy();

        header('Location: ../../index.php');
        exit;
    }

    if ($resposta['status'] >= 200 && $resposta['status'] < 300) {
        $_SESSION['mensagem'] = $resposta['dados']['mensagem']
            ?? 'Operação realizada com sucesso!';

        $_SESSION['tipo_mensagem'] = 'sucesso';
    } else {
        $_SESSION['mensagem'] = $resposta['dados']['mensagem']
            ?? 'Não foi possível realizar a operação. Verifique os dados.';

        $_SESSION['tipo_mensagem'] = 'erro';
    }

    header('Location: sugestoes.php');
    exit;
}

// Buscar dados para preencher a página
$resultadoSugestoes = requisicaoApi($urlApi . '/sugestoes', $token);
$resultadoEmpresas = requisicaoApi($urlApi . '/empresas', $token);
$resultadoServicos = requisicaoApi($urlApi . '/servicos', $token);

if (
    $resultadoSugestoes['status'] === 401 ||
    $resultadoEmpresas['status'] === 401 ||
    $resultadoServicos['status'] === 401
) {
    $_SESSION = [];
    session_destroy();

    header('Location: ../../index.php');
    exit;
}

$erroCarregamento =
    $resultadoSugestoes['status'] !== 200 ||
    $resultadoEmpresas['status'] !== 200 ||
    $resultadoServicos['status'] !== 200;

$sugestoes = is_array($resultadoSugestoes['dados'])
    ? $resultadoSugestoes['dados']
    : [];

$empresas = is_array($resultadoEmpresas['dados'])
    ? $resultadoEmpresas['dados']
    : [];

$servicos = is_array($resultadoServicos['dados'])
    ? $resultadoServicos['dados']
    : [];

// Criar mapa de empresas para exibição
$nomesEmpresas = [];

foreach ($empresas as $empresa) {
    if (isset($empresa['id'])) {
        $nomesEmpresas[$empresa['id']] = $empresa['nome'] ?? 'Empresa';
    }
}

// Ordenar sugestões mais recentes primeiro
usort($sugestoes, function ($a, $b) {
    return strtotime($b['created_at'] ?? '') <=> strtotime($a['created_at'] ?? '');
});

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sugestões | Insight CX</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/admin-sugestoes.css">
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
            <a href="dashboard.php">
                <span>▦</span>
                Dashboard
            </a>

            <a href="sugestoes.php" class="ativo">
                <span>✧</span>
                Sugestões
            </a>
        </nav>

        <form method="POST" class="sair">
            <button type="submit" name="acao" value="sair">
                <span>↪</span>
                Sair do sistema
            </button>
        </form>

    </aside>

    <main class="conteudo">

        <header class="topo">
            <div>
                <span class="subtitulo">GERENCIAMENTO</span>
                <h1>Sugestões de melhoria</h1>
                <p>Cadastre e acompanhe as melhorias propostas para as empresas.</p>
            </div>
        </header>

        <?php if ($mensagem): ?>
            <div class="alerta <?= escapar($tipoMensagem) ?>">
                <?= escapar($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de cadastro -->

        <section class="painel painel-formulario">

            <div class="painel-titulo">
                <div>
                    <h2>Nova sugestão</h2>
                    <p>Cadastre uma oportunidade de melhoria para uma empresa.</p>
                </div>
            </div>

            <form method="POST" class="form-sugestao">

                <input type="hidden" name="acao" value="cadastrar">

                <div class="campos-duplos">

                    <div class="campo">
                        <label for="id_empresa">Empresa</label>

                        <select name="id_empresa" id="id_empresa" required>
                            <option value="">Selecione uma empresa</option>

                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?= (int) $empresa['id'] ?>">
                                    <?= escapar($empresa['nome'] ?? 'Empresa') ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="campo">
                        <label for="id_servico">Serviço</label>

                        <select name="id_servico" id="id_servico" required>
                            <option value="">Selecione um serviço</option>

                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?= (int) $servico['id'] ?>">
                                    <?= escapar($servico['nome'] ?? 'Serviço') ?>
                                    — Empresa #<?= (int) ($servico['id_empresa'] ?? 0) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                        <small>
                            Selecione um serviço que pertença à empresa escolhida.
                        </small>
                    </div>

                </div>

                <div class="campo">
                    <label for="titulo">Título da sugestão</label>

                    <input
                        type="text"
                        name="titulo"
                        id="titulo"
                        maxlength="255"
                        placeholder="Ex.: Melhorar o tempo de atendimento"
                        required>
                </div>

                <div class="campo">
                    <label for="descricao">Descrição</label>

                    <textarea
                        name="descricao"
                        id="descricao"
                        rows="4"
                        placeholder="Descreva a melhoria proposta..."
                        required></textarea>
                </div>

                <div class="campo campo-status">
                    <label for="status">Status inicial</label>

                    <select name="status" id="status">
                        <option value="pendente">Pendente</option>
                        <option value="em andamento">Em andamento</option>
                        <option value="concluida">Concluída</option>
                    </select>
                </div>

                <button type="submit" class="botao-principal">
                    + Cadastrar sugestão
                </button>

            </form>

        </section>

        <!-- Lista de sugestões -->

        <section class="painel painel-lista">

            <div class="painel-titulo">
                <div>
                    <h2>Sugestões cadastradas</h2>
                    <p><?= count($sugestoes) ?> registro(s) encontrado(s).</p>
                </div>
            </div>

            <?php if ($erroCarregamento): ?>

                <p class="mensagem-erro">
                    Não foi possível carregar todos os dados. Verifique a conexão com a API.
                </p>

            <?php elseif (empty($sugestoes)): ?>

                <div class="estado-vazio">
                    <span>✧</span>
                    <h3>Nenhuma sugestão cadastrada</h3>
                    <p>As sugestões registradas aparecerão nesta área.</p>
                </div>

            <?php else: ?>

                <div class="lista-sugestoes">

                    <?php foreach ($sugestoes as $sugestao): ?>

                        <?php
                        $status = strtolower($sugestao['status'] ?? 'pendente');

                        $statusTexto = [
                            'pendente' => 'Pendente',
                            'em andamento' => 'Em andamento',
                            'concluida' => 'Concluída'
                        ][$status] ?? 'Não informado';

                        $empresaId = $sugestao['id_empresa'] ?? 0;

                        $nomeEmpresa = $nomesEmpresas[$empresaId]
                            ?? 'Empresa #' . $empresaId;

                        $data = !empty($sugestao['created_at'])
                            ? date('d/m/Y', strtotime($sugestao['created_at']))
                            : '—';
                        ?>

                        <article class="item-sugestao">

                            <div class="sugestao-conteudo">

                                <div class="sugestao-cabecalho">
                                    <h3><?= escapar($sugestao['titulo'] ?? 'Sem título') ?></h3>

                                    <span class="status-sugestao <?= escapar(str_replace(' ', '-', $status)) ?>">
                                        <?= escapar($statusTexto) ?>
                                    </span>
                                </div>

                                <p class="descricao-sugestao">
                                    <?= nl2br(escapar($sugestao['descricao'] ?? '')) ?>
                                </p>

                                <div class="informacoes-sugestao">
                                    <span>
                                        Empresa: <?= escapar($nomeEmpresa) ?>
                                    </span>

                                    <span>
                                        Serviço:
                                        <?= escapar($sugestao['servico']['nome'] ?? 'Não informado') ?>
                                    </span>

                                    <span>
                                        Criada em: <?= escapar($data) ?>
                                    </span>
                                </div>

                            </div>

                            <div class="acoes-sugestao">

                                <form method="POST" class="form-atualizar">
                                    <input type="hidden" name="acao" value="atualizar">
                                    <input type="hidden" name="id" value="<?= (int) $sugestao['id'] ?>">

                                    <label for="status-<?= (int) $sugestao['id'] ?>">
                                        Alterar status
                                    </label>

                                    <select
                                        name="status"
                                        id="status-<?= (int) $sugestao['id'] ?>">

                                        <option value="pendente" <?= $status === 'pendente' ? 'selected' : '' ?>>
                                            Pendente
                                        </option>

                                        <option value="em andamento" <?= $status === 'em andamento' ? 'selected' : '' ?>>
                                            Em andamento
                                        </option>

                                        <option value="concluida" <?= $status === 'concluida' ? 'selected' : '' ?>>
                                            Concluída
                                        </option>

                                    </select>

                                    <button type="submit" class="botao-atualizar">
                                        Salvar status
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Deseja realmente excluir esta sugestão?');">

                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= (int) $sugestao['id'] ?>">

                                    <button type="submit" class="botao-excluir">
                                        Excluir sugestão
                                    </button>

                                </form>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</body>
</html>
