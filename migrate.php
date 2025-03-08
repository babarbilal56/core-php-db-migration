<?php
require_once __DIR__ . '/config.php';

$dbHost = DB_HOST;
$dbUser = DB_USER;
$dbPassword = DB_PASSWORD;
$dbName = DB_NAME;
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) UNIQUE NOT NULL,
                    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");

    $migrationsDirectory = __DIR__ . '/migrations';
    if (!is_dir($migrationsDirectory)) {
        throw new Exception("Migrations directory does not exist.");
    }

    $isRollback = isset($argv[1]) && $argv[1] === 'rollback';
    $rollbackAll = isset($argv[2]) && $argv[2] === 'all';
    $specificMigration = isset($argv[1]) && !$isRollback ? $argv[1] : null;

    if ($isRollback) {
        // Rollback Logic
        if ($rollbackAll) {
            echo "⏪ Rolling back all migrations...\n";
            $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id DESC");
            $migrationsToRollback = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            echo "⏪ Rolling back the last applied migration...\n";
            $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 1");
            $migrationsToRollback = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        if (empty($migrationsToRollback)) {
            echo "No migrations to roll back.\n";
            exit;
        }

        foreach ($migrationsToRollback as $migrationFile) {
            require_once $migrationsDirectory . '/' . $migrationFile;
            $migrationClass = 'Migration_' . pathinfo($migrationFile, PATHINFO_FILENAME);

            if (!class_exists($migrationClass)) {
                throw new Exception("Migration class '$migrationClass' not found in '$migrationFile'.");
            }

            $migration = new $migrationClass();
            echo "Rolling back: $migrationClass\n";
            $migration->down($pdo);

            // Remove from migration history
            $stmt = $pdo->prepare("DELETE FROM migrations WHERE migration = ?");
            $stmt->execute([$migrationFile]);

            echo "⏪ Rolled back: $migrationFile\n";
        }

        echo "✅ Rollback completed successfully.\n";
        exit;
    }

    if ($specificMigration) {
        $migrationFile = $specificMigration;
        $migrationPath = $migrationsDirectory . '/' . $migrationFile;

        if (!file_exists($migrationPath)) {
            throw new Exception("Migration file '$migrationFile' not found.");
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migrationFile]);
        $alreadyApplied = $stmt->fetchColumn();

        if ($alreadyApplied) {
            echo "=>X Skipping already applied migration: $migrationFile\n";
            exit;
        }

        require_once $migrationPath;
        $migrationClass = 'Migration_' . pathinfo($migrationFile, PATHINFO_FILENAME);
        if (!class_exists($migrationClass)) {
            throw new Exception("Migration class '$migrationClass' not found in '$migrationFile'.");
        }

        $migration = new $migrationClass();
        echo "Running migration: $migrationClass\n";
        $migration->up($pdo);

        // Record migration as applied
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migrationFile]);

        echo "✅ Migration '$migrationClass' applied successfully.\n";
        exit;
    }

    echo "🚀 Running all pending migrations...\n";
    $migrations = array_diff(scandir($migrationsDirectory), ['.', '..']);
    usort($migrations, function ($a, $b) {
        return strcmp($a, $b);
    });

    foreach ($migrations as $migrationFile) {
        // Check if migration was already applied
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migrationFile]);
        $alreadyApplied = $stmt->fetchColumn();

        if ($alreadyApplied) {
            echo "=>X Skipping already applied migration: $migrationFile\n";
            continue;
        }

        require_once $migrationsDirectory . '/' . $migrationFile;
        $migrationClass = 'Migration_' . pathinfo($migrationFile, PATHINFO_FILENAME);
        if (!class_exists($migrationClass)) {
            throw new Exception("Migration class '$migrationClass' not found in '$migrationFile'.");
        }

        $migration = new $migrationClass();
        echo "Running migration: $migrationClass\n";
        $migration->up($pdo);

        // Record migration as applied
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migrationFile]);

        echo "✅ Migration '$migrationClass' applied successfully.\n";
    }

    echo "All migrations completed successfully.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
