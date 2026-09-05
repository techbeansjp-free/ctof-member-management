// スキルは 55 件あるので、目的のものを探せるようにする。
// 絞り込みそのものはサーバー側 (URL クエリ) で行う。ここは選択肢の表示を絞るだけ。
document.querySelectorAll('[data-filter-target]').forEach((input) => {
  const scope = document.getElementById(input.dataset.filterTarget);
  if (!scope) return;

  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();

    scope.querySelectorAll('[data-name]').forEach((row) => {
      row.hidden = q !== '' && !row.dataset.name.toLowerCase().includes(q);
    });

    scope.querySelectorAll('details').forEach((group) => {
      const visible = [...group.querySelectorAll('[data-name]')].some((row) => !row.hidden);
      group.hidden = !visible;
      if (q !== '' && visible) group.open = true;
    });
  });
});

// 選択中のスキル数をボタンに出す。
// スキル一覧が長く、下までスクロールしないと選択状態が分からないため。
(() => {
  const badge = document.querySelector('[data-picked-count]');
  const form  = badge?.closest('form');
  if (!badge || !form) return;

  const update = () => {
    const n = form.querySelectorAll('input[name="skill[]"]:checked').length;
    badge.textContent = n > 0 ? `(${n})` : '';
  };
  form.addEventListener('change', update);
  update();
})();

// 空の条件を URL に残さない。
// 絞り込んだ URL をそのまま Slack に貼る使い方をするので、余計なパラメータを付けない。
document.querySelectorAll('form[data-tidy]').forEach((form) => {
  form.addEventListener('submit', () => {
    form.querySelectorAll('input, select').forEach((el) => {
      const empty   = el.type !== 'checkbox' && el.value === '';
      const defaulted = el.name === 'level' && el.value === '1';
      if (empty || defaulted) el.disabled = true;
    });
  });
});
