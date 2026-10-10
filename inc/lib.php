<?php
// Общие функции сайта и админки

const ROOT_DIR = __DIR__ . '/..';
const DATA_FILE = ROOT_DIR . '/data/content.json';

const ICONS = [
    'speech' => 'Речь', 'abc' => 'Буквы', 'puzzle' => 'Пазл', 'heart' => 'Сердце', 'bulb' => 'Лампочка',
    'grid' => 'Плитки', 'blocks' => 'Кубики', 'swing' => 'Движение', 'leaf' => 'Лист', 'pulse' => 'Пульс',
    'hand' => 'Ладонь', 'star' => 'Звезда',
];

function load_content(): array
{
    $raw = @file_get_contents(DATA_FILE);
    $data = $raw === false ? null : json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// «+7 (938) 019-90-80» → «79380199080»
function phone_digits(string $phone): string
{
    $d = preg_replace('/\D+/', '', $phone);
    if (strlen($d) === 11 && $d[0] === '8') {
        $d = '7' . substr($d, 1);
    }
    return $d;
}

function wa_link(string $phone, string $text = ''): string
{
    $url = 'https://wa.me/' . phone_digits($phone);
    return $text === '' ? $url : $url . '?text=' . rawurlencode($text);
}

function icon(string $name, string $class = ''): string
{
    return '<svg class="icon' . ($class ? ' ' . $class : '') . '"><use href="#i-' . e($name) . '"/></svg>';
}

// Версия файла в адресе, чтобы браузер не держал старый кеш после обновления
function asset(string $path): string
{
    $mtime = @filemtime(ROOT_DIR . '/' . $path);
    return e($path) . ($mtime ? '?v=' . $mtime : '');
}
