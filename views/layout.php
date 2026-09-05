<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title !== '' ? $title . ' | SkillMap' : 'SkillMap') ?></title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="site-header">
  <a class="brand" href="/">SkillMap<small>シートエフ メンバースキル一覧</small></a>
  <?php if (Auth::check()): ?>
    <nav class="site-nav">
      <a class="btn btn-primary" href="/members/new">メンバーを登録</a>
      <form method="post" action="/logout">
        <?= Csrf::field() ?>
        <button type="submit" class="btn">ログアウト</button>
      </form>
    </nav>
  <?php endif; ?>
</header>

<main class="container">
  <?php $f = flash(); if ($f !== null): ?>
    <p class="flash"><?= e($f) ?></p>
  <?php endif; ?>
  <?php /* $content は各ビューが生成済みの HTML。変数の出力は各ビュー側で e() 済み */ ?>
  <?= $content ?>
</main>
</body>
</html>
