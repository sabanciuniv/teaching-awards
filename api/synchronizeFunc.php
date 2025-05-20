<?php
require_once 'commonFunc.php';
init_session();
ini_set('memory_limit', '512M'); 
ini_set('max_execution_time', 1200); // 20 minutes


$config = require __DIR__ . '/../config.php';
require_once '../database/dbConnection.php';

header('Content-Type: application/json');


//2024 yılındaki öğrencileri al, hepsinde parametre olarak academic year gelsin
//sync Students
//kriter: akademik yıl  202402 öğrencileri gelecek
function synchronizeStudents(PDO $pdo, int $yearID): array {
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

        // Get Academic_year value (2024) for the provided YearID
        $academicYear = getAcademicYearFromID($pdo, $yearID);
        if (!$academicYear) {
            return ['status' => 'error', 'message' => 'Invalid YearID provided.'];
        }
        
        $targetTermCode = $academicYear . '02'; //202402

        // Load API students
        $stmt = $pdo->prepare("SELECT * FROM API_STUDENTS WHERE TERM_CODE = ?");
        $stmt->execute([$targetTermCode]);
        $apiStudents = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['STU_CLASS_CODE'] = $classMapping[$row['STU_CLASS_CODE']] ?? 'Unknown';
            $fullName = trim(
                $row['STU_FIRST_NAME'] .
                ($row['STU_MI_NAME'] ? ' ' . $row['STU_MI_NAME'] : '') .
                ' ' . $row['STU_LAST_NAME']
            );
            
            // Map to Student_Table columns format
            $apiStudentData = [
                'StudentID'         => $row['STU_ID'],
                'YearID'            => $yearID, // Use the input $yearID consistently
                'StudentFullName'   => $fullName,
                'SuNET_Username'    => $row['STU_USERNAME'] ?: null,
                'Mail'              => $row['STU_EMAIL'] ?: null,
                'Class'             => $row['STU_CLASS_CODE'] ?? null, // Use mapped value
                'Faculty'           => $row['STU_FACULTY_CODE'] ?? null,
                'Department'        => $row['STU_PROGRAM_CODE'] ?? null,
                'CGPA'              => $row['STU_CUM_GPA_SU'] !== null ? (float) $row['STU_CUM_GPA_SU'] : null,
            ];

            // Use StudentID as key for easy lookup
            $apiStudents[$row['STU_ID']] = $apiStudentData;
        }

        //BUNU DENE ******
        $stmt->closeCursor();


        // Existing students
        $stmt = $pdo->prepare("SELECT id, StudentID, StudentFullName, SuNET_Username, Mail, Class, Faculty, Department, CGPA, YearID
                               FROM Student_Table
                               WHERE YearID = ?");
        $stmt->execute([$yearID]);

        $existingStudents = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingStudents[$row['StudentID']] = $row;
        }
        $stmt->closeCursor(); //BUNU DENE******

        $insertStmt = $pdo->prepare("INSERT INTO Student_Table 
            (StudentID, YearID, StudentFullName, SuNET_Username, Mail, Class, Faculty, Department, CGPA, Sync_Date) 
            VALUES (:StudentID, :YearID, :StudentFullName, :SuNET_Username, :Mail, :Class, :Faculty, :Department, :CGPA, NOW())");

        // Update statement specific to the StudentID AND YearID
        $updateStmt = $pdo->prepare("UPDATE Student_Table SET
            StudentFullName = :StudentFullName,
            SuNET_Username = :SuNET_Username,
            Mail = :Mail,
            Class = :Class,
            Faculty = :Faculty,
            Department = :Department,
            CGPA = :CGPA,
            Sync_Date = NOW()
            WHERE StudentID = :StudentID AND YearID = :YearID"); // More specific WHERE clause



        // --- Process Inserts and Updates ---
        $processedApiStudentIDs = []; // Keep track of StudentIDs from the API for this term

        foreach ($apiStudents as $stu_id => $studentData) {
             $processedApiStudentIDs[] = $stu_id; // Add to list of students found in API for this term

            if (isset($existingStudents[$stu_id])) {
                // --- Update Existing Student ---
                $existing = $existingStudents[$stu_id];
                $needsUpdate = false;

                if (
                    $existing['StudentFullName'] !== $studentData['StudentFullName'] ||
                    $existing['SuNET_Username'] !== $studentData['SuNET_Username'] ||
                    $existing['Mail'] !== $studentData['Mail'] ||
                    $existing['Class'] !== $studentData['Class'] ||
                    $existing['Faculty'] !== $studentData['Faculty'] ||
                    $existing['Department'] !== $studentData['Department'] ||
                    abs((float)($existing['CGPA'] ?? 0) - (float)($studentData['CGPA'] ?? 0)) > 0.001 
                ) {
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    // Bind only the necessary values for the update
                    $updateData = [
                        ':StudentID' => $stu_id,
                        ':YearID' => $yearID, // The specific YearID we are working with
                        ':StudentFullName' => $studentData['StudentFullName'],
                        ':SuNET_Username' => $studentData['SuNET_Username'],
                        ':Mail' => $studentData['Mail'],
                        ':Class' => $studentData['Class'],
                        ':Faculty' => $studentData['Faculty'],
                        ':Department' => $studentData['Department'],
                        ':CGPA' => $studentData['CGPA'],
                    ];
                    $updateStmt->execute($updateData);

                    if ($updateStmt->rowCount() > 0) {
                        $response['updated']++;
                        // Add the clean updated data to the response
                        $response['updatedRows'][] = [
                            'StudentID' => $stu_id,
                            'YearID' => $yearID,
                            'StudentFullName' => $studentData['StudentFullName'],
                            'SuNET_Username' => $studentData['SuNET_Username'],
                            'Mail' => $studentData['Mail'],
                            'Class' => $studentData['Class'],
                            'Faculty' => $studentData['Faculty'],
                            'Department' => $studentData['Department'],
                            'CGPA' => $studentData['CGPA']
                        ];
                    }
                }

            } else {
                // --- Insert New Student ---
                $insertData = [
                    ':StudentID' => $stu_id,
                    ':YearID' => $yearID, // Use the specific yearID
                    ':StudentFullName' => $studentData['StudentFullName'],
                    ':SuNET_Username' => $studentData['SuNET_Username'],
                    ':Mail' => $studentData['Mail'],
                    ':Class' => $studentData['Class'],
                    ':Faculty' => $studentData['Faculty'],
                    ':Department' => $studentData['Department'],
                    ':CGPA' => $studentData['CGPA']
                ];
                $insertStmt->execute($insertData);

                if ($insertStmt->rowCount() > 0) {
                    $response['inserted']++;
                     // Add the clean inserted data to the response
                    $response['insertedRows'][] = [
                        'StudentID' => $stu_id,
                        'YearID' => $yearID,
                        'StudentFullName' => $studentData['StudentFullName'],
                        'SuNET_Username' => $studentData['SuNET_Username'],
                        'Mail' => $studentData['Mail'],
                        'Class' => $studentData['Class'],
                        'Faculty' => $studentData['Faculty'],
                        'Department' => $studentData['Department'],
                        'CGPA' => $studentData['CGPA']
                    ];
                }
            }
        }
        // Clean up prepared statements
        $insertStmt->closeCursor();
        $updateStmt->closeCursor();


        // --- Process Deletions ---
        $studentIDsToDelete = [];
        $studentInternalIDsToDelete = []; 

        $sql = "";
        $params = [];

        if (!empty($processedApiStudentIDs)) {
            // Find existing students for this year NOT in the API list
            $placeholders = implode(',', array_fill(0, count($processedApiStudentIDs), '?'));
            $sql = "SELECT id, StudentID FROM Student_Table WHERE YearID = ? AND StudentID NOT IN ($placeholders)";
            $params = array_merge([$yearID], $processedApiStudentIDs);
        } else {
             $sql = "SELECT id, StudentID FROM Student_Table WHERE YearID = ?";
             $params = [$yearID];
        }

        if (!empty($sql)) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $studentsToDeleteResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!empty($studentsToDeleteResult)) {
                $studentIDsToDelete = array_column($studentsToDeleteResult, 'StudentID');
                $studentInternalIDsToDelete = array_column($studentsToDeleteResult, 'id'); 
            }
        }


        if (!empty($studentIDsToDelete)) {
            // --- Fetch full info for deleted rows response ---
            $placeholders = implode(',', array_fill(0, count($studentIDsToDelete), '?'));
            $sql = "SELECT StudentID, YearID, StudentFullName, SuNET_Username, Mail, Class, Faculty, Department, CGPA, Sync_Date
                    FROM Student_Table WHERE YearID = ? AND StudentID IN ($placeholders)";
            $params = array_merge([$yearID], $studentIDsToDelete);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $response['deletedRows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();


            // --- Delete from related tables (using internal IDs) ---
            if (!empty($studentInternalIDsToDelete)) {
                 $placeholders = implode(',', array_fill(0, count($studentInternalIDsToDelete), '?'));

                 $stmt = $pdo->prepare("DELETE FROM Votes_Table WHERE VoterID IN ($placeholders)");
                 $stmt->execute($studentInternalIDsToDelete);
                 $stmt->closeCursor();

                 $stmt = $pdo->prepare("DELETE FROM Student_Course_Relation WHERE `student.id` IN ($placeholders)");
                 $stmt->execute($studentInternalIDsToDelete);
                 $stmt->closeCursor();
            }

            // --- Delete from Student_Table itself ---
            $placeholders = implode(',', array_fill(0, count($studentIDsToDelete), '?'));
            $sql = "DELETE FROM Student_Table WHERE YearID = ? AND StudentID IN ($placeholders)";
            $params = array_merge([$yearID], $studentIDsToDelete);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $response['deleted'] = $stmt->rowCount();
            $stmt->closeCursor();
        }

        // --- Return Success Response ---
        return array_merge(['status' => 'success'], $response);

    } catch (PDOException $e) {
        error_log("Database Error in synchronizeStudents: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        return [
            'status' => 'error',
            'message' => 'A database error occurred during synchronization.'
        ];
    } catch (Exception $e) {
        error_log("General Error in synchronizeStudents: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        return [
            'status' => 'error',
            'message' => 'An unexpected error occurred during synchronization.'
        ];
    }
}





//sync Courses
//parametrik yıl 2024 year ID direkt girilsin
// dönemler 202401,202402,202301,202302
//yeardID den year bilgisine ulaş
function synchronizeCourses(PDO $pdo, int $targetYearID): array {
    $response = [
        'inserted' => 0,
        'updated' => 0,
        'deleted' => 0,
        'insertedRows' => [],
        'updatedRows' => [],
        'deletedRows' => []
    ];

    try {
        // MY: yeardID den year bilgisine ulaş 
        $academicYearString = getAcademicYearFromID($pdo, $targetYearID);
        if (!$academicYearString) {
            return ['status' => 'error', 'message' => "Academic year string not found for YearID: {$targetYearID}."];
        }

        $currentYearForTerms = (int)$academicYearString; 
        $previousYearForTerms = $currentYearForTerms - 1;

        // MY: dönemler şunlar olmalı: e.g. targetYearID maps to 2024 -> 202401, 202402, 202301, 202302
        $validTerms = [
            $previousYearForTerms . '01',
            $previousYearForTerms . '02',
            $currentYearForTerms . '01',
            $currentYearForTerms . '02'
        ];

        // Load API courses, filtered by valid terms
        // MY: validTerms değerleri ile kriter belirtilmeli
        $placeholdersForTerms = implode(',', array_fill(0, count($validTerms), '?'));
        $sqlApiCourses = "SELECT TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, CRSE_TITLE 
                        FROM API_COURSES 
                        WHERE TERM_CODE IN ({$placeholdersForTerms})";

        $stmtApi = $pdo->prepare($sqlApiCourses);
        $stmtApi->execute($validTerms);

        $apiCourses = [];
        while ($row = $stmtApi->fetch(PDO::FETCH_ASSOC)) {
            // Ensure key components are not null to prevent issues
            $crn = $row['CRN'] ?? 'UNKNOWN_CRN';
            $termCode = $row['TERM_CODE'] ?? 'UNKNOWN_TERM';
            $subjCode = $row['SUBJ_CODE'] ?? 'UNKNOWN_SUBJ';
            $crseNumb = $row['CRSE_NUMB'] ?? 'UNKNOWN_CRSE';
            
            $key = $crn . '_' . $termCode . '_' . $subjCode . '_' . $crseNumb;
            $apiCourses[$key] = $row;
        }

        // Load DB courses for the target YearID and valid terms
        $sqlDbCourses = "SELECT * 
                         FROM Courses_Table 
                         WHERE YearID = ? AND Term IN ({$placeholdersForTerms})";
        $stmtDb = $pdo->prepare($sqlDbCourses);
        $dbParams = array_merge([$targetYearID], $validTerms);
        $stmtDb->execute($dbParams);

        $existingCourses = [];
        while ($row = $stmtDb->fetch(PDO::FETCH_ASSOC)) {
            $crn = $row['CRN'] ?? 'UNKNOWN_CRN';
            $termCode = $row['Term'] ?? 'UNKNOWN_TERM';
            $subjCode = $row['Subject_Code'] ?? 'UNKNOWN_SUBJ';
            $crseNumb = $row['Course_Number'] ?? 'UNKNOWN_CRSE';

            $key = $crn . '_' . $termCode . '_' . $subjCode . '_' . $crseNumb;
            $existingCourses[$key] = $row;
        }


        // Prepare SQL statements
        $insertStmt = $pdo->prepare("INSERT INTO Courses_Table 
            (CourseName, Subject_Code, Course_Number, Section, CRN, Term, Sync_Date, YearID) 
            VALUES (:CourseName, :Subject_Code, :Course_Number, :Section, :CRN, :Term, NOW(), :YearID)");

        // Update only non-key fields. YearID is part of the WHERE to the update.
        $updateStmt = $pdo->prepare("UPDATE Courses_Table 
            SET CourseName = :CourseName, Section = :Section, Sync_Date = NOW() 
            WHERE Subject_Code = :Subject_Code AND Course_Number = :Course_Number AND 
                  CRN = :CRN AND Term = :Term AND YearID = :YearID");
        
        // MY: yearID de ekle (YearID is included in the WHERE clause for delete)
        $deleteStmt = $pdo->prepare("DELETE FROM Courses_Table 
            WHERE Subject_Code = :Subject_Code AND Course_Number = :Course_Number AND 
                  CRN = :CRN AND Term = :Term AND YearID = :YearID");

        // Process API courses: Insert or Update
        foreach ($apiCourses as $compositeKey => $apiCourseData) {
            // Ensure required fields have default values if null from API
            $apiCourseData['CRSE_TITLE'] = $apiCourseData['CRSE_TITLE'] ?? 'Unknown Course';
            $apiCourseData['SUBJ_CODE'] = $apiCourseData['SUBJ_CODE'] ?? 'N/A';
            $apiCourseData['CRSE_NUMB'] = $apiCourseData['CRSE_NUMB'] ?? '000';
            $apiCourseData['SEQ_NUMB'] = $apiCourseData['SEQ_NUMB'] ?? '';
            $apiCourseData['CRN'] = $apiCourseData['CRN'] ?? 'N/A_CRN';
            $apiCourseData['TERM_CODE'] = $apiCourseData['TERM_CODE'] ?? 'N/A_TERM';

            if (!in_array($apiCourseData['TERM_CODE'], $validTerms)) {
                continue;
            }

            $courseRecord = [
                'CourseName'     => $apiCourseData['CRSE_TITLE'],
                'Subject_Code'   => $apiCourseData['SUBJ_CODE'],
                'Course_Number'  => $apiCourseData['CRSE_NUMB'],
                'Section'        => $apiCourseData['SEQ_NUMB'],
                'CRN'            => $apiCourseData['CRN'],
                'Term'           => $apiCourseData['TERM_CODE'],
                'YearID'         => $targetYearID 
            ];

            if (isset($existingCourses[$compositeKey])) {
                $dbCourse = $existingCourses[$compositeKey];
                // Check if mutable fields have changed
                if (
                    $dbCourse['CourseName'] !== $apiCourseData['CRSE_TITLE'] ||
                    $dbCourse['Section'] !== $apiCourseData['SEQ_NUMB']
                ) {
                    $updateStmt->execute($courseRecord);
                    if ($updateStmt->rowCount() > 0) {
                        $response['updated']++;
                        $response['updatedRows'][] = $courseRecord;
                    }
                }
                unset($existingCourses[$compositeKey]);
            } else {
                $insertStmt->execute($courseRecord);
                if ($insertStmt->rowCount() > 0) {
                    $response['inserted']++;
                    $response['insertedRows'][] = $courseRecord;
                }
            }
        }

        // Delete missing ones:
        foreach ($existingCourses as $key => $dbCourseToDelete) {

            $deleteCourseID = $dbCourseToDelete['CourseID'];

            // First, remove from Candidate_Course_Relation
            $stmtCleanupCandidates = $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CourseID = ?");
            $stmtCleanupCandidates->execute([$deleteCourseID]);

            // Then remove from Student_Course_Relation
            $stmtCleanupStudents = $pdo->prepare("DELETE FROM Student_Course_Relation WHERE CourseID = ?");
            $stmtCleanupStudents->execute([$deleteCourseID]);
            // Construct params for delete statement from the $dbCourseToDelete
            $deleteParams = [
                ':Subject_Code'  => $dbCourseToDelete['Subject_Code'],
                ':Course_Number' => $dbCourseToDelete['Course_Number'],
                ':CRN'           => $dbCourseToDelete['CRN'],
                ':Term'          => $dbCourseToDelete['Term'],
                ':YearID'        => $dbCourseToDelete['YearID'] // targetYearID
            ];



            $deleteStmt->execute($deleteParams);
            if ($deleteStmt->rowCount() > 0) {
                $response['deleted']++;
                $response['deletedRows'][] = [ // Log the deleted course data
                    'CourseName'     => $dbCourseToDelete['CourseName'],
                    'Subject_Code'   => $dbCourseToDelete['Subject_Code'],
                    'Course_Number'  => $dbCourseToDelete['Course_Number'],
                    'Section'        => $dbCourseToDelete['Section'],
                    'CRN'            => $dbCourseToDelete['CRN'],
                    'Term'           => $dbCourseToDelete['Term'],
                    'YearID'         => $dbCourseToDelete['YearID']
                ];
            }
        }

        return array_merge(['status' => 'success'], $response);

    } catch (PDOException $e) { 
        return [
            'status' => 'error',
            'message' => "Database Error: " . $e->getMessage(),
        ];
    } catch (Exception $e) { 
        return [
            'status' => 'error',
            'message' => "General Error: " . $e->getMessage(),
        ];
    }
}


// sync student-course enrollment
//yearID parametre
//son 4 dönem

//enrollemnt status ta dropped yerini koy
function synchronizeStudentCourses(PDO $pdo, int $yearID): array {
    $response = [
        'inserted' => 0,
        'updated_to_enrolled' => 0,
        'updated_to_dropped' => 0,
        'insertedRows' => [],
        'updatedToEnrolledRows' => [],
        'updatedToDroppedRows' => []
    ];

    try {
        // MY: Get academic year string from yearID
        $academicYear = getAcademicYearFromID($pdo, $yearID);
        if (!$academicYear) {
            return ['status' => 'error', 'message' => 'Invalid academic year ID.'];
        }

        $currentYear = (int)$academicYear;
        $previousYear = $currentYear - 1;

        // Fetch all students with the given yearID, including their YearID
        $stmtStudents = $pdo->prepare("SELECT id, StudentID, YearID FROM Student_Table WHERE YearID = ?");
        $stmtStudents->execute([$yearID]);
        $studentsMap = []; 
        while ($row = $stmtStudents->fetch(PDO::FETCH_ASSOC)) {
            $studentsMap[$row['StudentID']] = [
                'student_id' => $row['id'],   
                'year_id' => $row['YearID']   // YearID of the student
            ];
        }
        $stmtStudents->closeCursor();

        // Fetch valid courses for the given YearID
        $stmtCourses = $pdo->prepare("SELECT CourseID, CRN, Term, YearID FROM Courses_Table WHERE YearID = ?");
        $stmtCourses->execute([$yearID]);
        $coursesMap = []; // Key: "CRN_Term", Value: internal 'CourseID'
        while ($row = $stmtCourses->fetch(PDO::FETCH_ASSOC)) {
            $key = trim($row['CRN']) . '_' . trim($row['Term']);
            $coursesMap[$key] = [
                'course_id' => $row['CourseID'],
                'year_id' => $row['YearID']   // YearID of the course
            ];
        }
        $stmtCourses->closeCursor();

        // Define valid terms based on the academic year derived from yearID
        $validTerms = [
            $previousYear . '01', $previousYear . '02',  // Last year's terms
            $currentYear . '01', $currentYear . '02'     // Current year's terms
        ];

        // Load student-course enrollments from API_STUDENT_COURSES
        $stmtApi = $pdo->query("SELECT STU_ID, CRN, TERM_CODE FROM API_STUDENT_COURSES WHERE STU_ID IS NOT NULL AND CRN IS NOT NULL AND TERM_CODE IS NOT NULL");
        $apiRows = $stmtApi->fetchAll(PDO::FETCH_ASSOC);
        $stmtApi->closeCursor();

        // Build a set of current enrollments from the API data
        $apiEnrollments = []; 
        foreach ($apiRows as $apiRow) {
            $apiStuID = trim($apiRow['STU_ID']);
            $apiCRN = trim($apiRow['CRN']);
            $apiTermCode = trim($apiRow['TERM_CODE']);

            if (!in_array($apiTermCode, $validTerms)) {
                continue;
            }

            $courseMapKey = $apiCRN . '_' . $apiTermCode;


            if (isset($studentsMap[$apiStuID]) && isset($coursesMap[$courseMapKey])) {
                // Check YearID match between student and course
                $studentYearID = $studentsMap[$apiStuID]['year_id'];
                $courseYearID = $coursesMap[$courseMapKey]['year_id'];

                if ($studentYearID === $courseYearID) {
                    $studentInternalId = $studentsMap[$apiStuID]['student_id'];
                    $courseInternalId = $coursesMap[$courseMapKey]['course_id'];

                    $relationKey = $studentInternalId . "_" . $courseInternalId;
                    $apiEnrollments[$relationKey] = [
                        'student_id' => $studentInternalId, 
                        'course_id' => $courseInternalId,   
                        'Stu_ID_external' => $apiStuID,     
                        'CRN_external' => $apiCRN,          
                        'Term_external' => $apiTermCode    
                    ];
                }
            }
        }

        // Fetch existing relations from Student_Course_Relation for the current YearID
        $stmtExisting = $pdo->prepare("
            SELECT scr.`student.id`, scr.CourseID, scr.EnrollmentStatus
            FROM Student_Course_Relation scr
            INNER JOIN Student_Table s ON scr.`student.id` = s.id
            INNER JOIN Courses_Table c ON scr.CourseID = c.CourseID
            WHERE s.YearID = :yearID AND c.YearID = :yearID
        ");
        $stmtExisting->execute([':yearID' => $yearID]);
        $existingRelations = [];
        while ($row = $stmtExisting->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['student.id'] . "_" . $row['CourseID'];
            $existingRelations[$key] = $row['EnrollmentStatus'];
        }
        $stmtExisting->closeCursor();

        // Prepare database statements
        $insertStmt = $pdo->prepare("INSERT INTO Student_Course_Relation (`student.id`, CourseID, EnrollmentStatus) VALUES (:student_id, :course_id, 'enrolled')");
        $updateToEnrolledStmt = $pdo->prepare("UPDATE Student_Course_Relation SET EnrollmentStatus = 'enrolled' WHERE `student.id` = :student_id AND CourseID = :course_id");
        $updateToDroppedStmt = $pdo->prepare("UPDATE Student_Course_Relation SET EnrollmentStatus = 'dropped' WHERE `student.id` = :student_id AND CourseID = :course_id");

        // insert new ones or update 'dropped' ones to 'enrolled'
        foreach ($apiEnrollments as $relationKey => $data) {
            if (!isset($existingRelations[$relationKey])) {
                // New enrollment found in API
                $insertStmt->execute([
                    ':student_id' => $data['student_id'],
                    ':course_id' => $data['course_id']
                ]);
                if ($insertStmt->rowCount() > 0) {
                    $response['inserted']++;
                    $response['insertedRows'][] = $data; 
                }
            } elseif ($existingRelations[$relationKey] === 'dropped') {
                // Existing enrollment was 'dropped', but now found in API
                $updateToEnrolledStmt->execute([ 
                    ':student_id' => $data['student_id'],
                    ':course_id' => $data['course_id']
                ]);
                if ($updateToEnrolledStmt->rowCount() > 0) {
                    $response['updated_to_enrolled']++;
                    $response['updatedToEnrolledRows'][] = $data; 
                }
            }
            // If it exists and is already 'enrolled', do nothing.
        }

        foreach ($existingRelations as $relationKey => $status) {
            if (!isset($apiEnrollments[$relationKey]) && $status !== 'dropped') {
                list($studentInternalIdToDrop, $courseInternalIdToDrop) = explode('_', $relationKey);
                $updateToDroppedStmt->execute([
                    ':student_id' => $studentInternalIdToDrop,
                    ':course_id' => $courseInternalIdToDrop
                ]);
                if ($updateToDroppedStmt->rowCount() > 0) {
                    $response['updated_to_dropped']++;
                    $response['updatedToDroppedRows'][] = [
                        'student_id' => $studentInternalIdToDrop,
                        'course_id' => $courseInternalIdToDrop   
                    ];
                }
            }
        }

        // Close cursors for prepared statements
        $insertStmt->closeCursor();
        $updateToEnrolledStmt->closeCursor();
        $updateToDroppedStmt->closeCursor();

        return array_merge(['status' => 'success'], $response);

    } catch (PDOException $e) {
        error_log("Database Error in synchronizeStudentCourses (YearID: {$yearID}): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        return [
            'status' => 'error',
            'message' => 'A database error occurred: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("General Error in synchronizeStudentCourses (YearID: {$yearID}): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        return [
            'status' => 'error',
            'message' => 'An unexpected error occurred: ' . $e->getMessage()
        ];
    }
}


//sync student and their categories
//fonksiyon ismini updateStudentCategories diye değişitr
//parametre olacak yearID
function updateStudentCategories(PDO $pdo, int $yearID): array {
    $response = [
        'inserted' => 0,
        'existing' => 0,
        'deleted' => 0,
        'insertedRows' => [],
        'existingRows' => [],
        'deletedRows' => []
    ];

    try {        
        // Get current and previous year
        $currentYearRow = fetchCurrentAcademicYear($pdo);
        if (!$currentYearRow) {
            return ['status' => 'error', 'message' => 'Current academic year not found.'];
        }

        $currentYear = (int)$currentYearRow['Academic_year'];
        $validYears = [$yearID]; // Use the current sync year only

        // Fetch existing student-category relations
        //sadece şu an olanları değişitr
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

        //  Get Category Map
        $stmt = $pdo->query("SELECT CategoryID, CategoryCode FROM Category_Table");
        $categoryMap = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categoryMap[(int)$row['CategoryID']] = $row['CategoryCode'];
        }

        $stmt = $pdo->prepare("
            SELECT
            s.id           AS student_id,
            s.StudentID,
            s.Class,
            s.CGPA,
            CONCAT(c.Subject_Code, ' ', c.Course_Number) AS full_code,
            api.CREDIT_HR_LOW
            FROM `Student_Course_Relation` AS scr
            JOIN `Courses_Table`            AS c
            ON scr.CourseID    = c.CourseID
            JOIN `API_COURSES`               api   -- no AS here
            ON api.TERM_CODE   = c.`Term`      -- quote Term in case it’s reserved
            AND api.SUBJ_CODE   = c.Subject_Code
            AND api.CRSE_NUMB   = c.Course_Number
            AND api.CRN         = c.CRN
            JOIN `Student_Table`            AS s
            ON scr.id  = s.StudentID
            WHERE s.YearID = ?
        ");

        //YEAR ID OLAYINI DÜZELT
        $stmt->execute([$yearID]);

        $desiredRelations = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $studentID = $row['StudentID'];
            $student_id = (int)$row['student_id'];
            $class = $row['Class'];
            $cgpa = (float)$row['CGPA'];
            $code = $row['full_code'];
            $creditHrLow  = (int)$row['CREDIT_HR_LOW']; 

            $categoryID = null;

            if (in_array($code, ['TLL 101', 'TLL 102', 'AL 102','SPS 101D', 'SPS 102D']) && $class === 'Freshman') {
                $categoryID = 1;
            } elseif (in_array($code, ['SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192']) && $class === 'Freshman') {
                $categoryID = 2;
            } elseif (in_array($code, ['ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004'])) {
                $categoryID = 4;
            } elseif (in_array($code, ['CIP 101N', 'IF 100R', 'MATH 101R', 'MATH 102R', 'NS 101R', 'NS 102R', 'SPS 101D', 'SPS 102D']) && $class === 'Freshman') {
                $categoryID = 5;
            } elseif (!in_array($code, ['TLL 101', 'TLL 102', 'AL 102',
                                       'SPS 101', 'SPS 102', 'MATH 101', 'MATH 102','MATH 101R', 'MATH 102R',
                                       'CIP 101N','NS 101R', 'NS 102R', 'SPS 101D', 'SPS 102D',
                                       'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192',
                                       'ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004']) && $cgpa >= 2 && $class === 'Senior' && $creditHrLow > 0 ) {
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

        //Prepare insert and delete statements
        $insertStmt = $pdo->prepare("INSERT INTO Student_Category_Relation (student_id, categoryID) VALUES (:student_id, :categoryID)");
        $deleteStmt = $pdo->prepare("DELETE FROM Student_Category_Relation WHERE student_id = :student_id AND categoryID = :categoryID");

        //Insert missing
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
                        unset($studentRow['id']); 

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

        // Delete outdated
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
//parametre yearID
//geçmiş yılın datasına dokunma
//
function synchronizeCandidates(PDO $pdo, int $yearID): array {
    $response = [
        'inserted' => 0,
        'updated' => 0,
        'deleted' => 0,
        'insertedRows' => [],
        'updatedRows' => [],
        'deletedRows' => []
    ];

    try {
        $statusMapping = [
            'Active' => 'Etkin',
            'Inactive' => 'İşten ayrıldı',
            'Terminated' => 'İşten ayrıldı',
            'İşten ayrıldı' => 'İşten ayrıldı' // Handles if status is already mapped
        ];

        $candidatesApiData = []; 
        

        $academicYear = getAcademicYearFromID($pdo, $yearID);
        if (!$academicYear) {
            return ['status' => 'error', 'message' => 'Invalid yearID or academic year not found.'];
        }
        
        //$validTermCodes = [$academicYear . '01', $academicYear . '02'];
        $previousAcademicYear = $academicYear - 1;
        $validTermCodes = [
            $previousAcademicYear . '01',
            $previousAcademicYear . '02',
            $academicYear . '01',
            $academicYear . '02'
        ];


        // Build valid SU_IDs from TERM_CODE filtering
        $validSuIdsFromTerms = [];

        $stmtApiTerms = $pdo->query("SELECT DISTINCT TA_ID, TERM_CODE FROM API_TAS WHERE TA_ID IS NOT NULL");
        while ($row = $stmtApiTerms->fetch(PDO::FETCH_ASSOC)) {
            if (in_array($row['TERM_CODE'], $validTermCodes)) {
                $validSuIdsFromTerms[$row['TA_ID']] = 'TA';
            }
        }
        $stmtApiTerms->closeCursor();

        $stmtApiTerms = $pdo->query("SELECT DISTINCT INST_ID, TERM_CODE FROM API_INSTRUCTORS WHERE INST_ID IS NOT NULL");
        while ($row = $stmtApiTerms->fetch(PDO::FETCH_ASSOC)) {
            if (in_array($row['TERM_CODE'], $validTermCodes)) {
                $validSuIdsFromTerms[$row['INST_ID']] = 'Instructor';
            }
        }
        $stmtApiTerms->closeCursor();

        // Fetch TA data
        $stmtTas = $pdo->query("SELECT TA_ID, TA_FIRST_NAME, TA_MI_NAME, TA_LAST_NAME, TA_EMAIL, EMPL_STATUS FROM API_TAS WHERE TA_ID IS NOT NULL");
        while ($row = $stmtTas->fetch(PDO::FETCH_ASSOC)) {
            $su_id = $row['TA_ID'];

            if (!isset($validSuIdsFromTerms[$su_id]) || $validSuIdsFromTerms[$su_id] !== 'TA') continue;

            $fullName = trim(
                $row['TA_FIRST_NAME'] .
                ($row['TA_MI_NAME'] ? ' ' . $row['TA_MI_NAME'] : '') .
                ' ' . $row['TA_LAST_NAME']
            );
            $status = $statusMapping[$row['EMPL_STATUS']] ?? 'Etkin';

            $candidatesApiData[$su_id] = [
                'SU_ID' => $su_id,
                'Name' => $fullName,
                'Mail' => $row['TA_EMAIL'] ?: null,
                'Role' => 'TA',
                'Status' => $status
            ];
        }
        $stmtTas->closeCursor();

        // Fetch Instructor data
        $stmtInst = $pdo->query("SELECT INST_ID, INST_FIRST_NAME, INST_MI_NAME, INST_LAST_NAME, INST_EMAIL, EMPL_STATUS FROM API_INSTRUCTORS WHERE INST_ID IS NOT NULL");
        while ($row = $stmtInst->fetch(PDO::FETCH_ASSOC)) {
            $su_id = $row['INST_ID'];
            // Only consider Instructors active in the valid terms
            if (!isset($validSuIdsFromTerms[$su_id]) || $validSuIdsFromTerms[$su_id] !== 'Instructor') continue;

            $fullName = trim(
                $row['INST_FIRST_NAME'] .
                ($row['INST_MI_NAME'] ? ' ' . $row['INST_MI_NAME'] : '') .
                ' ' . $row['INST_LAST_NAME']
            );
            $status = $statusMapping[$row['EMPL_STATUS']] ?? 'Etkin';

            if (isset($candidatesApiData[$su_id]) && $candidatesApiData[$su_id]['Role'] === 'TA') {
                 // 
            }
            $candidatesApiData[$su_id] = [
                'SU_ID' => $su_id,
                'Name' => $fullName,
                'Mail' => $row['INST_EMAIL'] ?: null,
                'Role' => 'Instructor',
                'Status' => $status
            ];
        }
        $stmtInst->closeCursor();

        // Handle Exceptions
        $exceptionStmt = $pdo->query("SELECT CandidateID FROM Exception_Table"); 
        $exceptionCandidateInternalIDs = $exceptionStmt->fetchAll(PDO::FETCH_COLUMN);
        $exceptionStmt->closeCursor();
        
        $exceptionSUIds = [];
        if (!empty($exceptionCandidateInternalIDs)) {
            // Ensure IDs are integers
            $safeExceptionInternalIDs = array_map('intval', $exceptionCandidateInternalIDs);
            $placeholders = implode(',', array_fill(0, count($safeExceptionInternalIDs), '?'));
            
            $stmtEx = $pdo->prepare("SELECT SU_ID FROM Candidate_Table WHERE id IN ($placeholders)");
            $stmtEx->execute($safeExceptionInternalIDs);
            while ($row = $stmtEx->fetch(PDO::FETCH_ASSOC)) {
                $exceptionSUIds[$row['SU_ID']] = true; 
            }
            $stmtEx->closeCursor();
        }

        // Override status for exceptions based on SU_ID
        foreach ($candidatesApiData as $su_id => &$candidateData) { 
            if (isset($exceptionSUIds[$su_id])) {
                $candidateData['Status'] = 'İşten ayrıldı';
            }
        }
        unset($candidateData); // Break reference

        // Fetch existing candidates from DB for the current YearID
        $stmtExisting = $pdo->prepare("SELECT id, SU_ID, Name, Mail, Role, Status FROM Candidate_Table WHERE YearID = ?");
        $stmtExisting->execute([$yearID]);        
        $existingCandidatesDb = []; 
        $existingCandidateInternalIdMap = []; // SU_ID, maps to internal DB 'id'
        while ($row = $stmtExisting->fetch(PDO::FETCH_ASSOC)) {
            $existingCandidatesDb[$row['SU_ID']] = $row;
            $existingCandidateInternalIdMap[$row['SU_ID']] = $row['id'];
        }
        $stmtExisting->closeCursor();

        // Prepare queries
        $insertStmt = $pdo->prepare("INSERT INTO Candidate_Table (SU_ID, Name, Mail, Role, Status, Sync_Date, YearID) 
            VALUES (:SU_ID, :Name, :Mail, :Role, :Status, NOW(), :YearID)");        

        $updateStmt = $pdo->prepare("UPDATE Candidate_Table SET 
            Name = :Name, Mail = :Mail, Role = :Role, Status = :Status, Sync_Date = NOW() 
            WHERE SU_ID = :SU_ID AND YearID = :YearID"); // Matched YearID in WHERE

        $processedSuIdsFromApi = [];

        foreach ($candidatesApiData as $su_id_api => $candidateData) {
            $processedSuIdsFromApi[] = $su_id_api; //  SU_IDs present in API for this $yearID's terms

            $params = [
                ':SU_ID'    => $candidateData['SU_ID'], 
                ':Name'     => $candidateData['Name'],
                ':Mail'     => $candidateData['Mail'],
                ':Role'     => $candidateData['Role'],
                ':Status'   => $candidateData['Status'],
                ':YearID'   => $yearID 
            ];

            if (isset($existingCandidatesDb[$su_id_api])) {
                // --- Update Existing Candidate ---
                $existing = $existingCandidatesDb[$su_id_api];
                $needsUpdate = false;
                $changes = [];

                if ($existing['Name'] !== $candidateData['Name']) { $needsUpdate = true; $changes['Name'] = ['old' => $existing['Name'], 'new' => $candidateData['Name']];}
                if ($existing['Mail'] !== $candidateData['Mail']) { $needsUpdate = true; $changes['Mail'] = ['old' => $existing['Mail'], 'new' => $candidateData['Mail']];}
                if ($existing['Role'] !== $candidateData['Role']) { $needsUpdate = true; $changes['Role'] = ['old' => $existing['Role'], 'new' => $candidateData['Role']];}
                if ($existing['Status'] !== $candidateData['Status']) { $needsUpdate = true; $changes['Status'] = ['old' => $existing['Status'], 'new' => $candidateData['Status']];}

                if ($needsUpdate) {
                    $updateStmt->execute($params); 
                    if ($updateStmt->rowCount() > 0) {
                        $response['updated']++;
                        $response['updatedRows'][] = [
                            'SU_ID' => $su_id_api,
                            'changes' => $changes
                        ];
                    }
                }
            } else {
                // --- Insert New Candidate ---
                $insertStmt->execute($params); // Uses all keys from $params
                if ($insertStmt->rowCount() > 0) {
                    $response['inserted']++;
                    $response['insertedRows'][] = $candidateData; // Log the full inserted candidate data
                }
            }
        }
        $insertStmt->closeCursor();
        $updateStmt->closeCursor();

        // --- Process Deletions ---
        // Find SU_IDs in the DB (for this YearID) that were NOT processed API data
        $existingSuIdsInDbForYear = array_keys($existingCandidatesDb);
        $suIdsToDelete = array_diff($existingSuIdsInDbForYear, $processedSuIdsFromApi);

        if (!empty($suIdsToDelete)) {
            $internalIdsToDelete = [];
            foreach($suIdsToDelete as $su_id_del) {
                if (isset($existingCandidateInternalIdMap[$su_id_del])) {
                    $internalIdsToDelete[] = $existingCandidateInternalIdMap[$su_id_del];
                }
            }

            if (!empty($internalIdsToDelete)) {
                $placeholdersForDelete = implode(',', array_fill(0, count($internalIdsToDelete), '?'));

                // Log deleted rows' info BEFORE actual deletion
                $stmtFetchDeleted = $pdo->prepare("SELECT SU_ID, Name, Mail, Role, Status FROM Candidate_Table WHERE id IN ($placeholdersForDelete)");
                $stmtFetchDeleted->execute($internalIdsToDelete);
                $deletedRowsData = $stmtFetchDeleted->fetchAll(PDO::FETCH_ASSOC);
                foreach($deletedRowsData as $deletedRow){
                    $response['deletedRows'][] = array_merge($deletedRow, ['reason' => 'Not found in current API data for this YearID context or marked for deletion']);
                }
                $stmtFetchDeleted->closeCursor();


                $stmtDelCCR = $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CandidateID IN ($placeholdersForDelete)");
                $stmtDelCCR->execute($internalIdsToDelete);
                $stmtDelCCR->closeCursor();

                $stmtDelEx = $pdo->prepare("DELETE FROM Exception_Table WHERE CandidateID IN ($placeholdersForDelete)"); // Assuming CandidateID links to Candidate_Table.id
                $stmtDelEx->execute($internalIdsToDelete);
                $stmtDelEx->closeCursor();

                $stmtDelCand = $pdo->prepare("DELETE FROM Candidate_Table WHERE id IN ($placeholdersForDelete)");
                $stmtDelCand->execute($internalIdsToDelete);
                $response['deleted'] += $stmtDelCand->rowCount();
                $stmtDelCand->closeCursor();
            }
        }

        return array_merge(['status' => 'success'], $response);

    } catch (PDOException $e) {
        // Catch specific PDO exceptions for database errors
        error_log("Database Error in synchronizeCandidates (YearID: {$yearID}): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        return [
            'status' => 'error',
            'message' => 'A database error occurred during candidate synchronization. Check logs. HY093 might originate in getAcademicYearFromID.',
        ];
    } catch (Exception $e) {
        // Catch general exceptions
        error_log("General Error in synchronizeCandidates (YearID: {$yearID}): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        return [
            'status' => 'error',
            'message' => 'An unexpected error occurred during candidate synchronization. Check logs.',
        ];
    }
}




//sync Candidate Course Relation
function synchronizeCandidateCourses(PDO $pdo, int $targetInternalYearID): array { 
    $response = [
        'status' => 'success',
        'inserted' => 0,
        'updated' => 0,
        'deleted' => 0,
        'insertedRows' => [],
        'updatedRows' => [],
        'deletedRows' => [],
        'messages' => []
    ];
    $processedRelationKeysInThisRun = [];


    $mapCategoryID = function($subject, $course, $role, $status) {
        $full = strtoupper(trim($subject)) . ' ' . strtoupper(trim($course));
        if ($role === 'Instructor' && $status === 'Etkin') {
            if (in_array($full, ['TLL 101', 'TLL 102', 'AL 102','SPS 101D', 'SPS 102D'])) return '1';
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
        //Get the "Academic_year" string for the targetInternalYearID
        $academicYearStringForTargetID = getAcademicYearFromID($pdo, $targetInternalYearID);
        $academicYearInt = (int) $academicYearStringForTargetID; // e.g., 2023
        $validTermPrefixes = [$academicYearInt, $academicYearInt - 1];

        if ($academicYearStringForTargetID === null) {
            return ['status' => 'error', 'message' => "Academic year string not found for YearID: {$targetInternalYearID}."];
        }

        //Extract the year from this string (e.g., 2023 from "2023-2024")
        $calendarYearToMatchAPI = null;
        if (preg_match('/^(\d{4})/', $academicYearStringForTargetID, $matches)) {
            $calendarYearToMatchAPI = (int)$matches[1];
        }
        if ($calendarYearToMatchAPI === null) {
            return ['status' => 'error', 'message' => "Could not parse calendar year from '{$academicYearStringForTargetID}' for YearID: {$targetInternalYearID}."];
        }


        // Load Courses FOR THE SPECIFIC $targetInternalYearID
        $stmtCourses = $pdo->prepare("SELECT CourseID, Term, Subject_Code, Course_Number, CRN, CourseName FROM Courses_Table WHERE YearID = ?");
        $stmtCourses->execute([$targetInternalYearID]);
        $courses = [];
        while ($row = $stmtCourses->fetch(PDO::FETCH_ASSOC)) {
            $key = strtoupper(trim($row['Term'])) . '_' .
                   strtoupper(trim($row['Subject_Code'])) . '_' .
                   strtoupper(trim($row['Course_Number'])) . '_' .
                   strtoupper(trim($row['CRN']));
            $courses[$key] = $row;
        }

        // Load Candidates FOR THE SPECIFIC $targetInternalYearID
        $stmtCandidates = $pdo->prepare("SELECT id, SU_ID, Role, Status FROM Candidate_Table WHERE YearID = ?");
        $stmtCandidates->execute([$targetInternalYearID]);
        $candidates = [];
        while ($row = $stmtCandidates->fetch(PDO::FETCH_ASSOC)) {
            $candidates[strtoupper(trim($row['SU_ID']))] = $row;
        }


        // Load Existing Relations 
        $stmtExistingRelations = $pdo->query("SELECT CandidateID, CourseID FROM Candidate_Course_Relation");
        $existingRelationsDB = [];
        while ($row = $stmtExistingRelations->fetch(PDO::FETCH_ASSOC)) {
            $existingRelationsDB["{$row['CandidateID']}_{$row['CourseID']}"] = true;
        }

        $insertStmt = $pdo->prepare("INSERT INTO Candidate_Course_Relation (CourseID, CandidateID, Academic_Year, CategoryID, Term)
            VALUES (:CourseID, :CandidateID, :Academic_Year, :CategoryID, :Term)");

        // Update statement from your original script 
        $updateStmt = $pdo->prepare("
            UPDATE Candidate_Course_Relation
            SET Academic_Year = :Academic_Year, CategoryID = :CategoryID, Term = :Term
            WHERE CandidateID = :CandidateID AND CourseID = :CourseID
              AND ( Academic_Year != :Academic_Year OR CategoryID != :CategoryID OR Term != :Term OR
                    Academic_Year IS NULL OR CategoryID IS NULL OR Term IS NULL )");


        $validKeysForDeletionCheck = [];

        $sources = ['API_INSTRUCTORS' => 'INST_ID', 'API_TAS' => 'TA_ID'];
        foreach ($sources as $apiTable => $idField) {
            $apiStmt = $pdo->prepare("
                SELECT TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, {$idField} AS SU_ID
                FROM {$apiTable}
                WHERE {$idField} IS NOT NULL
            ");
            $apiStmt->execute();



            while ($apiRow = $apiStmt->fetch(PDO::FETCH_ASSOC)) {
                $termFromAPI = strtoupper(trim($apiRow['TERM_CODE']));
                $crnFromAPI = strtoupper(trim($apiRow['CRN']));
                $suIdFromAPI = strtoupper(trim($apiRow['SU_ID']));
                $subjectFromAPI = strtoupper(trim($apiRow['SUBJ_CODE']));
                $courseNumFromAPI = strtoupper(trim($apiRow['CRSE_NUMB']));

                $courseKey = "{$termFromAPI}_{$subjectFromAPI}_{$courseNumFromAPI}_{$crnFromAPI}";
                $courseData = $courses[$courseKey] ?? null;

                $candidateData = $candidates[$suIdFromAPI] ?? null;

                if (!$courseData || !$candidateData) {
                    continue;
                }

                $currentRelationKey = "{$candidateData['id']}_{$courseData['CourseID']}";

                if (isset($processedRelationKeysInThisRun[$currentRelationKey])) {
                    $validKeysForDeletionCheck[$currentRelationKey] = true;
                    continue;
                }
                $processedRelationKeysInThisRun[$currentRelationKey] = true;
                $validKeysForDeletionCheck[$currentRelationKey] = true;

                $categoryID = $mapCategoryID($subjectFromAPI, $courseNumFromAPI, $candidateData['Role'], $candidateData['Status']);
                if (!$categoryID) continue;

                // Extract the 4-digit year prefix from TERM_CODE
                $yearPrefix = (int)substr($termFromAPI, 0, 4);

                // Apply custom filter logic
                if ($categoryID === '3') {
                    // Allow current year and previous year
                    if ($yearPrefix !== $academicYearInt && $yearPrefix !== ($academicYearInt - 1)) continue;
                } else {
                    // Allow only current year
                    if ($yearPrefix !== $academicYearInt) continue;
                }
                if (!$categoryID) continue;

                $logRow = [
                    'SU_ID' => $candidateData['SU_ID'],
                    'CandidateID' => $candidateData['id'],
                    'Role' => $candidateData['Role'],
                    'Status' => $candidateData['Status'],
                    'CourseID' => $courseData['CourseID'],
                    'Subject_Code' => $subjectFromAPI,
                    'Course_Number' => $courseNumFromAPI,
                    'CRN' => $crnFromAPI,
                    'Term' => $termFromAPI,
                    'CategoryID' => $categoryID
                ];
                

                $params = [
                    ':CourseID' => $courseData['CourseID'],
                    ':CandidateID' => $candidateData['id'],
                    // IMPORTANT: Store the $targetInternalYearID in Candidate_Course_Relation.Academic_Year
                    ':Academic_Year' => $targetInternalYearID,
                    ':CategoryID' => $categoryID,
                    ':Term' => $termFromAPI
                ];

                if (!isset($existingRelationsDB[$currentRelationKey])) {
                    if ($insertStmt->execute($params)) {
                        $response['inserted']++;
                        $response['insertedRows'][] = $logRow;
                    }
                } else {
                    if ($updateStmt->execute($params)) {
                        if ($updateStmt->rowCount() > 0) {
                            $response['updated']++;
                            $response['updatedRows'][] = $logRow;
                        }
                    } 
                }
            }
        }

        // --- Deletion Logic ---
        $stmtResignedCandidates = $pdo->prepare(
            "SELECT id, SU_ID FROM Candidate_Table WHERE Status = 'İşten ayrıldı' AND YearID = ?" // Filter by targetInternalYearID
        );
        $stmtResignedCandidates->execute([$targetInternalYearID]);
        $resignedCandidatesData = $stmtResignedCandidates->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($resignedCandidatesData)) {
            $deleteResignedStmt = $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CandidateID = :CandidateID AND Academic_Year = :AcademicYear"); // Also ensure we only delete for the target year context
            foreach ($resignedCandidatesData as $resignedCand) {
                // We don't need to fetch courses to delete anymore if we specify Academic_Year in DELETE
                $deleteResignedParams = [':CandidateID' => $resignedCand['id'], ':AcademicYear' => $targetInternalYearID];
                if ($deleteResignedStmt->execute($deleteResignedParams)) {
                    $deletedCount = $deleteResignedStmt->rowCount();
                    if ($deletedCount > 0) {
                        $response['deleted'] += $deletedCount;
                        $response['deletedRows'][] = [
                            'SU_ID' => $resignedCand['SU_ID'],
                            'CandidateID_internal' => $resignedCand['id'],
                            'reason' => 'Candidate status İşten ayrıldı for AcademicYear ' . $targetInternalYearID . ' (deleted ' . $deletedCount . ' relations)'
                        ];
                    }
                } 
            }
        }

        $stmtOrphanCheck = $pdo->prepare("
            SELECT ccr.CandidateCourseID, ccr.CandidateID, ccr.CourseID, ct.SU_ID, ccr.Term
            FROM Candidate_Course_Relation ccr
            JOIN Candidate_Table ct ON ct.id = ccr.CandidateID
            WHERE ccr.Academic_Year = ?  -- Filter by the internal YearID
        ");
        $stmtOrphanCheck->execute([$targetInternalYearID]);

        $deleteOrphanStmt = $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CandidateCourseID = :CandidateCourseID");

        foreach ($stmtOrphanCheck->fetchAll(PDO::FETCH_ASSOC) as $existingRelInDB) {
            $relationKeyFromDB = "{$existingRelInDB['CandidateID']}_{$existingRelInDB['CourseID']}";

            if (!isset($validKeysForDeletionCheck[$relationKeyFromDB])) {
                if ($deleteOrphanStmt->execute([':CandidateCourseID' => $existingRelInDB['CandidateCourseID']])) {
                    $response['deleted']++;
                    $response['deletedRows'][] = [
                        'SU_ID' => $existingRelInDB['SU_ID'],
                        'CourseID' => $existingRelInDB['CourseID'],
                        'reason' => 'Relation for Term ' . $existingRelInDB['Term'] . ' (YearID ' . $targetInternalYearID . ') not found.'
                    ];
                } 
            }
        }


        if (empty($response['messages'])) unset($response['messages']);
        return $response;

    } catch (PDOException | Exception $e) { // Catch both PDO and general exceptions
        return [
            'status' => 'error', 'message' => $e->getMessage(),
            'error_details' => ($e instanceof PDOException) ? $e->errorInfo : $e->getTraceAsString(),
            'inserted' => $response['inserted'], 'updated' => $response['updated'], 'deleted' => $response['deleted']
        ];
    }
}

//parametre default year için çalışcak
function runFullSynchronization(PDO $pdo, string $logDirBase): array {
//kontorl vote date--> burda error yaz kaçak olmasın, sunucu tarafında kontrol lazım

    $response = [
        "success" => true,
        "logs" => [],
        "message" => "",
        "logFilePath" => ""
    ];

    try {

        // Load academic year from DB
        $academicYearData = fetchCurrentAcademicYear($pdo);
        if (!$academicYearData) {
            return [
                "success" => false,
                "message" => "Unable to determine current academic year."
            ];
        }
    
        $academicYear = $academicYearData['Academic_year'];
        $yearID = (int) $academicYearData['YearID'];
        $startDate = new DateTime($academicYearData['Start_date_time']);
        $endDate = new DateTime($academicYearData['End_date_time']);
        $today = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
    
        // Prevent sync during voting period
        if ($today >= $startDate && $today <= $endDate) {
            return [
                "success" => false,
                "message" => "Synchronization is disabled during active voting period."
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
    
        // List of sync operations
        $syncTasks = [
            ["Courses", "synchronizeCourses"],
            ["Students", "synchronizeStudents"],
            ["Candidates", "synchronizeCandidates"],
            ["Student Courses Relation", "synchronizeStudentCourses"],
            ["Candidate Courses Relation", "synchronizeCandidateCourses"],
            ["Student Category Relation", "updateStudentCategories"]
        ];

        foreach ($syncTasks as [$section, $func]) {
            $result = $func($pdo, $yearID);
            if ($result['status'] === 'error') {
                throw new Exception("[$section] " . $result['message']);
            }

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

        // Save to Sync_Logs DB
        if (isset($_SESSION['user'])) {
            $stmt = $pdo->prepare("INSERT INTO Sync_Logs (user, filename, academicYear, ip_address) VALUES (:user, :filename, :year, :ip_address)");
            $stmt->execute([
                ':user' => $_SESSION['user'],
                ':filename' => basename($logFile),
                ':year' => $academicYear,
                ':ip_address' => getClientIP()
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