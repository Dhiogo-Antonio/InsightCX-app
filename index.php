
<?php
session_start();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!$email || !$senha) {

        $erro = 'Informe seu e-mail e senha.';

    } else {

        $dados = [
            'email' => $email,
            'senha' => $senha
        ];

        $curl = curl_init('http://127.0.0.1:8000/api/login');

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($dados),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15
        ]);

        $resposta = curl_exec($curl);
        $erroCurl = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($resposta === false) {

            $erro = 'Não foi possível conectar à API.';

        } else {

            $resultado = json_decode($resposta, true);

            if ($status === 200 && isset($resultado['usuario'], $resultado['token'])) {

                $usuario = $resultado['usuario'];

                if ($usuario['tipo'] === 'admin' || $usuario['tipo'] === 'cliente') {

                    session_regenerate_id(true);

                    $_SESSION['usuario'] = $usuario;
                    $_SESSION['token'] = $resultado['token'];

                    if ($usuario['tipo'] === 'admin') {
                        header('Location: views/admin/dashboard.php');
                        exit;
                    }

                    if ($usuario['tipo'] === 'cliente') {
                        header('Location: views/cliente/index.php');
                        exit;
                    }

                } else {

                    $erro = 'Tipo de usuário não autorizado.';
                }

            } else {

                $erro = $resultado['message'] ?? 'Erro ao realizar login.';
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insight CX - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <main class="right">

        <div class="login">

            <header class="login-header">
                <div class="subtitle">ACESSO AO SISTEMA</div>
                <h2>Bem-vindo ao Insight CX</h2>
                <p>Insira suas credenciais para acessar sua conta.</p>
            </header>

            <?php if ($erro): ?>
                <div class="error">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="group">
                    <label for="email">E-mail corporativo</label>

                    <div class="input-box">
                        <svg width="17" height="17" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="m3 7 9 6 9-6" />
                        </svg>

                        <input type="email" name="email" id="email"
                            placeholder="seuemail@empresa.com.br"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required>
                    </div>
                </div>

                <div class="group">
                    <label for="senha">Senha</label>

                    <div class="input-box">
                        <svg width="17" height="17" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="4" y="10" width="16" height="11" rx="2" />
                            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                        </svg>

                        <input type="password" name="senha" id="senha"
                            placeholder="Digite sua senha" required>

                        <button type="button" class="eye"
                            id="togglePassword" aria-label="Mostrar senha">
                            <svg width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor"
                                stroke-width="1.7">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="options">
                    <div class="cadastro-link">
                        <p>Ainda não tem uma conta?</p>
                        <a href="views/cadastro.php">Criar conta</a>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    Entrar no Insight CX
                    <svg width="17" height="17" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                    </svg>
                </button>

            </form>

           
        </div>

        <footer class="right-footer">
            © 2025 INSIGHT CX · Versão 1.0.0
        </footer>

    </main>

    <script>
       
const senha = document.getElementById('senha');
const toggle = document.getElementById('togglePassword');

toggle.addEventListener('click', () => {
    const mostrar = senha.type === 'password';

    senha.type = mostrar ? 'text' : 'password';

    toggle.setAttribute(
        'aria-label',
        mostrar ? 'Ocultar senha' : 'Mostrar senha'
    );
});

    </script>

</body>

</html>