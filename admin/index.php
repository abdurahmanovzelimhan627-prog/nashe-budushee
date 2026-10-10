<?php
// Админка сайта «Наше Будущее»: вход по паролю, правка содержимого, загрузка фото.
require __DIR__ . '/../inc/lib.php';

const CONFIG_FILE = ROOT_DIR . '/data/config.php';
const LOGIN_FILE = ROOT_DIR . '/data/login.json';
const BACKUP_DIR = ROOT_DIR . '/data/backups';
const UPLOAD_DIR = ROOT_DIR . '/uploads';
const MAX_UPLOAD = 12 * 1024 * 1024;
const MAX_FAILS = 5;
const LOCK_SECONDS = 600;
const KEEP_BACKUPS = 30;
const IMAGE_SIDE = ['photo' => 900, 'poster' => 1400, 'review' => 1200];

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_name('nb_admin');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => $https, 'path' => '/']);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

$config = is_file(CONFIG_FILE) ? (require CONFIG_FILE) : null;
$authed = !empty($_SESSION['admin']);
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function str_clean($v, int $max = 2000): string
{
    return is_scalar($v) ? mb_substr(trim((string) $v), 0, $max) : '';
}

function img_clean($v): string
{
    $v = is_string($v) ? $v : '';
    return preg_match('~^uploads/[A-Za-z0-9_.-]+\.(jpe?g|png|webp)$~', $v) && is_file(ROOT_DIR . '/' . $v) ? $v : '';
}

function list_clean($v, int $max = 60): array
{
    return is_array($v) ? array_slice(array_values($v), 0, $max) : [];
}

// Принимаем от браузера только известные поля и только строки нужной длины
function clean_content(array $in): array
{
    $out = ['contacts' => [], 'texts' => [], 'people' => [], 'services' => [], 'prices' => [], 'steps' => [], 'reviews' => [], 'faq' => []];
    foreach (['phone', 'instagram', 'address', 'hours'] as $k) {
        $out['contacts'][$k] = str_clean($in['contacts'][$k] ?? '', 200);
    }
    foreach (['hero_sub', 'hero_note', 'price_note'] as $k) {
        $out['texts'][$k] = str_clean($in['texts'][$k] ?? '', 600);
    }
    $ids = [];
    foreach (list_clean($in['people'] ?? []) as $p) {
        $name = str_clean($p['name'] ?? '', 120);
        if ($name === '') {
            continue;
        }
        $id = str_clean($p['id'] ?? '', 40);
        if (!preg_match('/^[a-z0-9_-]{1,40}$/', $id) || isset($ids[$id])) {
            $id = 'p' . bin2hex(random_bytes(4));
        }
        $ids[$id] = true;
        $out['people'][] = [
            'id' => $id, 'name' => $name,
            'role' => str_clean($p['role'] ?? '', 200), 'exp' => str_clean($p['exp'] ?? '', 80), 'about' => str_clean($p['about'] ?? '', 400),
            'photo' => img_clean($p['photo'] ?? ''), 'poster' => img_clean($p['poster'] ?? ''),
        ];
    }
    foreach (list_clean($in['services'] ?? []) as $s) {
        $title = str_clean($s['title'] ?? '', 120);
        if ($title === '') {
            continue;
        }
        $icon = str_clean($s['icon'] ?? '', 20);
        $out['services'][] = [
            'title' => $title, 'text' => str_clean($s['text'] ?? '', 400),
            'icon' => isset(ICONS[$icon]) ? $icon : 'star',
            'price' => str_clean($s['price'] ?? '', 40), 'price_note' => str_clean($s['price_note'] ?? '', 80),
            'people' => array_values(array_filter(list_clean($s['people'] ?? [], 10), fn($id) => is_string($id) && isset($ids[$id]))),
        ];
    }
    foreach (list_clean($in['prices'] ?? [], 20) as $t) {
        $title = str_clean($t['title'] ?? '', 120);
        if ($title === '') {
            continue;
        }
        $rows = [];
        foreach (list_clean($t['rows'] ?? [], 6) as $r) {
            $label = str_clean($r['label'] ?? '', 60);
            $value = str_clean($r['value'] ?? '', 40);
            if ($label !== '' || $value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }
        $tags = array_values(array_filter(array_map(fn($x) => str_clean($x, 60), list_clean($t['tags'] ?? [], 12)), 'strlen'));
        $out['prices'][] = ['title' => $title, 'tags' => $tags, 'rows' => $rows];
    }
    foreach (list_clean($in['steps'] ?? [], 8) as $s) {
        $title = str_clean($s['title'] ?? '', 120);
        if ($title !== '') {
            $out['steps'][] = ['title' => $title, 'text' => str_clean($s['text'] ?? '', 400)];
        }
    }
    foreach (list_clean($in['reviews'] ?? []) as $r) {
        $img = img_clean($r['img'] ?? '');
        if ($img !== '') {
            $out['reviews'][] = ['img' => $img, 'alt' => str_clean($r['alt'] ?? '', 300)];
        }
    }
    foreach (list_clean($in['faq'] ?? [], 30) as $f) {
        $q = str_clean($f['q'] ?? '', 200);
        if ($q !== '') {
            $out['faq'][] = ['q' => $q, 'a' => str_clean($f['a'] ?? '', 1000)];
        }
    }
    return $out;
}

function save_content(array $content): void
{
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
    if (is_file(DATA_FILE)) {
        copy(DATA_FILE, BACKUP_DIR . '/content-' . date('Ymd-His') . '.json');
        $old = glob(BACKUP_DIR . '/content-*.json');
        sort($old);
        foreach (array_slice($old, 0, max(0, count($old) - KEEP_BACKUPS)) as $f) {
            unlink($f);
        }
    }
    $tmp = DATA_FILE . '.tmp';
    $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, DATA_FILE)) {
        json_out(['error' => 'Не удалось записать файл. Проверьте права на папку data.'], 500);
    }
}

function handle_upload(): never
{
    $kind = $_POST['kind'] ?? '';
    $f = $_FILES['file'] ?? null;
    if (!isset(IMAGE_SIDE[$kind]) || !$f || $f['error'] !== UPLOAD_ERR_OK) {
        json_out(['error' => 'Файл не загрузился. Попробуйте ещё раз или выберите файл поменьше.'], 400);
    }
    if ($f['size'] > MAX_UPLOAD) {
        json_out(['error' => 'Файл больше 12 МБ. Выберите фото поменьше.'], 400);
    }
    $info = @getimagesize($f['tmp_name']);
    $loaders = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
    if (!$info || !isset($loaders[$info[2]]) || !function_exists($loaders[$info[2]])) {
        json_out(['error' => 'Подходят только фото JPG, PNG или WEBP.'], 400);
    }
    $src = @$loaders[$info[2]]($f['tmp_name']);
    if (!$src) {
        json_out(['error' => 'Не получилось прочитать фото. Попробуйте другой файл.'], 400);
    }
    // Телефоны пишут поворот снимка отдельной меткой — применяем её
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $turn = [3 => 180, 6 => -90, 8 => 90][@exif_read_data($f['tmp_name'])['Orientation'] ?? 0] ?? 0;
        if ($turn) {
            $src = imagerotate($src, $turn, 0);
        }
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, IMAGE_SIDE[$kind] / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $name = $kind . '_' . date('ymd') . '_' . bin2hex(random_bytes(5)) . '.jpg';
    if (!imagejpeg($dst, UPLOAD_DIR . '/' . $name, 84)) {
        json_out(['error' => 'Не удалось сохранить фото. Проверьте права на папку uploads.'], 500);
    }
    json_out(['path' => 'uploads/' . $name]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF'] ?? '')) {
        json_out(['error' => 'Сессия устарела. Обновите страницу и войдите снова.'], 403);
    }
    if ($action === 'login') {
        $lock = is_file(LOGIN_FILE) ? (json_decode(file_get_contents(LOGIN_FILE), true) ?: []) : [];
        if (($lock['until'] ?? 0) > time()) {
            json_out(['error' => 'Слишком много попыток. Подождите ' . ceil(($lock['until'] - time()) / 60) . ' мин.'], 429);
        }
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        if ($config && password_verify((string) ($body['password'] ?? ''), $config['password_hash'])) {
            @unlink(LOGIN_FILE);
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            json_out(['ok' => true]);
        }
        $fails = ($lock['fails'] ?? 0) + 1;
        file_put_contents(LOGIN_FILE, json_encode(['fails' => $fails >= MAX_FAILS ? 0 : $fails, 'until' => $fails >= MAX_FAILS ? time() + LOCK_SECONDS : 0]), LOCK_EX);
        json_out(['error' => 'Неверный пароль.'], 401);
    }
    if (!$authed) {
        json_out(['error' => 'Нужно войти заново.'], 401);
    }
    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        json_out(['ok' => true]);
    }
    if ($action === 'upload') {
        handle_upload();
    }
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) {
        json_out(['error' => 'Данные не дошли. Попробуйте ещё раз.'], 400);
    }
    if ($action === 'save') {
        $content = clean_content($body['content'] ?? []);
        save_content($content);
        json_out(['ok' => true, 'content' => $content]);
    }
    if ($action === 'password') {
        $new = (string) ($body['new'] ?? '');
        if (!password_verify((string) ($body['old'] ?? ''), $config['password_hash'])) {
            json_out(['error' => 'Старый пароль введён неверно.'], 400);
        }
        if (mb_strlen($new) < 10) {
            json_out(['error' => 'Новый пароль должен быть не короче 10 символов.'], 400);
        }
        $code = "<?php\nreturn " . var_export(['password_hash' => password_hash($new, PASSWORD_DEFAULT)], true) . ";\n";
        if (file_put_contents(CONFIG_FILE, $code, LOCK_EX) === false) {
            json_out(['error' => 'Не удалось сохранить пароль. Проверьте права на папку data.'], 500);
        }
        json_out(['ok' => true]);
    }
    json_out(['error' => 'Неизвестное действие.'], 400);
}

$boot = ['csrf' => $_SESSION['csrf'], 'authed' => $authed, 'ready' => (bool) $config];
if ($authed) {
    $boot['content'] = load_content();
    $boot['icons'] = ICONS;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Админка — Наше Будущее</title>
<link rel="stylesheet" href="../fonts/fonts.css">
<link rel="stylesheet" href="admin.css?v=<?= @filemtime(__DIR__ . '/admin.css') ?>">
</head>
<body>
<?php readfile(ROOT_DIR . '/inc/sprite.html'); ?>
<div id="app"></div>
<script>window.BOOT = <?= json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="admin.js?v=<?= @filemtime(__DIR__ . '/admin.js') ?>"></script>
</body>
</html>
