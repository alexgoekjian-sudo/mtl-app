<?php

/**
 * Import Historical Courses from Trello Export
 * 
 * Links 2022-2024 course participation data to existing student records
 * without requiring historical attendance data.
 * 
 * Usage:
 *   php import_historical_courses.php path/to/trello_export.csv [--dry-run] [--date-format="m/d/Y"]
 * 
 * Features:
 *   - Student matching by email (primary) and name (fallback)
 *   - Course name normalization with mapping rules
 *   - Idempotent (safe to re-run)
 *   - Dry-run mode for validation
 *   - Detailed import report
 */

// Parse command line arguments
$options = getopt('', ['dry-run', 'date-format:', 'create-missing', 'update-existing', 'verbose', 'help']);

if (isset($options['help']) || $argc < 2) {
    echo "Usage: php import_historical_courses.php <csv_file> [options]\n\n";
    echo "Options:\n";
    echo "  --dry-run              Validate without making changes\n";
    echo "  --date-format=FORMAT   Date format (default: m/d/Y for US, try d/m/Y for EU)\n";
    echo "  --create-missing       Create students not found in system\n";
    echo "  --update-existing      Update existing course details\n";
    echo "  --verbose              Detailed output\n";
    echo "  --help                 Show this help\n\n";
    echo "Examples:\n";
    echo "  php import_historical_courses.php trello_export.csv --dry-run\n";
    echo "  php import_historical_courses.php trello_export.csv --date-format='d/m/Y'\n";
    exit(0);
}

$csvFile = $argv[1];
$dryRun = isset($options['dry-run']);
$dateFormat = $options['date-format'] ?? 'm/d/Y';
$createMissing = isset($options['create-missing']);
$updateExisting = isset($options['update-existing']);
$verbose = isset($options['verbose']);

if (!file_exists($csvFile)) {
    die("Error: CSV file not found: $csvFile\n");
}

// Load .env file manually (no Composer required)
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Error: .env file not found at $path\n");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $value = trim($value, '"\'');
        putenv("$name=$value");
        $_ENV[$name] = $value;
    }
}

// Assume script is in mtl_app directory
$envPath = __DIR__ . '/.env';
loadEnv($envPath);

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Course name normalization mappings
$courseNameMappings = [
    '/A2.*Morning.*Enschede/i' => 'A2 PR_MORN_ENSCH',
    '/A2.*Afternoon.*Enschede/i' => 'A2 PR_AFT_ENSCH',
    '/A2.*Evening.*Online/i' => 'A2 PR_EVE_ONLINE',
    '/B1.*Morning.*Enschede/i' => 'B1 PR_MORN_ENSCH',
    '/B1.*Afternoon.*Enschede/i' => 'B1 PR_AFT_ENSCH',
    '/B1.*Evening.*Online/i' => 'B1 PR_EVE_ONLINE',
    '/A1.*Intensive/i' => 'A1 INT',
    '/B2.*Intensive/i' => 'B2 INT',
    // Add more patterns as needed
];

function normalizeCourseName($courseName, $mappings) {
    foreach ($mappings as $pattern => $normalized) {
        if (preg_match($pattern, $courseName)) {
            return $normalized;
        }
    }
    // Fallback: clean up original name
    return preg_replace('/\s+/', ' ', trim($courseName));
}

function parseDate($dateStr, $format) {
    if (empty($dateStr)) return null;
    
    // Try multiple formats
    $formats = [$format, 'm/d/Y', 'd/m/Y', 'Y-m-d', 'd-m-Y', 'm-d-Y'];
    
    foreach ($formats as $fmt) {
        $date = DateTime::createFromFormat($fmt, $dateStr);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }
    
    // Try strtotime as last resort
    $timestamp = strtotime($dateStr);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return null;
}

function extractYear($dateStr) {
    $parsed = parseDate($dateStr, 'm/d/Y');
    if ($parsed) {
        return date('Y', strtotime($parsed));
    }
    
    // Try to extract 4-digit year from string
    if (preg_match('/\b(20\d{2})\b/', $dateStr, $matches)) {
        return $matches[1];
    }
    
    return null;
}

// Statistics
$stats = [
    'rows_processed' => 0,
    'students_matched_email' => 0,
    'students_matched_name' => 0,
    'students_unmatched' => 0,
    'students_created' => 0,
    'courses_created' => 0,
    'enrollments_created' => 0,
    'enrollments_skipped' => 0,
    'previous_courses_updated' => 0,
];

$unmatchedRecords = [];
$coursesCache = [];
$studentsCache = [];

// Banner
if ($dryRun) {
    echo "=== DRY RUN MODE - NO CHANGES WILL BE MADE ===\n\n";
}

echo "Historical Course Import\n";
echo "CSV File: $csvFile\n";
echo "Date Format: $dateFormat\n\n";

// Read CSV
$file = fopen($csvFile, 'r');
if (!$file) {
    die("Error: Could not open CSV file\n");
}

$headers = fgetcsv($file);
if (!$headers) {
    die("Error: CSV file is empty or invalid\n");
}

// Detect column indices (flexible column naming)
function findColumn($headers, $possibleNames) {
    foreach ($possibleNames as $name) {
        $index = array_search($name, $headers);
        if ($index !== false) return $index;
    }
    return null;
}

$colFirstName = findColumn($headers, ['First name', 'First Name', 'FirstName', 'first_name']);
$colLastName = findColumn($headers, ['Surname', 'Last name', 'Last Name', 'LastName', 'last_name']);
$colEmail = findColumn($headers, ['Email address', 'Email', 'email', 'email_address']);
$colCourseName = findColumn($headers, ['Course Name', 'Card Name', 'Title', 'course_name', 'card_name']);
$colLevel = findColumn($headers, ['Level', 'level', 'Course Level']);
$colStartDate = findColumn($headers, ['Start Date', 'Start', 'start_date']);
$colEndDate = findColumn($headers, ['End Date', 'End', 'end_date']);
$colLocation = findColumn($headers, ['Location', 'location', 'Site']);
$colNotes = findColumn($headers, ['Notes', 'Description', 'notes']);

if ($colFirstName === null || $colLastName === null || $colCourseName === null) {
    die("Error: Required columns not found. Need: First name, Last name/Surname, Course Name\n");
}

echo "Column mapping:\n";
echo "  First Name: " . ($colFirstName !== null ? $headers[$colFirstName] : 'NOT FOUND') . "\n";
echo "  Last Name: " . ($colLastName !== null ? $headers[$colLastName] : 'NOT FOUND') . "\n";
echo "  Email: " . ($colEmail !== null ? $headers[$colEmail] : 'NOT FOUND') . "\n";
echo "  Course Name: " . ($colCourseName !== null ? $headers[$colCourseName] : 'NOT FOUND') . "\n";
echo "  Level: " . ($colLevel !== null ? $headers[$colLevel] : 'NOT FOUND') . "\n";
echo "  Start Date: " . ($colStartDate !== null ? $headers[$colStartDate] : 'NOT FOUND') . "\n";
echo "\nProcessing rows...\n\n";

// Process each row
while (($row = fgetcsv($file)) !== false) {
    $stats['rows_processed']++;
    
    $firstName = isset($row[$colFirstName]) ? trim($row[$colFirstName]) : '';
    $lastName = isset($row[$colLastName]) ? trim($row[$colLastName]) : '';
    $email = isset($row[$colEmail]) ? trim(strtolower($row[$colEmail])) : '';
    $courseName = isset($row[$colCourseName]) ? trim($row[$colCourseName]) : '';
    $level = isset($row[$colLevel]) ? trim($row[$colLevel]) : '';
    $startDate = isset($row[$colStartDate]) ? trim($row[$colStartDate]) : '';
    $endDate = isset($row[$colEndDate]) ? trim($row[$colEndDate]) : '';
    $location = isset($row[$colLocation]) ? trim($row[$colLocation]) : '';
    $notes = isset($row[$colNotes]) ? trim($row[$colNotes]) : '';
    
    if (empty($firstName) || empty($lastName) || empty($courseName)) {
        if ($verbose) echo "  Skipping row {$stats['rows_processed']}: missing required fields\n";
        continue;
    }
    
    // 1. Match student
    $studentId = null;
    $matchMethod = null;
    
    // Try email match first
    if (!empty($email)) {
        $cacheKey = "email:$email";
        if (isset($studentsCache[$cacheKey])) {
            $studentId = $studentsCache[$cacheKey];
            $matchMethod = 'email (cached)';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM students WHERE LOWER(email) = ?");
            $stmt->execute([$email]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                $studentId = $student['id'];
                $studentsCache[$cacheKey] = $studentId;
                $matchMethod = 'email';
                $stats['students_matched_email']++;
            }
        }
    }
    
    // Try name match if email failed
    if (!$studentId) {
        $cacheKey = "name:$firstName:$lastName";
        if (isset($studentsCache[$cacheKey])) {
            $studentId = $studentsCache[$cacheKey];
            $matchMethod = 'name (cached)';
        } else {
            $stmt = $pdo->prepare(
                "SELECT id FROM students 
                 WHERE LOWER(first_name) = ? AND LOWER(last_name) = ?
                 LIMIT 2"
            );
            $stmt->execute([strtolower($firstName), strtolower($lastName)]);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($matches) == 1) {
                $studentId = $matches[0]['id'];
                $studentsCache[$cacheKey] = $studentId;
                $matchMethod = 'name';
                $stats['students_matched_name']++;
            } elseif (count($matches) > 1) {
                $unmatchedRecords[] = [
                    'row' => $stats['rows_processed'],
                    'reason' => 'Multiple students with same name',
                    'data' => "$firstName $lastName ($email)",
                ];
                $stats['students_unmatched']++;
                if ($verbose) echo "  Row {$stats['rows_processed']}: Multiple students found for $firstName $lastName\n";
                continue;
            }
        }
    }
    
    // Create student if not found and flag enabled
    if (!$studentId && $createMissing) {
        if (!$dryRun) {
            $stmt = $pdo->prepare(
                "INSERT INTO students (first_name, last_name, email, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([$firstName, $lastName, $email ?: null]);
            $studentId = $pdo->lastInsertId();
            $studentsCache["email:$email"] = $studentId;
            $matchMethod = 'created';
            $stats['students_created']++;
        } else {
            $matchMethod = 'would create';
        }
    }
    
    if (!$studentId) {
        $unmatchedRecords[] = [
            'row' => $stats['rows_processed'],
            'reason' => 'Student not found',
            'data' => "$firstName $lastName ($email)",
        ];
        $stats['students_unmatched']++;
        if ($verbose) echo "  Row {$stats['rows_processed']}: Student not found - $firstName $lastName ($email)\n";
        continue;
    }
    
    // 2. Parse course data
    $normalizedCourseName = normalizeCourseName($courseName, $courseNameMappings);
    $parsedStartDate = parseDate($startDate, $dateFormat);
    $parsedEndDate = parseDate($endDate, $dateFormat);
    $year = extractYear($startDate) ?: extractYear($endDate) ?: date('Y');
    
    // 3. Find or create course
    $courseKey = strtolower($normalizedCourseName) . '_' . $year;
    $cacheKey = "course:$courseKey";
    
    if (isset($coursesCache[$cacheKey])) {
        $courseId = $coursesCache[$cacheKey];
    } else {
        // Try to find existing course
        $stmt = $pdo->prepare(
            "SELECT id FROM course_offerings 
             WHERE course_full_name = ? AND YEAR(COALESCE(start_date, end_date)) = ?
             LIMIT 1"
        );
        $stmt->execute([$normalizedCourseName, $year]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($course) {
            $courseId = $course['id'];
        } else {
            // Create new historical course
            if (!$dryRun) {
                $stmt = $pdo->prepare(
                    "INSERT INTO course_offerings 
                     (course_key, course_full_name, level, location, start_date, end_date, 
                      is_historical, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW(), NOW())"
                );
                $stmt->execute([
                    $courseKey,
                    $normalizedCourseName,
                    $level ?: null,
                    $location ?: null,
                    $parsedStartDate,
                    $parsedEndDate,
                ]);
                $courseId = $pdo->lastInsertId();
                $stats['courses_created']++;
            } else {
                $courseId = 'DRY_RUN_' . $courseKey;
                $stats['courses_created']++;
            }
        }
        
        $coursesCache[$cacheKey] = $courseId;
    }
    
    // 4. Check if enrollment exists
    if (!$dryRun) {
        $stmt = $pdo->prepare(
            "SELECT id FROM enrollments 
             WHERE student_id = ? AND course_offering_id = ?"
        );
        $stmt->execute([$studentId, $courseId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $stats['enrollments_skipped']++;
            if ($verbose) echo "  Row {$stats['rows_processed']}: Enrollment already exists\n";
            continue;
        }
    }
    
    // 5. Create enrollment
    $metadata = json_encode([
        'original_course_name' => $courseName,
        'import_date' => date('Y-m-d H:i:s'),
        'notes' => $notes,
        'source' => 'trello_historical_import',
    ]);
    
    if (!$dryRun) {
        $stmt = $pdo->prepare(
            "INSERT INTO enrollments 
             (student_id, course_offering_id, status, historical_metadata, 
              enrolled_at, created_at, updated_at)
             VALUES (?, ?, 'completed', ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $studentId,
            $courseId,
            $metadata,
            $parsedStartDate ?: date('Y-m-d'),
        ]);
        $stats['enrollments_created']++;
    } else {
        $stats['enrollments_created']++;
    }
    
    // 6. Update student.previous_courses field
    $courseEntry = "$normalizedCourseName ($year)";
    
    if (!$dryRun) {
        $stmt = $pdo->prepare("SELECT previous_courses FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $previousCourses = $student['previous_courses'] ?? '';
        $coursesArray = array_filter(array_map('trim', explode(',', $previousCourses)));
        
        if (!in_array($courseEntry, $coursesArray)) {
            $coursesArray[] = $courseEntry;
            $updatedPreviousCourses = implode(', ', $coursesArray);
            
            $stmt = $pdo->prepare("UPDATE students SET previous_courses = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$updatedPreviousCourses, $studentId]);
            $stats['previous_courses_updated']++;
        }
    } else {
        $stats['previous_courses_updated']++;
    }
    
    if ($verbose) {
        echo "  Row {$stats['rows_processed']}: $firstName $lastName → $normalizedCourseName ($year) [matched: $matchMethod]\n";
    }
}

fclose($file);

// Final report
echo "\n" . str_repeat('=', 60) . "\n";
if ($dryRun) {
    echo "DRY RUN COMPLETE - NO CHANGES MADE\n";
} else {
    echo "IMPORT COMPLETE\n";
}
echo str_repeat('=', 60) . "\n\n";

echo "Summary:\n";
echo "  Total rows processed: {$stats['rows_processed']}\n";
echo "  Students matched (email): {$stats['students_matched_email']}\n";
echo "  Students matched (name): {$stats['students_matched_name']}\n";
echo "  Students unmatched: {$stats['students_unmatched']}\n";
echo "  Students created: {$stats['students_created']}\n";
echo "  Courses created: {$stats['courses_created']}\n";
echo "  Enrollments created: {$stats['enrollments_created']}\n";
echo "  Enrollments skipped (duplicates): {$stats['enrollments_skipped']}\n";
echo "  Previous courses updated: {$stats['previous_courses_updated']}\n";

if (count($unmatchedRecords) > 0) {
    echo "\nUnmatched Records (" . count($unmatchedRecords) . "):\n";
    foreach (array_slice($unmatchedRecords, 0, 20) as $record) {
        echo "  Row {$record['row']}: {$record['reason']} - {$record['data']}\n";
    }
    
    if (count($unmatchedRecords) > 20) {
        echo "  ... and " . (count($unmatchedRecords) - 20) . " more\n";
    }
    
    // Export unmatched to CSV
    $unmatchedFile = 'historical_import_unmatched_' . date('Y-m-d') . '.csv';
    $fp = fopen($unmatchedFile, 'w');
    fputcsv($fp, ['Row', 'Reason', 'Data']);
    foreach ($unmatchedRecords as $record) {
        fputcsv($fp, [$record['row'], $record['reason'], $record['data']]);
    }
    fclose($fp);
    
    echo "\nUnmatched records exported to: $unmatchedFile\n";
}

echo "\nDone!\n";
