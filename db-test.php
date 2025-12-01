<?php
header('Content-Type: text/plain');

// Your hardcoded credentials
define('DB_HOST', 'mysql-database');
define('DB_USER', 'root');
define('DB_PASS', 'lUlceU2YvoQTQ6dfmPAFPJqSJKETMSRV3ZsqBySgoAzTL4k4sRNWbRiu6hZhsjoZ');
define('DB_NAME', 'campus_voice');
define('DB_PORT', '3306');

echo "Testing database connection...\n";
echo "Host: " . DB_HOST . "\n";
echo "User: " . DB_USER . "\n";
echo "Database: " . DB_NAME . "\n\n";

// Test 1: Basic connection
echo "Test 1: Connecting to MySQL server...\n";
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);

if ($conn->connect_error) {
    echo "❌ FAILED: " . $conn->connect_error . "\n";
    
    // Try without database name
    echo "\nTest 2: Trying without database...\n";
    $conn2 = @new mysqli(DB_HOST, DB_USER, DB_PASS, null, DB_PORT);
    if ($conn2->connect_error) {
        echo "❌ FAILED: " . $conn2->connect_error . "\n";
    } else {
        echo "✅ SUCCESS: Connected to MySQL server!\n";
        $conn2->close();
    }
} else {
    echo "✅ SUCCESS: Connected to MySQL server!\n";
    
    // Check databases
    echo "\nAvailable databases:\n";
    $result = $conn->query("SHOW DATABASES");
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
    
    // Try to select database
    echo "\nTrying to select database '" . DB_NAME . "'...\n";
    if ($conn->select_db(DB_NAME)) {
        echo "✅ Database selected!\n";
        
        // Show tables
        $result = $conn->query("SHOW TABLES");
        echo "Tables in database:\n";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "\n";
        }
    } else {
        echo "❌ Database doesn't exist. Creating it...\n";
        if ($conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME)) {
            echo "✅ Database created!\n";
            $conn->select_db(DB_NAME);
        } else {
            echo "❌ Failed to create database: " . $conn->error . "\n";
        }
    }
    
    $conn->close();
}

// Test PDO connection
echo "\n\nTest 3: Testing PDO connection...\n";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ PDO Connection successful!\n";
} catch (PDOException $e) {
    echo "❌ PDO Connection failed: " . $e->getMessage() . "\n";
}
?>