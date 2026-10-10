<?php
// Задать пароль админки. Запуск только из терминала: php inc/set-password.php "новый пароль"
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$password = $argv[1] ?? '';
if (mb_strlen($password) < 10) {
    fwrite(STDERR, "Пароль должен быть не короче 10 символов.\n");
    exit(1);
}
$file = __DIR__ . '/../data/config.php';
file_put_contents($file, "<?php\nreturn " . var_export(['password_hash' => password_hash($password, PASSWORD_DEFAULT)], true) . ";\n");
echo "Пароль сохранён в data/config.php\n";
