<?php
// rel-20250515

$config = require __DIR__ . '/../config.php';
require_once '../database/dbConnection.php';
require_once 'commonFunc.php';

// init_session();


// enforceAdminAccess($pdo);




ini_set('max_execution_time', 20*60);

function syncCourses($acadYear){

    global $pdo,$config;

    $apiKey = $config['sis_api_key']; 

    $insertCount=0;
    $errCount=0;
    $terms=[];

    $pdo->beginTransaction();
    // clear target table
    // Delete existing records from API_COURSES
    $stmt = $pdo->prepare("DELETE FROM API_COURSES");
    $stmt->execute();

    $prevAcadYear=$acadYear-1;

    foreach([$acadYear.'01', $acadYear.'02', $prevAcadYear.'01', $prevAcadYear.'02'] as $term){

        $terms[]=$term;
        
        $json = file_get_contents('https://suis.sabanciuniv.edu/prod/sabanci.sis_to_teaching_awards.courses?apikey='.$apiKey.'&term='.$term);
        $arr = json_decode($json,1);

        if(is_array($arr) && count($arr)>0){

            // Prepare insert statement
            $insertStmt = $pdo->prepare("INSERT INTO api_courses (TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, SCHD_CODE, GMOD_CODE, PTRM_CODE, CRSE_TITLE, CREDIT_HR_LOW,GRADABLE_IND) VALUES(:TERM_CODE, :CRN, :SUBJ_CODE, :CRSE_NUMB, :SEQ_NUMB, :SCHD_CODE, :GMOD_CODE, :PTRM_CODE, :CRSE_TITLE, :CREDIT_HR_LOW,:GRADABLE_IND);");
            
            // Insert each course
            foreach($arr as $row) {
                try {

                    $insertStmt->execute([
                        ':TERM_CODE' => $row['TERM_CODE'],
                        ':CRN' => $row['CRN'],
                        ':SUBJ_CODE' => $row['SUBJ_CODE'],
                        ':CRSE_NUMB' => $row['CRSE_NUMB'], 
                        ':SEQ_NUMB' => $row['SEQ_NUMB'],
                        ':SCHD_CODE' => $row['SCHD_CODE'],
                        ':GMOD_CODE' => $row['GMOD_CODE'],
                        ':PTRM_CODE' => $row['PTRM_CODE'],
                        ':CRSE_TITLE' => $row['TITLE'],
                        ':CREDIT_HR_LOW' => $row['CREDIT_HR_LOW'],
                        ':GRADABLE_IND' => $row['GRADABLE_IND']
                    ]);

                    $insertCount++;

                } catch(PDOException $e) {
                    $errCount++;
                    error_log("Error inserting instructors: " . $e->getMessage());
                    print_r([$e,$row]);
                    $pdo->rollBack();
                    die('Sync Error (line:'.__LINE__.')');

                    continue;
                }
            } // foreach row

        } // is valid response
        else{
            error_log("Error retrieving data: " . __FUNCTION__.":".$term);
            $pdo->rollBack();
            die('Sync Error (line:'.__LINE__.')');
        }


    } // foreach terms

    $pdo->commit(); 
    //$pdo->rollBack();

    echo "\r\nCOURSES Sync: ";
    echo join(',',$terms).  ' terms transferred; ';
    echo $insertCount." rows inserted; ";
    echo $errCount." rows error;";    
}

function syncCoursesInstructors($acadYear){

    global $pdo,$config;

    $apiKey = $config['sis_api_key']; 

    $insertCount=0;
    $errCount=0;
    $terms=[];

    $pdo->beginTransaction();

    // clear target table
    // Delete existing records from API_INSTRUCTORS
    $stmt = $pdo->prepare("DELETE FROM API_INSTRUCTORS");
    $stmt->execute();
    
    $prevAcadYear=$acadYear-1;

    foreach([$acadYear.'01', $acadYear.'02', $prevAcadYear.'01', $prevAcadYear.'02'] as $term){

        $terms[]=$term; 
    
        $json = file_get_contents('https://suis.sabanciuniv.edu/prod/sabanci.sis_to_teaching_awards.courses_instructors?apikey='.$apiKey.'&term='.$term);
        $arr = json_decode($json,1);
    
        if(is_array($arr) && count($arr)>0){
    
            // Prepare insert statement
            $insertStmt = $pdo->prepare("INSERT INTO api_instructors (TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, PRIMARY_IND, INST_PERCENT_RESPONSE, INST_ID, INST_FIRST_NAME, INST_MI_NAME, INST_LAST_NAME, INST_USERNAME, INST_EMAIL, EMPL_GROUPCODE, HOMEDEPT_CODE, EMPL_STATUS, LASTWORK_DATE) VALUES(:TERM_CODE, :CRN, :SUBJ_CODE, :CRSE_NUMB, :SEQ_NUMB, :PRIMARY_IND, :INST_PERCENT_RESPONSE, :INST_ID, :INST_FIRST_NAME, :INST_MI_NAME, :INST_LAST_NAME, :INST_USERNAME, :INST_EMAIL, :EMPL_GROUPCODE, :HOMEDEPT_CODE, :EMPL_STATUS, :LASTWORK_DATE);");
                            // Insert each course
            foreach($arr as $row) {
                try {
    
                    $insertStmt->execute([
                        ':TERM_CODE' => $row['TERM_CODE'],
                        ':CRN' => $row['CRN'],
                        ':SUBJ_CODE' => $row['SUBJ_CODE'], 
                        ':CRSE_NUMB' => $row['CRSE_NUMB'],
                        ':SEQ_NUMB' => $row['SEQ_NUMB'],
                        ':PRIMARY_IND' => $row['PRIMARY_IND'],
                        ':INST_PERCENT_RESPONSE' => $row['INST_PERCENT_RESPONSE'],
                        ':INST_ID' => $row['INST_ID'],
                        ':INST_FIRST_NAME' => $row['INST_FIRST_NAME'],
                        ':INST_MI_NAME' => $row['INST_MI_NAME'],
                        ':INST_LAST_NAME' => $row['INST_LAST_NAME'],
                        ':INST_USERNAME' => $row['INST_USERNAME'],
                        ':INST_EMAIL' => $row['INST_EMAIL'],
                        ':EMPL_GROUPCODE' => $row['EMPL_GROUPCODE'],
                        ':HOMEDEPT_CODE' => $row['HOMEDEPT_CODE'],
                        ':EMPL_STATUS' => $row['EMPL_STATUS'],
                        ':LASTWORK_DATE' => $row['LASTWORK_DATE']
                    ]);
    
                    $insertCount++;
    
                } catch(PDOException $e) {
                    $errCount++;
                    error_log("Error inserting instructors: " . $e->getMessage());
                    print_r([$e,$row]);
                    $pdo->rollBack();
                    die('Sync Error (line:'.__LINE__.')');

                    continue;
                }
            }



        }// if valid response
        else{
            error_log("Error retrieving data: " . __FUNCTION__.":".$term);
            $pdo->rollBack();
            die('Sync Error (line:'.__LINE__.')');
        }



    } // foreach terms

    $pdo->commit(); 
    //$pdo->rollBack();

    echo "\r\nCOURSES-INSTRUCTORS Sync: ";
    echo join(',',$terms).  ' terms transferred; ';
    echo $insertCount." rows inserted; ";
    echo $errCount." rows error;";    
}


function syncStudents($acadYear){
    global $pdo,$config;

    $apiKey = $config['sis_api_key']; 

    $insertCount=0;
    $errCount=0;
    $terms=[];

    $pdo->beginTransaction();

    // clear target table
    // Delete existing records from API_INSTRUCTORS
    $stmt = $pdo->prepare("DELETE FROM API_STUDENTS");
    $stmt->execute();

    $prevAcadYear=$acadYear-1;

    foreach([$acadYear.'02'] as $term){

        $terms[]=$term; 

        
        $json = file_get_contents('https://suis.sabanciuniv.edu/prod/sabanci.sis_to_teaching_awards.students?apikey='.$apiKey.'&term='.$term);
        $arr = json_decode($json,1);

        if(is_array($arr) && count($arr)>0){


            // Prepare insert statement
            $insertStmt = $pdo->prepare("INSERT INTO api_students (TERM_CODE, STU_ID, STU_FIRST_NAME, STU_MI_NAME, STU_LAST_NAME, STU_USERNAME, STU_EMAIL, STU_STS_CODE, STU_STS_TERM_CODE_EFF, STU_CUM_GPA_SU_TERM, STU_CUM_GPA_SU, STU_CUM_GPA_SU_ELIGIBLE,STU_CLASS_CODE, STU_FACULTY_CODE, STU_PROGRAM_CODE) VALUES(:TERM_CODE, :STU_ID, :STU_FIRST_NAME, :STU_MI_NAME, :STU_LAST_NAME, :STU_USERNAME, :STU_EMAIL, :STU_STS_CODE, :STU_STS_TERM_CODE_EFF, :STU_CUM_GPA_SU_TERM, :STU_CUM_GPA_SU,:STU_CUM_GPA_SU_ELIGIBLE, :STU_CLASS_CODE, :STU_FACULTY_CODE, :STU_PROGRAM_CODE);");

            // Insert each course
            foreach($arr as $row) {
                try {

                    $insertStmt->execute([
                        ':TERM_CODE' => $row['TERM_CODE'],
                        ':STU_ID' => $row['STU_ID'], 
                        ':STU_FIRST_NAME' => $row['STU_FIRST_NAME'],
                        ':STU_MI_NAME' => $row['STU_MI_NAME'],
                        ':STU_LAST_NAME' => $row['STU_LAST_NAME'],
                        ':STU_USERNAME' => $row['STU_USERNAME'],
                        ':STU_EMAIL' => $row['STU_EMAIL'],
                        ':STU_STS_CODE' => $row['STU_STS_CODE'],
                        ':STU_STS_TERM_CODE_EFF' => $row['STU_STS_TERM_CODE_EFF'],
                        ':STU_CUM_GPA_SU_TERM' => $row['STU_CUM_GPA_SU_TERM'],
                        ':STU_CUM_GPA_SU' => $row['STU_CUM_GPA_SU'],
                        ':STU_CUM_GPA_SU_ELIGIBLE' => $row['STU_CUM_GPA_SU_ELIGIBLE'],
                        ':STU_CLASS_CODE' => $row['STU_CLASS_CODE'],
                        ':STU_FACULTY_CODE' => $row['STU_FACULTY_CODE'],
                        ':STU_PROGRAM_CODE' => $row['STU_PROGRAM_CODE']
                    ]);

                    $insertCount++;

                } catch(PDOException $e) {
                    $errCount++;
                    error_log("Error inserting instructors: " . $e->getMessage());
                    print_r($e);
                    $pdo->rollBack();
                    die('Sync Error (line:'.__LINE__.')');

                    continue;
                }
            }

        }// if valid data
        else{
            error_log("Error retrieving data: " . __FUNCTION__.":".$term);
            $pdo->rollBack();
            die('Sync Error (line:'.__LINE__.')');
        }


    } // foreach terms

    $pdo->commit(); 
    //$pdo->rollBack();

    echo "\r\nSTUDENTS Sync: ";
    echo join(',',$terms).  ' terms transferred; ';
    echo $insertCount." rows inserted; ";
    echo $errCount." rows error;";   

}



function syncCoursesStudents($acadYear){


    global $pdo,$config;

    $apiKey = $config['sis_api_key'];
    
    $insertCount=0;
    $errCount=0;
    $terms=[];

    $pdo->beginTransaction();

    // clear target table
    // Delete existing records from API_INSTRUCTORS
    $stmt = $pdo->prepare("DELETE FROM API_STUDENT_COURSES");
    $stmt->execute();

    $prevAcadYear=$acadYear-1;

    foreach([$acadYear.'01', $acadYear.'02', $prevAcadYear.'01', $prevAcadYear.'02'] as $term){

        $terms[]=$term; 

        
        $json = file_get_contents('https://suis.sabanciuniv.edu/prod/sabanci.sis_to_teaching_awards.courses_students?apikey='.$apiKey.'&term='.$term);
        $arr = json_decode($json,1);

        if(is_array($arr) && count($arr)>0){


            // Prepare insert statement
            $insertStmt = $pdo->prepare("INSERT INTO api_student_courses (TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, STU_ID) VALUES(:TERM_CODE, :CRN, :SUBJ_CODE, :CRSE_NUMB, :SEQ_NUMB, :STU_ID);");
                        // Insert each course
            foreach($arr as $row) {
                try {

                    $insertStmt->execute([
                        ':TERM_CODE' => $row['TERM_CODE'],
                        ':CRN' => $row['CRN'], 
                        ':SUBJ_CODE' => $row['SUBJ_CODE'], 
                        ':CRSE_NUMB' => $row['CRSE_NUMB'], 
                        ':SEQ_NUMB' => $row['SEQ_NUMB'], 
                        ':STU_ID' => $row['STU_ID'], 
                    ]);

                    $insertCount++;

                } catch(PDOException $e) {
                    $errCount++;
                    error_log("Error inserting instructors: " . $e->getMessage());
                    print_r($e);
                    $pdo->rollBack();
                    die('Sync Error (line:'.__LINE__.')');

                    continue;
                }
            }

        }// if valid data
        else{
            error_log("Error retrieving data: " . __FUNCTION__.":".$term);
            $pdo->rollBack();
            die('Sync Error (line:'.__LINE__.')');
        }

    }


    $pdo->commit(); 
    //$pdo->rollBack();

    echo "\r\nCOURSES-STUDENTS Sync: ";
    echo join(',',$terms).  ' terms transferred; ';
    echo $insertCount." rows inserted; ";
    echo $errCount." rows error;";     

}

function syncTAs($acadYear){

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    if($acadYear<'2024'){
        return syncTAsLegacy($acadYear);        
    }

    global $pdo,$config;
        
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM API_TAS");
    $stmt->execute();

    $insertCount=0;
    $errCount=0;
    $terms=[];

    foreach([$acadYear.'01', $acadYear.'02'] as $term){
        // $term = '202402';
        $url = "https://apps.sabanciuniv.edu/grad/ta_assignment/service/service.php?term=".$term;

        $terms[]=$term;
            
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $config['ta_assignment_secret']);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        
        $json_file = curl_exec($ch);
        curl_close($ch);  
        
        $arr= json_decode($json_file,true);

        if(is_array($arr) && count($arr)>0){

            // clear target table
            // Delete existing records from API_INSTRUCTORS

            // Prepare insert statement
            $insertStmt = $pdo->prepare("INSERT INTO api_tas (TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, TA_PERCENT_RESPONSE, TA_ID, TA_FIRST_NAME, TA_LAST_NAME, TA_USERNAME, TA_EMAIL, EMPL_GROUPCODE, LASTWORK_DATE, EMPL_STATUS) VALUES(:TERM_CODE, :CRN, :SUBJ_CODE, :CRSE_NUMB, :SEQ_NUMB, :TA_PERCENT_RESPONSE, :TA_ID, :TA_FIRST_NAME, :TA_LAST_NAME, :TA_USERNAME, :TA_EMAIL, :EMPL_GROUPCODE, :LASTWORK_DATE, :EMPL_STATUS);");
            
            $sampleSrcSchema=json_decode('    {
                "course_term": "202401",
                "course_crn": "11048",
                "subj_code": "SPS",
                "course_numb": "303",
                "course_section": "B8",
                "schd_desc": "Discussion",
                "course_title": "Law and Ethics",
                "primary_instructor_id": "00001547",
                "primary_instructor": "Etrit Shkreli",
                "ta_student_id": "00034657",
                "ta_username": "duygudeniz",
                "ta_firstname": "Duygu Deniz",
                "ta_lastname": "Baş",
                "ta_email": "duygudeniz@sabanciuniv.edu",
                "ta_type": "TA",
                "ta_update_time": "2024-09-23 17:20:05"
                }',true);

            // Load Term Courses
            $crsSelect = $pdo->prepare("select * from api_courses where TERM_CODE=?;");
            $crnMap=[];
            $crsSelect->execute([$term]);
            $courses = $crsSelect->fetchAll(PDO::FETCH_ASSOC);
            foreach($courses as $row){
                $crnMap[$row['CRN']]=$row;
            }
    

            // Insert each course
            foreach($arr as $row) {
                try {

                    if($row['ta_type']=='LA'){
                        continue;
                    }


                    if(array_key_exists($row['course_crn'],$crnMap)){
                        $crseNumb=$crnMap[$row['course_crn']]['CRSE_NUMB'];
                    }else{
                        $crseNumb=$row['course_numb'];
                        echo '***Not found in courses**: '.$row['course_term'].':'.$row['course_crn'].'='.$row['subj_code'].$row['course_numb'].'***';
                    }


                    $insertStmt->execute([
                        ':TERM_CODE' => $row['course_term'],
                        ':CRN' => $row['course_crn'],
                        ':SUBJ_CODE' => $row['subj_code'],
                        ':CRSE_NUMB' => $crseNumb, // $row['course_numb'],
                        ':SEQ_NUMB' => $row['course_section'],
                        ':TA_PERCENT_RESPONSE' => 100,
                        ':TA_ID' => $row['ta_student_id'],
                        ':TA_FIRST_NAME' => $row['ta_firstname'],
                        ':TA_LAST_NAME' => $row['ta_lastname'],
                        ':TA_USERNAME' => $row['ta_username'],
                        ':TA_EMAIL' => $row['ta_email'],
                        ':EMPL_GROUPCODE' => $row['ta_type'],
                        ':EMPL_STATUS' => 'Etkin',
                        ':LASTWORK_DATE' => $row['ta_update_time'],
                    ]);

                    $insertCount++;

                } catch(PDOException $e) {
                    $errCount++;
                    error_log("Error inserting instructors: " . $e->getMessage());
                    print_r($e);
                    $pdo->rollBack();
                    die('Sync Error (line:'.__LINE__.')');

                    continue;
                }// try

            } // foreach row in arr

        }// is valid json response
        else{
            error_log("Error retrieving data: " . __FUNCTION__.":".$term);
            $pdo->rollBack();
            die('Sync Error (line:'.__LINE__.')');
        }


    }/// term loop


    $pdo->commit(); 
    //$pdo->rollBack();

    echo "\r\nTA Sync: ";
    echo join(',',$terms).  ' terms transferred; ';
    echo $insertCount." rows inserted; ";
    echo $errCount." rows error;";    


}

function syncTAsLegacy($acadYear){

    if($acadYear>='2024'){
        die(__FUNCTION__.' line:'.__LINE__.' => cannot be called for 202401 term and further.');
    }

    global $pdo,$config;

    $apiKey = $config['sis_api_key'];

    $insertCount=0;
    $errCount=0;
    $terms=[];

    $pdo->beginTransaction();

    // clear target table
    // Delete existing records from API_TAS
    $stmt = $pdo->prepare("DELETE FROM API_TAS");
    $stmt->execute();
    
    $prevAcadYear=$acadYear-1;

    foreach([$acadYear.'01', $acadYear.'02'] as $term){

        $terms[]=$term;
    
        $json = file_get_contents('https://suis.sabanciuniv.edu/prod/sabanci.sis_to_teaching_awards.courses_tas?apikey='.$apiKey.'&term='.$term);
        $arr = json_decode($json,1);
    
        if(is_array($arr) && count($arr)>0){
    
            // Prepare insert statement
            $insertStmt = $pdo->prepare("INSERT INTO api_tas (TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, PRIMARY_IND, TA_PERCENT_RESPONSE, TA_ID, TA_FIRST_NAME, TA_MI_NAME, TA_LAST_NAME, TA_USERNAME, TA_EMAIL, EMPL_GROUPCODE) VALUES(:TERM_CODE, :CRN, :SUBJ_CODE, :CRSE_NUMB, :SEQ_NUMB, :PRIMARY_IND, :TA_PERCENT_RESPONSE, :TA_ID, :TA_FIRST_NAME, :TA_MI_NAME, :TA_LAST_NAME, :TA_USERNAME, :TA_EMAIL, :EMPL_GROUPCODE);");
                            // Insert each course
            foreach($arr as $row) {
                try {
    
                    $insertStmt->execute([
                        ':TERM_CODE' => $row['TERM_CODE'],
                        ':CRN' => $row['CRN'],
                        ':SUBJ_CODE' => $row['SUBJ_CODE'], 
                        ':CRSE_NUMB' => $row['CRSE_NUMB'],
                        ':SEQ_NUMB' => $row['SEQ_NUMB'],
                        ':PRIMARY_IND' => $row['PRIMARY_IND'],
                        ':TA_PERCENT_RESPONSE' => $row['TA_PERCENT_RESPONSE'],
                        ':TA_ID' => $row['TA_ID'],
                        ':TA_FIRST_NAME' => $row['TA_FIRST_NAME'],
                        ':TA_MI_NAME' => $row['TA_MI_NAME'],
                        ':TA_LAST_NAME' => $row['TA_LAST_NAME'],
                        ':TA_USERNAME' => $row['TA_USERNAME'],
                        ':TA_EMAIL' => $row['TA_EMAIL'],
                        ':EMPL_GROUPCODE' => 'TA',
                    ]);
    
                    $insertCount++;
    
                } catch(PDOException $e) {
                    $errCount++;
                    error_log("Error inserting instructors: " . $e->getMessage());
                    print_r([$e,$row]);
                    $pdo->rollBack();
                    die('Sync Error (line:'.__LINE__.')');

                    continue;
                }
            }

        }// if valid data
        else{
            error_log("Error retrieving data: " . __FUNCTION__.":".$term);
            $pdo->rollBack();
            die('Sync Error (line:'.__LINE__.')');
        }


    } // foreach terms

    $pdo->commit(); 
    //$pdo->rollBack();

    echo "\r\nCOURSES-TAs-Legacy(SiS) Sync: ";
    echo join(',',$terms).  ' terms transferred; ';
    echo $insertCount." rows inserted; ";
    echo $errCount." rows error;"; 


}



$acadYearRow = fetchCurrentAcademicYear($pdo);
$acadYear = $acadYearRow['Academic_year'];

// $acadYear='2024';

// syncTAs($acadYear); die('ok');

$startTime=time();

echo "<pre>";
echo "\r\nSync Started For Acad Year:".$acadYear;

syncCourses($acadYear);
syncCoursesInstructors($acadYear);
syncStudents($acadYear);
syncCoursesStudents($acadYear);
syncTAs($acadYear);
echo "\r\n-- FIN --";
$elapsedTime=time()-$startTime;
echo "\r\n".$elapsedTime." seconds elapsed.";
echo "</pre>";

