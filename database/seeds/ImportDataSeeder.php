<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CourseOffering;
use App\Models\Student;
use App\Models\Lead;
use App\Models\Enrollment;

class ImportDataSeeder extends Seeder
{
    /**
     * Seed database with data from normalized JSON files in specs/001-title-english-language/imports/out/
     */
    public function run()
    {
        $basePath = base_path('specs/001-title-english-language/imports/out');
        
        $this->command->info('Starting import from normalized JSON files...');
        
        // 1. Import Courses
        $coursesFile = $basePath . '/courses_normalized.json';
        if (file_exists($coursesFile)) {
            $this->importCourses($coursesFile);
        } else {
            $this->command->warn('Courses file not found: ' . $coursesFile);
        }
        
        // 2. Import Students/Leads from Trello
        $trelloFile = $basePath . '/trello_normalized.json';
        if (file_exists($trelloFile)) {
            $this->importTrelloData($trelloFile);
        } else {
            $this->command->warn('Trello file not found: ' . $trelloFile);
        }
        
        $this->command->info('Import complete!');
    }
    
    /**
     * Import course offerings from normalized JSON
     */
    protected function importCourses($filePath)
    {
        $this->command->info('Importing courses from: ' . $filePath);
        
        $json = json_decode(file_get_contents($filePath), true);
        $imported = 0;
        $skipped = 0;
        
        foreach ($json as $row) {
            if ($row['import_status'] !== 'ok') {
                $skipped++;
                continue;
            }
            
            $data = $row['course_offering'];
            
            // Check if course already exists (by course_key)
            $existing = CourseOffering::where('course_key', $data['course_key'])->first();
            if ($existing) {
                $this->command->warn('Skipping duplicate course: ' . $data['course_key']);
                $skipped++;
                continue;
            }
            
            // Map normalized data to database schema
            $courseData = [
                'course_key' => $data['course_key'] ?? null,
                'course_full_name' => $data['course_full_name'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'price' => $data['price'] ?? null,
                'location' => $data['location'] ?? null,
                'online' => ($data['delivery_mode'] ?? '') === 'online',
            ];
            
            // Parse schedule data
            if (!empty($data['days'])) {
                $courseData['schedule'] = json_encode([
                    'days' => $data['days'],
                    'time_range' => $data['time_range'] ?? null,
                ]);
            }
            
            // Extract level from course_key (e.g., "A1 BEGINNER" -> "A1")
            if (preg_match('/^([ABC][12])\b/', $data['course_key'] ?? '', $matches)) {
                $courseData['level'] = $matches[1];
            }
            
            // Extract type from schedule_type (EVENING ONLINE, MORNING, etc.)
            $scheduleType = strtolower($data['schedule_type'] ?? '');
            if (strpos($scheduleType, 'morning') !== false) {
                $courseData['type'] = 'morning';
            } elseif (strpos($scheduleType, 'evening') !== false) {
                $courseData['type'] = 'evening';
            } elseif (strpos($scheduleType, 'afternoon') !== false) {
                $courseData['type'] = 'afternoon';
            } elseif (strpos($scheduleType, 'online') !== false) {
                $courseData['type'] = 'online';
            } elseif (strpos($scheduleType, 'intensive') !== false) {
                $courseData['type'] = 'intensive';
            }
            
            // Parse hours_total from hours_raw (e.g., "24 hours/6 weeks" -> 24)
            if (!empty($data['hours_raw'])) {
                if (preg_match('/(\d+)\s*hours?/', $data['hours_raw'], $matches)) {
                    $courseData['hours_total'] = (int)$matches[1];
                }
            }
            
            // Set program based on course type
            $courseData['program'] = $this->inferProgram($data);
            
            try {
                CourseOffering::create($courseData);
                $imported++;
                if ($imported % 10 == 0) {
                    $this->command->info("Imported {$imported} courses...");
                }
            } catch (\Exception $e) {
                $this->command->error('Failed to import course: ' . $data['course_key'] . ' - ' . $e->getMessage());
                $skipped++;
            }
        }
        
        $this->command->info("Courses imported: {$imported}, skipped: {$skipped}");
    }
    
    /**
     * Import students/leads from Trello normalized JSON
     */
    protected function importTrelloData($filePath)
    {
        $this->command->info('Importing students/leads from: ' . $filePath);
        
        $json = json_decode(file_get_contents($filePath), true);
        $studentsCreated = 0;
        $leadsCreated = 0;
        $enrollmentsCreated = 0;
        $skipped = 0;
        
        // Build course index for linking
        $courseIndex = [];
        foreach (CourseOffering::all() as $course) {
            $courseIndex[$course->course_key] = $course->id;
        }
        
        foreach ($json as $row) {
            $data = $row['student'];
            
            // Skip if no contact info
            if (empty($data['email']) && empty($data['phone'])) {
                $skipped++;
                continue;
            }
            
            // Check for existing student by email or phone
            $existingStudent = null;
            if (!empty($data['email'])) {
                $existingStudent = Student::where('email', $data['email'])->first();
            }
            if (!$existingStudent && !empty($data['phone'])) {
                $existingStudent = Student::where('phone', $data['phone'])->first();
            }
            
            if ($existingStudent) {
                $this->command->warn('Skipping duplicate student: ' . $data['email'] . ' / ' . $data['phone']);
                $skipped++;
                continue;
            }
            
            // Determine if this should be a Lead or Student
            // If they have assessed_level and course_name, they're likely a student
            $hasLevelCheck = !empty($data['assessed_level']) || !empty($data['placement_result']);
            $hasCourse = !empty($data['course_name']) && !empty($data['linked_course_key']);
            
            if ($hasLevelCheck || $hasCourse) {
                // Create as Student
                $studentData = [
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'country_of_origin' => $data['country'] ?? null,
                    'city_of_residence' => $data['city'] ?? null,
                    'initial_level' => $data['assessed_level'] ?? null,
                    'current_level' => $data['assessed_level'] ?? null,
                    'profile_notes' => $data['activity_notes'] ?? null,
                ];
                
                // Parse languages
                if (!empty($data['languages']) && is_array($data['languages'])) {
                    $studentData['languages'] = json_encode($data['languages']);
                }
                
                try {
                    $student = Student::create($studentData);
                    $studentsCreated++;
                    
                    // Create enrollment if course is linked
                    if ($hasCourse && isset($courseIndex[$data['linked_course_key']])) {
                        $courseId = $courseIndex[$data['linked_course_key']];
                        
                        // Check if enrollment already exists
                        $existingEnrollment = Enrollment::where('student_id', $student->id)
                            ->where('course_offering_id', $courseId)
                            ->first();
                        
                        if (!$existingEnrollment) {
                            Enrollment::create([
                                'student_id' => $student->id,
                                'course_offering_id' => $courseId,
                                'status' => 'registered',
                                'enrolled_at' => now(),
                            ]);
                            $enrollmentsCreated++;
                        }
                    }
                    
                    if ($studentsCreated % 10 == 0) {
                        $this->command->info("Created {$studentsCreated} students, {$enrollmentsCreated} enrollments...");
                    }
                } catch (\Exception $e) {
                    $this->command->error('Failed to create student: ' . $data['email'] . ' - ' . $e->getMessage());
                    $skipped++;
                }
            } else {
                // Create as Lead
                $leadData = [
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'country' => $data['country'] ?? null,
                    'source' => 'trello_import',
                    'activity_notes' => $data['activity_notes'] ?? null,
                ];
                
                // Parse languages
                if (!empty($data['languages']) && is_array($data['languages'])) {
                    $leadData['languages'] = implode(', ', $data['languages']);
                }
                
                try {
                    Lead::create($leadData);
                    $leadsCreated++;
                    
                    if ($leadsCreated % 10 == 0) {
                        $this->command->info("Created {$leadsCreated} leads...");
                    }
                } catch (\Exception $e) {
                    $this->command->error('Failed to create lead: ' . $data['email'] . ' - ' . $e->getMessage());
                    $skipped++;
                }
            }
        }
        
        $this->command->info("Students created: {$studentsCreated}, Leads created: {$leadsCreated}, Enrollments created: {$enrollmentsCreated}, Skipped: {$skipped}");
    }
    
    /**
     * Infer program type from course data
     */
    protected function inferProgram($data)
    {
        $courseKey = strtolower($data['course_key'] ?? '');
        $scheduleType = strtolower($data['schedule_type'] ?? '');
        
        if (strpos($courseKey, 'intensive') !== false || strpos($scheduleType, 'intensive') !== false) {
            return 'intensive';
        }
        
        if (strpos($courseKey, 'conversation') !== false || strpos($scheduleType, 'conversation') !== false) {
            return 'conversation';
        }
        
        if (strpos($courseKey, 'business') !== false) {
            return 'business';
        }
        
        if (strpos($courseKey, '1-2-1') !== false || strpos($courseKey, 'private') !== false) {
            return 'private';
        }
        
        // Default to general based on level
        if (preg_match('/^[abc][12]/i', $courseKey)) {
            return 'general';
        }
        
        return 'general';
    }
}
