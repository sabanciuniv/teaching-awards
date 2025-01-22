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
