
<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['token'])) {

    $curl = curl_init('http://127.0.0.1:8000/api/logout');

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $_SESSION['token']
        ],
        CURLOPT_TIMEOUT => 10
    ]);

    curl_exec($curl);
    curl_close($curl);
}

// Encerra a sessão local mesmo se a API estiver indisponível.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: index.php');
exit;
