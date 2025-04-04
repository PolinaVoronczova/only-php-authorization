<?php

use Controllers\Route\Router;
use OnlyPhpAuthorization\Controllers\PdoConnection;
use Controllers\User\User;
use Controllers\User\UserValidator;

define('APP_PATH', dirname(__DIR__));

require_once APP_PATH . '/autoload.php';

function setYandexSmartCaptchaToken()
{
    $params = parse_ini_file(APP_PATH . '/yandex-smartcaptcha.ini');
    if ($params === false) {
        throw new \Exception("Проверьте файл конфигурации yandex-smartcaptcha.ini.");
    } else {
        $_SESSION['client_key'] = $params['client_key'];
        $_SESSION['server_key'] = $params['server_key'];
    }
}
function checkCaptcha($token) {
    $ch = curl_init("https://smartcaptcha.yandexcloud.net/validate");

    $args = [
        "secret" => $_SESSION['server_key'],
        "token" => $token,
        "ip" => $_SERVER['REMOTE_ADDR']
    ];

    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_setopt($ch, CURLOPT_POST, true);    
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch); 
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo $httpcode;
    if ($httpcode !== 200) {
        return false;
    }
 
    $resp = json_decode($server_output);
    return $resp->status === "ok";
}

function pdoConnect() : PDO
{
    $params = parse_ini_file(APP_PATH . '/database/database.ini');
    if ($params === false) {
        throw new \Exception("Проверьте файл конфигурации database.ini.");
    }

    $conStr = sprintf(
        "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
        $params['host'],
        $params['port'],
        $params['database'],
        $params['user'],
        $params['password']
    );

    $pdo = new \PDO($conStr);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
}

session_start();

try {
    setYandexSmartCaptchaToken();
} catch (\Throwable $e) {
    echo $e->getMessage();
}

$router = new Router();

$router->addRoute('GET', '/', function() {
    include APP_PATH . '/views/index.phtml';
});

$router->addRoute('GET', '/registration', function() {
    include APP_PATH . '/views/registration.phtml';

    unset($_SESSION["reg_validation_errors"]); 
    unset($_SESSION['reg_old']);
});

$router->addRoute('POST', '/registration', function() {
    try {
        $pdo = pdoConnect();
    } catch (\Throwable $e) {
        echo $e->getMessage();
    }
    
    $validator = new UserValidator($pdo);
    $errors = $validator
        ->uniqueName($_POST['name'])
        ->uniquePhone($_POST['phone'])
        ->validPhone($_POST['phone'])
        ->uniqueEmail($_POST['email'])
        ->validEmail($_POST['email'])
        ->validPassword($_POST['password'], $_POST['confirmPassword'])
        ->getErrors();

    if ($errors) {
        $_SESSION['reg_validation_errors'] = $errors;
        $_SESSION['reg_old'] = $_POST;
        header("Location: registration");
        exit();
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user = new User($pdo);
        $user->create($_POST['name'], $_POST['phone'], $_POST['email'], $password);
        header("Location: login");
        exit();
    }
    
});

$router->addRoute('GET', '/login', function() {
    include APP_PATH . '/views/login.phtml';

    unset($_SESSION['login_error']);
});

$router->addRoute('POST', '/login', function() {
    try {
        $pdo = pdoConnect();
    } catch (\Throwable $e) {
        echo $e->getMessage();
    }

    $user = new User($pdo);

    if (!($user->findBy('email', $_POST['login'], true)
        || $user->findBy('phone', $_POST['login'], true))
    ) {
        $_SESSION['login_error'] = 'Пользователь не найден.';
        $_SESSION['log_old'] = $_POST;

        header("Location: login");
        exit();
    } elseif (!password_verify($_POST["password"], $user->getPassword())) {
        $_SESSION['login_error'] = 'Неверный пароль.';
        $_SESSION['log_old'] = $_POST;

        header("Location: login");
        exit();
    } elseif (!checkCaptcha($_POST['smart-token'])) {
        $_SESSION['login_error'] = 'Капча не пройдена.';

        header("Location: login");
        exit();
    }

    $_SESSION['user_id'] = $user->getId();
    $_SESSION['user_name'] = $user->getName();
    $_SESSION['user_phone'] = $user->getPhone();
    $_SESSION['user_email'] = $user->getEmail();

    header("Location: personal-account");
    exit();
});

$router->addRoute('GET', '/personal-account', function() {
    if (isset($_SESSION['user_id'])) {
        include APP_PATH . '/views/personal-account.phtml';
        
        unset($_SESSION['edit_validation_errors']);
    } else {
        header("Location: /");
        exit();
    }
    
});

$router->addRoute('POST', '/personal-account', function() {
    try {
        $pdo = pdoConnect();
    } catch (\Throwable $e) {
        echo $e->getMessage();
    }

    $validator = new UserValidator($pdo);
    $errors = $validator
        ->validPhone($_POST['phone'])
        ->validEmail($_POST['email'])
        ->validPassword($_POST['password'], $_POST['confirmPassword'])
        ->getErrors();

    if ($errors) {
        $_SESSION['edit_validation_errors'] = $errors;

        header("Location: personal-account");
        exit();
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $user = new User($pdo);

        $user->findBy('id', $_SESSION['user_id'], true);
        $user->edit($_POST['name'], $_POST['phone'], $_POST['email'], $password);

        $_SESSION['user_name'] = $user->getName();
        $_SESSION['user_phone'] = $user->getPhone();
        $_SESSION['user_email'] = $user->getEmail();
    }

    header("Location: personal-account");
    exit();
});

$router->addRoute('POST', '/logout', function() 
{
    session_destroy();
    setcookie("user", 'false', time() - 1);

    header("Location: /");
    exit();
});

$requestUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

$router->getRoute($requestMethod, $requestUrl);