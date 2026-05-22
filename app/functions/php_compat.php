<?php
/**
 * Polyfills para PHP 7.4 (produção) — funções nativas do PHP 8+.
 */

if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle)
  {
    return $needle === '' || strpos((string) $haystack, (string) $needle) !== false;
  }
}

if (!function_exists('str_starts_with')) {
  function str_starts_with($haystack, $needle)
  {
    return strpos((string) $haystack, (string) $needle) === 0;
  }
}

if (!function_exists('str_ends_with')) {
  function str_ends_with($haystack, $needle)
  {
    $needle = (string) $needle;
    if ($needle === '') {
      return true;
    }
    $haystack = (string) $haystack;
    $len = strlen($needle);
    if ($len > strlen($haystack)) {
      return false;
    }
    return substr($haystack, -$len) === $needle;
  }
}
