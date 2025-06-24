<?php
// /home/andev/Projetos/www/participantesici/app/config/config.php

function loadEnv($path)
{
  if (!file_exists($path)) {
    throw new Exception('.env file not found at: ' . $path);
  }

  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) {
      continue; // Pular comentários
    }

    if (strpos($line, '=') === false) {
      continue; // Pular linhas inválidas
    }

    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);

    if (!array_key_exists($name, $_ENV)) {
      putenv(sprintf('%s=%s', $name, $value));
      $_ENV[$name] = $value;
      $_SERVER[$name] = $value;
    }
  }
}

// Carregar o .env da raiz do projeto (2 níveis acima)
$envPath = __DIR__ . '/../../.env';
loadEnv($envPath);

// Função helper para pegar valores
function env($key, $default = null)
{
  return $_ENV[$key] ?? getenv($key) ?: $default;
}
?>