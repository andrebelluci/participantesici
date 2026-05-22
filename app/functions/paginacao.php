<?php

/**
 * Números de página visíveis (sem 1/última quando há botões « » e não está perto das extremidades).
 *
 * @return array<int|string>
 */
function paginacaoPaginasVisiveis(int $paginaAtual, int $totalPaginas, int $vizinhos = 2): array
{
  if ($totalPaginas <= 0) {
    return [];
  }

  // Poucas páginas: mostra todas
  if ($totalPaginas <= 5) {
    return range(1, $totalPaginas);
  }

  $paginaAtual = max(1, min($paginaAtual, $totalPaginas));
  $inicio = max(1, $paginaAtual - $vizinhos);
  $fim = min($totalPaginas, $paginaAtual + $vizinhos);

  $pertoInicio = $inicio <= 2;
  $pertoFim = $fim >= $totalPaginas - 1;

  if ($pertoInicio) {
    $inicio = 1;
  }
  if ($pertoFim) {
    $fim = $totalPaginas;
  }

  $paginas = [];

  if (!$pertoInicio && $inicio > 1) {
    $paginas[] = '...';
  }

  for ($i = $inicio; $i <= $fim; $i++) {
    $paginas[] = $i;
  }

  if (!$pertoFim && $fim < $totalPaginas) {
    $paginas[] = '...';
  }

  return $paginas;
}

/**
 * Mobile: sempre 3 números (atual no centro quando possível).
 * Ex.: pág. 1 → 1,2,3 | pág. 4 → 3,4,5 | última → n-2,n-1,n
 *
 * @return array<int>
 */
function paginacaoPaginasVisiveisMobile(int $paginaAtual, int $totalPaginas): array
{
  if ($totalPaginas <= 0) {
    return [];
  }

  if ($totalPaginas <= 3) {
    return range(1, $totalPaginas);
  }

  $paginaAtual = max(1, min($paginaAtual, $totalPaginas));

  if ($paginaAtual <= 2) {
    return [1, 2, 3];
  }

  if ($paginaAtual >= $totalPaginas - 1) {
    return [$totalPaginas - 2, $totalPaginas - 1, $totalPaginas];
  }

  return [$paginaAtual - 1, $paginaAtual, $paginaAtual + 1];
}

/** Monta query string preservando parâmetros e alterando a página. */
function paginacaoUrl(int $pagina, array $params = []): string
{
  $params['pagina'] = $pagina;
  $params = array_filter($params, static function ($valor) {
    if (is_array($valor)) {
      return $valor !== [];
    }
    return $valor !== null && $valor !== '';
  });

  return '?' . http_build_query($params);
}
