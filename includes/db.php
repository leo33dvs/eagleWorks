<?php
// Database connection configuration
// Using PostgreSQL with environment variables

try {
    // Get environment variables
    $host = getenv('PGHOST');
    $port = getenv('PGPORT');
    $db_name = getenv('PGDATABASE');
    $username = getenv('PGUSER');
    $password = getenv('PGPASSWORD');
    
    // Construct DSN for PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$db_name;user=$username;password=$password";
    
    // Connect using PDO
    $pdo = new PDO($dsn);
    
    // Set error mode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Use prepared statements by default
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Keep connection
    $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
} catch(PDOException $e) {
    // Handle connection error
    die("Connection failed: " . $e->getMessage());
}
?>
