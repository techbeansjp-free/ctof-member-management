<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title !== '' ? $title . ' | SkillMap' : 'SkillMap') ?></title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="topbar">
  <a class="wordmark" href="<?= Auth::checkMember() ? '/mypage' : '/' ?>">
    <b>SkillMap</b><span>シートエフ メンバー名簿</span>
  </a>
  <?php if (Auth::checkAdmin()): ?>
    <div class="topbar-actions">
      <a class="btn btn-solid" href="/members/new">メンバーを追加</a>
      <form method="post" action="/logout">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-quiet">ログアウト</button>
      </form>
    </div>
  <?php elseif (Auth::checkMember()): ?>
    <div class="topbar-actions">
      <form method="post" action="/mypage/logout">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-quiet">ログアウト</button>
      </form>
    </div>
  <?php endif; ?>
</header>

<div class="shell">
  <?php $f = flash(); if ($f !== null): ?>
    <p class="notice"><?= e($f) ?></p>
  <?php endif; ?>
  <?php /* $content は各ビューが生成した HTML。値のエスケープは各ビュー側で済ませている */ ?>
  <?= $content ?>
</div>

<script src="/assets/app.js" defer></script>
</body>
</html>
