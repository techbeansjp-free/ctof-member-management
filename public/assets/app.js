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
