-- Database Views for MTL App
-- These views simplify common queries and improve Retool performance

-- 1. Student Overview with Current Enrollments
CREATE OR REPLACE VIEW student_overview AS
SELECT 
    s.id,
    s.first_name,
    s.last_name,
    s.email,
    s.phone,
    s.country_of_origin,
    s.current_level,
    s.created_at as student_since,
    COUNT(DISTINCT e.id) as total_enrollments,
    COUNT(DISTINCT CASE WHEN e.status IN ('active', 'registered') THEN e.id END) as active_enrollments,
    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) as completed_courses,
    MAX(e.enrolled_at) as last_enrollment_date
FROM students s
LEFT JOIN enrollments e ON s.id = e.student_id
GROUP BY s.id, s.first_name, s.last_name, s.email, s.phone, s.country_of_origin, s.current_level, s.created_at;

-- 2. Active Enrollments with Course Details
CREATE OR REPLACE VIEW active_enrollments_detail AS
SELECT 
    e.id as enrollment_id,
    e.status,
    e.enrolled_at,
    e.payment_override_reason,
    s.id as student_id,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.email as student_email,
    s.phone as student_phone,
    co.id as course_offering_id,
    co.course_full_name,
    co.level,
    co.program,
    co.type,
    co.start_date,
    co.end_date,
    co.price,
    co.location,
    co.online,
    DATEDIFF(co.start_date, CURDATE()) as days_until_start
FROM enrollments e
JOIN students s ON e.student_id = s.id
JOIN course_offerings co ON e.course_offering_id = co.id
WHERE e.status IN ('pending', 'registered', 'active');

-- 3. Lead Conversion Funnel
CREATE OR REPLACE VIEW lead_conversion_funnel AS
SELECT 
    l.id as lead_id,
    l.first_name,
    l.last_name,
    l.email,
    l.phone,
    l.reference as lead_source,
    l.source_detail as marketing_channel,
    l.created_at as lead_created,
    s.id as student_id,
    s.created_at as converted_date,
    DATEDIFF(s.created_at, l.created_at) as days_to_conversion,
    COUNT(DISTINCT e.id) as total_enrollments,
    SUM(co.price) as total_revenue
FROM leads l
LEFT JOIN students s ON l.id = s.lead_id
LEFT JOIN enrollments e ON s.id = e.student_id AND e.status NOT IN ('dropped')
LEFT JOIN course_offerings co ON e.course_offering_id = co.id
GROUP BY l.id, l.first_name, l.last_name, l.email, l.phone, l.reference, l.source_detail, l.created_at, s.id, s.created_at;

-- 4. Payment Status Overview
CREATE OR REPLACE VIEW payment_status_overview AS
SELECT 
    e.id as enrollment_id,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    co.course_full_name,
    co.price as course_price,
    e.status as enrollment_status,
    COALESCE(SUM(CASE WHEN p.status = 'completed' AND p.is_refund = 0 THEN p.amount ELSE 0 END), 0) as total_paid,
    COALESCE(SUM(CASE WHEN p.status = 'completed' AND p.is_refund = 1 THEN p.amount ELSE 0 END), 0) as total_refunded,
    co.price - COALESCE(SUM(CASE WHEN p.status = 'completed' AND p.is_refund = 0 THEN p.amount ELSE 0 END), 0) + 
    COALESCE(SUM(CASE WHEN p.status = 'completed' AND p.is_refund = 1 THEN p.amount ELSE 0 END), 0) as balance_due,
    CASE 
        WHEN e.payment_override_reason IS NOT NULL THEN 'Override'
        WHEN co.price <= COALESCE(SUM(CASE WHEN p.status = 'completed' AND p.is_refund = 0 THEN p.amount ELSE 0 END), 0) THEN 'Paid'
        WHEN COALESCE(SUM(CASE WHEN p.status = 'completed' AND p.is_refund = 0 THEN p.amount ELSE 0 END), 0) > 0 THEN 'Partial'
        ELSE 'Unpaid'
    END as payment_status
FROM enrollments e
JOIN students s ON e.student_id = s.id
JOIN course_offerings co ON e.course_offering_id = co.id
LEFT JOIN invoices i ON i.student_id = s.id
LEFT JOIN payments p ON p.invoice_id = i.id
WHERE e.status NOT IN ('dropped')
GROUP BY e.id, s.first_name, s.last_name, co.course_full_name, co.price, e.status, e.payment_override_reason;

-- 5. Course Offerings with Enrollment Count
CREATE OR REPLACE VIEW course_offerings_summary AS
SELECT 
    co.id,
    co.course_full_name,
    co.level,
    co.program,
    co.type,
    co.start_date,
    co.end_date,
    co.price,
    co.capacity,
    co.location,
    co.online,
    COUNT(DISTINCT CASE WHEN e.status IN ('registered', 'active') THEN e.id END) as current_enrollments,
    COUNT(DISTINCT CASE WHEN e.status = 'pending' THEN e.id END) as pending_enrollments,
    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) as completed_enrollments,
    CASE 
        WHEN co.capacity IS NOT NULL THEN co.capacity - COUNT(DISTINCT CASE WHEN e.status IN ('registered', 'active') THEN e.id END)
        ELSE NULL
    END as seats_available,
    CASE 
        WHEN CURDATE() < co.start_date THEN 'Upcoming'
        WHEN CURDATE() BETWEEN co.start_date AND co.end_date THEN 'In Progress'
        WHEN CURDATE() > co.end_date THEN 'Completed'
    END as course_status
FROM course_offerings co
LEFT JOIN enrollments e ON co.id = e.course_offering_id
GROUP BY co.id, co.course_full_name, co.level, co.program, co.type, co.start_date, co.end_date, 
         co.price, co.capacity, co.location, co.online;

-- 6. Recent Activities Timeline
CREATE OR REPLACE VIEW recent_activities_timeline AS
SELECT 
    a.id,
    a.related_entity_type as entity_type,
    a.related_entity_id as entity_id,
    a.activity_type,
    a.subject,
    a.body,
    a.created_at,
    CASE 
        WHEN a.related_entity_type = 'App\\Models\\Lead' THEN 
            (SELECT CONCAT(first_name, ' ', last_name) FROM leads WHERE id = a.related_entity_id)
        WHEN a.related_entity_type = 'App\\Models\\Student' THEN 
            (SELECT CONCAT(first_name, ' ', last_name) FROM students WHERE id = a.related_entity_id)
        WHEN a.related_entity_type = 'App\\Models\\Enrollment' THEN 
            (SELECT CONCAT(s.first_name, ' ', s.last_name) FROM enrollments e JOIN students s ON e.student_id = s.id WHERE e.id = a.related_entity_id)
    END as entity_name,
    CASE 
        WHEN a.related_entity_type = 'App\\Models\\Lead' THEN 'Lead'
        WHEN a.related_entity_type = 'App\\Models\\Student' THEN 'Student'
        WHEN a.related_entity_type = 'App\\Models\\Enrollment' THEN 'Enrollment'
    END as entity_display_type
FROM activities a
ORDER BY a.created_at DESC;

-- 7. Pending Enrollments Requiring Action
CREATE OR REPLACE VIEW pending_enrollments_action_required AS
SELECT 
    e.id as enrollment_id,
    e.enrolled_at,
    DATEDIFF(CURDATE(), e.enrolled_at) as days_pending,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.email,
    s.phone,
    co.course_full_name,
    co.start_date,
    co.price,
    CASE 
        WHEN DATEDIFF(CURDATE(), e.enrolled_at) > 7 THEN 'Urgent - Over 7 days'
        WHEN DATEDIFF(CURDATE(), e.enrolled_at) > 3 THEN 'Follow up needed'
        ELSE 'Recent'
    END as priority,
    EXISTS(
        SELECT 1 FROM invoices i 
        JOIN payments p ON p.invoice_id = i.id 
        WHERE i.student_id = s.id AND p.status = 'completed'
    ) as has_payment
FROM enrollments e
JOIN students s ON e.student_id = s.id
JOIN course_offerings co ON e.course_offering_id = co.id
WHERE e.status = 'pending'
ORDER BY days_pending DESC;

-- 8. Marketing Source Performance
CREATE OR REPLACE VIEW marketing_source_performance AS
SELECT 
    l.reference as lead_source,
    l.source_detail as marketing_channel,
    COUNT(DISTINCT l.id) as total_leads,
    COUNT(DISTINCT s.id) as converted_students,
    ROUND(COUNT(DISTINCT s.id) * 100.0 / COUNT(DISTINCT l.id), 2) as conversion_rate,
    COUNT(DISTINCT e.id) as total_enrollments,
    COALESCE(SUM(co.price), 0) as total_revenue,
    ROUND(COALESCE(SUM(co.price), 0) / COUNT(DISTINCT l.id), 2) as revenue_per_lead
FROM leads l
LEFT JOIN students s ON l.id = s.lead_id
LEFT JOIN enrollments e ON s.id = e.student_id AND e.status NOT IN ('dropped')
LEFT JOIN course_offerings co ON e.course_offering_id = co.id
WHERE l.reference IS NOT NULL
GROUP BY l.reference, l.source_detail
ORDER BY total_revenue DESC;

-- 9. Student Course History
CREATE OR REPLACE VIEW student_course_history AS
SELECT 
    s.id as student_id,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.email,
    s.current_level,
    e.id as enrollment_id,
    e.status as enrollment_status,
    e.enrolled_at,
    e.dropped_at,
    co.course_full_name,
    co.level as course_level,
    co.start_date,
    co.end_date,
    co.price,
    e.mid_course_level,
    e.mid_course_notes
FROM students s
JOIN enrollments e ON s.id = e.student_id
JOIN course_offerings co ON e.course_offering_id = co.id
ORDER BY s.id, e.enrolled_at DESC;

-- 10. Upcoming Courses Needing Attention
CREATE OR REPLACE VIEW upcoming_courses_attention AS
SELECT 
    co.id as course_offering_id,
    co.course_full_name,
    co.start_date,
    co.end_date,
    DATEDIFF(co.start_date, CURDATE()) as days_until_start,
    COUNT(DISTINCT CASE WHEN e.status = 'registered' THEN e.id END) as registered_count,
    COUNT(DISTINCT CASE WHEN e.status = 'pending' THEN e.id END) as pending_count,
    co.capacity,
    CASE 
        WHEN co.capacity IS NOT NULL THEN 
            co.capacity - COUNT(DISTINCT CASE WHEN e.status = 'registered' THEN e.id END)
        ELSE NULL
    END as seats_remaining,
    CASE 
        WHEN DATEDIFF(co.start_date, CURDATE()) <= 7 AND COUNT(DISTINCT CASE WHEN e.status = 'pending' THEN e.id END) > 0 
            THEN 'Urgent - Pending enrollments need activation'
        WHEN DATEDIFF(co.start_date, CURDATE()) <= 3 
            THEN 'Starting soon'
        WHEN co.capacity IS NOT NULL AND co.capacity - COUNT(DISTINCT CASE WHEN e.status = 'registered' THEN e.id END) <= 2 
            THEN 'Almost full'
        ELSE 'Normal'
    END as attention_flag
FROM course_offerings co
LEFT JOIN enrollments e ON co.id = e.course_offering_id
WHERE co.start_date >= CURDATE()
GROUP BY co.id, co.course_full_name, co.start_date, co.end_date, co.capacity
HAVING days_until_start <= 30
ORDER BY days_until_start ASC;
