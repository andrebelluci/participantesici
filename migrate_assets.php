<?php
/**
 * Script de Migração - Converte <script src> e <link href> para usar asset_helper
 *
 * USO: php migrate_assets.php
 */

$baseDir = __DIR__ . '/app';
$filesUpdated = 0;
$totalReplacements = 0;

function processFile($filePath) {
    global $filesUpdated, $totalReplacements;

    $content = file_get_contents($filePath);
    $originalContent = $content;
    $replacements = 0;

    // Padrão: <script src="/assets/..."></script>
    $pattern1 = '/<script\s+src=["\'](\/assets\/[^"\']+)["\']([^>]*)><\/script>/i';
    $content = preg_replace_callback($pattern1, function($matches) use (&$replacements) {
        $replacements++;
        $path = $matches[1];
        return "<?= asset_script('" . $path . "') ?>";
    }, $content);

    // Padrão: <link rel="stylesheet" href="/assets/...">
    $pattern2 = '/<link\s+rel=["\']stylesheet["\']\s+href=["\'](\/assets\/[^"\']+)["\']([^>]*)>/i';
    $content = preg_replace_callback($pattern2, function($matches) use (&$replacements) {
        $replacements++;
        $path = $matches[1];
        return "<?= asset_style('" . $path . "') ?>";
    }, $content);

    // Padrão: <script src="/assets/...?v=..."></script> (remove ?v=)
    $pattern3 = '/<script\s+src=["\'](\/assets\/[^"\']+)\?[^"\']+["\']([^>]*)><\/script>/i';
    $content = preg_replace_callback($pattern3, function($matches) use (&$replacements) {
        $replacements++;
        $path = $matches[1];
        return "<?= asset_script('" . $path . "') ?>";
    }, $content);

    // Padrão: <link rel="stylesheet" href="/assets/...?v=...">
    $pattern4 = '/<link\s+rel=["\']stylesheet["\']\s+href=["\'](\/assets\/[^"\']+)\?[^"\']+["\']([^>]*)>/i';
    $content = preg_replace_callback($pattern4, function($matches) use (&$replacements) {
        $replacements++;
        $path = $matches[1];
        return "<?= asset_style('" . $path . "') ?>";
    }, $content);

    // Só salva se houve mudanças
    if ($content !== $originalContent) {
        // Verificar se já inclui header.php (que já inclui asset_helper)
        $hasHeader = preg_match('/require_once.*header\.php/', $content);

        // Se não tiver header.php, adicionar require do asset_helper
        if (!$hasHeader && strpos($content, 'asset_helper') === false) {
            $content = preg_replace(
                '/(<\?php\s+)/',
                '$1require_once __DIR__ . \'/../../includes/asset_helper.php\';\n',
                $content,
                1
            );
        }

        // Criar backup
        $backupPath = $filePath . '.backup';
        file_put_contents($backupPath, $originalContent);

        // Salvar arquivo atualizado
        file_put_contents($filePath, $content);

        $filesUpdated++;
        $totalReplacements += $replacements;

        echo "✅ Atualizado: $filePath ($replacements substituições)\n";
        echo "   Backup: $backupPath\n";

        return true;
    }

    return false;
}

// Encontrar todos os arquivos PHP
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

echo "🔍 Procurando arquivos PHP com assets...\n\n";

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());

        // Verifica se tem assets
        if (preg_match('/<(script|link)[^>]+(src|href)=["\']\/assets/', $content)) {
            processFile($file->getPathname());
        }
    }
}

echo "\n═══════════════════════════════════════\n";
echo "✅ Migração concluída!\n";
echo "   Arquivos atualizados: $filesUpdated\n";
echo "   Total de substituições: $totalReplacements\n";
echo "\n⚠️  Backups criados com extensão .backup\n";
echo "═══════════════════════════════════════\n";
