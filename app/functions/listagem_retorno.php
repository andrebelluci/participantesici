<?php

/** Parâmetros GET da listagem de participantes a preservar ao voltar. */
const LISTAGEM_PARTICIPANTES_KEYS = [
  'pagina',
  'filtro_nome',
  'filtro_cpf',
  'filtro_aniversariantes',
  'filtro_mes_aniversario',
  'filtro_status',
  'order_by',
  'order_dir',
];

/** Parâmetros GET da listagem de rituais a preservar ao voltar. */
const LISTAGEM_RITUAIS_KEYS = [
  'pagina',
  'filtro_nome',
  'data_inicio',
  'data_fim',
  'order_by',
  'order_dir',
];

/** Parâmetros GET da listagem de usuários a preservar ao voltar. */
const LISTAGEM_USUARIOS_KEYS = [
  'pagina',
  'filtro_nome',
  'order_by',
  'order_dir',
];

/**
 * Extrai da requisição atual os parâmetros de filtro/paginação da listagem.
 *
 * @return array<string, mixed>
 */
function listagemFiltroParamsFromRequest(array $keys): array
{
  $params = [];

  foreach ($keys as $key) {
    if ($key === 'filtro_status') {
      if (isset($_GET['filtro_status']) && is_array($_GET['filtro_status'])) {
        $status = array_values(array_filter($_GET['filtro_status'], static fn($v) => is_string($v) && $v !== ''));
        if ($status !== []) {
          $params['filtro_status'] = $status;
        }
      }
      continue;
    }

    if (!isset($_GET[$key])) {
      continue;
    }

    $valor = $_GET[$key];
    if ($valor === '' || $valor === null) {
      continue;
    }

    $params[$key] = $valor;
  }

  return $params;
}

/** Monta a URL da listagem com os filtros atuais (ex.: /participantes?filtro_nome=...). */
function listagemRetornoUrl(string $basePath, array $keys): string
{
  $params = listagemFiltroParamsFromRequest($keys);
  if ($params === []) {
    return $basePath;
  }

  return $basePath . '?' . http_build_query($params);
}

/**
 * Query string para propagar em detalhe/edição (ex.: retorno_lista=%2Fparticipantes%3F...).
 * Retorna string vazia se não há filtros/página na listagem.
 */
function listagemRetornoQuerySuffix(string $basePath, array $keys): string
{
  $url = listagemRetornoUrl($basePath, $keys);
  if ($url === $basePath) {
    return '';
  }

  return 'retorno_lista=' . rawurlencode($url);
}

/** Anexa retorno_lista a uma URL de detalhe (respeita ? existente). */
function listagemUrlComRetornoLista(string $targetPath, string $listBasePath, array $keys): string
{
  $suffix = listagemRetornoQuerySuffix($listBasePath, $keys);
  if ($suffix === '') {
    return $targetPath;
  }

  $sep = str_contains($targetPath, '?') ? '&' : '?';

  return $targetPath . $sep . $suffix;
}

/**
 * Valida e devolve a URL de retorno à listagem (apenas caminhos internos do app).
 */
function listagemUrlFromRetorno(?string $retorno, string $fallbackPath): string
{
  if ($retorno === null || $retorno === '') {
    return $fallbackPath;
  }

  $retorno = trim($retorno);

  if (!str_starts_with($retorno, $fallbackPath)) {
    return $fallbackPath;
  }

  if (preg_match('#^(https?:|//)#i', $retorno)) {
    return $fallbackPath;
  }

  return $retorno;
}

/** Propaga retorno_lista já presente na requisição para outra URL. */
function listagemUrlPreservarRetorno(string $url): string
{
  if (empty($_GET['retorno_lista']) || !is_string($_GET['retorno_lista'])) {
    return $url;
  }

  $sep = str_contains($url, '?') ? '&' : '?';

  return $url . $sep . 'retorno_lista=' . rawurlencode($_GET['retorno_lista']);
}
