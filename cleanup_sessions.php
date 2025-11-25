<?php
/**
 * Cleanup Sessions Script
 * Deletes all sessions and attendance records for a specific course to allow clean re-import
 * 
 * Usage: php cleanup_sessions.php ATTENDANCE_ID
 */

// Load .env file manually
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remove quotes if present
        if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
            $value = $matches[2];
        }
        
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

loadEnv(__DIR__ . '/.env');

// Database connection
$host = $_ENV['DB_HOST'] ?? 'localhost';
$database = $_ENV['DB_DATABASE'] ?? 'u5021d9810_mtldb';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connected\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$attendanceId = $argv[1] ?? null;

if (!$attendanceId) {
    die("Usage: php cleanup_sessions.php ATTENDANCE_ID\n");
}

// Find course
$stmt = $pdo->prepare("SELECT * FROM course_offerings WHERE attendance_id = ?");
$stmt->execute([$attendanceId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Error: No course found with attendance_id: $attendanceId\n");
}

echo "Found course: {$course['course_full_name']}\n";
echo "Course ID: {$course['id']}\n\n";

// Get session count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sessions WHERE course_offering_id = ?");
$stmt->execute([$course['id']]);
$sessionCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "Sessions to delete: $sessionCount\n";

if ($sessionCount == 0) {
    echo "No sessions to delete.\n";
    exit(0);
}

// Confirm deletion
echo "\nThis will delete all sessions and attendance records for this course.\n";
echo "Type 'yes' to confirm: ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'yes') {
    echo "Cancelled.\n";
    exit(0);
}

// Delete attendance records first (due to foreign key)
$stmt = $pdo->prepare(
    "DELETE ar FROM attendance_records ar 
     INNER JOIN sessions s ON ar.session_id = s.id 
     WHERE s.course_offering_id = ?"
);
$stmt->execute([$course['id']]);
$deletedRecords = $stmt->rowCount();

echo "✓ Deleted $deletedRecords attendance records\n";

// Delete sessions
$stmt = $pdo->prepare("DELETE FROM sessions WHERE course_offering_id = ?");
$stmt->execute([$course['id']]);
$deletedSessions = $stmt->rowCount();

echo "✓ Deleted $deletedSessions sessions\n";

echo "\n✓ Cleanup complete! You can now re-import this course.\n";
