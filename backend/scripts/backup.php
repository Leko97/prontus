<?php
require_once __DIR__ . '/../config/constants.php';

$backupDir = __DIR__ . '/../db/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$filename = $backupDir . '/prontus_' . date('Y-m-d') . '.sql';
$command  = sprintf(
    'mysqldump -h%s -P%s -u%s -p%s %s > %s 2>&1',
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_PORT),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME),
    escapeshellarg($filename)
);

exec($command, $output, $code);

if ($code !== 0) {
    echo "ERRO no backup: " . implode("\n", $output) . "\n";
    exit(1);
}

$backups = glob($backupDir . '/prontus_*.sql');
sort($backups);
while (count($backups) > 7) {
    unlink(array_shift($backups));
}

echo "Backup salvo em $filename\n";
