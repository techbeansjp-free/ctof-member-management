<?php
$grouped = [];
foreach ($skills as $s) {
    $grouped[$s['category_name']][] = $s;
}
?>
<?php if ($backUrl !== null): ?>
  <a class="back" href="<?= e($backUrl) ?>">← メンバー名簿</a>
<?php endif; ?>

<article class="profile">
  <header class="profile-head">
    <?= avatar_html($member, 52) ?>
    <div>
      <h1><?= e($member['name']) ?></h1>
      <p class="profile-meta">スキル <?= count($skills) ?> 件</p>
    </div>
    <a class="btn" href="<?= e($editUrl) ?>">編集</a>
  </header>

  <?php if (!empty($member['bio'])): ?>
    <section class="block">
      <h2>自己紹介</h2>
      <p class="bio"><?= nl2br(e((string)$member['bio'])) ?></p>
    </section>
  <?php endif; ?>

  <section class="block">
    <h2>スキル</h2>
    <?php if ($grouped === []): ?>
      <p class="note">まだ登録されていません。「編集」から追加できます。</p>
    <?php else: ?>
      <dl class="sheet-rows">
        <?php foreach ($grouped as $categoryName => $items): ?>
          <div class="sheet-row">
            <dt><?= e((string)$categoryName) ?></dt>
            <dd>
              <ul class="chips">
                <?php foreach ($items as $s): ?><?= skill_chip($s) ?><?php endforeach; ?>
              </ul>
            </dd>
          </div>
        <?php endforeach; ?>
      </dl>
    <?php endif; ?>
  </section>
</article>
