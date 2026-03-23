<?php
session_start();

if (!isset($_SESSION['habits'])) {
    $_SESSION['habits'] = [];
}

$flash_errors  = $_SESSION['flash_errors']  ?? [];
$flash_success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_errors'], $_SESSION['flash_success']);

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(16));
}
$token = $_SESSION['token'];

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$getRoutes = [
        '/'    => 'show_habits',
        '/new' => 'show_form',
];

$postRoutes = [
        '/new' => 'create_habit',
];

$routes = ($method === 'POST') ? $postRoutes : $getRoutes;

if (array_key_exists($path, $routes)) {
    $handler = $routes[$path];
    $handler($flash_errors, $flash_success, $token);
} else {
    http_response_code(404);
    echo 'Страница не найдена (404)';
}


function show_habits(array $errors, string $success, string $token): void
{
    $habits = $_SESSION['habits'];
    ?>
    <h1>Трекер привычек</h1>

    <?php if ($success): ?>
    <p style="color: green"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

    <?php if ($errors): ?>
    <?php foreach ($errors as $error): ?>
        <p style="color: red"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>
<?php endif; ?>

    <?php if (empty($habits)): ?>
    <p>Пока нет ни одной привычки.</p>
<?php else: ?>
    <?php foreach ($habits as $habit): ?>
        <p><?= htmlspecialchars($habit) ?></p>
    <?php endforeach; ?>
<?php endif; ?>

    <a href="/new">Добавить привычку</a>
    <?php
}

function show_form(array $errors, string $success, string $token): void
{
    ?>
    <h1>Новая привычка</h1>

    <?php if ($errors): ?>
    <?php foreach ($errors as $error): ?>
        <p style="color: red"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>
<?php endif; ?>

    <form method="POST" action="/new">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label>Привычка:
            <input type="text" name="title">
        </label>
        <br><br>

        <button type="submit">Сохранить</button>
    </form>

    <p><a href="/">Назад к списку</a></p>
    <?php
}

function create_habit(array $errors, string $success, string $token): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo 'Метод не разрешён';
        return;
    }

    if (
            !isset($_POST['token']) ||
            !hash_equals($_SESSION['token'], $_POST['token'])
    ) {
        $_SESSION['flash_errors'] = ['Неверный токен безопасности, попробуйте ещё раз'];
        header('Location: /new');
        exit;
    }

    $title = trim($_POST['title'] ?? '');

    $errors = [];

    if ($title === '') {
        $errors[] = 'Название привычки обязательно';
    } elseif (mb_strlen($title) < 3) {
        $errors[] = 'Название должно быть не короче 3 символов';
    }

    if ($errors) {
        $_SESSION['flash_errors'] = $errors;
        header('Location: /new');
        exit;
    }

    $_SESSION['habits'][] = $title;

    $_SESSION['flash_success'] = 'Привычка добавлена';

    header('Location: /');
    exit;
}
