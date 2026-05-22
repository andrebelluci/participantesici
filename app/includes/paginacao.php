<?php
/**
 * Paginação: « ‹ números › » (ícones + title; mobile = 3 páginas)
 *
 * Variáveis esperadas:
 * - $paginacao_atual (int)
 * - $paginacao_total (int)
 * - $paginacao_params (array) parâmetros GET sem "pagina"
 */
require_once __DIR__ . '/../functions/paginacao.php';

$paginacao_atual = max(1, (int) ($paginacao_atual ?? 1));
$paginacao_total = max(0, (int) ($paginacao_total ?? 0));
$paginacao_params = $paginacao_params ?? [];

if ($paginacao_total <= 1) {
  return;
}

$paginasDesktop = paginacaoPaginasVisiveis($paginacao_atual, $paginacao_total);
$paginasMobile = paginacaoPaginasVisiveisMobile($paginacao_atual, $paginacao_total);

$urlPrimeira = paginacaoUrl(1, $paginacao_params);
$urlAnterior = paginacaoUrl(max(1, $paginacao_atual - 1), $paginacao_params);
$urlProxima = paginacaoUrl(min($paginacao_total, $paginacao_atual + 1), $paginacao_params);
$urlUltima = paginacaoUrl($paginacao_total, $paginacao_params);

$temPrimeira = $paginacao_atual > 1;
$temUltima = $paginacao_atual < $paginacao_total;

$renderPaginas = static function (array $lista) use ($paginacao_atual, $paginacao_params): void {
  foreach ($lista as $item) {
    if ($item === '...') {
      echo '<span class="paginacao-nav__ellipsis" aria-hidden="true">…</span>';
      continue;
    }
    $num = (int) $item;
    if ($num === $paginacao_atual) {
      echo '<span class="paginacao-nav__btn paginacao-nav__btn--ativo" aria-current="page">' . $num . '</span>';
      continue;
    }
    $href = htmlspecialchars(paginacaoUrl($num, $paginacao_params));
    echo '<a href="' . $href . '" class="paginacao-nav__btn" title="Página ' . $num . '" aria-label="Página ' . $num . '">' . $num . '</a>';
  }
};
?>
<nav class="paginacao-nav" aria-label="Paginação">
  <p class="paginacao-nav__info">
    Página <strong><?= $paginacao_atual ?></strong> de <strong><?= $paginacao_total ?></strong>
  </p>

  <div class="paginacao-nav__row">
    <?php if ($temPrimeira): ?>
      <a href="<?= htmlspecialchars($urlPrimeira) ?>" class="paginacao-nav__btn"
        title="Primeira página" aria-label="Primeira página">
        <i class="fa-solid fa-angles-left"></i>
      </a>
      <a href="<?= htmlspecialchars($urlAnterior) ?>" class="paginacao-nav__btn"
        title="Página anterior" aria-label="Página anterior">
        <i class="fa-solid fa-chevron-left"></i>
      </a>
    <?php else: ?>
      <span class="paginacao-nav__btn paginacao-nav__btn--disabled" aria-disabled="true"
        title="Primeira página">
        <i class="fa-solid fa-angles-left"></i>
      </span>
      <span class="paginacao-nav__btn paginacao-nav__btn--disabled" aria-disabled="true"
        title="Página anterior">
        <i class="fa-solid fa-chevron-left"></i>
      </span>
    <?php endif; ?>

    <div class="paginacao-nav__pages paginacao-nav__pages--mobile">
      <?php $renderPaginas($paginasMobile); ?>
    </div>
    <div class="paginacao-nav__pages paginacao-nav__pages--desktop">
      <?php $renderPaginas($paginasDesktop); ?>
    </div>

    <?php if ($temUltima): ?>
      <a href="<?= htmlspecialchars($urlProxima) ?>" class="paginacao-nav__btn"
        title="Próxima página" aria-label="Próxima página">
        <i class="fa-solid fa-chevron-right"></i>
      </a>
      <a href="<?= htmlspecialchars($urlUltima) ?>" class="paginacao-nav__btn"
        title="Última página" aria-label="Última página">
        <i class="fa-solid fa-angles-right"></i>
      </a>
    <?php else: ?>
      <span class="paginacao-nav__btn paginacao-nav__btn--disabled" aria-disabled="true"
        title="Próxima página">
        <i class="fa-solid fa-chevron-right"></i>
      </span>
      <span class="paginacao-nav__btn paginacao-nav__btn--disabled" aria-disabled="true"
        title="Última página">
        <i class="fa-solid fa-angles-right"></i>
      </span>
    <?php endif; ?>
  </div>
</nav>
