<?php
/**
 * Create Sessions for All Courses
 * 
 * Generates all session dates for courses based on their schedule JSON
 * This ensures the attendance grid shows all dates even before students are enrolled
 * 
 * Usage: php create_all_sessions.php
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
    echo "✓ Database connected\n\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

/**
 * Generate course schedule from start date, end date, and schedule JSON
 */
function generateCourseSchedule($startDate, $endDate, $scheduleJson) {
    $dates = [];
    
    if (empty($scheduleJson)) {
        return $dates;
    }
    
    $schedule = json_decode($scheduleJson, true);
    if (!$schedule || !isset($schedule['days']) || empty($schedule['days'])) {
        return $dates;
    }
    
    // Map day abbreviations to PHP day numbers (1=Monday, 7=Sunday)
    $dayMap = [
        'M' => 1,   // Monday
        'T' => 2,   // Tuesday
        'W' => 3,   // Wednesday
        'Th' => 4,  // Thursday
        'F' => 5,   // Friday
        'Sa' => 6,  // Saturday
        'Su' => 7   // Sunday
    ];
    
    $scheduledDays = [];
    foreach ($schedule['days'] as $day) {
        if (isset($dayMap[$day])) {
            $scheduledDays[] = $dayMap[$day];
        }
    }
    
    if (empty($scheduledDays)) {
        return $dates;
    }
    
    // Generate all dates between start and end that match scheduled days
    $current = new DateTime($startDate);
    $end = new DateTime($endDate);
    
    while ($current <= $end) {
        $dayOfWeek = (int)$current->format('N'); // 1=Monday, 7=Sunday
        
        if (in_array($dayOfWeek, $scheduledDays)) {
            $dates[] = $current->format('Y-m-d');
        }
        
        $current->modify('+1 day');
    }
    
    return $dates;
}

// Get all courses
$stmt = $pdo->query("
    SELECT * FROM course_offerings 
    WHERE start_date IS NOT NULL 
    AND end_date IS NOT NULL 
    AND schedule IS NOT NULL
    ORDER BY start_date
");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($courses) . " courses with schedule data\n\n";

$stats = [
    'courses_processed' => 0,
    'sessions_created' => 0,
    'sessions_existed' => 0
];

foreach ($courses as $course) {
    echo "Processing: {$course['course_full_name']}\n";
    
    // Generate schedule
    $sessionDates = generateCourseSchedule(
        $course['start_date'],
        $course['end_date'],
        $course['schedule']
    );
    
    if (empty($sessionDates)) {
        echo "  ⚠ No session dates generated (invalid schedule)\n\n";
        continue;
    }
    
    echo "  → Generated " . count($sessionDates) . " session dates\n";
    
    $created = 0;
    $existed = 0;
    
    foreach ($sessionDates as $date) {
        // Check if session exists
        $stmt = $pdo->prepare(
            "SELECT id FROM sessions WHERE course_offering_id = ? AND date = ?"
        );
        $stmt->execute([$course['id'], $date]);
        
        if ($stmt->fetch()) {
            $existed++;
            continue;
        }
        
        // Create session
        $stmt = $pdo->prepare(
            "INSERT INTO sessions 
             (course_offering_id, date, start_time, end_time, teacher_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $course['id'],
            $date,
            $course['start_time'] ?? null,
            $course['end_time'] ?? null,
            $course['teacher_id'] ?? null
        ]);
        
        $created++;
    }
    
    echo "  ✓ Created $created sessions";
    if ($existed > 0) {
        echo " ($existed already existed)";
    }
    echo "\n\n";
    
    $stats['courses_processed']++;
    $stats['sessions_created'] += $created;
    $stats['sessions_existed'] += $existed;
}

echo "=================================\n";
echo "Summary\n";
echo "=================================\n";
echo "Courses processed: {$stats['courses_processed']}\n";
echo "Sessions created: {$stats['sessions_created']}\n";
echo "Sessions already existed: {$stats['sessions_existed']}\n";
echo "\n✓ Complete!\n";
