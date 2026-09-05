<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$path   = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$path   = '/' . trim((string)$path, '/');
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    // 静的なパスから順に見る。/members/new を /members/{id} より先に置くこと
    if ($path === '/' && $method === 'GET') {
        MemberController::index();
    } elseif ($path === '/login') {
        $method === 'POST' ? AuthController::login() : AuthController::showLogin();
    } elseif ($path === '/logout' && $method === 'POST') {
        AuthController::logout();
    } elseif ($path === '/members/new' && $method === 'GET') {
        MemberController::createForm();
    } elseif ($path === '/members' && $method === 'POST') {
        MemberController::store();
    } elseif (preg_match('#\A/members/(\d+)\z#', $path, $m) === 1) {
        $method === 'POST'
            ? MemberController::update((int)$m[1])
            : MemberController::show((int)$m[1]);
    } elseif (preg_match('#\A/members/(\d+)/edit\z#', $path, $m) === 1 && $method === 'GET') {
        MemberController::editForm((int)$m[1]);
    } elseif (preg_match('#\A/members/(\d+)/delete\z#', $path, $m) === 1 && $method === 'POST') {
        MemberController::destroy((int)$m[1]);
    } elseif (preg_match('#\A/avatars/([A-Za-z0-9._-]+)\z#', $path, $m) === 1 && $method === 'GET') {
        AvatarController::show($m[1]);
    } elseif ($path === '/mypage/login') {
        $method === 'POST' ? MyPageController::login() : MyPageController::showLogin();
    } elseif ($path === '/mypage/logout' && $method === 'POST') {
        MyPageController::logout();
    } elseif ($path === '/mypage/edit') {
        $method === 'POST' ? MyPageController::update() : MyPageController::editForm();
    } elseif ($path === '/mypage' && $method === 'GET') {
        // /mypage/* はすべて固定パス。member_id は一切 URL に出てこない
        // (04_改修_メンバー個人ログイン.md — URL に ID を含めない設計で IDOR を防ぐ)
        MyPageController::show();
    } else {
        abort(404, 'ページが見つかりません。');
    }
} catch (Throwable $ex) {
    // 例外の中身は画面に出さずログへ。display_errors は Off (02_基本設計.md §7)
    error_log('[skillmap] ' . $ex);
    abort(500, 'エラーが発生しました。時間をおいてやり直してください。');
}
