<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = get_pdo();

    // Fase 1: schema
    $schema = file_get_contents(__DIR__ . '/../db/migrations/001_schema.sql');
    $pdo->exec($schema);
    echo "Schema criado com sucesso.\n";

    // Fase 2: usuário admin
    $hash = password_hash('prontus123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute(['Administrador', 'admin@prontus.app', $hash, 'admin']);
    echo "Usuário admin criado: admin@prontus.app / prontus123\n";

    // Fase 3: seed de restrições
    $seed = file_get_contents(__DIR__ . '/../db/migrations/002_seed.sql');
    $pdo->exec($seed);
    echo "Restrições padrão inseridas.\n";

    echo "\nMigration concluída com sucesso!\n";
} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
