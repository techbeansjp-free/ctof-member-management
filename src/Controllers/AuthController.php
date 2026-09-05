<?php
declare(strict_types=1);

final class AuthController
{
    public static function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        Auth::ensureAdminExists();
        view('login', ['error' => null, 'loginId' => ''], 'ログイン');
    }

    public static function login(): void
    {
        Csrf::verify();
        Auth::ensureAdminExists();

        $loginId  = trim((string)($_POST['login_id'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (Auth::attempt($loginId, $password)) {
            redirect('/');
        }

        // 「ID が違う」「パスワードが違う」を区別しない
        http_response_code(401);
        view('login', [
            'error'   => 'ログインIDまたはパスワードが違います。',
            'loginId' => $loginId,
        ], 'ログイン');
    }

    public static function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        redirect('/login');
    }
}
