<?php
declare(strict_types=1);

/** 管理者ログイン。メンバー本人のログインは MyPageController */
final class AuthController
{
    public static function showLogin(): void
    {
        if (Auth::checkAdmin()) {
            redirect('/');
        }
        Auth::ensureAdminExists();
        view('login', self::viewData('', null), 'ログイン');
    }

    public static function login(): void
    {
        Csrf::verify();
        Auth::ensureAdminExists();

        $loginId  = trim((string)($_POST['login_id'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (Auth::attemptAdmin($loginId, $password)) {
            redirect('/');
        }

        // 「ID が違う」「パスワードが違う」を区別しない
        http_response_code(401);
        view('login', self::viewData($loginId, 'ログインIDまたはパスワードが違います。'), 'ログイン');
    }

    public static function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        redirect('/login');
    }

    /**
     * メンバー本人はこの画面にたどり着いても絶対にログインできない
     * (member_credentials はここでは見ない)。/mypage/login への導線を
     * 必ず出す。これが無いと「URLを渡されただけの人が / を開く → ここに
     * リダイレクトされる → 自分のIDでは通らない」で詰む
     * (04_改修_メンバー個人ログイン.md 運用トラブル参照)。
     */
    private static function viewData(string $loginId, ?string $error): array
    {
        return [
            'error'           => $error,
            'loginId'         => $loginId,
            'actionUrl'       => '/login',
            'subtitle'        => 'メンバー名簿を開きます',
            'otherLoginUrl'   => '/mypage/login',
            'otherLoginLabel' => 'メンバーの方はこちら',
        ];
    }
}
