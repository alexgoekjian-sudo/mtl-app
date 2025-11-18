-- Students and Leads Import
-- Generated: 2025-11-18T16:28:38.432938
-- Source: C:\Users\alex\MTL_App\specs\001-title-english-language\imports\out\trello_normalized.json

-- Note: Run this AFTER importing courses

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Solis', 'Jorge', 'sanchezloor98@gmail.com', '+34635950959', 'Spain', NULL,
    'A1-', 'A1-', NULL, 'Level Orque (her boyfriend): A1-', NOW(), NOW()
);

-- Create enrollment for student (email: sanchezloor98@gmail.com) to course (attendance_id: A1 BEGINNER - EDMONTON - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'sanchezloor98@gmail.com'
  AND co.attendance_id = 'A1 BEGINNER - EDMONTON - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Nami', 'Tsuji', 'nami.733.sun@gmail.com', '+31685182077', 'Japan', 'Amsterdam',
    'A1-', 'A1-', '["Japanese"]', '#', NOW(), NOW()
);

-- Create enrollment for student (email: nami.733.sun@gmail.com) to course (attendance_id: A1 BEGINNER - EDMONTON - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'nami.733.sun@gmail.com'
  AND co.attendance_id = 'A1 BEGINNER - EDMONTON - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Coco', 'Li', 'beibeizhou88@gmail.com', '+31622380866', 'China', 'Haarlem',
    'A2 PreInt-', 'A2 PreInt-', '["Chinese"]', 'Completed:

1. A2 PRE-INT EVE ONLINE - EDMONTON - 21.10.2025 - R7 ( John & Tessa ) ? A2+ Pre-Int-', NOW(), NOW()
);

-- Create enrollment for student (email: beibeizhou88@gmail.com) to course (attendance_id: A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'beibeizhou88@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Selcan', 'Senol', 'selcansenoll@gmail.com', '+905346990292', 'Turkey', NULL,
    'A2-', 'A2-', '["Turkish"]', '5 x PL online - Deborah

Completed:

1. A2 PRE-INT EVE ONLINE - EDMONTON - 21.10.2025 - R7 ( John & Tessa ) ? A2+ Pre-Int-', NOW(), NOW()
);

-- Create enrollment for student (email: selcansenoll@gmail.com) to course (attendance_id: A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'selcansenoll@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Gül?en', 'Cebeci Ballim', 'gulsencbc@gmail.com', '+31625393780', 'Turkey', NULL,
    'A2 PreInt', 'A2 PreInt', '["Turkish"]', '**Completed Courses**

1. A1 Beginners 17.01.24 - DUBLIN - Deborah - New Level ?A1+
2. A2 Morning (ELEM) 08.04.24 - EDMONTON - Deborah - New Level -->A2-
3. A2 Morning (ELEM) 21.08.24 - EDMONTON - Magui/Deborah/Harbani - New Level ?A2
4. A2 Morning (ELEM) 30.09.24 - FIFE - Deborah - New Level ?A2 PreInt-
5. A2 Morning (PRE-INT) 12.11.24 - DUBLIN - Anastasia - New Level ?A2 PreInt', NOW(), NOW()
);

-- Create enrollment for student (email: gulsencbc@gmail.com) to course (attendance_id: A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'gulsencbc@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Eva', 'Rispoli', 'evarispolie@outlook.fr', '+33659527379', NULL, 'France',
    'A2 PreInt', 'A2 PreInt', '["French"]', '**Completed courses**

1. A2 Int 28.07.25 -  CAPE TOWN - Kathleen/Magui - New Level ?A2 PreInt+', NOW(), NOW()
);

-- Create enrollment for student (email: evarispolie@outlook.fr) to course (attendance_id: A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'evarispolie@outlook.fr'
  AND co.attendance_id = 'A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Mustafa', 'Kimyon', 'mustafakimyon@gmail.com', '+905056103112', 'Turkey', NULL,
    'A2 PreInt', 'A2 PreInt', '["Turkish"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: mustafakimyon@gmail.com) to course (attendance_id: A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'mustafakimyon@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Shelty', 'Arguello', 'shelty.2508@gmail.com', '+31681954490', NULL, NULL,
    'A2 PreInt', 'A2 PreInt', '["Spanish"]', '**Completed courses**

1. A2 Morning (PRE-INT) 12.11.24 - DUBLIN - Anastasia - New Level ?A2 PreInt+', NOW(), NOW()
);

-- Create enrollment for student (email: shelty.2508@gmail.com) to course (attendance_id: A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'shelty.2508@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Dilek', 'Beydilli', 'beydillidilek@gmail.com', '+905355175750', NULL, 'Turkey',
    'A2', 'A2', '["Turkish"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: beydillidilek@gmail.com) to course (attendance_id: A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'beydillidilek@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Yuri', 'Bentes', 'yurivladmir3@gmail.com', '+351913091359', 'Brazil', NULL,
    'A2', 'A2', '["Portuguese"]', '1. A2 ELEM - ED - 29.09.2025 - DEB ? A2', NOW(), NOW()
);

-- Create enrollment for student (email: yurivladmir3@gmail.com) to course (attendance_id: A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'yurivladmir3@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Miguel', 'Rubio', 'miguel.rubio01@outlook.com', '+34664362635', 'Mexico', NULL,
    'A2 PreInt', 'A2 PreInt', '["Spanish"]', '1. A2 Int 28.07.25 -  CAPE TOWN - Kathleen/Magui - New Level ?A2 PreInt+
2. A2 INTENSIVE - AUCKLAND - 01.09.2025 - R9 - A2+ Pre-Int - Deborah, Anastasia', NOW(), NOW()
);

-- Create enrollment for student (email: miguel.rubio01@outlook.com) to course (attendance_id: A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'miguel.rubio01@outlook.com'
  AND co.attendance_id = 'A2 PRE-INT EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Heba', 'Alessa', 'alissa_heba@yahoo.com', '+31687176639', 'Syria', NULL,
    'A2 PreInt+', 'A2 PreInt+', '["Arabic"]', '**Completed courses**

1. A2 PRE-INT MORNING 01.07.25 - FIFE - Anastasia - New Level ?A2+ PreInt
2. A2 PRE-INT EVE ONLINE - DUBLIN - 26.08.2025 - R6 - A2+ Pre-Int+
3. A2 PRE-INT EVE ONLINE - EDMONTON - 21.10.2025 - R7 ( John & Tessa ) ? B1-', NOW(), NOW()
);

-- Create enrollment for student (email: alissa_heba@yahoo.com) to course (attendance_id: B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'alissa_heba@yahoo.com'
  AND co.attendance_id = 'B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Noriko', 'Tsuruda', 'nekomamireninaritai877@gmail.com', '+31645508590', 'Japan', NULL,
    'A2 PreInt+', 'A2 PreInt+', '["Japanese"]', '**Completed courses**

1. A2+ INT 08.04.24 - BOSTON - Deborah/Anastasia - New Level -->A2 PreInt-
2. A2 (PRE-INT) EVE 22.04.25 - DUBLIN - Dale - New Level ?A2 PreInt
3. A2 Pre-Int Online - Edmonton - 17.06.2025 - R4 - John/Carol - A2 PRE-INT+', NOW(), NOW()
);

-- Create enrollment for student (email: nekomamireninaritai877@gmail.com) to course (attendance_id: B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'nekomamireninaritai877@gmail.com'
  AND co.attendance_id = 'B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Alice', 'Olivier', 'alice.olivier.doc@gmail.com', '+33682285355', 'France', 'Amsterdam',
    'B1', 'B1', '["French"]', '**Completed courses**

1. A2 EVE 28.02.23 - EDMONTON - Jackson - New Level -->A2+
2. A2 Morning (ELEM) 03.07.23 - EDMONTON - Deborah - New Level -->A2 PreInt
3. A2+ Morning (PRE-INT) 15.08.23 - FIFE - Anastasia - New Level ?A2 PreInt+
4. A2 EVE (PRE-INT) 16.01.24 - FIFE - Clare/Tessa - New Level -->B1-
5. B1 EVE 08.04.24 - EDMONTON - Clare - New Level -->B1
6. B1 EVE ONLINE - DUBLIN 18.08.205 - B1', NOW(), NOW()
);

-- Create enrollment for student (email: alice.olivier.doc@gmail.com) to course (attendance_id: B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'alice.olivier.doc@gmail.com'
  AND co.attendance_id = 'B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Daniel', 'Lopez Gomez', 'lopezgomezd@hotmail.com', '+34699675109', NULL, 'Spain',
    'B1', 'B1', '["Spanish"]', '1. B1 Eve ONLINE - EDMONTON 02.06.25 - Tessa - New Level ?B1', NOW(), NOW()
);

-- Create enrollment for student (email: lopezgomezd@hotmail.com) to course (attendance_id: B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'lopezgomezd@hotmail.com'
  AND co.attendance_id = 'B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Hajar', 'Khalil', 'hajark054@gmail.com', '+31628105923', 'Netherlands', 'Amsterdam',
    'B1-', 'B1-', '["French", "Arabic", "Dutch"]', '1. A2 PRE-INT EVE ONLINE - DUBLIN - 26.08.2025 - R6 - B1-', NOW(), NOW()
);

-- Create enrollment for student (email: hajark054@gmail.com) to course (attendance_id: B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'hajark054@gmail.com'
  AND co.attendance_id = 'B1 EVE ONLINE - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Yury', 'Gomes de Sousa', 'brasil@inkblu.net', '+5585994449204', NULL, NULL,
    'A2 PreInt+', 'A2 PreInt+', '["Portugueses"]', '**Completed courses**

1. A2+ INT 07.04.25 - BOSTON - Deborah/Anastasia - New Level ?A2 PreInt+', NOW(), NOW()
);

-- Create enrollment for student (email: brasil@inkblu.net) to course (attendance_id: A2 INTENSIVE - BOSTON - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'brasil@inkblu.net'
  AND co.attendance_id = 'A2 INTENSIVE - BOSTON - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Yuria', 'Shimoda', 'flytonld@gmail.com', '+61449703315', 'Japan', NULL,
    'A2 PreInt+', 'A2 PreInt+', '["Japanese"]', '[youliyaxiatian@gmail.com](mailto:youliyaxiatian@gmail.com "?")', NOW(), NOW()
);

-- Create enrollment for student (email: flytonld@gmail.com) to course (attendance_id: A2 INTENSIVE - BOSTON - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'flytonld@gmail.com'
  AND co.attendance_id = 'A2 INTENSIVE - BOSTON - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Jayane', 'Nascimento', 'jayanenascimento@hotmail.com', '+5521981786171', 'Brazil', 'Rio',
    'B1', 'B1', '["Portuguese"]', '1. B1 INTENSIVE - BOSTON - 29.09.25 - Magui/Kathleen ? B1-', NOW(), NOW()
);

-- Create enrollment for student (email: jayanenascimento@hotmail.com) to course (attendance_id: B1 INTENSIVE - BOSTON - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'jayanenascimento@hotmail.com'
  AND co.attendance_id = 'B1 INTENSIVE - BOSTON - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Rinka', 'Sekino', 'rinkasekino@gmail.com', '+310612825017', 'Japan', 'Amsterdam',
    'B1-', 'B1-', '["Japanese"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: rinkasekino@gmail.com) to course (attendance_id: B1 INTENSIVE - BOSTON - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'rinkasekino@gmail.com'
  AND co.attendance_id = 'B1 INTENSIVE - BOSTON - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Sayuna', 'Tamura', 'flytonld@gmail.com', '+819039692489', 'Japan', NULL,
    'B1+', 'B1+', '["Japanese"]', '# student email: [sayuna.18129.ballet@gmail.com](mailto:sayuna.18129.ballet@gmail.com "?")', NOW(), NOW()
);

-- Create enrollment for student (email: flytonld@gmail.com) to course (attendance_id: B2 INTENSIVE - BOSTON - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'flytonld@gmail.com'
  AND co.attendance_id = 'B2 INTENSIVE - BOSTON - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Cansu', 'Argun', 'cansukosucu@gmail.com', '+31649526826', NULL, 'Turkey',
    'B2', 'B2', '["Turkish"]', '**Completed courses**

1. B2 Int 30.06.25 - BOSTON - Benjamin & Anastasia - New Level ?B2', NOW(), NOW()
);

-- Create enrollment for student (email: cansukosucu@gmail.com) to course (attendance_id: B2 INTENSIVE - BOSTON - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'cansukosucu@gmail.com'
  AND co.attendance_id = 'B2 INTENSIVE - BOSTON - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Karina', 'Panduwinata', 'karinapanduwinata30@gmail.com', '+31629959706', NULL, 'Indonesian',
    'B2-', 'B2-', '["Indonesian"]', '**Completed courses**

1. B2 Int 28.07.25 -  CAPE TOWN - Deborah/Anastasia- New Level ?B2+', NOW(), NOW()
);

-- Create enrollment for student (email: karinapanduwinata30@gmail.com) to course (attendance_id: B2 INTENSIVE - BOSTON - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'karinapanduwinata30@gmail.com'
  AND co.attendance_id = 'B2 INTENSIVE - BOSTON - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Bianca', 'Candelorio', 'bianca.candelorio@gmail.com', '+31616893914', 'Brasil', 'Hoofddorp',
    'A2-', 'A2-', '["Portuguese", "Spanish"]', '1. A2 ELEM MORNING - DUBLIN - 18.08.25 - R6 - Deborah - New Level ?A2-', NOW(), NOW()
);

-- Create enrollment for student (email: bianca.candelorio@gmail.com) to course (attendance_id: A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'bianca.candelorio@gmail.com'
  AND co.attendance_id = 'A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'SEYNABOU', 'SARR', 'biramemonamour@gmail.com', '+34633058730', 'Senegal', 'Amsterdam',
    'A2 PreInt', 'A2 PreInt', '["French"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: biramemonamour@gmail.com) to course (attendance_id: A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'biramemonamour@gmail.com'
  AND co.attendance_id = 'A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Elif', 'Buluttekin', 'elifolekli@gmail.com', '+31625656713', 'Turkey', NULL,
    'A2-', 'A2-', '["Turkish"]', '#', NOW(), NOW()
);

-- Create enrollment for student (email: elifolekli@gmail.com) to course (attendance_id: A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'elifolekli@gmail.com'
  AND co.attendance_id = 'A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Yana', 'Sorokina', 'sorokinajana@gmail.com', '+31687671086', 'Ukraine', 'Amsterdam',
    'A1+', 'A1+', '["Ukrainian / Russian"]', '1. A2 ELEM - ED - 29.09.2025 - DEB ? A2+', NOW(), NOW()
);

-- Create enrollment for student (email: sorokinajana@gmail.com) to course (attendance_id: A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'sorokinajana@gmail.com'
  AND co.attendance_id = 'A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Noriyuki', 'Hattai', 'monstykeaki@gmail.com', '+31684759572', 'Japan', 'Amsterdam',
    'A1+', 'A1+', '["Japanese"]', 'SAME EMAIL AS WIFE (Akiko Hattai)

1. A2 ELEM - ED - 29.09.2025 - DEB ? A2-', NOW(), NOW()
);

-- Create enrollment for student (email: monstykeaki@gmail.com) to course (attendance_id: A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'monstykeaki@gmail.com'
  AND co.attendance_id = 'A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Akiko', 'Hattai', 'monstykeaki@gmail.com', '+31684759572', 'Japan', 'Amsterdam',
    'A1+', 'A1+', '["Japanese"]', '1. A2 ELEM - ED - 29.09.2025 - DEB ? A2-', NOW(), NOW()
);

-- Create enrollment for student (email: monstykeaki@gmail.com) to course (attendance_id: A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'monstykeaki@gmail.com'
  AND co.attendance_id = 'A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Ana', 'Romero Ruiz', 'arom031646@gmail.com', '+31618343226', 'Venezuela', 'Amsterdam',
    'A1+', 'A1+', '["Spanish"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: arom031646@gmail.com) to course (attendance_id: A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'arom031646@gmail.com'
  AND co.attendance_id = 'A2 ELEM MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Jesus', 'Diaz', 'jesusnj2007@gmail.com', '+31627610422', NULL, 'Venezuela',
    'B1-', 'B1-', '["Spanish"]', 'Correct email address: [jesusnj2007@gmail.com](mailto:jesusnj2007@gmail.com "?")

?

**Completed Courses**

1. B1 INT 12.02.24 - CAPE TOWN - Benjamin/Kathleen/James - New Level ?B1
2. B1 INT 29.07.24 - CAPE TOWN - Benjamin/Anastasia - New Level ?B1
3. B1 INT 02.09.24 - AUCKLAND - Kathleen/James - New Level ?B1+
4. B2 Int AUCKLAND 02.06.25 - New Level ?B2-
5. B2 EVE - FIFE - 30.06.25 - New Level ?B2', NOW(), NOW()
);

-- Create enrollment for student (email: jesusnj2007@gmail.com) to course (attendance_id: B2 EVE ONLINE - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'jesusnj2007@gmail.com'
  AND co.attendance_id = 'B2 EVE ONLINE - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Ana', 'Arroyo', 'abarroyoc@gmail.com', '+34676154792', 'Spain', 'Amsterdam',
    'B2-', 'B2-', '["Spanish"]', '1. B2 Eve - ED - 06.10.2025 - Dale - B2', NOW(), NOW()
);

-- Create enrollment for student (email: abarroyoc@gmail.com) to course (attendance_id: B2 EVE ONLINE - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'abarroyoc@gmail.com'
  AND co.attendance_id = 'B2 EVE ONLINE - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Yana', 'Stafichuk', 'sedunkova@gmail.com', '+31616745005', NULL, NULL,
    NULL, NULL, NULL, 'Completed courses:

1. B2 Morning - Ed - 06.10.25 - Kathleen ? B1+', NOW(), NOW()
);

-- Create enrollment for student (email: sedunkova@gmail.com) to course (attendance_id: B2 EVE ONLINE - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'sedunkova@gmail.com'
  AND co.attendance_id = 'B2 EVE ONLINE - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Ricardo', 'Santos', 'ricardo.f.f.santos99@gmail.com', '+351919797366', 'Portugual', NULL,
    'C1-', 'C1-', '["Portuguese"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: ricardo.f.f.santos99@gmail.com) to course (attendance_id: C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'ricardo.f.f.santos99@gmail.com'
  AND co.attendance_id = 'C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Beatriz', 'Raposo', 'beatrizraposo30@gmail.com', '+33749287624', NULL, 'Portugal',
    'C1', 'C1', '["Portugueses"]', '**Completed courses**

1. C1 EVE 15.07.25 - FIFE - Tessa - New Level ?C1+', NOW(), NOW()
);

-- Create enrollment for student (email: beatrizraposo30@gmail.com) to course (attendance_id: C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'beatrizraposo30@gmail.com'
  AND co.attendance_id = 'C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Andrea', 'Villarroel', 'viloandrina@gmail.com', '+31641211163', 'Venezuela', NULL,
    'C1', 'C1', '["Spanish"]', '**Completed courses**

1. B2 EVE 13.01.25 - EDMONTON - Dale - New Level ?B2-
2. B2 EVE 24.02.25 - FIFE - Dale/Claire-Marie - New Level ?B2
3. B2 EVE 07.04.25 - DUBLIN - Dale - New Level ?B2+
4. B2 EVE 19.05.25 - EDMONTON - Dale - New Level ?B2+
5. C1 EVE 15.07.25 - FIFE - Tessa - New Level ?C1-
6. C1 EVE ONLINE - DUBLIN - 26.08.2025 - R6 - C1 - Dale
7. C1 EVE - ED - 07.10.25 - DALE ? C1', NOW(), NOW()
);

-- Create enrollment for student (email: viloandrina@gmail.com) to course (attendance_id: C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'viloandrina@gmail.com'
  AND co.attendance_id = 'C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Sungwon', 'Yoon', 'celineyoon93@gmail.com', '+31627371375', NULL, 'South Korea',
    'C1', 'C1', '["Korean"]', '1. C1 Morning - EDMONTON 20.05.25 - Level Check: C1-
2. C1 Morn - FIFE - 01.07.25 - Magui/Kathleen - New level-  C1', NOW(), NOW()
);

-- Create enrollment for student (email: celineyoon93@gmail.com) to course (attendance_id: C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'celineyoon93@gmail.com'
  AND co.attendance_id = 'C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Saeid', 'Sami', 'samisaeid1990@gmail.com', '+31686117876', 'Iran', NULL,
    'B2+', 'B2+', '["Persian"]', '**Completed Courses**

1. B1 Morning 17.01.24 - FIFE - Anastasia M./Harbani - New Level ?B1+
2. B2 Morning 26.02.24 - DUBLIN - James/Nicole - New Level ?B2-
3. B2 INT 08.04.24 - BOSTON - Kathleen/James/Ashley - New Level -->B2
4. B2 INT 29.07.24 - CAPE TOWN - Deborah/Anastasia - New Level ?B2+
5. C1 EVE ONLINE - DUBLIN - 26.08.2025 - R6 - B2+ - Dale', NOW(), NOW()
);

-- Create enrollment for student (email: samisaeid1990@gmail.com) to course (attendance_id: C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'samisaeid1990@gmail.com'
  AND co.attendance_id = 'C1 EVE ONLINE - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Laiana', 'Ferrari', 'laianaferraripp@gmail.com', '+31621624734', 'Brazil', NULL,
    'A2 PreInt+', 'A2 PreInt+', '["Portuguese"]', '**Completed courses**

1. A2 Morning (ELEM) 29.05.24 - FIFE - Magui/Harbani - New Level ? A2
2. A2 Morning (ELEM) 08.07.24 - DUBLIN - Deborah - New Level ?A2 PreInt-
3. A2 Morning (ELEM) 30.09.24 - FIFE - Deborah - New Level ?A2 PreInt-
4. A2 Morning (PRE-INT) 12.11.24 - DUBLIN - Anastasia - New Level ?A2 PreInt
5. A2 Morning (PRE-INT) 25.02.24 - FIFE - Anastasia - New Level ? A2 PreInt
6. A2 PRE-INT MORNING 01.07.25 - FIFE - Anastasia - New Level ?A2+ PreInt+
7. A2 PRE-INT MORNING - DUBLIN - 19.08.2025 - R6 - A2+ Pre-Int+ - Anastasia', NOW(), NOW()
);

-- Create enrollment for student (email: laianaferraripp@gmail.com) to course (attendance_id: A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'laianaferraripp@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Eleonora', 'Tuci', 'eletuci@hotmail.it', '+31611906862', 'Italy', NULL,
    'A2 PreInt', 'A2 PreInt', '["Italian"]', '1. A2 Pre-Int Morn - ED - 30.09.2025 - Anastasia ? A2+ Pre-Int', NOW(), NOW()
);

-- Create enrollment for student (email: eletuci@hotmail.it) to course (attendance_id: A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'eletuci@hotmail.it'
  AND co.attendance_id = 'A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Ayca', 'Sertkaya', 'aycasertkaya9@gmail.com', '+31627320802', 'Turkey', 'Amsterdam',
    'A2 PreInt', 'A2 PreInt', '["Turkish"]', '1. A2 Pre-Int Morn - ED - 30.09.2025 - Anastasia ? A2+ Pre-Int', NOW(), NOW()
);

-- Create enrollment for student (email: aycasertkaya9@gmail.com) to course (attendance_id: A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'aycasertkaya9@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Maria Eduarda', 'Meregali', 'melmeregali@gmail.com', '+5551999338630', 'Brazil', 'Rio de Janeiro',
    'A2 PreInt-', 'A2 PreInt-', '["Portuguese", "English"]', '#', NOW(), NOW()
);

-- Create enrollment for student (email: melmeregali@gmail.com) to course (attendance_id: A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'melmeregali@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Selenay', 'Ayten', 'xxselenayxx@gmail.com', '+31618884358', 'Netherlands', 'Amsterdam',
    'A2 PreInt+', 'A2 PreInt+', '["Dutch"]', '1. A2 Pre-Int Morn - ED - 30.09.2025 - Anastasia ? A2+ Pre-Int', NOW(), NOW()
);

-- Create enrollment for student (email: xxselenayxx@gmail.com) to course (attendance_id: A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'xxselenayxx@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Tatiana', 'Papacidero', 'tati_menezes87@yahoo.com.br', '+31638892377', 'Brazil', 'Hoofdorp',
    'A2 PreInt+', 'A2 PreInt+', '["Portuguese"]', '#', NOW(), NOW()
);

-- Create enrollment for student (email: tati_menezes87@yahoo.com.br) to course (attendance_id: A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'tati_menezes87@yahoo.com.br'
  AND co.attendance_id = 'A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Gamze', 'Babiloglu', 'gmzszgtrmz@gmail.com', '+31647417675', 'Turkey', 'Amsterdam',
    'A2 PreInt-', 'A2 PreInt-', '["Turkish"]', '1. A2 ELEM MORNING 18.08.25 - DUBLIN - Deborah - New Level ?A2+ Pre-Int-
2. A2 Pre-Int Morn - ED - 30.09.2025 - Anastasia ? A2+ Pre-Int', NOW(), NOW()
);

-- Create enrollment for student (email: gmzszgtrmz@gmail.com) to course (attendance_id: A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'gmzszgtrmz@gmail.com'
  AND co.attendance_id = 'A2 PRE-INT MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Yalin', 'Zeren', 'yalinzerenn@gmail.com', '+905367949225', 'Turkey', 'Bodrum',
    'B1', 'B1', '["Turkish", "English"]', '**email mum:** banuaktoz@icloud.com', NOW(), NOW()
);

-- Create enrollment for student (email: yalinzerenn@gmail.com) to course (attendance_id: B1 MORNING - DUBLIN - 14.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'yalinzerenn@gmail.com'
  AND co.attendance_id = 'B1 MORNING - DUBLIN - 14.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Neda', 'Noormohammadi', 'neda.mohammadi88@gmail.com', '+31628191934', 'Iran', NULL,
    'B1-', 'B1-', '["Persian"]', '**Completed courses**

1. A2 PRE-INT MORNING 01.07.25 - FIFE - Anastasia - New Level ?A2+ PreInt+
2. A2 PRE-INT MORNING - DUBLIN - 19.08.2025 - R6 - B1- - Anastasia
3. B1 Morning - Ed - 08.10.25 - Magui/Deb ? B1', NOW(), NOW()
);

-- Create enrollment for student (email: neda.mohammadi88@gmail.com) to course (attendance_id: B1 MORNING - DUBLIN - 14.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'neda.mohammadi88@gmail.com'
  AND co.attendance_id = 'B1 MORNING - DUBLIN - 14.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Daiane', 'Gomes', 'dai.md@hotmail.com', '+351914962892', NULL, 'Brazil',
    'B1-', 'B1-', '["Portuguese"]', '**Completed courses**

1. A2 Morning (PRE-INT) 08.04.25 - DUBLIN - Anastasia - New Level ?A2 PreInt
2. A2+ (PRE-INT) Morning - EDMONTON 20.05.25 - New Level ?B1- (Anastasia)
3. B1 Morning - FIFE - 16.07.25 - New level B1', NOW(), NOW()
);

-- Create enrollment for student (email: dai.md@hotmail.com) to course (attendance_id: B1 MORNING - DUBLIN - 14.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'dai.md@hotmail.com'
  AND co.attendance_id = 'B1 MORNING - DUBLIN - 14.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Silvia', 'De Leo', 'silvia.deleo57@gmail.com', '+393402926527', 'Italy', 'Amsterdam',
    NULL, NULL, '["Italian"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: silvia.deleo57@gmail.com) to course (attendance_id: B1 MORNING - DUBLIN - 14.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'silvia.deleo57@gmail.com'
  AND co.attendance_id = 'B1 MORNING - DUBLIN - 14.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Kate', 'Kim', 'kate.kimhj80@gmail.com', '+31620517889', 'South Korea', 'Ijburg',
    'B1+', 'B1+', '["Korean / Chinese"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: kate.kimhj80@gmail.com) to course (attendance_id: B1 MORNING - DUBLIN - 14.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'kate.kimhj80@gmail.com'
  AND co.attendance_id = 'B1 MORNING - DUBLIN - 14.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Hashem', 'Jreikh', 'mr.hashem13@gmail.com', '+31636296339', 'Syria', 'Arnhem',
    'B1+', 'B1+', '["Syrian"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: mr.hashem13@gmail.com) to course (attendance_id: B1 MORNING - DUBLIN - 14.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'mr.hashem13@gmail.com'
  AND co.attendance_id = 'B1 MORNING - DUBLIN - 14.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'ANDRESSA', 'LIMPIAS', 'andressa.limpias@gmail.com', '+5511974380035', 'Brazil', NULL,
    'B2+', 'B2+', '["Portuguese"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: andressa.limpias@gmail.com) to course (attendance_id: B2 MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'andressa.limpias@gmail.com'
  AND co.attendance_id = 'B2 MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Iskender', 'Erbay', 'iskenderbay@gmail.com', '+31614651844', 'Turkey', NULL,
    'B2', 'B2', '["Turkish"]', '**Completed courses**

1. B1 Int 06.05.25 - CAPE TOWN - Anastasia/Deborah - New Level ? B1+
2. B2 Morning - EDMONTON 02.06.25 - Kathleen - New Level ? B2', NOW(), NOW()
);

-- Create enrollment for student (email: iskenderbay@gmail.com) to course (attendance_id: B2 MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'iskenderbay@gmail.com'
  AND co.attendance_id = 'B2 MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Maria', 'Bellido', 'mariabellidomas105@gmail.com', '+34601196841', 'Spain', 'Amsterdan',
    'B2', 'B2', '["Spanish"]', 'Completed Course:

1. B2 MORNING - DUBLIN - 25.08.2025 - R6 - B2 - Kathleen', NOW(), NOW()
);

-- Create enrollment for student (email: mariabellidomas105@gmail.com) to course (attendance_id: B2 MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'mariabellidomas105@gmail.com'
  AND co.attendance_id = 'B2 MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Manon', 'Ruardij', 'jacobamanon@hotmail.com', '+31646380001', 'NL', 'AMS',
    'B2', 'B2', '["Dutch"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: jacobamanon@hotmail.com) to course (attendance_id: B2 MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'jacobamanon@hotmail.com'
  AND co.attendance_id = 'B2 MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Tessa', 'Florance', 'florancetessa77@gmail.com', '+33768870643', 'France', 'Amsterdam (to be)',
    'B2-', 'B2-', '["French"]', 'Completed courses:

1. B2 Morning - Ed - 06.10.25 - Kathleen ? B2', NOW(), NOW()
);

-- Create enrollment for student (email: florancetessa77@gmail.com) to course (attendance_id: B2 MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'florancetessa77@gmail.com'
  AND co.attendance_id = 'B2 MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Zakaria', 'Bel Madani', 'belmadani61@gmail.com', '+212649646678', 'Morocco', 'Diemen',
    'B1+', 'B1+', '["French / Arabic"]', '**COMPLETED COURSES:**

1. B1 MORNING - FIFE - 16.07.2025 - B1
2. B1 MORNING - DUBLIN - 27.08.2025 - R6 - B1 - Deborah, Magui
3. B1 Morning - Ed - 08.10.25 - Magui/Deb ? B1+', NOW(), NOW()
);

-- Create enrollment for student (email: belmadani61@gmail.com) to course (attendance_id: B2 MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'belmadani61@gmail.com'
  AND co.attendance_id = 'B2 MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'BongGun', 'Cho', 'panic8846@naver.com', '+31626295512', 'Korea', NULL,
    'B2', 'B2', NULL, 'Completed courses:

1. B2 Morning - Ed - 06.10.25 - Kathleen ? B2', NOW(), NOW()
);

-- Create enrollment for student (email: panic8846@naver.com) to course (attendance_id: B2 MORNING - DUBLIN - 12.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'panic8846@naver.com'
  AND co.attendance_id = 'B2 MORNING - DUBLIN - 12.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Deniz', 'Isik', 'deniz8isik@gmail.com', '+31682081900', NULL, 'Turkey',
    'B2', 'B2', '["Turkish"]', '**Completed courses**

1. B1 INT 28.10.24 - CAPE TOWN - Deborah/Anastasia/Kathleen/Harbani - New Level ?B1+
2. B1 INT 25.11.24 - AUCKLAND - Kathleen/James - New Level ?B1+
3. B1 INT 13.01.25 - BOSTON - Kathleen/James - New Level ?B2-
4. B2 INT 10.02.25 - CAPE TOWN - Deborah/Anastasia - New Level ?B2-
5. B2 INT 10.03.25 - AUCKLAND - Deborah/Anastasia - New Level ?B2-
6. B2 INT 07.04.25 - BOSTON - Kathleen/Magui - New Level ?B2+
7. C1 Morning FIFE - 01.07.25 - Kathleen/Magui - C1-', NOW(), NOW()
);

-- Create enrollment for student (email: deniz8isik@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'deniz8isik@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Belen', 'Ibarra', 'ibarrabelen95@gmail.com', '+31627267719', NULL, 'Argentina',
    'B2', 'B2', '["Spanish"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: ibarrabelen95@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'ibarrabelen95@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Dmitrii', 'Pankratov', 'dmitriipankratov971@gmail.com', '+79312251755', 'Russia', 'Russia',
    'B2+', 'B2+', '["Russian"]', '**Completed courses**

1. B1 INT 25.11.24 - AUCKLAND - Kathleen/James - New Level ?B1
2. PL (30 hours online - Kathleen) - New Level ?B2-
3. B2 INT 10.03.25 - AUCKLAND - Deborah/Anastasia - New Level ?B2-
4. B2 INT 06.05.25 - CAPE TOWN - Magui/Kathleen - New Level ? B2
5. B2 Int 30.06.25 - BOSTON - Benjamin & Anastasia - New Level ?B2+', NOW(), NOW()
);

-- Create enrollment for student (email: dmitriipankratov971@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'dmitriipankratov971@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Katja', 'Vasilyeva', 'alewife.stat.9v@icloud.com', '+31615058822', 'Russia', 'Amsterdam',
    'C1', 'C1', '["Russian", "English", "Dutch"]', '#', NOW(), NOW()
);

-- Create enrollment for student (email: alewife.stat.9v@icloud.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'alewife.stat.9v@icloud.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Nozomi', 'Ide', 'nonono.non213@gmail.com', NULL, 'Japan', NULL,
    'B2+', 'B2+', '["Japanese"]', '**Completed courses:**

1. B2 Morn - EDMONTON 02.06.25 - Kathleen - new level - B2+', NOW(), NOW()
);

-- Create enrollment for student (email: nonono.non213@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'nonono.non213@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Tatiana', 'Sineva', 'mailvantati@gmail.com', '+31616029947', 'Russia', NULL,
    'B2', 'B2', '["Russian"]', 'Has an extra **10% discount** on the next course - **05.08.2025 (Vita) and 11.08.2025 (Inna)** = **15%** in total

?

**Completed courses**

1. B1 INT 29.07.24 - CAPE TOWN - Benjamin/James/Magui - New Level ?B1
2. B1 INT 02.09.24 - AUCKLAND - Kathleen/James - New Level ?B1+
3. B1 INT 30.09.24 - BOSTON - Deborah/Anastasia/Magui - New Level ?B2-
4. B2 Int 30.06.25 - BOSTON - Benjamin & Anastasia - New Level ?B2-
5. B2 Int 28.07.25 -  CAPE TOWN - Deborah/Anastasia- New Level ?B2
6. B2 Morning - Ed - 06.10.25 - Kathleen ? C1-', NOW(), NOW()
);

-- Create enrollment for student (email: mailvantati@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'mailvantati@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Tamir', 'Badih', 'tamirsami2004@gmail.com', '+31681826687', 'NL', 'Haarlem',
    'C1-', 'C1-', '["NL", "Arabic"]', '1. C1 Morning - EDMONTON 30.09.25 - New Level: C1+', NOW(), NOW()
);

-- Create enrollment for student (email: tamirsami2004@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'tamirsami2004@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Maria Clara', 'Moreira Matias Goncalves', 'mariacmmatias@gmail.com', '+31630795175', NULL, 'Brazil',
    'C1', 'C1', '["Portuguese", "Spanish"]', '**Completed courses**

1. A2+ INT 03.06.24 - AUCKLAND - Benjamin/James/Kathleen - New Level ? B1
2. B1 INT 01.07.24 - BOSTON - Kathleen/James/Benjamin - New Level -->B1+
3. B1 INT 29.07.24 - CAPE TOWN - Benjamin/James/Magui - New Level ?B1+
4. B1 INT 02.09.24 - AUCKLAND - Kathleen/James - New Level ?B2-
5. B2 INT 30.09.24 - BOSTON - Kathleen/Deborah/Anastasia/Magui - New Level ?B2
6. B2 INT 25.11.24 - AUCKLAND - Deborah/Anastasia - New Level ?B2+
7. B2 INT 13.01.25 - BOSTON - Magui/Anastasia/James - New Level ?B2+
8. B2 INT 10.02.25 - CAPE TOWN - Benjamin/Anastasia - New Level ?C1-
9. C1 Morning 08.04.25 - DUBLIN - Kathleen/Magui - New Level ?C1-
10. C1 Morning - EDMONTON 20.05.25 **-** New Level ?C1-
11. C1 MORNING - DUBLIN - 19.08.25 - C1', NOW(), NOW()
);

-- Create enrollment for student (email: mariacmmatias@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'mariacmmatias@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Heloisa', 'Adam', 'hhelo.adam@gmail.com', '+31613702293', 'Brazil', 'Amsterdam',
    'C1-', 'C1-', '["Portuguese"]', '1. C1 MORNING - DUBLIN 19.08.25 - Kathleen/Magui > C1', NOW(), NOW()
);

-- Create enrollment for student (email: hhelo.adam@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'hhelo.adam@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Alisa', 'Sinelnikova', 'lorianbathory@gmail.com', '+31619198520', 'Russia', 'Russia',
    'B2+', 'B2+', '["Russian"]', '**Completed courses**

1. B1 INT 29.07.24 - CAPE TOWN - Benjamin/James/Magui - New Level ?B1
2. B2 INT 28.10.24 - CAPE TOWN - Deborah/Anastasia/Harbani - New Level ?B2-
3. B2 INT 13.01.25 - BOSTON - Magui/Anastasia/James - New Level ?B2
4. B2 Morn 14.07.2025 - FIFE - B2+
5. B2 MORNING - DUBLIN - 25.08.2025 - R6 - B2+ - Kathleen', NOW(), NOW()
);

-- Create enrollment for student (email: lorianbathory@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'lorianbathory@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Olga', 'Mokrushina', 'moqrush@gmail.com', NULL, NULL, 'Russia',
    'C1-', 'C1-', NULL, 'Her husband Yaroslav''s number: +31652648615

Yaroslav Sinitsov <[ysinitsov@gmail.com](mailto:ysinitsov@gmail.com "?")\\>

**Courses completed**

1. B2 Int 01.08.22 - CAPE TOWN -  James/Clare - New Level --> B2
2. B2 Int 21.11.22 - AUCKLAND - Deborah/Kathleen - New level --> B2+
3. B2 INT 11.04.23 - BOSTON - James/Anastasia M./Harbani/Benjamin - New Level -->B2+
4. C1 Morning 09.07.24 - DUBLIN - Magui/James - New Level ?C1-
5. C1 Morning 01.07.2025 - FIFE - Magui / Kathleen - C1', NOW(), NOW()
);

-- Create enrollment for student (email: moqrush@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'moqrush@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Blanca', 'Roth Caravaca', 'blanquiroth@gmail.com', '+34638799798', NULL, 'Spain',
    'B2+', 'B2+', '["Spanish"]', 'Completed courses:

1. B2 Morning - FIFE - 14.07.25 - C1-', NOW(), NOW()
);

-- Create enrollment for student (email: blanquiroth@gmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'blanquiroth@gmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Issam', 'Saied', 'issamsaied@hotmail.com', '+31642830443', 'Morocco', 'Amsterdam',
    'B2-', 'B2-', '["Moroccan / French / Dutch"]', '1. B1 INTENSIVE - AUCKLAND - 01.09.2025 - R9 - B2- - Kathleen, Magui
2. B2 Morning - Ed - 06.10.25 - Kathleen ? C1-', NOW(), NOW()
);

-- Create enrollment for student (email: issamsaied@hotmail.com) to course (attendance_id: C1 MORNING - DUBLIN - 13.01.2026 - R1)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'issamsaied@hotmail.com'
  AND co.attendance_id = 'C1 MORNING - DUBLIN - 13.01.2026 - R1'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Güne?', 'Güler', 'gunesgulerr@gmail.com', '+31642986844', 'Turkey', NULL,
    'B2', 'B2', '["Turkish"]', '**Completed courses**

1. A2+ INT 13.01.25 - BOSTON - Deborah/Anastasia - New Level ?B1-
2. B1 INT 10.02.25 - CAPE TOWN - Kathleen/James/Magui - New Level ?B1
3. B1 INT 07.04.25 - BOSTON - Benjamin/Anastasia - New Level ?B1
4. B1 Int 06.05.25 - CAPE TOWN - Anastasia/Deborah - New Level ? B1+
5. B2 Int 02.06.25 - AUCKLAND - New Level ?B2-
6. B2 Int 30.06.25 - BOSTON - Benjamin & Anastasia - New Level ?B2-
7. B2 INTENSIVE - AUCKLAND - 01.09.2025 - R9 - B2 - Benjamin, Anastasia', NOW(), NOW()
);

-- Create enrollment for student (email: gunesgulerr@gmail.com) to course (attendance_id: B1 INTENSIVE - CAPE TOWN - 09.02.2026 - R2)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'gunesgulerr@gmail.com'
  AND co.attendance_id = 'B1 INTENSIVE - CAPE TOWN - 09.02.2026 - R2'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Soudabeh', 'Jamshidi', 'sjamshidi099@gmail.com', '+31629748062', 'Iran', 'Amsterdam',
    'B1-', 'B1-', '["Farsi"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: sjamshidi099@gmail.com) to course (attendance_id: B1 INTENSIVE - CAPE TOWN - 09.02.2026 - R2)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'sjamshidi099@gmail.com'
  AND co.attendance_id = 'B1 INTENSIVE - CAPE TOWN - 09.02.2026 - R2'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Sarah', 'Malonga', 'smalongaleparquier@gmail.com', '+34645419739', 'France', 'Amsterdam',
    'B1+', 'B1+', '["French"]', '**Completed Courses:**

1. B1 INTENSIVE - AUCKLAND - 01.09.2025 - Kathleen, Magui - B1+
2. B2 INTENSIVE - BOSTON - 29.09.25 - Anastasia/Deborah ? B2-', NOW(), NOW()
);

-- Create enrollment for student (email: smalongaleparquier@gmail.com) to course (attendance_id: B1 INTENSIVE - CAPE TOWN - 09.02.2026 - R2)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'smalongaleparquier@gmail.com'
  AND co.attendance_id = 'B1 INTENSIVE - CAPE TOWN - 09.02.2026 - R2'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Katherine', 'Garcia', 'miretracto@gmail.com', '+34634607548', 'Colombia / Spain', NULL,
    'B1+', 'B1+', '["Spanish"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: miretracto@gmail.com) to course (attendance_id: B1 INTENSIVE - CAPE TOWN - 09.02.2026 - R2)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'miretracto@gmail.com'
  AND co.attendance_id = 'B1 INTENSIVE - CAPE TOWN - 09.02.2026 - R2'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Fahd', 'Morsi', 'fahdnapil75@gmail.com', '+31684459352', 'Egypt', 'Amsterdam',
    'B1-', 'B1-', '["Arabic/ Dutch"]', '1. B1 INTENSIVE - BOSTON - 29.09.25 - Magui/Kathleen ? B1+', NOW(), NOW()
);

-- Create enrollment for student (email: fahdnapil75@gmail.com) to course (attendance_id: B2 INTENSIVE - CAPE TOWN - 09.02.2026 - R2)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'fahdnapil75@gmail.com'
  AND co.attendance_id = 'B2 INTENSIVE - CAPE TOWN - 09.02.2026 - R2'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Ariadne', 'Raphaeta', 'ariadneraphaeta@gmail.com', '+31629286317', 'Brazil', 'Purmerend',
    'A1', 'A1', '["Portuguese"]', '1. A1 BEGINNER - DUBLIN - 18.08.2025 - R6 - Deborah - A1', NOW(), NOW()
);

-- Create enrollment for student (email: ariadneraphaeta@gmail.com) to course (attendance_id: A1 BEGINNER - DUBLIN - 23.02.2026 - R2)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'ariadneraphaeta@gmail.com'
  AND co.attendance_id = 'A1 BEGINNER - DUBLIN - 23.02.2026 - R2'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Michèl Marie', 'Agoua', 'agoua1986@gmail.com', '+237693141214', 'Cameroon', NULL,
    'A1-', 'A1-', '["French"]', '#', NOW(), NOW()
);

-- Create enrollment for student (email: agoua1986@gmail.com) to course (attendance_id: A1 BEGINNER - DUBLIN - 23.02.2026 - R2)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'agoua1986@gmail.com'
  AND co.attendance_id = 'A1 BEGINNER - DUBLIN - 23.02.2026 - R2'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Laura Nataly', 'Rubiano Martinez', 'natalyrubiano396@gmail.com', '+31630218989', NULL, 'Colombia',
    'B1-', 'B1-', '["Spanish"]', '**Completed Courses**

1. A2 Morning (PRE-INT) 14.11.23 - EDMONTON - Anastasia - New Level -->A2 PreInt+
2. A2 Morning (PRE-INT) 16.01.24 - FIFE - Anastasia M. - New Level ?B1-
3. B1 Morning - EDMONTON 04.06.25 - Magui/Deborah - New Level ?B1-
4. B1 Morning - FIFE - 16.07.25 - Magui/Deborah - New Level ?B1', NOW(), NOW()
);

-- Create enrollment for student (email: natalyrubiano396@gmail.com) to course (attendance_id: B1 MORNING - EDMONTON - 25.02.2026 - R2)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'natalyrubiano396@gmail.com'
  AND co.attendance_id = 'B1 MORNING - EDMONTON - 25.02.2026 - R2'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Stefano', 'Turchi', 'turchi.s@libero.it', '+393487732335', 'Italy', NULL,
    'A2 PreInt-', 'A2 PreInt-', '["Italian / French"]', '**Completed Courses:**

1. 10 hours PLs onsite - Jacque
2. A2 INTENSIVE - BOSTON - 29.09.25 - Anastasia/James ? A2+ Pre-Int+', NOW(), NOW()
);

-- Create enrollment for student (email: turchi.s@libero.it) to course (attendance_id: A2 INTENSIVE - AUCKLAND - 09.03.2026 - R3)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'turchi.s@libero.it'
  AND co.attendance_id = 'A2 INTENSIVE - AUCKLAND - 09.03.2026 - R3'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Luiz', 'Sesti', 'luizotaviofs2007@gmail.com', '+5555999711100', 'Brazil', NULL,
    'B1', 'B1', '["Portuguese"]', '#', NOW(), NOW()
);

-- Create enrollment for student (email: luizotaviofs2007@gmail.com) to course (attendance_id: B1 INTENSIVE - AUCKLAND - 09.03.2026 - R3)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'luizotaviofs2007@gmail.com'
  AND co.attendance_id = 'B1 INTENSIVE - AUCKLAND - 09.03.2026 - R3'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Bárbara', 'Marmo', 'babi.marmo@gmail.com', '+5521982562434', 'Brazil', NULL,
    'B2+', 'B2+', '["Portuguese"]', '#', NOW(), NOW()
);

-- Create enrollment for student (email: babi.marmo@gmail.com) to course (attendance_id: B2 INTENSIVE - AUCKLAND - 09.03.2026 - R3)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'babi.marmo@gmail.com'
  AND co.attendance_id = 'B2 INTENSIVE - AUCKLAND - 09.03.2026 - R3'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Eleonora', 'Fagiolini', 'eleonora.fagiolini@hotmail.com', '+393488968648', 'Italy', NULL,
    'A1', 'A1', '["Italian"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: eleonora.fagiolini@hotmail.com) to course (attendance_id: A1 BEGINNER - EDMONTON - 06.04.2026 - R3)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'eleonora.fagiolini@hotmail.com'
  AND co.attendance_id = 'A1 BEGINNER - EDMONTON - 06.04.2026 - R3'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Megumi', 'Inagaki', 'june.milkyway.d@gmail.com', '+818073301987', 'Japan', NULL,
    'A2 PreInt', 'A2 PreInt', '["Japanese"]', '**Completed courses**

1. A2 Int AUCKLAND 02.06.25 - New Level: A2+
2. A2 PRE-INT MORNING 01.07.25 - FIFE - Anastasia - New Level ?A2+ PreInt
3. A2 Pre-Int Morn - ED - 30.09.2025 - Anastasia ? A2+ Pre-Int+ (Can try B1)', NOW(), NOW()
);

-- Create enrollment for student (email: june.milkyway.d@gmail.com) to course (attendance_id: B1 MORNING - FIFE - 08.04.2026 - R3)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'june.milkyway.d@gmail.com'
  AND co.attendance_id = 'B1 MORNING - FIFE - 08.04.2026 - R3'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Simay', 'Demir', 'zeynepsimay2008@hotmail.com', '+905449200401', 'Turkey', NULL,
    'A2 PreInt', 'A2 PreInt', '["Turkish"]', '**Completed courses**

1. A2 Int 28.07.25 -  CAPE TOWN - Kathleen/Magui - New Level ?A2 PreInt+', NOW(), NOW()
);

-- Create enrollment for student (email: zeynepsimay2008@hotmail.com) to course (attendance_id: A2 INTENSIVE - AUCKLAND - 08.06.2026 - R6)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'zeynepsimay2008@hotmail.com'
  AND co.attendance_id = 'A2 INTENSIVE - AUCKLAND - 08.06.2026 - R6'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Bianca', 'Leite', 'biancalf@gmail.com', '+5511983083712', 'Brasil', 'Sao Paolo',
    'A2 PreInt-', 'A2 PreInt-', '["Portuguese"]', NULL, NOW(), NOW()
);

-- Create enrollment for student (email: biancalf@gmail.com) to course (attendance_id: A2 INTENSIVE - AUCKLAND - 08.06.2026 - R6)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 'biancalf@gmail.com'
  AND co.attendance_id = 'A2 INTENSIVE - AUCKLAND - 08.06.2026 - R6'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );

INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    'Sandor', 'Szokody', 's.szokody@gmail.com', '+36303775703', 'Hungary', NULL,
    'B1-', 'B1-', '["Hungarian"]', '**Completed courses**

1. B1 INT 02.09.24 - AUCKLAND - Kathleen/James - New Level ?B1
2. A2 INT 02.06.25 - AUCKLAND - Magui/Kathleen ? B1-', NOW(), NOW()
);

-- Create enrollment for student (email: s.szokody@gmail.com) to course (attendance_id: A2 INTENSIVE - BOSTON - 06.07.2026 - R7)
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = 's.szokody@gmail.com'
  AND co.attendance_id = 'A2 INTENSIVE - BOSTON - 06.07.2026 - R7'
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );
