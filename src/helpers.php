<?php
declare(strict_types=1);

/** HTML エスケープ。ビューでの変数出力は必ずこれを通す (02_基本設計.md §7) */
function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function abort(int $status, string $message = ''): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><meta charset="utf-8"><title>' . $status . '</title>'
       . '<body style="font-family:sans-serif;padding:2rem">'
       . '<h1>' . $status . '</h1><p>' . e($message) . '</p>'
       . '<p><a href="/">トップへ</a></p>';
    exit;
}

function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/** ビューを layout に流し込んで出力する */
function view(string $name, array $data = [], string $title = ''): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_ROOT . '/views/' . $name . '.php';
    $content = ob_get_clean();
    require APP_ROOT . '/views/layout.php';
}

/** 現在のクエリを引き継いだ URL を作る */
function url_with(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter(
        $params,
        static fn($v) => $v !== '' && $v !== null && $v !== []
    );
    return $params === [] ? '/' : '/?' . http_build_query($params);
}

/** レベルの定義 (01_要件定義.md §4)。「未経験」は段階として持たない */
function skill_levels(): array
{
    return [1 => '学習中', 2 => '実務経験あり', 3 => '指導できる'];
}

function level_label(int $level): string
{
    return skill_levels()[$level] ?? '';
}

/** アイコン。未設定なら氏名の頭 1 文字を丸背景で出す (01_要件定義.md §5.2) */
function avatar_html(array $member, int $size = 44): string
{
    $style = 'width:' . $size . 'px;height:' . $size . 'px';

    if (!empty($member['avatar_path'])) {
        return '<img class="avatar" style="' . $style . '"'
             . ' src="/avatars/' . e((string)$member['avatar_path']) . '" alt="">';
    }

    $name = (string)($member['name'] ?? '');
    $hue  = crc32($name) % 360;
    return '<span class="avatar avatar-initial" style="' . $style
         . ';background:hsl(' . $hue . ' 42% 58%);font-size:' . round($size * 0.42) . 'px">'
         . e(mb_substr($name, 0, 1)) . '</span>';
}
