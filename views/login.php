<div class="gate">
  <form method="post" action="<?= e($actionUrl) ?>">
    <?= Csrf::field() ?>
    <h1>ログイン</h1>
    <p class="gate-sub"><?= e($subtitle) ?></p>

    <?php if ($error !== null): ?>
      <p class="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <label for="login_id">ログインID</label>
    <input id="login_id" name="login_id" type="text" autocomplete="username"
           value="<?= e($loginId) ?>" required autofocus>

    <label for="password">パスワード</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>

    <button type="submit" class="btn btn-solid btn-wide">ログイン</button>

    <p class="gate-switch">
      <a href="<?= e($otherLoginUrl) ?>"><?= e($otherLoginLabel) ?></a>
    </p>
  </form>
</div>
