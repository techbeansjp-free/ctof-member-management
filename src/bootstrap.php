<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('AVATAR_DIR', APP_ROOT . '/storage/avatars');

require APP_ROOT . '/src/helpers.php';
require APP_ROOT . '/src/Database.php';
require APP_ROOT . '/src/Csrf.php';
require APP_ROOT . '/src/Auth.php';
require APP_ROOT . '/src/Repositories/SkillRepository.php';
require APP_ROOT . '/src/Repositories/MemberRepository.php';
require APP_ROOT . '/src/Controllers/AuthController.php';
require APP_ROOT . '/src/Controllers/MemberController.php';
require APP_ROOT . '/src/Controllers/AvatarController.php';

// HTTPS のときだけ Secure 属性を付ける。
// 本番は 443 で SSLEngine on のため必ず付き、ローカル HTTP では付かない。
$isHttps = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off');

session_set_cookie_params([
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
