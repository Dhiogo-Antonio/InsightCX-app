
<?php
session_start();

// Verifica se o cliente está autenticado
if (empty($_SESSION['usuario'])) {
    header('Location: ../../index.php');
    exit;
}

// Recupera os dados do usuário
$usuario = $_SESSION['usuario'];

if (is_array($usuario)) {
    $nome = $usuario['nome'] ?? 'Cliente';
} elseif (is_object($usuario)) {
    $nome = $usuario->nome ?? 'Cliente';
} else {
    $nome = 'Cliente';
}

$nome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');

// Encerrar sessão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sair'])) {
    session_unset();
    session_destroy();

    header('Location: ../../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Início | Insight CX</title>

    <link rel="stylesheet" href="../../assets/css/cliente.css">
</head>

<body>

    <!-- MENU SUPERIOR -->
    <header class="navbar">

        <a href="index.php" class="logo">
            <div class="logo-icon">✦</div>

            <div>
                <h2>Insight <span>CX</span></h2>
                <small>PORTAL DO CLIENTE</small>
            </div>
        </a>

        <nav class="menu">
            <a href="index.php" class="active">Início</a>
            <a href="enviar-feedback.php">Enviar feedback</a>
            <a href="meus-feedbacks.php">Meus feedbacks</a>
            <a href="sugestoes.php">Sugestões</a>
        </nav>

        <div class="user-area">
            <div class="user-icon">👤</div>

            <span><?php echo $nome; ?></span>

            <form method="POST">
                <button type="submit" name="sair" class="logout">
                    Sair
                </button>
            </form>
        </div>

    </header>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="container">

        <!-- BOAS-VINDAS -->
        <section class="welcome">

            <span class="tag">PORTAL DO CLIENTE</span>

            <h1>
                Olá, <?php echo $nome; ?>!
                <br>
                Sua opinião transforma experiências.
            </h1>

            <p>
                Compartilhe suas experiências, acompanhe seus feedbacks
                e confira as sugestões de melhoria preparadas para você.
            </p>

        </section>

        <!-- OPÇÕES PRINCIPAIS -->
        <section class="options">

            <a href="enviar-feedback.php" class="option-card">

                <div class="card-icon purple">
                    <span>✉</span>
                </div>

                <h3>Enviar feedback</h3>

                <p>
                    Compartilhe sua opinião, experiência ou sugestão
                    com a empresa.
                </p>

                <span class="card-link">
                    Enviar feedback <span>→</span>
                </span>

            </a>

            <a href="meus-feedbacks.php" class="option-card">

                <div class="card-icon blue">
                    <span>☷</span>
                </div>

                <h3>Meus feedbacks</h3>

                <p>
                    Acompanhe seus feedbacks e consulte as respostas
                    e atualizações da empresa.
                </p>

                <span class="card-link">
                    Acompanhar <span>→</span>
                </span>

            </a>

            <a href="sugestoes.php" class="option-card">

                <div class="card-icon violet">
                    <span>✧</span>
                </div>

                <h3>Sugestões de melhoria</h3>

                <p>
                    Confira as recomendações e oportunidades de melhoria
                    preparadas para você.
                </p>

                <span class="card-link">
                    Ver sugestões <span>→</span>
                </span>

            </a>

        </section>

        <!-- MENSAGEM INFORMATIVA -->
        <section class="help">
            <div class="help-icon">?</div>

            <div>
                <h3>Precisa de ajuda?</h3>
                <p>
                    Utilize o menu acima para navegar pelo portal
                    e acessar suas funcionalidades.
                </p>
            </div>
        </section>

    </main>

    <!-- RODAPÉ -->
    <footer class="footer">
        <p>© <?php echo date('Y'); ?> Insight CX. Todos os direitos reservados.</p>
        <span>Sua experiência importa.</span>
    </footer>

</body>
</html>
