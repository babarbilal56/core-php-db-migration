<?php
class Migration_2025_03_09_001_test
{
    public function up($pdo)
    {
        $sql = "CREATE TABLE IF NOT EXISTS test_migration (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    description VARCHAR(255) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )";
        $pdo->exec($sql);
        echo "Table 'test_migration' created successfully.\n";

        // Insert test data
        $pdo->exec("INSERT INTO test_migration (description) VALUES ('Test migration entry')");
        echo "Test data inserted into 'test_migration' table.\n";
    }

    public function down($pdo)
    {
        $sql = "DROP TABLE IF EXISTS test_migration";
        $pdo->exec($sql);
        echo "Table 'test_migration' dropped successfully.\n";
    }
}
