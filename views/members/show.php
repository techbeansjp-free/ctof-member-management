<?php
// カテゴリごとにまとめ直す
$grouped = [];
foreach ($skills as $s) {
    $grouped[$s['category_name']][] = $s;
}
?>
<p class="breadcrumb"><a href="/">← メンバー一覧</a></p>

<article class="detail">
  <header class="detail-head">
    <?= avatar_html($member, 96) ?>
    <div>
      <h1><?= e($member['name']) ?></h1>
      <p class="detail-meta"><?= count($skills) ?> 件のスキル</p>
    </div>
    <a class="btn btn-primary" href="/members/<?= (int)$member['id'] ?>/edit">編集</a>
  </header>

  <?php if (!empty($member['bio'])): ?>
    <section class="detail-section">
      <h2>自己紹介</h2>
      <p class="bio"><?= nl2br(e((string)$member['bio'])) ?></p>
    </section>
  <?php endif; ?>

  <section class="detail-section">
    <h2>スキル</h2>
    <?php if ($grouped === []): ?>
      <p class="hint">まだスキルが登録されていません。</p>
    <?php else: ?>
      <?php foreach ($grouped as $categoryName => $items): ?>
        <div class="skill-group">
          <h3><?= e((string)$categoryName) ?></h3>
          <ul class="badges">
            <?php foreach ($items as $s): ?>
              <li class="badge lv<?= (int)$s['level'] ?>">
                <?= e($s['skill_name']) ?>
                <small><?= e(level_label((int)$s['level'])) ?></small>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</article>
