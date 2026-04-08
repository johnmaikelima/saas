<?php
/**
 * Executor de migrations automático (MySQL)
 */

function runMigrations(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        arquivo VARCHAR(255) NOT NULL UNIQUE,
        executado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $pdo->query("SELECT arquivo FROM migrations");
    $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $dir = __DIR__ . '/migrations';
    $files = glob($dir . '/*.sql');
    sort($files);

    foreach ($files as $file) {
        $filename = basename($file);
        if (!in_array($filename, $executed)) {
            $sql = file_get_contents($file);
            $sql = preg_replace('/^--.*$/m', '', $sql);
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($statements as $stmt) {
                if (empty($stmt)) continue;
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e) {
                    if (str_contains($e->getMessage(), 'Duplicate column')
                        || str_contains($e->getMessage(), 'already exists')) {
                        continue;
                    }
                    error_log("Erro na migration {$filename}: " . $e->getMessage());
                }
            }

            try {
                $ins = $pdo->prepare("INSERT INTO migrations (arquivo) VALUES (?)");
                $ins->execute([$filename]);
            } catch (PDOException $e) {
                // Já registrada
            }
        }
    }
}

if (php_sapi_name() === 'cli' && basename($argv[0] ?? '') === 'migrate.php') {
    require_once __DIR__ . '/config.php';
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    runMigrations($pdo);
    echo "Migrations executadas com sucesso!\n";
}
