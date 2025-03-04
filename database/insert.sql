INSERT INTO Courses_Table (CourseName, Subject_Code, Course_Number, Section, CRN, YearID, Term)
SELECT
    CRSE_TITLE AS CourseName,
    SUBJ_CODE AS Subject_Code,
    CRSE_NUMB AS Course_Number,
    SEQ_NUMB AS Section,
    CRN,
    1 AS YearID,
    TERM_CODE AS Term
FROM API_COURSES
ON DUPLICATE KEY UPDATE
    CourseName = VALUES(CourseName),
    Subject_Code = VALUES(Subject_Code),
    Course_Number = VALUES(Course_Number),
    Section = VALUES(Section),
    YearID = VALUES(YearID),
    Term = VALUES(Term);


-- for instructors
INSERT INTO Candidate_Table (SU_ID, Name, Mail, Role, YearID, Status)
SELECT
    INST_ID AS SU_ID,
    CONCAT(INST_FIRST_NAME, ' ', IFNULL(INST_MI_NAME, ''), ' ', INST_LAST_NAME) AS Name,
    INST_EMAIL AS Mail,
    'Instructor' AS Role,
    1 AS YearID,  -- Assuming YearID should be set manually or retrieved from AcademicYear_Table
    CASE
        WHEN EMPL_STATUS = 'Etkin' THEN 'Etkin'
        ELSE 'İşten ayrıldı'
    END AS Status
FROM API_INSTRUCTORS
ON DUPLICATE KEY UPDATE
    Name = VALUES(Name),
    Mail = VALUES(Mail),
    Role = VALUES(Role),
    YearID = VALUES(YearID),
    Status = VALUES(Status);




INSERT INTO Candidate_Course_Relation (CourseID, CandidateID, Academic_Year, CategoryID, Term)
SELECT
    c.CourseID,
    ca.id AS CandidateID,
    c.YearID AS Academic_Year,
    CASE
        -- Category ID 1: Specific courses (Instructor)
        WHEN ca.Role = 'Instructor'
             AND ca.Status = 'Etkin'
             AND CONCAT(c.Subject_Code, ' ', c.Course_Number) IN
            ('TLL 101', 'TLL 102', 'AL 102')
            THEN '1'

        -- Category ID 2: Another set of courses (Instructor)
        WHEN ca.Role = 'Instructor'
             AND ca.Status = 'Etkin'
             AND CONCAT(c.Subject_Code, ' ', c.Course_Number) IN
            ('SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101',
             'NS 102', 'HIST 191', 'HIST 192')
            THEN '2'

        -- Category ID 4: ENG courses (Instructor)
        WHEN ca.Role = 'Instructor'
             AND ca.Status = 'Etkin'
             AND CONCAT(c.Subject_Code, ' ', c.Course_Number) IN
            ('ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004')
            THEN '4'

        -- Category ID 5: TA courses (TA)
        WHEN ca.Role = 'TA'
             AND ca.Status = 'Etkin'
             AND CONCAT(c.Subject_Code, ' ', c.Course_Number) IN
            ('AL 102', 'CIP 101N', 'HIST 191', 'HIST 192', 'IF 100', 'MATH 101',
             'MATH 102', 'NS 101', 'NS 102', 'SPS 101', 'SPS 102')
            THEN '5'

        -- Category ID 3: Everything else (Instructor)
        WHEN ca.Role = 'Instructor'
             AND ca.Status = 'Etkin'
             AND CONCAT(c.Subject_Code, ' ', c.Course_Number) NOT IN
            ('TLL 101', 'TLL 102', 'AL 102',
             'SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101',
             'NS 102', 'HIST 191', 'HIST 192', 'ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004')
            THEN '3'

        ELSE NULL
    END AS CategoryID,
    c.Term
FROM Courses_Table c
JOIN API_INSTRUCTORS ai
    ON c.Subject_Code = ai.SUBJ_CODE
    AND c.Course_Number = ai.CRSE_NUMB
    AND c.Section = ai.SEQ_NUMB
    AND c.CRN = ai.CRN  -- Matching by CRN as well
JOIN Candidate_Table ca
    ON ca.SU_ID = ai.INST_ID
WHERE
    (ca.Role = 'Instructor' AND ca.Status = 'Etkin')
    OR
    (ca.Role = 'TA' AND ca.Status = 'Etkin')
ON DUPLICATE KEY UPDATE
    Academic_Year = VALUES(Academic_Year),
    CategoryID = VALUES(CategoryID),
    Term = VALUES(Term);


UPDATE Candidate_Course_Relation
SET Academic_Year =
    CASE
        WHEN Term LIKE '2020%' THEN 2020
        WHEN Term LIKE '2021%' THEN 2021
        WHEN Term LIKE '2022%' THEN 2022
        WHEN Term LIKE '2023%' THEN 2023
        WHEN Term LIKE '2024%' THEN 2024
        WHEN Term LIKE '2025%' THEN 2025
        ELSE Academic_Year  -- Keep the existing value if no match
    END
WHERE Term IS NOT NULL;  -- Ensure we only update rows with a valid Term




-- duplicateları görmek için
SELECT CourseID, CandidateID, Academic_Year, CategoryID, Term, COUNT(*) AS duplicate_count
FROM Candidate_Course_Relation
GROUP BY CourseID, CandidateID, Academic_Year, CategoryID, Term
HAVING COUNT(*) > 1;



-- aynı olanları silmek için
DELETE FROM Candidate_Course_Relation
WHERE CandidateCourseID NOT IN (
    SELECT MIN(CandidateCourseID)
    FROM Candidate_Course_Relation
    GROUP BY CourseID, CandidateID, Academic_Year, CategoryID, Term
);


--student course relation query

INSERT INTO Student_Category_Relation (student_id, categoryID)
SELECT DISTINCT scr.`student.id`,
    CASE
        -- Category ID 1: Specific courses
        WHEN CONCAT(c.Subject_Code, ' ', c.Course_Number) IN
            ('TLL 101', 'TLL 102', 'AL 102')
            THEN 1

        -- Category ID 2: Another set of courses
        WHEN CONCAT(c.Subject_Code, ' ', c.Course_Number) IN
            ('SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101',
             'NS 102', 'HIST 191', 'HIST 192')
            THEN 2

        -- Category ID 4: ENG courses
        WHEN CONCAT(c.Subject_Code, ' ', c.Course_Number) IN
            ('ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004')
            THEN 4

        -- Category ID 5: TA-related courses
        WHEN CONCAT(c.Subject_Code, ' ', c.Course_Number) IN
            ('AL 102', 'CIP 101N', 'HIST 191', 'HIST 192', 'IF 100', 'MATH 101',
             'MATH 102', 'NS 101', 'NS 102', 'SPS 101', 'SPS 102')
            THEN 5

        -- Category ID 3: Everything else
        WHEN CONCAT(c.Subject_Code, ' ', c.Course_Number) NOT IN
            ('TLL 101', 'TLL 102', 'AL 102',
             'SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101',
             'NS 102', 'HIST 191', 'HIST 192', 'ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004')
            THEN 3
    END AS categoryID
FROM Student_Course_Relation scr
JOIN Courses_Table c ON scr.CourseID = c.CourseID
HAVING categoryID IS NOT NULL;