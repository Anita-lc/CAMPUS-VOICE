<?php
// This script will create the missing database tables

// Allow config.php to be included safely
define('IN_CAMPUS_VOICE', true);

// Include database configuration
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Create database connection (use host, port, and database name)
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Helper function to check if a column exists in a table
function columnExists(PDO $pdo, $table, $column) {
    // Both $table and $column are hardcoded in this script, but sanitize anyway
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'";
    $stmt = $pdo->query($sql);
    return $stmt && $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Create feedback table if not exists (with all columns used by the app)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `email` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(255) DEFAULT NULL,
        `category_id` INT,
        `status` ENUM('pending', 'in_progress', 'resolved', 'viewed') DEFAULT 'pending',
        `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
        `is_anonymous` BOOLEAN DEFAULT FALSE,
        `duplicate_of` INT DEFAULT NULL,
        `resolution` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FULLTEXT (`title`, `description`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Ensure feedback table has newer columns even if it already existed
    if (!columnExists($pdo, 'feedback', 'email')) {
        $pdo->exec("ALTER TABLE `feedback` ADD COLUMN `email` VARCHAR(255) NOT NULL AFTER `description`");
    }
    if (!columnExists($pdo, 'feedback', 'file_path')) {
        $pdo->exec("ALTER TABLE `feedback` ADD COLUMN `file_path` VARCHAR(255) DEFAULT NULL AFTER `email`");
    }
    if (!columnExists($pdo, 'feedback', 'duplicate_of')) {
        $pdo->exec("ALTER TABLE `feedback` ADD COLUMN `duplicate_of` INT DEFAULT NULL AFTER `is_anonymous`");
    }
    if (!columnExists($pdo, 'feedback', 'resolution')) {
        $pdo->exec("ALTER TABLE `feedback` ADD COLUMN `resolution` TEXT NULL AFTER `status`");
    }
    
    // Create feedback_duplicates table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback_duplicates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `feedback_id` INT NOT NULL,
        `duplicate_of` INT NOT NULL,
        `similarity_score` FLOAT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`feedback_id`) REFERENCES `feedback`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`duplicate_of`) REFERENCES `feedback`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_duplicate` (`feedback_id`, `duplicate_of`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Create categories table (with icon column used on dashboard)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `icon` VARCHAR(50) DEFAULT 'fa-folder',
        `description` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ensure categories table has icon column even if it already existed
    if (!columnExists($pdo, 'categories', 'icon')) {
        $pdo->exec("ALTER TABLE `categories` ADD COLUMN `icon` VARCHAR(50) DEFAULT 'fa-folder' AFTER `name`");
    }
    
    // Create polls table (basic version used by JSON-based polls)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `polls` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `question` TEXT NOT NULL,
        `options` JSON NOT NULL,
        `end_date` DATETIME NOT NULL,
        `is_public` BOOLEAN DEFAULT TRUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ensure polls table has columns used by polls.php even if it already existed
    if (!columnExists($pdo, 'polls', 'description')) {
        $pdo->exec("ALTER TABLE `polls` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `question`");
    }
    if (!columnExists($pdo, 'polls', 'status')) {
        $pdo->exec("ALTER TABLE `polls` ADD COLUMN `status` ENUM('draft','active','upcoming','ended') NOT NULL DEFAULT 'draft' AFTER `description`");
    }
    if (!columnExists($pdo, 'polls', 'start_date')) {
        $pdo->exec("ALTER TABLE `polls` ADD COLUMN `start_date` DATETIME DEFAULT NULL AFTER `status`");
    }
    if (!columnExists($pdo, 'polls', 'allow_multiple')) {
        $pdo->exec("ALTER TABLE `polls` ADD COLUMN `allow_multiple` TINYINT(1) NOT NULL DEFAULT 0 AFTER `end_date`");
    }
    if (!columnExists($pdo, 'polls', 'show_results')) {
        $pdo->exec("ALTER TABLE `polls` ADD COLUMN `show_results` TINYINT(1) NOT NULL DEFAULT 1 AFTER `allow_multiple`");
    }
    if (!columnExists($pdo, 'polls', 'anonymous_voting')) {
        $pdo->exec("ALTER TABLE `polls` ADD COLUMN `anonymous_voting` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_results`");
    }
    if (!columnExists($pdo, 'polls', 'created_by')) {
        $pdo->exec("ALTER TABLE `polls` ADD COLUMN `created_by` INT DEFAULT NULL AFTER `created_at`");
    }

    // Create poll_options table (used by admin/polls.php and vote.php)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `poll_options` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `poll_id` INT NOT NULL,
        `option_text` VARCHAR(255) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY `poll_id` (`poll_id`),
        CONSTRAINT `poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create poll_votes table (per-option votes for polls)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `poll_votes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `poll_id` INT NOT NULL,
        `option_id` INT NOT NULL,
        `user_id` INT DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` VARCHAR(255) DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `poll_user` (`poll_id`,`user_id`) USING BTREE,
        KEY `option_id` (`option_id`),
        KEY `ip_address` (`ip_address`),
        CONSTRAINT `poll_votes_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls`(`id`) ON DELETE CASCADE,
        CONSTRAINT `poll_votes_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `poll_options`(`id`) ON DELETE CASCADE,
        CONSTRAINT `poll_votes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create poll_responses table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `poll_responses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `poll_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `selected_option` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`poll_id`) REFERENCES `polls`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_poll_response` (`poll_id`, `user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Create feedback_responses table (used for responses shown in view-feedback.php and manage-feedback.php)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback_responses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `feedback_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `response` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`feedback_id`) REFERENCES `feedback`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Create surveys table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `surveys` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `questions` JSON NOT NULL,
        `end_date` DATETIME,
        `is_public` BOOLEAN DEFAULT TRUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Create survey_responses table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `survey_responses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `survey_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `responses` JSON NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create notifications table (used by notifications.php and notification APIs)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `type` VARCHAR(50) NOT NULL,
        `reference_id` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `read_at` TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        KEY `user_read_idx` (`user_id`,`read_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Insert default categories if not exist
    $pdo->exec("INSERT IGNORE INTO `categories` (`name`, `description`) VALUES
    ('Academic', 'Issues related to academic programs, courses, and faculty'),
    ('Facilities', 'Concerns about campus buildings, classrooms, and amenities'),
    ('Administration', 'Feedback about administrative processes and services'),
    ('Student Life', 'Activities, clubs, and student organizations'),
    ('Technology', 'IT services, WiFi, and technical support'),
    ('Safety', 'Campus security and emergency services'),
    ('Other', 'Any other feedback or suggestions')");
    
    echo "Database tables created successfully!\n";
    
} catch (PDOException $e) {
    // Roll back the transaction if something failed
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error: " . $e->getMessage() . "\n");
}

echo "You can now access the dashboard without errors. <a href='dashboard.php'>Go to Dashboard</a>";
