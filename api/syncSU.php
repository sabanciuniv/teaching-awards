<?php

// Fill Candidates and Students - 20250526

```




-- CONNECTION: name=teaching_awards_dev


-- show variables where Variable_name like '%colla%'
/*
 * 
 * 
set names utf8mb4 collate utf8mb4_unicode_ci;
set names utf8mb4 collate utf8mb4_general_ci;

select CONCAT('alter table ',TABLE_SCHEMA,'.', TABLE_NAME,' charset=utf8mb4 collate utf8mb4_general_ci;') from information_schema.TABLES WHERE TABLE_NAME in ('api_tas','api_courses','api_students','api_student_courses','api_instructors') ;
select CONCAT('alter table ',TABLE_SCHEMA,'.',TABLE_NAME,' modify column ', COLUMN_NAME,' ',DATA_TYPE,'(',CHARACTER_MAXIMUM_LENGTH,')  character set utf8mb4 collate utf8mb4_general_ci;') from information_schema.COLUMNS WHERE table_schema like 'teaching_awards_test' and data_type='varchar' -- TABLE_NAME in ('api_tas','api_courses','api_students','api_student_courses','api_instructors') ;


ALTER TABLE odul.api_tas MODIFY COLUMN TERM_CODE varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL;

select * from information_schema.`COLUMNS` c where c.TABLE_NAME in ('api_tas','api_courses','api_students','api_student_courses','api_instructors') and data_type='varchar'

*/

-- /* Q1-Courses */ INSERT INTO courses_table (CourseName, Subject_Code, Course_Number, `Section`, CRN, YearID, Term, Sync_Date) 
-- /* Q2-Candidates */ INSERT INTO candidate_table (SU_ID, Name, Mail, `Role`, YearID, Status, Sync_Date) 
-- /* Q3-CandidateRel */ INSERT INTO candidate_course_relation ( CourseID, CandidateID, Academic_Year, CategoryID, Term)
-- /* Q4-Students */ INSERT INTO student_table (StudentID, YearID, StudentFullName, SuNET_Username, Class, Faculty, Mail, Department, CGPA, StudentLevel, Sync_Date)
-- /* Q5-StudentCourses */ INSERT INTO student_course_relation (`student.id`, CourseID, EnrollmentStatus) 
-- /* Q6-StudentsCatRel */ INSERT INTO student_category_relation (student_id, categoryID) 
with acadYearQ as (
	select 1 yearId, 2024 acadYear from dual
),terms as (
	select 
		yearId,acadYear,
		concat(acadYear,'01,',acadyear,'02') catTerms, 
		concat(acadyear-1,'01,',acadyear-1,'02,',acadyear,'01,',acadyear,'02') catTermsB, 
		concat(acadyear,'02') stuTerms 
	from acadYearQ
), origins as (
	select 'SIS' origin from dual
	union 
	select 'TAA' origin from dual
), cat_rules as (
	select 1 catId, 'A1' catCode, 'TLL 101,TLL 102,AL 102,SPS 101D,SPS 102D' crsSet, 'SIS' originSet, null notInCatCodeSet from dual
	union
	select 2 catId, 'A2' catCode, 'SPS 101,SPS 102,MATH 101,MATH 102,IF 100,NS 101,NS 102,HIST 191,HIST 192' crsSet, 'SIS' originSet, null notInCatCodeSet from dual
	union
	select 4 catId, 'C' catCode, 'ENG 0001,ENG 0002,ENG 0003,ENG 0004' crsSet, 'SIS' originSet, null notInCatCodeSet  from dual
	union 
	select 5 catId, 'D' catCode, 'IF 100R,MATH 101R,MATH 102R,NS 101R,NS 102R,NS 101,NS 102,SPS 101D,SPS 102D' crsSet, 'TAA' originSet, null notInCatCodeSet from dual
	union 
	select 3 catId, 'B' catCode, '%' crsSet, 'SIS' originSet, 'A1,A2,C,D' notInCatCodeSet from dual
), crs as (
	select r.*,t.*,o.*,c.* 
	from cat_rules r
	cross join terms t
	join origins o on FIND_IN_SET(o.origin,r.originSet)
	join api_courses c on FIND_IN_SET(c.term_code, case when r.catCode='B' then catTermsB else catTerms end)>0 -- Terms limit
		and  c.crse_numb regexp '^[0,1,2,3,4]' -- Only UG courses
		and (catCode!='B' or coalesce(c.credit_hr_low,0)>0) -- B cat must have credit 	
		and (catCode!='B' or not exists (
											select 1 
											from cat_rules r2
											where r.notInCatCodeSet is not null
												and find_in_set(r2.catCode,r.notInCatCodeSet)>0
												and find_in_set(concat(c.subj_code,' ',c.crse_numb),r2.crsSet)>0
										)
			) -- B cat must not contain other cat courses 	
	where (r.crsSet='%' or FIND_IN_SET(concat(c.subj_code,' ',c.crse_numb),r.crsSet)>0) -- Rule CourseSet Check
)
,inst as (
	select c.*, 
		PRIMARY_IND,INST_PERCENT_RESPONSE,INST_ID,INST_FIRST_NAME,INST_MI_NAME,INST_LAST_NAME,INST_USERNAME,INST_EMAIL,EMPL_GROUPCODE,HOMEDEPT_CODE,EMPL_STATUS,LASTWORK_DATE
		
	from crs c
	join api_instructors ci on ci.term_code=c.term_code and ci.crn=c.crn 	
	where  
		FIND_IN_SET('SIS',originSet) -- only Banner 
		and FIND_IN_SET(catCode,'D')=0 -- Cats except D
),ta as (
	select c.*, 
		PRIMARY_IND,TA_PERCENT_RESPONSE INST_PERCENT_RESPONSE,TA_ID INST_ID,TA_FIRST_NAME INST_FIRST_NAME,TA_MI_NAME INST_MI_NAME,TA_LAST_NAME INST_LAST_NAME,TA_USERNAME INST_USERNAME,TA_EMAIL INST_EMAIL,EMPL_GROUPCODE,HOMEDEPT_CODE,EMPL_STATUS,LASTWORK_DATE
		
	from crs c
	join api_tas ci on ci.term_code=c.term_code and ci.crn=c.crn 	
	where  
		FIND_IN_SET('TAA',originSet) -- only TAA 
		and catCode='D' -- Cats = D
),stu as (
	select c.*, 
		s.STU_ID,s.STU_FIRST_NAME,s.STU_MI_NAME,s.STU_LAST_NAME,s.STU_USERNAME,s.STU_EMAIL,s.STU_STS_CODE,s.STU_STS_TERM_CODE_EFF,s.STU_CUM_GPA_SU_TERM,s.STU_CUM_GPA_SU,s.STU_CUM_GPA_SU_ELIGIBLE,s.STU_CLASS_CODE,s.STU_FACULTY_CODE,s.STU_PROGRAM_CODE
	from api_students s
	join terms t on FIND_IN_SET(s.term_code,t.stuTerms)>0
	join api_student_courses sc on sc.stu_id=s.stu_id
	join crs c on c.term_code=sc.term_code and c.crn=sc.crn
),inst_ta as (
	select * from inst
	union
	select * from ta
)-- select * from inst_ta
, report as (
	select 
		INST_ID,INST_FIRST_NAME,INST_MI_NAME,INST_LAST_NAME,INST_USERNAME,INST_EMAIL,
		group_concat(distinct catCode) catCodes,
		group_concat(distinct concat(SUBJ_CODE,CRSE_NUMB,'-',SEQ_NUMB,'-',TERM_CODE)) courses,
		group_concat(distinct EMPL_STATUS) empStatus
	from inst_ta
	group by 	INST_ID,INST_FIRST_NAME,INST_MI_NAME,INST_LAST_NAME,INST_USERNAME,INST_EMAIL	
)
-- /* Q1-Courses */ select distinct CRSE_TITLE,SUBJ_CODE,CRSE_NUMB,SEQ_NUMB,CRN, yearId, TERM_CODE, now() from crs
-- /* Q2-Candidates */ select INST_ID,concat_ws(' ',INST_FIRST_NAME,INST_MI_NAME,INST_LAST_NAME) INST_NAME, INST_EMAIL,case when origin='TAA' then 'TA' else 'Instructor' end, yearId, EMPL_STATUS ,now() from inst_ta group by INST_ID
--  		select * from inst_ta where inst_id='00036379'
-- /* Q3-CandidateRel*/ 
/*
			select ct.CourseID, ct2.id  CandidateID,	it.yearId,	min(it.catId),	it.term_code
			from inst_ta it
			join courses_table ct on  ct.yearId=it.yearId and ct.term=it.term_code and ct.crn=it.crn
			join candidate_table ct2 on ct2.yearId=it.yearId and ct2.su_id=it.INST_ID
			group by ct.CourseID, ct2.id ,	it.yearId,	it.term_code
*/

-- /* Q4-Students */ select distinct STU_ID,yearid,concat_ws(' ',STU_FIRST_NAME,STU_MI_NAME,STU_LAST_NAME),STU_USERNAME,case STU_CLASS_CODE when 'FR' then 'Freshman' when 'SO' then 'Sophomore' when 'JU' then 'Junior'  when 'SE' then 'Senior' end,STU_FACULTY_CODE,STU_EMAIL,STU_PROGRAM_CODE,STU_CUM_GPA_SU,STU_LEVEL,now() from api_students s join terms t on t.stuTerms=s.term_code
 /* Q5-StudentCourses */ 
/*
						select st.id studentTblId,  ct.courseid  courseTblId,'enrolled' 
						 from api_student_courses s
						 join terms t on FIND_IN_SET(s.term_code,t.catTerms)>0 or FIND_IN_SET(s.term_code,t.catTermsB)>0 
						 join student_table st on st.YearID =t.yearId and st.StudentID =s.STU_ID
						 join courses_table ct where ct.YearID=t.yearid and ct.Term =s.term_code and ct.CRN =s.crn 
						 group by st.id,ct.courseid
*/
/* Q6- StuddentCatRel*/
/*
							select s.id,crc.CategoryID
							-- select * 
							from student_table s
							join acadYearQ ayq on ayq.yearid=s.yearid
							join student_course_relation scr on scr.`student.id`=s.id
							join courses_table ct on ct.courseid=scr.courseid
							join candidate_course_relation crc on crc.courseid=ct.courseid
							where  (s.class='Freshman' and crc.CategoryID in (1,2,5))
								or (s.StudentLevel='F' and crc.CategoryID in (4))
								or (s.class='Senior' and s.CGPA>=2 and crc.CategoryID in (3))							
								group by s.id,crc.CategoryID

  
  */
  










/*
  
  select * from api_student_courses asc2 where asc2.CRSE_NUMB ='0004';
  
  select * from student_table where StudentID ='00033820';
  select * from student_course_relation r join courses_table ct where ct.CourseID =r.CourseID and r.`student.id`=2314;
  select * from courses_table ct where ct.CourseID =1149

  
  select * 
  from api_students as2  
  left outer join student_table st on st.YearID =1 and st.StudentID =as2.STU_ID 
  left outer join student_category_relation scr on scr.student_id =st.id
  left outer join student_course_relation scr2 on scr2.`student.id` =st.id
  left outer join courses_table ct on ct.CourseID =scr2.CourseID 
  left outer join candidate_course_relation ccr on ccr.CourseID =ct.CourseID
  left outer join category_table ct3 on ct3.CategoryID =ccr.CategoryID
  left outer join category_table ct2 on ct2.CategoryID =scr.categoryID
  where as2.STU_ID ='00031992'
  
  
  
  select * 
  from api_student_courses asc2 
  join api_students s on s.STU_ID =asc2.STU_ID
  where asc2.STU_ID ='00031992' -- and asc2.CRSE_NUMB ='0004'
  */
  
  
  
  /*
 select * from api_instructors ai where INST_ID ='00036379'
 select * from api_tas where ta_id='00036379'
 select * from 

*/
 -- 1363	00004474	Fatma Berna Uysal	berna.uysal@sabanciuniv.edu	Instructor	1	Etkin		2025-05-23 16:33:53.000
 -- 642	Humanity and Society II - Discussion	SPS	102D	A5	20758	1	202402	2025-05-23 16:22:06.000
 -- '1363-642
			/*
 select * from candidate_table where id=1363; 
 select * from courses_table where courseid=642;
  
 select * from api_courses ac where TERM_CODE =202402 and crn=20758;
 select * from api_instructors where TERM_CODE =202402 and crn=20758;
 
 select * from api_tas where TERM_CODE =202402 and crn=20758;
			
*/
  

/*
  select * from api_students as2 where as2.stu_id='00035174'
  select * from student_table st where st.StudentID ='00033992'
  
  select * from api_student_courses asc2 where stu_id='00028265'
  
  select * from api_student_courses asc2 where crn=10813
  
  select * from api_students as2 where as2.STU_ID ='00034492'
  
  select * from api_students sss where sss.STU_USERNAME ='kbarutcuoglu'
  
  select ss.STU_ID ,ss.STU_FIRST_NAME ,ss.STU_LAST_NAME ,GROUP_CONCAT(concat(SUBJ_CODE,CRSE_NUMB,'-',aa.TERM_CODE)) 
  from api_Student_courses aa 
  join api_students ss on ss.STU_ID =aa.STU_ID
  where aa.TERM_CODE in ('202401','202402') and SUBJ_CODE ='ENG' and CRSE_NUMB in ('0001','0002','0003','0004')
  group by ss.STU_ID ,ss.STU_FIRST_NAME ,ss.STU_LAST_NAME 

*/




```





?>

