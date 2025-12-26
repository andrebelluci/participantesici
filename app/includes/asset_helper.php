<?php
/**
 * Helper para gerenciar versionamento de assets (JS/CSS)
 * Evita problemas de cache durante desenvolvimento e produção
 */

/**
 * Retorna a versão do arquivo baseada na data de modificação
 * Em desenvolvimento: usa timestamp do arquivo (muda sempre que o arquivo é modificado)
 * Em produção: pode usar uma versão fixa definida em constante
 */
function asset_version($file_path) {
  // Se estiver em produção e tiver uma versão definida, usa ela
  if (defined('ASSET_VERSION') && ASSET_VERSION) {
    return ASSET_VERSION;
  }

  // Em desenvolvimento: usa timestamp do arquivo
  $full_path = __DIR__ . '/../../public_html' . $file_path;

  if (file_exists($full_path)) {
    // Retorna timestamp da última modificação do arquivo
    return filemtime($full_path);
  }

  // Fallback: usa timestamp atual se arquivo não existir
  return time();
}

/**
 * Gera URL de asset com versionamento automático
 *
 * @param string $path Caminho do asset (ex: '/assets/js/documentos.js')
 * @return string URL com query string de versão
 */
function asset_url($path) {
  $version = asset_version($path);
  return $path . '?v=' . $version;
}

/**
 * Gera tag <script> com versionamento automático
 */
function asset_script($path, $attributes = []) {
  $url = asset_url($path);
  $attrs = '';

  foreach ($attributes as $key => $value) {
    if (is_numeric($key)) {
      $attrs .= ' ' . htmlspecialchars($value);
    } else {
      $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
  }

  return '<script src="' . htmlspecialchars($url) . '"' . $attrs . '></script>';
}

/**
 * Gera tag <link> para CSS com versionamento automático
 */
function asset_style($path, $attributes = []) {
  $url = asset_url($path);
  $attrs = '';

  foreach ($attributes as $key => $value) {
    if (is_numeric($key)) {
      $attrs .= ' ' . htmlspecialchars($value);
    } else {
      $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
  }

  return '<link rel="stylesheet" href="' . htmlspecialchars($url) . '"' . $attrs . '>';
}

