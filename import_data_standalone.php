<?php
/**
 * Standalone import script for MTL database
 * Run this directly on the server: php import_data_standalone.php
 * 
 * This script connects directly to the database and imports data from normalized JSON files
 */

// Load environment variables from .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Error: .env file not found at {$path}\n");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, '"\'');
        
        if (!getenv($key)) {
            putenv("{$key}={$value}");
        }
    }
}

// Get current script directory
$baseDir = __DIR__;
loadEnv($baseDir . '/.env');

// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$database = getenv('DB_DATABASE');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

echo "Connecting to database: {$database}@{$host}\n";

try {
    $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connected\n\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Import courses
function importCourses($pdo, $filePath) {
    echo "Importing courses from: {$filePath}\n";
    
    if (!file_exists($filePath)) {
        echo "⚠ File not found, skipping\n\n";
        return;
    }
    
    $json = json_decode(file_get_contents($filePath), true);
    $imported = 0;
    $skipped = 0;
    
    foreach ($json as $row) {
        if ($row['import_status'] !== 'ok') {
            $skipped++;
            continue;
        }
        
        $data = $row['course_offering'];
        
        // Check if course already exists
        $stmt = $pdo->prepare("SELECT id FROM course_offerings WHERE course_key = ?");
        $stmt->execute([$data['course_key']]);
        if ($stmt->fetch()) {
            echo "  - Skipping duplicate: {$data['course_key']}\n";
            $skipped++;
            continue;
        }
        
        // Parse schedule
        $schedule = null;
        if (!empty($data['days'])) {
            $schedule = json_encode([
                'days' => $data['days'],
                'time_range' => $data['time_range'] ?? null,
            ]);
        }
        
        // Extract level from course_key
        $level = null;
        if (preg_match('/^([ABC][12])\b/', $data['course_key'] ?? '', $matches)) {
            $level = $matches[1];
        }
        
        // Extract type from schedule_type
        $type = null;
        $scheduleType = strtolower($data['schedule_type'] ?? '');
        if (strpos($scheduleType, 'morning') !== false) $type = 'morning';
        elseif (strpos($scheduleType, 'evening') !== false) $type = 'evening';
        elseif (strpos($scheduleType, 'afternoon') !== false) $type = 'afternoon';
        elseif (strpos($scheduleType, 'online') !== false) $type = 'online';
        elseif (strpos($scheduleType, 'intensive') !== false) $type = 'intensive';
        
        // Parse hours_total
        $hoursTotal = null;
        if (!empty($data['hours_raw']) && preg_match('/(\d+)\s*hours?/', $data['hours_raw'], $matches)) {
            $hoursTotal = (int)$matches[1];
        }
        
        // Infer program
        $courseKey = strtolower($data['course_key'] ?? '');
        if (strpos($courseKey, 'intensive') !== false) $program = 'intensive';
        elseif (strpos($courseKey, 'conversation') !== false) $program = 'conversation';
        elseif (strpos($courseKey, 'business') !== false) $program = 'business';
        elseif (strpos($courseKey, '1-2-1') !== false) $program = 'private';
        else $program = 'general';
        
        $online = ($data['delivery_mode'] ?? '') === 'online' ? 1 : 0;
        
        // Insert course
        $sql = "INSERT INTO course_offerings (
            course_key, course_full_name, level, program, type, start_date, end_date, 
            hours_total, schedule, price, location, online, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['course_key'],
            $data['course_full_name'],
            $level,
            $program,
            $type,
            $data['start_date'],
            $data['end_date'],
            $hoursTotal,
            $schedule,
            $data['price'],
            $data['location'],
            $online
        ]);
        
        $imported++;
        if ($imported % 10 == 0) {
            echo "  Imported {$imported} courses...\n";
        }
    }
    
    echo "✓ Courses imported: {$imported}, skipped: {$skipped}\n\n";
}

// Import students/leads from Trello
function importTrelloData($pdo, $filePath) {
    echo "Importing students/leads from: {$filePath}\n";
    
    if (!file_exists($filePath)) {
        echo "⚠ File not found, skipping\n\n";
        return;
    }
    
    $json = json_decode(file_get_contents($filePath), true);
    $studentsCreated = 0;
    $leadsCreated = 0;
    $enrollmentsCreated = 0;
    $skipped = 0;
    
    // Build course index
    $courseIndex = [];
    $stmt = $pdo->query("SELECT id, course_key FROM course_offerings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $courseIndex[$row['course_key']] = $row['id'];
    }
    
    foreach ($json as $row) {
        $data = $row['student'];
        
        // Skip if no contact info
        if (empty($data['email']) && empty($data['phone'])) {
            $skipped++;
            continue;
        }
        
        // Check for existing student
        $existingStudent = null;
        if (!empty($data['email'])) {
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$data['email']]);
            $existingStudent = $stmt->fetch();
        }
        if (!$existingStudent && !empty($data['phone'])) {
            $stmt = $pdo->prepare("SELECT id FROM students WHERE phone = ?");
            $stmt->execute([$data['phone']]);
            $existingStudent = $stmt->fetch();
        }
        
        if ($existingStudent) {
            $skipped++;
            continue;
        }
        
        // Determine if this should be a Lead or Student
        $hasLevelCheck = !empty($data['assessed_level']) || !empty($data['placement_result']);
        $hasCourse = !empty($data['course_name']) && !empty($data['linked_course_key']);
        
        if ($hasLevelCheck || $hasCourse) {
            // Create as Student
            $languages = null;
            if (!empty($data['languages']) && is_array($data['languages'])) {
                $languages = json_encode($data['languages']);
            }
            
            $sql = "INSERT INTO students (
                first_name, last_name, email, phone, country_of_origin, city_of_residence,
                initial_level, current_level, languages, profile_notes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['first_name'] ?? null,
                $data['last_name'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['country'] ?? null,
                $data['city'] ?? null,
                $data['assessed_level'] ?? null,
                $data['assessed_level'] ?? null,
                $languages,
                $data['activity_notes'] ?? null
            ]);
            
            $studentId = $pdo->lastInsertId();
            $studentsCreated++;
            
            // Create enrollment if course is linked
            if ($hasCourse && isset($courseIndex[$data['linked_course_key']])) {
                $courseId = $courseIndex[$data['linked_course_key']];
                
                // Check if enrollment exists
                $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_offering_id = ?");
                $stmt->execute([$studentId, $courseId]);
                
                if (!$stmt->fetch()) {
                    $sql = "INSERT INTO enrollments (
                        student_id, course_offering_id, status, enrolled_at, created_at, updated_at
                    ) VALUES (?, ?, 'registered', NOW(), NOW(), NOW())";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$studentId, $courseId]);
                    $enrollmentsCreated++;
                }
            }
            
            if ($studentsCreated % 10 == 0) {
                echo "  Created {$studentsCreated} students, {$enrollmentsCreated} enrollments...\n";
            }
        } else {
            // Create as Lead
            $languages = null;
            if (!empty($data['languages']) && is_array($data['languages'])) {
                $languages = implode(', ', $data['languages']);
            }
            
            $sql = "INSERT INTO leads (
                first_name, last_name, email, phone, country, source, languages, activity_notes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, 'trello_import', ?, ?, NOW(), NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['first_name'] ?? null,
                $data['last_name'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['country'] ?? null,
                $languages,
                $data['activity_notes'] ?? null
            ]);
            
            $leadsCreated++;
            
            if ($leadsCreated % 10 == 0) {
                echo "  Created {$leadsCreated} leads...\n";
            }
        }
    }
    
    echo "✓ Students: {$studentsCreated}, Leads: {$leadsCreated}, Enrollments: {$enrollmentsCreated}, Skipped: {$skipped}\n\n";
}

// Main execution
echo "=====================================\n";
echo "MTL Database Import\n";
echo "=====================================\n\n";

$importsPath = $baseDir . '/specs/001-title-english-language/imports/out';

importCourses($pdo, $importsPath . '/courses_normalized.json');
importTrelloData($pdo, $importsPath . '/trello_normalized.json');

echo "=====================================\n";
echo "✓ Import complete!\n";
echo "=====================================\n";
