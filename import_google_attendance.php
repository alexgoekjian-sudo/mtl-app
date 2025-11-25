<?php
/**
 * Import Google Sheets Attendance Data
 * 
 * Imports attendance data exported from Google Sheets
 * Each CSV file name should match the attendance_id from course_offerings table
 * 
 * Usage:
 * php import_google_attendance.php path/to/export/folder
 * or
 * php import_google_attendance.php path/to/single/file.csv
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

// Get input path
$inputPath = $argv[1] ?? null;
if (!$inputPath) {
    die("Usage: php import_google_attendance.php <folder_or_file_path>\n");
}

if (!file_exists($inputPath)) {
    die("Error: Path does not exist: $inputPath\n");
}

// Get list of CSV files to process
$csvFiles = [];
if (is_dir($inputPath)) {
    $files = scandir($inputPath);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'csv' && !str_starts_with($file, '_')) {
            $csvFiles[] = $inputPath . '/' . $file;
        }
    }
} else {
    $csvFiles[] = $inputPath;
}

if (empty($csvFiles)) {
    die("No CSV files found to import\n");
}

echo "Found " . count($csvFiles) . " CSV file(s) to import\n\n";

$stats = [
    'courses_processed' => 0,
    'students_processed' => 0,
    'sessions_created' => 0,
    'attendance_records' => 0,
    'errors' => []
];

foreach ($csvFiles as $csvFile) {
    echo "Processing: " . basename($csvFile) . "\n";
    
    // Extract attendance_id from filename
    $attendanceId = pathinfo($csvFile, PATHINFO_FILENAME);
    
    // Find course by attendance_id
    $stmt = $pdo->prepare("SELECT * FROM course_offerings WHERE attendance_id = ?");
    $stmt->execute([$attendanceId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        echo "  ⚠ Warning: No course found with attendance_id: $attendanceId\n";
        $stats['errors'][] = "Course not found: $attendanceId";
        continue;
    }
    
    echo "  ✓ Found course: {$course['course_full_name']}\n";
    $stats['courses_processed']++;
    
    // Generate full course schedule from start_date, end_date, and schedule JSON
    $courseSessionDates = generateCourseSchedule(
        $course['start_date'],
        $course['end_date'],
        $course['schedule']
    );
    
    echo "  → Generated " . count($courseSessionDates) . " scheduled sessions\n";
    
    // Create all sessions for this course first
    foreach ($courseSessionDates as $sessionDate) {
        $stmt = $pdo->prepare(
            "SELECT id FROM sessions WHERE course_offering_id = ? AND date = ?"
        );
        $stmt->execute([$course['id'], $sessionDate]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            $stmt = $pdo->prepare(
                "INSERT INTO sessions 
                 (course_offering_id, date, start_time, end_time, teacher_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $course['id'],
                $sessionDate,
                $course['start_time'],
                $course['end_time'],
                $course['teacher_id']
            ]);
            $stats['sessions_created']++;
        }
    }
    
    // Parse CSV
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        echo "  ✗ Error: Could not open file\n";
        $stats['errors'][] = "Could not open: $csvFile";
        continue;
    }
    
    // Skip header row
    $header = fgetcsv($handle);
    
    $studentCount = 0;
    
    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[0])) continue; // Skip empty rows
        
        // Parse row data
        $studentName = $row[0] ?? '';
        $studentEmail = $row[1] ?? '';
        $datesPresent = $row[2] ?? '';
        $initialLevel = $row[3] ?? '';
        $midLevel = $row[4] ?? '';
        $teacherNotes = $row[5] ?? '';
        $previousCourses = $row[6] ?? '';
        $trelloCardId = $row[7] ?? '';
        $country = $row[8] ?? '';
        
        // Split name into first/last
        $nameParts = explode(' ', trim($studentName), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';
        
        // Find or create student
        $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->execute([$studentEmail]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            // Create new student
            $stmt = $pdo->prepare(
                "INSERT INTO students (first_name, last_name, email, initial_level, country_of_origin, previous_courses, profile_notes, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $firstName, 
                $lastName, 
                $studentEmail,
                $initialLevel,
                $country,
                $previousCourses,
                "Trello: $trelloCardId"
            ]);
            $studentId = $pdo->lastInsertId();
            echo "  + Created student: $studentName ($studentEmail)\n";
        } else {
            $studentId = $student['id'];
            
            // Update student info (only if fields are not empty)
            $updates = [];
            $params = [];
            
            if ($firstName) {
                $updates[] = "first_name = ?";
                $params[] = $firstName;
            }
            if ($lastName) {
                $updates[] = "last_name = ?";
                $params[] = $lastName;
            }
            if ($initialLevel) {
                $updates[] = "initial_level = ?";
                $params[] = $initialLevel;
            }
            if ($country) {
                $updates[] = "country_of_origin = ?";
                $params[] = $country;
            }
            if ($previousCourses) {
                $updates[] = "previous_courses = ?";
                $params[] = $previousCourses;
            }
            
            if (!empty($updates)) {
                $updates[] = "updated_at = NOW()";
                $params[] = $studentId;
                $sql = "UPDATE students SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
        }
        
        // Check if enrollment exists
        $stmt = $pdo->prepare(
            "SELECT id FROM enrollments WHERE student_id = ? AND course_offering_id = ?"
        );
        $stmt->execute([$studentId, $course['id']]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$enrollment) {
            // Create enrollment
            $stmt = $pdo->prepare(
                "INSERT INTO enrollments 
                 (student_id, course_offering_id, status, mid_course_level, mid_course_notes, created_at, updated_at)
                 VALUES (?, ?, 'active', ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $studentId,
                $course['id'],
                $midLevel,
                $teacherNotes
            ]);
        } else {
            // Update enrollment
            $stmt = $pdo->prepare(
                "UPDATE enrollments 
                 SET status = 'active', mid_course_level = ?, mid_course_notes = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([
                $midLevel,
                $teacherNotes,
                $enrollment['id']
            ]);
        }
        
        // Parse dates present - these are the days the student actually attended
        if (!empty($datesPresent)) {
            $attendedDates = parseDatesPresent($datesPresent, $course['start_date']);
            
            foreach ($attendedDates as $date) {
                // Find the session for this date
                $stmt = $pdo->prepare(
                    "SELECT id FROM sessions 
                     WHERE course_offering_id = ? AND date = ?"
                );
                $stmt->execute([$course['id'], $date]);
                $session = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($session) {
                    // Create attendance record
                    $stmt = $pdo->prepare(
                        "INSERT INTO attendance_records 
                         (session_id, student_id, status, created_at, updated_at)
                         VALUES (?, ?, 'present', NOW(), NOW())
                         ON DUPLICATE KEY UPDATE status = 'present', updated_at = NOW()"
                    );
                    $stmt->execute([$session['id'], $studentId]);
                    $stats['attendance_records']++;
                } else {
                    echo "  ⚠ Warning: No session found for date $date (student attended but session doesn't exist)\n";
                }
            }
        }
        
        $studentCount++;
        $stats['students_processed']++;
    }
    
    fclose($handle);
    echo "  ✓ Processed $studentCount students\n\n";
}

// Print summary
echo "\n=================================\n";
echo "Import Summary\n";
echo "=================================\n";
echo "Courses processed: {$stats['courses_processed']}\n";
echo "Students processed: {$stats['students_processed']}\n";
echo "Sessions created: {$stats['sessions_created']}\n";
echo "Attendance records: {$stats['attendance_records']}\n";

if (!empty($stats['errors'])) {
    echo "\nErrors:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - $error\n";
    }
}

echo "\n✓ Import complete!\n";

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

/**
 * Parse dates present from various formats
 */
function parseDatesPresent($datesString, $courseStartDate) {
    $dates = [];
    $year = date('Y', strtotime($courseStartDate));
    
    // Split by comma
    $parts = array_map('trim', explode(',', $datesString));
    
    foreach ($parts as $part) {
        if (empty($part)) continue;
        
        // Try different date formats
        $date = null;
        
        // Format: "2024-12-05" (ISO format) - check this FIRST
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $part)) {
            $date = $part;
        }
        // Format: "12/5" or "12-5"
        else if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})$/', $part, $matches)) {
            $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $date = "$year-$month-$day";
        }
        // Format: "Dec 5" or "December 5"
        else if (preg_match('/^([A-Za-z]+)\s+(\d{1,2})$/', $part, $matches)) {
            $monthName = $matches[1];
            $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $timestamp = strtotime("$monthName $day, $year");
            if ($timestamp) {
                $date = date('Y-m-d', $timestamp);
            }
        }
        
        if ($date && strtotime($date)) {
            $dates[] = $date;
        }
    }
    
    return $dates;
}
