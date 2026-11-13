
<?php

session_start();

$erro = '';
$sucesso = '';

$nome = '';
$email = '';
$empresa = '';
$cpf = '';
$cnpj = '';
$telefone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recebendo os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $empresa = trim($_POST['empresa'] ?? '');

    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');

    $telefone = trim($_POST['telefone'] ?? '');

    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    $termos = isset($_POST['termos']);

    // ==================================
    // VALIDAÇÃO DOS CAMPOS
    // ==================================

    $camposComErro = [];

    if (!$nome) {
        $camposComErro[] = 'Nome vazio';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $camposComErro[] = 'E-mail inválido';
    }

    if (!$empresa) {
        $camposComErro[] = 'Empresa vazia';
    }

    if (strlen($cpf) !== 11) {
        $camposComErro[] = 'CPF: ' . strlen($cpf) . ' números recebidos';
    }

    if (strlen($cnpj) !== 14) {
        $camposComErro[] = 'CNPJ: ' . strlen($cnpj) . ' números recebidos';
    }

    if (!$telefone) {
        $camposComErro[] = 'Telefone vazio';
    }

    if (!$senha) {
        $camposComErro[] = 'Senha vazia';
    }

    if (!$confirmar) {
        $camposComErro[] = 'Confirmação de senha vazia';
    }

    // Verificando os campos
    if (!empty($camposComErro)) {

        $erro = 'Campos com problema: ' . implode(', ', $camposComErro);

    } elseif (strlen($senha) < 8) {

        $erro = 'A senha deve possuir pelo menos 8 caracteres.';

    } elseif ($senha !== $confirmar) {

        $erro = 'As senhas não são iguais.';


    } else {

        // ==================================
        // DADOS PARA A API
        // ==================================

        $dados = [
            'nome' => $nome,
            'email' => $email,
            'empresa' => $empresa,
            'cnpj' => $cnpj,
            'cpf' => $cpf,
            'telefone' => preg_replace('/\D/', '', $telefone),
            'senha' => $senha
        ];

        // ==================================
        // CONEXÃO COM LARAVEL
        // ==================================

        $curl = curl_init('http://127.0.0.1:8000/api/cadastrar');

        curl_setopt_array($curl, [

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST => true,

            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],

            CURLOPT_POSTFIELDS => json_encode(
                $dados,
                JSON_UNESCAPED_UNICODE
            ),

            CURLOPT_CONNECTTIMEOUT => 10,

            CURLOPT_TIMEOUT => 30

        ]);

        $resposta = curl_exec($curl);

        $status = curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

        $erroCurl = curl_error($curl);

        curl_close($curl);

        // ==================================
        // VERIFICANDO A CONEXÃO
        // ==================================

        if ($resposta === false) {

            $erro = 'Não foi possível conectar ao servidor Laravel.';

            if ($erroCurl) {
                $erro .= ' ' . $erroCurl;
            }

        } else {

            $resultado = json_decode($resposta, true);

            // ==================================
            // CADASTRO REALIZADO
            // ==================================

            if ($status === 201) {

                $sucesso = 'Cadastro realizado com sucesso! Agora você pode fazer login.';

                // Limpando os campos
                $nome = '';
                $email = '';
                $empresa = '';
                $cpf = '';
                $cnpj = '';
                $telefone = '';

            } else {

                $erro = $resultado['message']
                    ?? 'Não foi possível realizar o cadastro.';

                // Exibindo erros de validação da API
                if (!empty($resultado['errors']) && is_array($resultado['errors'])) {

                    foreach ($resultado['errors'] as $mensagens) {

                        foreach ($mensagens as $mensagem) {

                            $erro .= ' ' . $mensagem;

                        }

                    }

                }

                // Mostrando o status HTTP para facilitar o diagnóstico
                $erro .= ' (HTTP ' . $status . ')';

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

    <title>Criar conta | Insight CX</title>

    <link rel="stylesheet" href="../assets/css/cadastro.css">

</head>

<body>

    <main class="right">

        <div class="register">

            <header class="header">

                <div class="subtitle">
                    CRIAR NOVA CONTA
                </div>

                <h2>Cadastre-se no Insight CX</h2>

                <p>
                    Preencha seus dados para começar a utilizar o sistema.
                </p>

            </header>

            <?php if ($erro): ?>

                <div class="message error">

                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>

                </div>

            <?php endif; ?>

            <?php if ($sucesso): ?>

                <div class="message success">

                    <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>

                </div>

            <?php endif; ?>

            <form method="POST" id="registerForm">

                <div class="form-grid">

                    <!-- Nome -->
                    <div class="group full">

                        <label for="nome">
                            Nome completo
                        </label>

                        <div class="input-box">

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                placeholder="Digite seu nome completo"
                                autocomplete="name"
                                value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>

                    </div>

                    <!-- E-mail -->
                    <div class="group">

                        <label for="email">
                            E-mail corporativo
                        </label>

                        <div class="input-box">

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="seuemail@empresa.com.br"
                                autocomplete="email"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>

                    </div>

                    <!-- Empresa -->
                    <div class="group">

                        <label for="empresa">
                            Empresa
                        </label>

                        <div class="input-box">

                            <input
                                type="text"
                                id="empresa"
                                name="empresa"
                                placeholder="Nome da empresa"
                                value="<?= htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>

                    </div>

                    <!-- CNPJ -->
                    <div class="group">

                        <label for="cnpj">
                            CNPJ da empresa
                        </label>

                        <div class="input-box">

                            <input
                                type="text"
                                id="cnpj"
                                name="cnpj"
                                placeholder="00.000.000/0000-00"
                                maxlength="18"
                                inputmode="numeric"
                                value="<?= htmlspecialchars($cnpj, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>

                    </div>

                    <!-- CPF -->
                    <div class="group">

                        <label for="cpf">
                            CPF
                        </label>

                        <div class="input-box">

                            <input
                                type="text"
                                id="cpf"
                                name="cpf"
                                placeholder="000.000.000-00"
                                maxlength="14"
                                inputmode="numeric"
                                value="<?= htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>

                    </div>

                    <!-- Telefone -->
                    <div class="group">

                        <label for="telefone">
                            Telefone
                        </label>

                        <div class="input-box">

                            <input
                                type="tel"
                                id="telefone"
                                name="telefone"
                                placeholder="(00) 00000-0000"
                                maxlength="15"
                                inputmode="tel"
                                value="<?= htmlspecialchars($telefone, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>

                    </div>

                    <!-- Senha -->
                    <div class="group">

                        <label for="senha">
                            Senha
                        </label>

                        <div class="input-box">

                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                placeholder="Mínimo de 8 caracteres"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="toggle"
                                data-target="senha"
                            ><svg width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor"
                                stroke-width="1.7">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg></button>

                        </div>

                    </div>

                    <!-- Confirmar senha -->
                    <div class="group">

                        <label for="confirmar">
                            Confirmar senha
                        </label>

                        <div class="input-box">

                            <input
                                type="password"
                                id="confirmar"
                                name="confirmar"
                                placeholder="Repita sua senha"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="toggle"
                                data-target="confirmar"
                            ><svg width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor"
                                stroke-width="1.7">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg></button>

                        </div>

                    </div>

                </div>

                <br>

                <button
                    type="submit"
                    class="submit-button"
                >
                    Criar minha conta
                </button>

            </form>

            <div class="login-link">

                Já possui uma conta?

                <a href="../index.php">
                    Fazer login
                </a>

            </div>

        </div>

        <footer class="footer">

            © 2025 INSIGHT CX · Versão 1.0.0

        </footer>

    </main>

    <script>

        // Mostrar e ocultar senha
        document.querySelectorAll('.toggle').forEach(button => {

            button.addEventListener('click', () => {

                const campo = document.getElementById(
                    button.dataset.target
                );

                campo.type = campo.type === 'password'
                    ? 'text'
                    : 'password';

            });

        });


        // Máscara CPF
        document.getElementById('cpf').addEventListener('input', function() {

            let value = this.value.replace(/\D/g, '').slice(0, 11);

            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');

            this.value = value;

        });


        // Máscara CNPJ
        document.getElementById('cnpj').addEventListener('input', function() {

            let value = this.value.replace(/\D/g, '').slice(0, 14);

            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');

            this.value = value;

        });


        // Máscara telefone
        document.getElementById('telefone').addEventListener('input', function() {

            let value = this.value.replace(/\D/g, '').slice(0, 11);

            if (value.length > 10) {

                value = value.replace(
                    /^(\d{2})(\d{5})(\d{4})$/,
                    '($1) $2-$3'
                );

            } else if (value.length > 6) {

                value = value.replace(
                    /^(\d{2})(\d{4})(\d{1,4})$/,
                    '($1) $2-$3'
                );

            } else if (value.length > 2) {

                value = value.replace(
                    /^(\d{2})(\d+)$/,
                    '($1) $2'
                );

            }

            this.value = value;

        });


        // Validar formulário antes do envio
        document.getElementById('registerForm').addEventListener('submit', function(e) {

            const senha = document.getElementById('senha').value;

            const confirmar = document.getElementById('confirmar').value;

            if (senha !== confirmar) {

                e.preventDefault();

                alert('As senhas não coincidem!');

                return;

            }

            if (senha.length < 8) {

                e.preventDefault();

                alert('A senha precisa ter pelo menos 8 caracteres.');

            }

        });


        // Termos de uso
        document.getElementById('termsLink').addEventListener('click', function(e) {

            e.preventDefault();

            alert('Adicione aqui o documento de Termos de Uso.');

        });

    </script>

</body>

</html>
