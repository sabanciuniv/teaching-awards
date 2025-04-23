<?php
require_once 'commonFunc.php';
init_session();

$config = require __DIR__ . '/../config.php';
require_once '../database/dbConnection.php';
require_once 'commonFunc.php';
header('Content-Type: application/json');

//sync Students
function synchronizeStudents(PDO $pdo): array {
    $response = [
        'inserted' => 0,
        'updated' => 0,
        'deleted' => 0,
        'insertedRows' => [],
        'updatedRows' => [],
        'deletedRows' => []
    ];

    try {
        $classMapping = [
            'SE' => 'Senior',
            'JU' => 'Junior',
            'FR' => 'Freshman',
            'SO' => 'Sophomore'
        ];

        // Year mapping
        $stmt = $pdo->query("SELECT YearID, Academic_year FROM AcademicYear_Table");
        $academicYears = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $academicYears[$row['Academic_year']] = $row['YearID'];
        }

        // Load API students
        $stmt = $pdo->query("SELECT * FROM API_STUDENTS");
        $apiStudents = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['STU_CLASS_CODE'] = $classMapping[$row['STU_CLASS_CODE']] ?? 'Unknown';
            $fullName = trim($row['STU_FIRST_NAME'] . ' ' . ($row['STU_MI_NAME'] ?? '') . ' ' . $row['STU_LAST_NAME']);

            $row['StudentFullName'] = $fullName;
            $row['SuNET_Username'] = $row['STU_USERNAME'] ?: null;
            $row['Mail'] = $row['STU_EMAIL'] ?: null;
            $row['Class'] = $row['STU_CLASS_CODE'] ?? null;
            $row['Faculty'] = $row['STU_FACULTY_CODE'] ?? null;
            $row['Department'] = $row['STU_PROGRAM_CODE'] ?? null;
            $row['CGPA'] = $row['STU_CUM_GPA_SU'] !== null ? (float) $row['STU_CUM_GPA_SU'] : null;
            $row['TermYear'] = (int)substr($row['TERM_CODE'], 0, 4);
            $row['YearID'] = $academicYears[$row['TermYear']] ?? null;

            $apiStudents[$row['STU_ID']] = $row;
        }

        // Existing students
        $stmt = $pdo->query("SELECT StudentID, StudentFullName, SuNET_Username, Mail, Class, Faculty, Department, CGPA, YearID FROM Student_Table");
        $existingStudents = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingStudents[$row['StudentID']] = $row;
        }

        $insertStmt = $pdo->prepare("INSERT INTO Student_Table 
            (StudentID, YearID, StudentFullName, SuNET_Username, Mail, Class, Faculty, Department, CGPA, Sync_Date) 
            VALUES (:StudentID, :YearID, :StudentFullName, :SuNET_Username, :Mail, :Class, :Faculty, :Department, :CGPA, NOW())");

        $updateStmt = $pdo->prepare("UPDATE Student_Table SET 
            YearID = :YearID,
            StudentFullName = :StudentFullName, 
            SuNET_Username = :SuNET_Username, 
            Mail = :Mail, 
            Class = :Class, 
            Faculty = :Faculty, 
            Department = :Department, 
            CGPA = :CGPA, 
            Sync_Date = NOW() 
            WHERE StudentID = :StudentID");

        $allProcessedIDs = [];

        foreach ($apiStudents as $stu_id => $student) {
            $yearID = $student['YearID'];
            if (!$yearID) continue;

            if (isset($existingStudents[$stu_id])) {
                $existing = $existingStudents[$stu_id];

                if (
                    $existing['StudentFullName'] !== $student['StudentFullName'] ||
                    $existing['SuNET_Username'] !== $student['SuNET_Username'] ||
                    $existing['Mail'] !== $student['Mail'] ||
                    $existing['Class'] !== $student['Class'] ||
                    $existing['Faculty'] !== $student['Faculty'] ||
                    $existing['Department'] !== $student['Department'] ||
                    (float)$existing['CGPA'] !== (float)$student['CGPA'] ||
                    (int)$existing['YearID'] !== (int)$yearID
                ) {
                    $updateStmt->execute([
                        ':StudentID' => $stu_id,
                        ':YearID' => $yearID,
                        ':StudentFullName' => $student['StudentFullName'],
                        ':SuNET_Username' => $student['SuNET_Username'],
                        ':Mail' => $student['Mail'],
                        ':Class' => $student['Class'],
                        ':Faculty' => $student['Faculty'],
                        ':Department' => $student['Department'],
                        ':CGPA' => $student['CGPA']
                    ]);

                    if ($updateStmt->rowCount() > 0) {
                        $response['updated']++;
                        $response['updatedRows'][] = [
                            'StudentID' => $stu_id,
                            'YearID' => $yearID,
                            'StudentFullName' => $student['StudentFullName'],
                            'SuNET_Username' => $student['SuNET_Username'],
                            'Mail' => $student['Mail'],
                            'Class' => $student['Class'],
                            'Faculty' => $student['Faculty'],
                            'Department' => $student['Department'],
                            'CGPA' => $student['CGPA']
                        ];
                        
                    }
                }

            } else {
                $insertStmt->execute([
                    ':StudentID' => $stu_id,
                    ':YearID' => $yearID,
                    ':StudentFullName' => $student['StudentFullName'],
                    ':SuNET_Username' => $student['SuNET_Username'],
                    ':Mail' => $student['Mail'],
                    ':Class' => $student['Class'],
                    ':Faculty' => $student['Faculty'],
                    ':Department' => $student['Department'],
                    ':CGPA' => $student['CGPA']
                ]);

                if ($insertStmt->rowCount() > 0) {
                    $response['inserted']++;
                    $response['insertedRows'][] = [
                        'StudentID' => $stu_id,
                        'YearID' => $yearID,
                        'StudentFullName' => $student['StudentFullName'],
                        'SuNET_Username' => $student['SuNET_Username'],
                        'Mail' => $student['Mail'],
                        'Class' => $student['Class'],
                        'Faculty' => $student['Faculty'],
                        'Department' => $student['Department'],
                        'CGPA' => $student['CGPA']
                    ];
                    
                }
            }

            $allProcessedIDs[] = $stu_id;
        }

        // Deletion
        if (!empty($allProcessedIDs)) {
            $placeholders = rtrim(str_repeat('?,', count($allProcessedIDs)), ',');
            $stmt = $pdo->prepare("SELECT id, StudentID FROM Student_Table WHERE StudentID NOT IN ($placeholders)");
            $stmt->execute($allProcessedIDs);
            $studentsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($studentsToDelete)) {
                $studentIDs = array_column($studentsToDelete, 'StudentID');
                $studentInternalIDs = array_column($studentsToDelete, 'id'); 

                // Fetch full student info before deletion
                $placeholderStr = rtrim(str_repeat('?,', count($studentIDs)), ',');
                $stmt = $pdo->prepare("SELECT * FROM Student_Table WHERE StudentID IN ($placeholderStr)");
                $stmt->execute($studentIDs);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // dont show the AUTO incremented ID in json
                foreach ($rows as &$row) {
                    unset($row['id']);
                }
                $response['deletedRows'] = $rows;


                if (!empty($studentInternalIDs)) {
                    $inIDs = rtrim(str_repeat('?,', count($studentInternalIDs)), ',');

                    $stmt = $pdo->prepare("DELETE FROM Votes_Table WHERE VoterID IN ($inIDs)");
                    $stmt->execute($studentInternalIDs);

                    $stmt = $pdo->prepare("DELETE FROM Student_Course_Relation WHERE `student.id` IN ($inIDs)");
                    $stmt->execute($studentInternalIDs);

                    $stmt = $pdo->prepare("DELETE FROM Student_Table WHERE StudentID IN (" . rtrim(str_repeat('?,', count($studentIDs)), ',') . ")");
                    $stmt->execute($studentIDs);

                    $response['deleted'] = $stmt->rowCount();
                }
            }
        }

        return array_merge(['status' => 'success'], $response);

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}


//sync Courses
function synchronizeCourses(PDO $pdo): array {
    $response = [
        'inserted' => 0,
        'updated' => 0,
        'deleted' => 0,
        'insertedRows' => [],
        'updatedRows' => [],
        'deletedRows' => []
    ];

    try {
        // Load Academic Year Map
        $stmt = $pdo->query("SELECT YearID, Academic_year FROM AcademicYear_Table");
        $academicYears = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $academicYears[$row['Academic_year']] = $row['YearID'];
        }

        // Load API courses
        $stmt = $pdo->query("SELECT TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, CRSE_TITLE FROM API_COURSES");
        $apiCourses = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['CRN'] . '_' . $row['TERM_CODE'] . '_' . $row['SUBJ_CODE'] . '_' . $row['CRSE_NUMB'];
            $apiCourses[$key] = $row;
        }

        // Load DB courses
        $stmt = $pdo->query("SELECT * FROM Courses_Table");
        $existingCourses = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['CRN'] . '_' . $row['Term'] . '_' . $row['Subject_Code'] . '_' . $row['Course_Number'];
            $existingCourses[$key] = $row;
        }

        // Prepare SQL statements
        $insertStmt = $pdo->prepare("INSERT INTO Courses_Table 
            (CourseName, Subject_Code, Course_Number, Section, CRN, Term, Sync_Date, YearID) 
            VALUES (:CourseName, :Subject_Code, :Course_Number, :Section, :CRN, :Term, NOW(), :YearID)");

        $updateStmt = $pdo->prepare("UPDATE Courses_Table 
            SET CourseName = :CourseName, Subject_Code = :Subject_Code, Course_Number = :Course_Number, 
                Section = :Section, Term = :Term, Sync_Date = NOW(), YearID = :YearID 
            WHERE CRN = :CRN");

        $deleteStmt = $pdo->prepare("DELETE FROM Courses_Table WHERE CRN = :CRN AND Term = :Term");

        foreach ($apiCourses as $compositeKey => $course) {
            $course['CRSE_TITLE'] = $course['CRSE_TITLE'] ?? 'Unknown Course';
            $course['SUBJ_CODE'] = $course['SUBJ_CODE'] ?? 'N/A';  
            $course['CRSE_NUMB'] = $course['CRSE_NUMB'] ?? '000';
            $course['SEQ_NUMB'] = $course['SEQ_NUMB'] ?? '';
            $course['TERM_CODE'] = $course['TERM_CODE'] ?? '';

            $yearCode = substr($course['TERM_CODE'], 0, 4);
            $yearID = $academicYears[$yearCode] ?? null;
            if (!$yearID) continue;

            $fullCourse = [
                'CourseName'     => $course['CRSE_TITLE'],
                'Subject_Code'   => $course['SUBJ_CODE'],
                'Course_Number'  => $course['CRSE_NUMB'],
                'Section'        => $course['SEQ_NUMB'],
                'CRN'            => $course['CRN'],
                'Term'           => $course['TERM_CODE'],
                'YearID'         => $yearID
            ];

            if (isset($existingCourses[$compositeKey])) {
                $dbCourse = $existingCourses[$compositeKey];
                if (
                    $dbCourse['CourseName'] !== $course['CRSE_TITLE'] ||
                    $dbCourse['Subject_Code'] !== $course['SUBJ_CODE'] ||
                    $dbCourse['Course_Number'] !== $course['CRSE_NUMB'] ||
                    $dbCourse['Section'] !== $course['SEQ_NUMB'] ||
                    $dbCourse['Term'] !== $course['TERM_CODE'] ||
                    $dbCourse['YearID'] != $yearID
                ) {
                    $updateStmt->execute($fullCourse);
                    $response['updated']++;
                    $response['updatedRows'][] = $fullCourse;
                }
            } else {
                $insertStmt->execute($fullCourse);
                $response['inserted']++;
                $response['insertedRows'][] = $fullCourse;
            }
        }

        // Delete missing ones
        foreach ($existingCourses as $key => $course) {
            if (!isset($apiCourses[$key])) {
                $deleteStmt->execute([':CRN' => $course['CRN'], ':Term' => $course['Term']]);
                $response['deleted']++;
                $response['deletedRows'][] = [
                    'CourseName'     => $course['CourseName'],
                    'Subject_Code'   => $course['Subject_Code'],
                    'Course_Number'  => $course['Course_Number'],
                    'Section'        => $course['Section'],
                    'CRN'            => $course['CRN'],
                    'Term'           => $course['Term'],
                    'YearID'         => $course['YearID']
                ];
            }
        }

        return array_merge(['status' => 'success'], $response);

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => "Error: " . $e->getMessage()
        ];
    }
}


// sync student-course enrollment
function synchronizeStudentCourses(PDO $pdo): array {
    $response = [
        'inserted' => 0,
        'updated_to_enrolled' => 0,
        'updated_to_dropped' => 0,
        'insertedRows' => [],
        'updatedToEnrolledRows' => [],
        'updatedToDroppedRows' => []
    ];

    try {
        // Fetch students
        $stmt = $pdo->query("SELECT id, StudentID FROM Student_Table");
        $students = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $students[$row['StudentID']] = $row['id'];
        }

        // Fetch courses
        $stmt = $pdo->query("SELECT CourseID, CRN FROM Courses_Table");
        $courses = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $courses[$row['CRN']] = $row['CourseID'];
        }

        // Fetch current API data
        $stmt = $pdo->query("SELECT STU_ID, CRN FROM API_STUDENT_COURSES WHERE STU_ID IS NOT NULL");
        $apiRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $apiEnrollments = [];
        foreach ($apiRows as $row) {
            $stuId = $row['STU_ID'];
            $crn = $row['CRN'];

            if (isset($students[$stuId]) && isset($courses[$crn])) {
                $studentId = $students[$stuId];
                $courseId = $courses[$crn];
                $key = "$studentId" . "_" . "$courseId";
                $apiEnrollments[$key] = [
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'Stu_ID' => $stuId,
                    'CRN' => $crn
                ];
            }
        }

        // Fetch existing relations
        $stmt = $pdo->query("SELECT `student.id`, CourseID, EnrollmentStatus FROM Student_Course_Relation");
        $existingRelations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = "{$row['student.id']}_{$row['CourseID']}";
            $existingRelations[$key] = $row['EnrollmentStatus'];
        }

        // Prepare statements
        $insertStmt = $pdo->prepare("INSERT INTO Student_Course_Relation (`student.id`, CourseID, EnrollmentStatus) VALUES (:student_id, :course_id, 'enrolled')");
        $updateToEnrolledStmt = $pdo->prepare("UPDATE Student_Course_Relation SET EnrollmentStatus = 'enrolled' WHERE `student.id` = :student_id AND CourseID = :course_id");
        $updateToDroppedStmt = $pdo->prepare("UPDATE Student_Course_Relation SET EnrollmentStatus = 'dropped' WHERE `student.id` = :student_id AND CourseID = :course_id");

        // Insert or update to enrolled
        foreach ($apiEnrollments as $key => $data) {
            if (!isset($existingRelations[$key])) {
                $insertStmt->execute([
                    ':student_id' => $data['student_id'],
                    ':course_id' => $data['course_id']
                ]);
                $response['inserted']++;
                $response['insertedRows'][] = $data;
            } elseif ($existingRelations[$key] === 'dropped') {
                $updateToEnrolledStmt->execute([
                    ':student_id' => $data['student_id'],
                    ':course_id' => $data['course_id']
                ]);
                $response['updated_to_enrolled']++;
                $response['updatedToEnrolledRows'][] = $data;
            }
        }

        // Update to dropped
        foreach ($existingRelations as $key => $status) {
            if (!isset($apiEnrollments[$key]) && $status !== 'dropped') {
                [$studentId, $courseId] = explode('_', $key);
                $updateToDroppedStmt->execute([
                    ':student_id' => $studentId,
                    ':course_id' => $courseId
                ]);
                $response['updated_to_dropped']++;
                $response['updatedToDroppedRows'][] = [
                    'student_id' => $studentId,
                    'course_id' => $courseId
                ];
            }
        }

        return array_merge(['status' => 'success'], $response);

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}


//sync student and their categories
function synchronizeStudentCategories(PDO $pdo): array {
    $response = [
        'inserted' => 0,
        'existing' => 0,
        'deleted' => 0,
        'insertedRows' => [],
        'existingRows' => [],
        'deletedRows' => []
    ];

    try {
        // Step 1: Fetch existing student-category relations
        $stmt = $pdo->query("SELECT scr.student_id, scr.categoryID, s.StudentID 
                             FROM Student_Category_Relation scr 
                             JOIN Student_Table s ON scr.student_id = s.id");

        $existingRelations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = (int)$row['student_id'] . '_' . (int)$row['categoryID'];
            $existingRelations[$key] = [
                'student_id' => (int)$row['student_id'],
                'StudentID' => $row['StudentID'],
                'CategoryID' => (int)$row['categoryID']
            ];
        }

        // Step 2: Get Category Map
        $stmt = $pdo->query("SELECT CategoryID, CategoryCode FROM Category_Table");
        $categoryMap = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categoryMap[(int)$row['CategoryID']] = $row['CategoryCode'];
        }

        // Step 3: Desired Relations
        $stmt = $pdo->query("SELECT DISTINCT s.id AS student_id, s.StudentID, s.Class, s.CGPA,
                                   CONCAT(c.Subject_Code, ' ', c.Course_Number) AS full_code
                            FROM Student_Course_Relation scr
                            JOIN Courses_Table c ON scr.CourseID = c.CourseID
                            JOIN Student_Table s ON scr.`student.id` = s.id");

        $desiredRelations = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $studentID = $row['StudentID'];
            $student_id = (int)$row['student_id'];
            $class = $row['Class'];
            $cgpa = (float)$row['CGPA'];
            $code = $row['full_code'];

            $categoryID = null;

            if (in_array($code, ['TLL 101', 'TLL 102', 'AL 102']) && $class === 'Freshman') {
                $categoryID = 1;
            } elseif (in_array($code, ['SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192']) && $class === 'Freshman') {
                $categoryID = 2;
            } elseif (in_array($code, ['ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004'])) {
                $categoryID = 4;
            } elseif (in_array($code, ['CIP 101N', 'IF 100R', 'MATH 101R', 'MATH 102R', 'NS 101R', 'NS 102R', 'SPS 101D', 'SPS 102D']) && $class === 'Freshman') {
                $categoryID = 5;
            } elseif (!in_array($code, ['TLL 101', 'TLL 102', 'AL 102',
                                       'SPS 101', 'SPS 102', 'MATH 101', 'MATH 102',
                                       'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192',
                                       'ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004']) && $cgpa >= 2 && $class === 'Senior') {
                $categoryID = 3;
            }

            if ($categoryID !== null) {
                $key = $student_id . '_' . $categoryID;
                $desiredRelations[$key] = [
                    'student_id' => $student_id,
                    'StudentID' => $studentID,
                    'CategoryID' => $categoryID,
                    'CategoryCode' => $categoryMap[$categoryID] ?? 'Unknown'
                ];
            }
        }

        // Step 4: Prepare insert and delete statements
        $insertStmt = $pdo->prepare("INSERT INTO Student_Category_Relation (student_id, categoryID) VALUES (:student_id, :categoryID)");
        $deleteStmt = $pdo->prepare("DELETE FROM Student_Category_Relation WHERE student_id = :student_id AND categoryID = :categoryID");

        // Step 5: Insert missing
        foreach ($desiredRelations as $key => $info) {
            if (!isset($existingRelations[$key])) {
                try {
                    $insertStmt->execute([
                        ':student_id' => $info['student_id'],
                        ':categoryID' => $info['CategoryID']
                    ]);

                    if ($insertStmt->rowCount() > 0) {
                        $response['inserted']++;
                        $stmtStudent = $pdo->prepare("SELECT * FROM Student_Table WHERE id = ?");
                        $stmtStudent->execute([$info['student_id']]);
                        $studentRow = $stmtStudent->fetch(PDO::FETCH_ASSOC);
                        unset($studentRow['id']); // remove internal auto-increment if needed

                        $response['insertedRows'][] = array_merge(
                            $studentRow,
                            [
                                'CategoryID' => $info['CategoryID'],
                                'CategoryCode' => $info['CategoryCode']
                            ]
                        );
                        
                    }
                } catch (PDOException $ex) {
                    error_log("Insert error for student_id={$info['student_id']} | categoryID={$info['CategoryID']}: " . $ex->getMessage());
                }
            } 
        }

        // Step 6: Delete outdated
        foreach ($existingRelations as $key => $relation) {
            if (!isset($desiredRelations[$key])) {
                $deleteStmt->execute([
                    ':student_id' => $relation['student_id'],
                    ':categoryID' => $relation['CategoryID']
                ]);
                $response['deleted']++;
                $response['deletedRows'][] = [
                    'StudentID' => $relation['StudentID'],
                    'CategoryID' => $relation['CategoryID'],
                    'CategoryCode' => $categoryMap[$relation['CategoryID']] ?? 'Unknown'
                ];
            }
        }

        return array_merge(['status' => 'success'], $response);

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}


//sync Candidates
function synchronizeCandidates(PDO $pdo): array {
    $response = [
        'inserted' => 0,
        'updated' => 0,
        'insertedRows' => [],
        'updatedRows' => [],
    ];

    try {
        $statusMapping = [
            'Active' => 'Etkin',
            'Inactive' => 'İşten ayrıldı',
            'Terminated' => 'İşten ayrıldı',
            'İşten ayrıldı' => 'İşten ayrıldı'
        ];

        $candidates = [];

        // TA data
        $stmt = $pdo->query("SELECT TA_ID, TA_FIRST_NAME, TA_MI_NAME, TA_LAST_NAME, TA_EMAIL, EMPL_STATUS FROM API_TAS");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fullName = trim($row['TA_FIRST_NAME'] . ' ' . ($row['TA_MI_NAME'] ?? '') . ' ' . $row['TA_LAST_NAME']);
            $status = $statusMapping[$row['EMPL_STATUS']] ?? 'Etkin';

            $candidates[$row['TA_ID']] = [
                'SU_ID' => $row['TA_ID'],
                'Name' => $fullName,
                'Mail' => $row['TA_EMAIL'] ?: null,
                'Role' => 'TA',
                'Status' => $status
            ];
        }

        // Instructor data
        $stmt = $pdo->query("SELECT INST_ID, INST_FIRST_NAME, INST_MI_NAME, INST_LAST_NAME, INST_EMAIL, EMPL_STATUS FROM API_INSTRUCTORS");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fullName = trim($row['INST_FIRST_NAME'] . ' ' . ($row['INST_MI_NAME'] ?? '') . ' ' . $row['INST_LAST_NAME']);
            $status = $statusMapping[$row['EMPL_STATUS']] ?? 'Etkin';

            $candidates[$row['INST_ID']] = [
                'SU_ID' => $row['INST_ID'],
                'Name' => $fullName,
                'Mail' => $row['INST_EMAIL'] ?: null,
                'Role' => 'Instructor',
                'Status' => $status
            ];
        }

        // Fetch exceptions
        $exceptionStmt = $pdo->query("SELECT CandidateID FROM Exception_Table");
        $exceptionList = $exceptionStmt->fetchAll(PDO::FETCH_COLUMN);
        $exceptionSUIds = [];

        if ($exceptionList) {
            $stmt = $pdo->prepare("SELECT SU_ID FROM Candidate_Table WHERE id IN (" . implode(",", array_map('intval', $exceptionList)) . ")");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $exceptionSUIds[$row['SU_ID']] = true;
            }
        }

        // Override status for exceptions
        foreach ($candidates as $su_id => &$candidate) {
            if (isset($exceptionSUIds[$su_id])) {
                $candidate['Status'] = 'İşten ayrıldı';
            }
        }
        unset($candidate); // break reference

        // Fetch existing
        $stmt = $pdo->query("SELECT SU_ID, Name, Mail, Role, Status FROM Candidate_Table");
        $existingCandidates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingCandidates[$row['SU_ID']] = $row;
        }

        // Prepare queries
        $insertStmt = $pdo->prepare("INSERT INTO Candidate_Table (SU_ID, Name, Mail, Role, Status, Sync_Date) 
            VALUES (:SU_ID, :Name, :Mail, :Role, :Status, NOW())");

        $updateStmt = $pdo->prepare("UPDATE Candidate_Table SET 
            Name = :Name, Mail = :Mail, Role = :Role, Status = :Status, Sync_Date = NOW() 
            WHERE SU_ID = :SU_ID");

        foreach ($candidates as $su_id => $candidate) {
            if (isset($existingCandidates[$su_id])) {
                $existing = $existingCandidates[$su_id];
                $changes = [];

                foreach (['Name', 'Mail', 'Role', 'Status'] as $field) {
                    if ($existing[$field] !== $candidate[$field]) {
                        $changes[$field] = [
                            'old' => $existing[$field],
                            'new' => $candidate[$field]
                        ];
                    }
                }

                if (!empty($changes)) {
                    $updateStmt->execute([
                        ':SU_ID' => $su_id,
                        ':Name' => $candidate['Name'],
                        ':Mail' => $candidate['Mail'],
                        ':Role' => $candidate['Role'],
                        ':Status' => $candidate['Status']
                    ]);

                    if ($updateStmt->rowCount() > 0) {
                        $response['updated']++;
                        $response['updatedRows'][] = [
                            'SU_ID' => $su_id,
                            'changes' => $changes
                        ];
                    }
                }
            } else {
                $insertStmt->execute([
                    ':SU_ID' => $su_id,
                    ':Name' => $candidate['Name'],
                    ':Mail' => $candidate['Mail'],
                    ':Role' => $candidate['Role'],
                    ':Status' => $candidate['Status']
                ]);
                if ($insertStmt->rowCount() > 0) {
                    $response['inserted']++;
                    $response['insertedRows'][] = $candidate;
                }
            }
        }

        // Step: Fully delete candidates no longer in API_TAS or API_INSTRUCTORS
        $suIdsFromAPI = array_keys($candidates);
        $suIdsInDB = array_keys($existingCandidates);

        $toDeleteSuIds = array_diff($suIdsInDB, $suIdsFromAPI);

        if (!empty($toDeleteSuIds)) {
            // Step 1: Get their internal IDs
            $placeholders = rtrim(str_repeat('?,', count($toDeleteSuIds)), ',');
            $stmt = $pdo->prepare("SELECT id, SU_ID FROM Candidate_Table WHERE SU_ID IN ($placeholders)");
            $stmt->execute($toDeleteSuIds);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $internalIds = array_column($rows, 'id');
            $suIdMap = [];
            foreach ($rows as $row) {
                $suIdMap[$row['id']] = $row['SU_ID'];
            }

            // Step 2: Delete from related tables
            if (!empty($internalIds)) {
                $internalPlaceholder = rtrim(str_repeat('?,', count($internalIds)), ',');

                $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CandidateID IN ($internalPlaceholder)")->execute($internalIds);
                $pdo->prepare("DELETE FROM Exception_Table WHERE CandidateID IN ($internalPlaceholder)")->execute($internalIds);
                $pdo->prepare("DELETE FROM Candidate_Table WHERE id IN ($internalPlaceholder)")->execute($internalIds);

                $response['deleted'] = count($internalIds);
                foreach ($internalIds as $id) {
                    $response['deletedRows'][] = [
                        'SU_ID' => $suIdMap[$id],
                        'reason' => 'Does not exist anymore in the real database'
                    ];
                }
            }
        }


        return array_merge(['status' => 'success'], $response);

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'error' => true,
            'message' => "Error: " . $e->getMessage()
        ];
    }
}


//sync Candidate Course Relation
function synchronizeCandidateCourses(PDO $pdo): array {
    $response = [
        'inserted' => 0,
        'updated' => 0,
        'deleted' => 0,
        'insertedRows' => [],
        'updatedRows' => [],
        'deletedRows' => []
    ];
    // Local helper function
    $mapCategoryID = function($subject, $course, $role, $status) {
        $full = "$subject $course";
        if ($role === 'Instructor' && $status === 'Etkin') {
            if (in_array($full, ['TLL 101', 'TLL 102', 'AL 102'])) return '1';
            if (in_array($full, ['SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192'])) return '2';
            if (in_array($full, ['ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004'])) return '4';
            return '3';
        }
        if ($role === 'TA' && $status === 'Etkin') {
            if (in_array($full, ['CIP 101N','IF 100R', 'MATH 101R', 'MATH 102R', 'NS 101R', 'NS 102R', 'SPS 101D', 'SPS 102D'])) return '5';
        }
        return null;
    };
    try {
        // Load academic year mapping
        $stmt = $pdo->query("SELECT Academic_year, YearID FROM AcademicYear_Table");
        $yearMap = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $yearMap[$row['Academic_year']] = $row['YearID'];
        }

        // Load all courses
        $stmt = $pdo->query("SELECT * FROM Courses_Table");
        $courses = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = "{$row['Term']}_{$row['Subject_Code']}_{$row['Course_Number']}_{$row['CRN']}";
            $courses[$key] = $row;
        }

        // Load candidates
        $stmt = $pdo->query("SELECT id, SU_ID, Role, Status FROM Candidate_Table");
        $candidates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $candidates[$row['SU_ID']] = $row;
        }

        // Load existing relations
        $stmt = $pdo->query("SELECT CandidateID, CourseID FROM Candidate_Course_Relation");
        $existingRelations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingRelations["{$row['CandidateID']}_{$row['CourseID']}"] = true;
        }

        $insertStmt = $pdo->prepare("INSERT INTO Candidate_Course_Relation (CourseID, CandidateID, Academic_Year, CategoryID, Term)
            VALUES (:CourseID, :CandidateID, :Academic_Year, :CategoryID, :Term)");

        $updateStmt = $pdo->prepare("UPDATE Candidate_Course_Relation SET Academic_Year = :Academic_Year,
                CategoryID = :CategoryID, Term = :Term
                WHERE CandidateID = :CandidateID AND CourseID = :CourseID
                AND (Academic_Year IS NULL OR CategoryID IS NULL OR Term IS NULL
                        OR Academic_Year != :Academic_Year OR CategoryID != :CategoryID OR Term != :Term)");

        $validKeys = [];
        $sources = ['API_INSTRUCTORS' => 'INST_ID', 'API_TAS' => 'TA_ID'];

        foreach ($sources as $table => $idField) {
            $stmt = $pdo->query("SELECT TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, $idField AS SU_ID FROM $table WHERE $idField IS NOT NULL");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $term = $row['TERM_CODE'];
                $crn = $row['CRN'];
                $suId = $row['SU_ID'];
                $subject = $row['SUBJ_CODE'];
                $course = $row['CRSE_NUMB'];

                $yearCode = substr($term, 0, 4);
                $academicYear = $yearMap[$yearCode] ?? null;
                $courseKey = "{$term}_{$subject}_{$course}_{$crn}";
                $courseData = $courses[$courseKey] ?? null;

                if (!$academicYear || !$courseData || !isset($candidates[$suId])) continue;

                $candidateData = $candidates[$suId];
                $key = "{$candidateData['id']}_{$courseData['CourseID']}";
                $validKeys[$key] = true;

                $categoryID = $mapCategoryID($subject, $course, $candidateData['Role'], $candidateData['Status']);
                if (!$categoryID) continue;

                $logRow = [
                    'SU_ID' => $suId,
                    'Subject_Code' => $courseData['Subject_Code'],
                    'Course_Number' => $courseData['Course_Number'],
                    'CourseName' => $courseData['CourseName'],
                    'CategoryID' => $categoryID,
                    'Term' => $term
                ];

                if (!isset($existingRelations[$key])) {
                    $insertStmt->execute([
                        ':CourseID' => $courseData['CourseID'],
                        ':CandidateID' => $candidateData['id'],
                        ':Academic_Year' => $academicYear,
                        ':CategoryID' => $categoryID,
                        ':Term' => $term
                    ]);
                    $response['inserted']++;
                    $response['insertedRows'][] = $logRow;
                } else {
                    $updateStmt->execute([
                        ':Academic_Year' => $academicYear,
                        ':CategoryID' => $categoryID,
                        ':Term' => $term,
                        ':CandidateID' => $candidateData['id'],
                        ':CourseID' => $courseData['CourseID']
                    ]);
                    if ($updateStmt->rowCount() > 0) {
                        $response['updated']++;
                        $response['updatedRows'][] = $logRow;
                    }
                }
            }
        }

        // Delete "İşten ayrıldı"
        $stmt = $pdo->query("SELECT id, SU_ID FROM Candidate_Table WHERE Status = 'İşten ayrıldı'");
        $resignedCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $deleteResignedStmt = $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CandidateID = :CandidateID");

        foreach ($resignedCandidates as $candidate) {
            $stmt = $pdo->prepare("SELECT CourseID FROM Candidate_Course_Relation WHERE CandidateID = :CandidateID");
            $stmt->execute([':CandidateID' => $candidate['id']]);
            $coursesToDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($coursesToDelete)) {
                $deleteResignedStmt->execute([':CandidateID' => $candidate['id']]);
                foreach ($coursesToDelete as $cid) {
                    $response['deleted']++;
                    $response['deletedRows'][] = [
                        'SU_ID' => $candidate['SU_ID'],
                        'CourseID' => $cid,
                        'reason' => 'İşten ayrıldı'
                    ];
                }
            }
        }

        // Delete orphan relations not found in API
        $stmt = $pdo->query("
            SELECT ccr.CandidateID, ccr.CourseID, ct.SU_ID, c.Subject_Code, c.Course_Number, c.CRN, c.Term
            FROM Candidate_Course_Relation ccr
            JOIN Candidate_Table ct ON ct.id = ccr.CandidateID
            JOIN Courses_Table c ON c.CourseID = ccr.CourseID
        ");
        $deleteOrphanStmt = $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CandidateID = :CandidateID AND CourseID = :CourseID");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = "{$row['CandidateID']}_{$row['CourseID']}";
            if (!isset($validKeys[$key])) {
                $deleteOrphanStmt->execute([
                    ':CandidateID' => $row['CandidateID'],
                    ':CourseID' => $row['CourseID']
                ]);
                $response['deleted']++;
                $response['deletedRows'][] = [
                    'SU_ID' => $row['SU_ID'],
                    'CourseID' => $row['CourseID'],
                    'reason' => 'Not found in API_INSTRUCTORS or API_TAS'
                ];
            }
        }

        return array_merge(['status' => 'success'], $response);

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'error' => true,
            'message' => $e->getMessage()
        ];
    }
}


function runFullSynchronization(PDO $pdo, string $logDirBase): array {
    $response = [
        "success" => true,
        "logs" => [],
        "message" => "",
        "logFilePath" => ""
    ];

    // Load academic year from DB
    $academicYear = getCurrentAcademicYear($pdo);
    if (!$academicYear) {
        return [
            "success" => false,
            "message" => "Unable to determine current academic year."
        ];
    }

    // Create log directory and file
    $timestamp = date("Ymd_His");
    $logDir = rtrim($logDirBase, '/') . '/' . $academicYear;
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . "/sync_log_$timestamp.json";

    // Helper to log each step
    $logChanges = function ($section, $inserted, $updated, $deleted, $insertedRows, $updatedRows, $deletedRows) use (&$response) {
        $response["logs"][] = [
            "section" => $section,
            "inserted" => $inserted ?? 0,
            "updated" => $updated ?? 0,
            "deleted" => $deleted ?? 0,
            "insertedRows" => $insertedRows ?? [],
            "updatedRows" => $updatedRows ?? [],
            "deletedRows" => $deletedRows ?? [],
            "timestamp" => date("Y-m-d H:i:s")
        ];
    };

    try {
        // List of sync operations
        $syncTasks = [
            ["Courses", "synchronizeCourses"],
            ["Students", "synchronizeStudents"],
            ["Candidates", "synchronizeCandidates"],
            ["Student Courses Relation", "synchronizeStudentCourses"],
            ["Candidate Courses Relation", "synchronizeCandidateCourses"],
            ["Student Category Relation", "synchronizeStudentCategories"]
        ];

        foreach ($syncTasks as [$section, $func]) {
            $result = $func($pdo);
            if ($result['status'] === 'error') {
                throw new Exception("[$section] " . $result['message']);
            }

            // Adjust updated keys for Student Courses and Candidate Courses
            $logChanges(
                $section,
                $result['inserted'] ?? 0,
                $result['updated'] ?? $result['updated_to_enrolled'] ?? $result['existing'] ?? 0,
                $result['deleted'] ?? $result['updated_to_dropped'] ?? 0,
                $result['insertedRows'] ?? [],
                $result['updatedRows'] ?? $result['updatedToEnrolledRows'] ?? $result['existingRows'] ?? [],
                $result['deletedRows'] ?? $result['updatedToDroppedRows'] ?? []
            );
        }

        // Save log file
        file_put_contents($logFile, json_encode($response["logs"], JSON_PRETTY_PRINT));
        chmod($logFile, 0777);

        // Save to Sync_Logs table
        if (isset($_SESSION['user'])) {
            $username = $_SESSION['user'];
            $filename = basename($logFile);

            $stmt = $pdo->prepare("INSERT INTO Sync_Logs (user, filename, academicYear) VALUES (:user, :filename, :year)");
            $stmt->execute([
                ':user' => $username,
                ':filename' => $filename,
                ':year' => $academicYear
            ]);
        }


        // Store for caller
        $response["logFilePath"] = $logFile;
        $response["message"] = "All synchronizations completed successfully!";
    } catch (Exception $e) {
        return [
            "success" => false,
            "message" => "Error during synchronization: " . $e->getMessage()
        ];
    }

    return $response;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['syncAll']) && $_POST['syncAll'] === 'true') {
    require_once '../database/dbConnection.php';
    $config = require __DIR__ . '/../config.php';

    $response = runFullSynchronization($pdo, $config['log_dir']);
    echo json_encode($response);
    exit;
}


?>
