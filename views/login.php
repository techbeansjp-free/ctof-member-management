<div class="login-wrap">
  <form class="login-card" method="post" action="/login">
    <?= Csrf::field() ?>
    <h1>ログイン</h1>
    <?php if ($error !== null): ?>
      <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <label for="login_id">ログインID</label>
    <input id="login_id" name="login_id" type="text" autocomplete="username"
           value="<?= e($loginId) ?>" required autofocus>

    <label for="password">パスワード</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>

    <button type="submit" class="btn btn-primary btn-block">ログイン</button>
  </form>
</div>
