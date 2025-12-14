<?php
$archive = 'backend_restore.tar.gz';

echo "<pre>\n";
echo "=== Восстановление бекенда ===\n\n";

if (!file_exists($archive)) {
    die("ERROR: Archive $archive not found!\n");
}

echo "1. Распаковка архива...\n";

// Распаковываем tar.gz
$phar = new PharData($archive);
$phar->decompress();

$tar = str_replace('.gz', '', $archive);
$phar2 = new PharData($tar);
$phar2->extractTo('.', null, true);

echo "   Готово!\n\n";

echo "2. Установка прав...\n";
exec('chmod -R 755 storage bootstrap/cache 2>&1');
echo "   Готово!\n\n";

echo "3. Очистка...\n";
@unlink($tar);
echo "   Готово!\n\n";

echo "=== Бекенд восстановлен! ===\n";
echo "</pre>";
