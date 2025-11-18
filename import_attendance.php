<?php
/**
 * Import attendance records from CSV export
 * Usage: php import_attendance.php <csv_file_path>
 * Example: php import_attendance.php "specs/001-title-english-language/imports/Class Attendance & Certificate System - A2 PR_MORN_EDMON_7.csv"
 */

// Load environment
require_once __DIR__ . '/vendor/autoload.php';

function loadEnv($path) {
    if (!file_exists($path)) {
        die("Error: .env file not found\n");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value, '"\''));
    }
}

loadEnv(__DIR__ . '/.env');

// Database connection
$pdo = new PDO(
    "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE') . ";charset=utf8mb4",
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD')
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get CSV file from command line
$csvFile = $argv[1] ?? null;
if (!$csvFile || !file_exists($csvFile)) {
    die("Usage: php import_attendance.php <csv_file_path>\n");
}

// Extract attendance_id from filename
// Format: "Class Attendance & Certificate System - A2 PR_MORN_EDMON_7.csv"
$filename = basename($csvFile);
if (preg_match('/- ([A-Z0-9_ ]+)\.csv$/i', $filename, $matches)) {
    $attendance_id = trim($matches[1]);
} else {
    die("Error: Could not extract attendance_id from filename\n");
}

echo "Attendance ID from filename: {$attendance_id}\n";

// Find course offering
$stmt = $pdo->prepare("SELECT id, course_full_name FROM course_offerings WHERE attendance_id = ?");
$stmt->execute([$attendance_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Error: Course not found with attendance_id '{$attendance_id}'\n");
}

echo "Found course: {$course['course_full_name']}\n";
$courseId = $course['id'];

// Get all sessions for this course
$stmt = $pdo->prepare("SELECT id, session_date FROM sessions WHERE course_offering_id = ? ORDER BY session_date");
$stmt->execute([$courseId]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($sessions)) {
    echo "Warning: No sessions found for this course. Creating sessions from attendance dates...\n";
    $createSessions = true;
} else {
    echo "Found " . count($sessions) . " existing sessions\n";
    $createSessions = false;
}

// Build session date index
$sessionIndex = [];
foreach ($sessions as $session) {
    $sessionIndex[$session['session_date']] = $session['id'];
}

// Parse CSV
$handle = fopen($csvFile, 'r');
$headers = fgetcsv($handle);

// Verify expected columns
$expectedCols = ['Student Name', 'Student Email', 'Dates Present', 'Initial Level', 'Mid Level'];
foreach ($expectedCols as $col) {
    if (!in_array($col, $headers)) {
        die("Error: Missing expected column '{$col}'\n");
    }
}

$studentEmailIdx = array_search('Student Email', $headers);
$datesIdx = array_search('Dates Present', $headers);
$midLevelIdx = array_search('Mid Level', $headers);

$imported = 0;
$skipped = 0;
$sessionDates = [];

while (($row = fgetcsv($handle)) !== false) {
    $email = $row[$studentEmailIdx] ?? null;
    $dates = $row[$datesIdx] ?? '';
    $midLevel = $row[$midLevelIdx] ?? null;
    
    if (empty($email)) {
        $skipped++;
        continue;
    }
    
    // Find student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo "Warning: Student not found: {$email}\n";
        $skipped++;
        continue;
    }
    
    $studentId = $student['id'];
    
    // Update mid-level if provided
    if (!empty($midLevel)) {
        $stmt = $pdo->prepare("UPDATE students SET current_level = ? WHERE id = ?");
        $stmt->execute([$midLevel, $studentId]);
    }
    
    // Parse attendance dates
    if (empty($dates)) {
        continue;
    }
    
    $attendanceDates = array_map('trim', explode(',', $dates));
    
    foreach ($attendanceDates as $date) {
        if (empty($date)) continue;
        
        $sessionDates[] = $date;
        
        // Find or create session
        if (!isset($sessionIndex[$date])) {
            if ($createSessions) {
                // Create session
                $stmt = $pdo->prepare("INSERT INTO sessions (course_offering_id, session_date, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $stmt->execute([$courseId, $date]);
                $sessionId = $pdo->lastInsertId();
                $sessionIndex[$date] = $sessionId;
                echo "  Created session for date: {$date}\n";
            } else {
                echo "Warning: No session found for date {$date}\n";
                continue;
            }
        } else {
            $sessionId = $sessionIndex[$date];
        }
        
        // Check if attendance record already exists
        $stmt = $pdo->prepare("SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?");
        $stmt->execute([$sessionId, $studentId]);
        
        if ($stmt->fetch()) {
            continue; // Already recorded
        }
        
        // Create attendance record
        $stmt = $pdo->prepare("
            INSERT INTO attendance_records (session_id, student_id, status, recorded_at, created_at, updated_at)
            VALUES (?, ?, 'present', ?, NOW(), NOW())
        ");
        $stmt->execute([$sessionId, $studentId, $date]);
        $imported++;
    }
}

fclose($handle);

echo "\n";
echo "=====================================\n";
echo "✓ Attendance import complete!\n";
echo "=====================================\n";
echo "Attendance records created: {$imported}\n";
echo "Students skipped: {$skipped}\n";
echo "Unique session dates: " . count(array_unique($sessionDates)) . "\n";
echo "\n";
