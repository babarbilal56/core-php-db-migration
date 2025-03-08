# Database Migration System

This script provides a simple migration system using PHP and MySQL. It allows applying and rolling back database migrations efficiently.

## 📌 Features
- **Automatic migration tracking** using a `migrations` table.
- **Prevents duplicate migrations** by checking if a migration has already been applied.
- **Supports rollback of the last migration** or **all migrations**.
- **Runs specific migrations** if needed.

---

## 🛠️ Setup Instructions

### 1️⃣ **Clone the Repository & Configure Database**
Ensure you have a `config.php` file with database credentials:
```php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'your_database');
```

### 2️⃣ **Create the Migrations Folder**
Ensure there is a `migrations` directory in the project root:
```sh
mkdir migrations
```

### 3️⃣ **Write a Migration File**
Create a PHP file inside `migrations/` (e.g., `2025_03_09_001_test.php`):
```php
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
        echo "Migration applied successfully.\n";
    }

    public function down($pdo)
    {
        $sql = "DROP TABLE IF EXISTS test_migration";
        $pdo->exec($sql);
        echo "Migration rolled back.\n";
    }
}
```

---

## 🚀 Running Migrations

### ✅ **Run All Pending Migrations**
```sh
php migrate.php
```
> This applies all migrations that haven't been executed yet.

### 🎯 **Run a Specific Migration**
```sh
php migrate.php 2025_03_09_001_test.php
```
> This applies only the specified migration file if it hasn’t been applied yet.

### ⏪ **Rollback the Last Migration**
```sh
php migrate.php rollback
```
> This rolls back **only the most recent migration**.

### ⏪ **Rollback All Migrations**
```sh
php migrate.php rollback all
```
> This rolls back **all applied migrations** in reverse order.

---

## 📌 How It Works
1. **Migration History Tracking**
   - Applied migrations are stored in a `migrations` table to prevent duplicate execution.
2. **Automatic Sorting**
   - Migrations are run in ascending order based on filename.
3. **Rollback System**
   - Only executed migrations are rolled back based on the tracking table.
4. **Error Handling**
   - Ensures missing classes or duplicate execution do not break the process.

---

## 📌 Notes
- **Migrations must follow the naming convention**: `YYYY_MM_DD_XXX_description.php`
- Each migration file **must contain a class** named `Migration_YYYY_MM_DD_XXX_description`.
- **Always test migrations in a development environment** before applying them in production.



