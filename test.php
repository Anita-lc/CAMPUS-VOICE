<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '38.242.241.148';
$user = 'mysql';
$pass = '3Ib0gaJSlqdHPsEyXCu4IeEThPIs2KpIDtxaxsCxmdPQ8NXWS79Iwh80gWBFlf6F';
$db = 'default';
$port = 5432;

echo "<h2>Testing Public Database Connection</h2>";
echo "Host: $host<br>";
echo "User: $user<br>";
echo "Port: $port<br>";
echo "Database: $db<br><br>";

// Test connection
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error . "<br>";
    
    // Try without database
    echo "<br>Trying without database selection...<br>";
    $conn2 = new mysqli($host, $user, $pass, '', $port);
    if ($conn2->connect_error) {
        echo "❌ Still failed: " . $conn2->connect_error;
    } else {
        echo "✅ Connected to MySQL server!<br>";
        
        // Show databases
        $result = $conn2->query("SHOW DATABASES");
        echo "Available databases:<br>";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "<br>";
        }
        
        // Try to create/use the database
        if (!$conn2->select_db($db)) {
            echo "<br>Database '$db' doesn't exist. Creating...<br>";
            if ($conn2->query("CREATE DATABASE IF NOT EXISTS $db")) {
                echo "✅ Database created!<br>";
            } else {
                echo "❌ Failed to create database: " . $conn2->error;
            }
        }
        
        $conn2->close();
    }
} else {
    echo "✅ Connected successfully!<br>";
    
    // Show MySQL version
    echo "MySQL Version: " . $conn->server_version . "<br><br>";
    
    // Show tables
    $result = $conn->query("SHOW TABLES");
    if ($result->num_rows > 0) {
        echo "Tables in database:<br>";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "<br>";
        }
    } else {
        echo "No tables found. Database is empty.<br>";
    }
    
    $conn->close();
}
?>