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

/** 英数記号のみか。技術トークン (React, HTML/CSS) を等幅で組むための判定 */
function is_latin_token(string $s): bool
{
    return preg_match('/\A[\x20-\x7E]+\z/', $s) === 1;
}

/**
 * レベルメーター。3本のバーの高さで順序を表す。
 * レベルは順序尺度なので、カテゴリ色ではなく段階のあるメーターで符号化する。
 */
function level_meter(int $level): string
{
    $out = '<span class="meter" aria-hidden="true">';
    for ($i = 1; $i <= 3; $i++) {
        $out .= '<i' . ($i <= $level ? ' class="on"' : '') . '></i>';
    }
    return $out . '</span>';
}

/**
 * スキルチップ。
 * $matched = 絞り込み条件に含まれるスキル。画面上で有彩色を使うのはここだけ。
 */
function skill_chip(array $skill, bool $matched = false): string
{
    $level = (int)$skill['level'];
    $name  = (string)$skill['skill_name'];

    $class = 'chip lv' . $level;
    if (is_latin_token($name)) { $class .= ' chip-mono'; }
    if ($matched)              { $class .= ' is-match'; }

    return '<li class="' . $class . '" title="' . e($name . ' — ' . level_label($level)) . '">'
         . level_meter($level)
         . '<span class="chip-name">' . e($name) . '</span>'
         . '</li>';
}

/**
 * アイコン。未設定なら氏名の頭 1 文字。
 * 背景は算出した虹色ではなく、彩度を抑えた 5 色から選ぶ (画面全体の色数を絞るため)。
 */
function avatar_html(array $member, int $size = 30): string
{
    $style = 'width:' . $size . 'px;height:' . $size . 'px';

    if (!empty($member['avatar_path'])) {
        return '<img class="avatar" style="' . $style . '"'
             . ' src="/avatars/' . e((string)$member['avatar_path']) . '" alt="">';
    }

    $tones = ['#5b6b78', '#6d7a63', '#7a6a63', '#5f6a7d', '#75677a'];
    $name  = (string)($member['name'] ?? '');
    $tone  = $tones[crc32($name) % count($tones)];

    return '<span class="avatar" style="' . $style . ';background:' . $tone
         . ';font-size:' . round($size * 0.4) . 'px">'
         . e(mb_substr($name, 0, 1)) . '</span>';
}
