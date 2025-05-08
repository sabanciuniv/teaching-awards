/* TABLES CREATED FOR THE SYSTEM */

create table AcademicYear_Table
(
    YearID          int auto_increment
        primary key,
    Academic_year   int      not null,
    Start_date_time datetime not null,
    End_date_time   datetime not null
);


create table AdditionalDocuments_Table
(
    Id                   int auto_increment
        primary key,
    NominationID         int                                   null,
    DocumentType         varchar(50)                           null,
    DocumentCodedName    varchar(255)                          null,
    DocumentOriginalName varchar(255)                          null,
    SubmissionDate       timestamp default current_timestamp() null,
    constraint AdditionalDocuments_Table_ibfk_1
        foreign key (NominationID) references Nomination_Table (nominationID)
);

create index NominationID
    on AdditionalDocuments_Table (NominationID);


create table Admin_Table
(
    AdminID         int auto_increment
        primary key,
    AdminSuUsername varchar(50)                             not null,
    Role            varchar(50)                             not null,
    GrantedBy       varchar(50)                             not null,
    GrantedDate     datetime    default current_timestamp() not null,
    RemovedBy       varchar(50)                             null,
    RemovedDate     datetime                                null,
    checkRole       varchar(50) default 'Active'            not null
);

create table Candidate_Course_Relation
(
    CandidateCourseID int auto_increment
        primary key,
    CourseID          int         null,
    CandidateID       int         null,
    Academic_Year     int         null,
    CategoryID        varchar(10) null,
    Term              varchar(10) not null,
    constraint Candidate_Course_Relation_ibfk_1
        foreign key (CourseID) references Courses_Table (CourseID),
    constraint Candidate_Course_Relation_ibfk_2
        foreign key (CandidateID) references Candidate_Table (id)
);

create index CourseID
    on Candidate_Course_Relation (CourseID);

create index id
    on Candidate_Course_Relation (CandidateID);


create table Candidate_Table
(
    id                 int auto_increment
        primary key,
    SU_ID              varchar(50)                     null,
    Name               varchar(255)                    null,
    Mail               varchar(255)                    null,
    Role               enum ('TA', 'Instructor')       null,
    YearID             int                             null,
    Status             enum ('Etkin', 'İşten ayrıldı') null,
    Status_description mediumtext                      null,
    Sync_Date          datetime                        null,
    constraint SU_ID
        unique (SU_ID),
    constraint Candidate_Table_ibfk_1
        foreign key (YearID) references AcademicYear_Table (YearID)
);

create index YearID
    on Candidate_Table (YearID);

create table Category_Table
(
    CategoryID          int auto_increment
        primary key,
    CategoryCode        varchar(10) charset latin1 not null,
    CategoryDescription varchar(255)               null
);



create table Courses_Table
(
    CourseID      int auto_increment
        primary key,
    CourseName    varchar(255)               null,
    Subject_Code  varchar(10) charset latin1 not null,
    Course_Number varchar(10) charset latin1 not null,
    Section       varchar(10) charset latin1 null,
    CRN           varchar(20) charset latin1 null,
    YearID        int                        null,
    Term          varchar(10) charset latin1 null,
    Sync_Date     datetime                   null,
    constraint unique_course
        unique (Subject_Code, Course_Number, CRN, Term),
    constraint Courses_Table_ibfk_2
        foreign key (YearID) references AcademicYear_Table (YearID)
);

create index YearID
    on Courses_Table (YearID);

create table Exception_Table
(
    id          int auto_increment
        primary key,
    CandidateID int                                   not null,
    excluded_by varchar(100)                          not null,
    excluded_at timestamp default current_timestamp() null,
    constraint Exception_Table_ibfk_1
        foreign key (CandidateID) references Candidate_Table (id)
            on delete cascade
);

create index candidate_id
    on Exception_Table (CandidateID);

create table MailLog_Table
(
    LogID        int auto_increment
        primary key,
    Sender       varchar(255)                         not null,
    StudentEmail varchar(255)                         not null,
    StudentName  varchar(255)                         null,
    TemplateID   int                                  null,
    YearID       int                                  null,
    MailContent  text                                 not null,
    SentTime     datetime default current_timestamp() not null,
    constraint fk_maillog_template
        foreign key (TemplateID) references MailTemplate_Table (TemplateID)
            on delete set null,
    constraint fk_maillog_year
        foreign key (YearID) references AcademicYear_Table (YearID)
            on delete set null
);

create table MailTemplate_Table
(
    TemplateID int auto_increment
        primary key,
    MailType   varchar(50)  not null,
    MailHeader varchar(255) not null,
    MailBody   text         not null
);

create index idx_mailtype
    on MailTemplate_Table (MailType);

create table Nomination_Table
(
    nominationID   int auto_increment
        primary key,
    SUnetUsername  varchar(100)                          null,
    NomineeName    varchar(100)                          null,
    NomineeSurname varchar(100)                          null,
    isAccepted     mediumtext                            null,
    YearID         int                                   null,
    SubmissionDate timestamp default current_timestamp() null,
    constraint Nomination_Table_ibfk_1
        foreign key (YearID) references AcademicYear_Table (YearID)
);

create index YearID
    on Nomination_Table (YearID);

create table Student_Category_Relation
(
    id         int auto_increment
        primary key,
    student_id int not null,
    categoryID int not null,
    constraint Student_Category_Relation_ibfk_1
        foreign key (student_id) references Student_Table (id)
            on delete cascade,
    constraint Student_Category_Relation_ibfk_2
        foreign key (categoryID) references Category_Table (CategoryID)
            on delete cascade
);

create table Student_Course_Relation
(
    id               int auto_increment
        primary key,
    `student.id`     int                          null,
    CourseID         int                          null,
    EnrollmentStatus enum ('enrolled', 'dropped') null,
    constraint Student_Course_Relation_ibfk_1
        foreign key (`student.id`) references Student_Table (id),
    constraint Student_Course_Relation_ibfk_2
        foreign key (CourseID) references Courses_Table (CourseID)
);

create index CourseID
    on Student_Course_Relation (CourseID);

create index StudentID
    on Student_Course_Relation (`student.id`);

create table Student_Table
(
    id              int auto_increment
        primary key,
    StudentID       varchar(8)                            not null,
    YearID          int                                   null,
    StudentFullName varchar(255)                          not null,
    SuNET_Username  varchar(100)                          null,
    Class           varchar(50)                           null,
    Faculty         varchar(50)                           null,
    Mail            varchar(255)                          null,
    Department      varchar(100)                          null,
    LectureList     mediumtext                            null,
    CGPA            float                                 null,
    Sync_Date       timestamp default current_timestamp() null on update current_timestamp(),
    constraint Student_Table_ibfk_1
        foreign key (YearID) references AcademicYear_Table (YearID)
);

create index SuNET_Username
    on Student_Table (SuNET_Username);

create index YearID
    on Student_Table (YearID);

create table Sync_Logs
(
    id           int auto_increment
        primary key,
    user         varchar(100)                         not null,
    filename     varchar(255)                         not null,
    sync_date    datetime default current_timestamp() not null,
    academicYear varchar(10)                          null,
    ip_address   varchar(255)                         null
);

create table user_cookies
(
    SUNET_Username varchar(255) not null
        primary key,
    cookie_id      varchar(64)  not null
);

create table Votes_Table
(
    id           int auto_increment
        primary key,
    AcademicYear int null,
    VoterID      int null,
    CandidateID  int null,
    CategoryID   int not null,
    Points       int null,
    `Rank`       int null,
    constraint Votes_Table_ibfk_1
        foreign key (AcademicYear) references AcademicYear_Table (YearID),
    constraint Votes_Table_ibfk_2
        foreign key (VoterID) references Student_Table (id),
    constraint Votes_Table_ibfk_3
        foreign key (CandidateID) references Candidate_Table (id),
    constraint Votes_Table_ibfk_4
        foreign key (CategoryID) references Category_Table (CategoryID)
            on update cascade on delete cascade
);

create index AcademicYear
    on Votes_Table (AcademicYear);

create index CandidateID
    on Votes_Table (CandidateID);

create index VoterID
    on Votes_Table (VoterID);

create table Winners_Table
(
    WinnerID         int auto_increment
        primary key,
    CategoryID       int                                            not null,
    YearID           int                                            not null,
    WinnerName       varchar(255)                                   not null,
    Faculty          varchar(255)                                   not null,
    `Rank`           varchar(50)                                    not null,
    ImagePath        varchar(255)                                   not null,
    CreatedAt        datetime           default current_timestamp() not null,
    readyDisplay     enum ('yes', 'no') default 'no'                not null,
    displayDate      datetime                                       null,
    SuNET_Username   varchar(50)                                    not null,
    SU_ID            int                                            null,
    Email            varchar(100)                                   null,
    candidate_points int                default 0                   not null,
    constraint fk_winner_academicyear
        foreign key (YearID) references AcademicYear_Table (YearID)
            on delete cascade,
    constraint fk_winner_category
        foreign key (CategoryID) references Category_Table (CategoryID)
            on delete cascade
);

/* API TABLES PROVIDED FROM IT */

create table API_COURSES
(
    TERM_CODE     varchar(6)    null,
    CRN           varchar(5)    null,
    SUBJ_CODE     varchar(4)    null,
    CRSE_NUMB     varchar(5)    null,
    SEQ_NUMB      varchar(3)    null,
    SCHD_CODE     varchar(3)    null,
    GMOD_CODE     varchar(1)    null,
    PTRM_CODE     varchar(3)    null,
    CRSE_TITLE    varchar(4000) null,
    CREDIT_HR_LOW int           null
);

create table API_INSTRUCTORS
(
    TERM_CODE             varchar(6)    null,
    CRN                   varchar(5)    null,
    SUBJ_CODE             varchar(4)    null,
    CRSE_NUMB             varchar(5)    null,
    SEQ_NUMB              varchar(3)    null,
    PRIMARY_IND           varchar(1)    null,
    INST_PERCENT_RESPONSE int(3)        null,
    INST_ID               varchar(9)    null,
    INST_FIRST_NAME       varchar(60)   null,
    INST_MI_NAME          varchar(60)   null,
    INST_LAST_NAME        varchar(60)   null,
    INST_USERNAME         varchar(4000) null,
    INST_EMAIL            varchar(4000) null,
    EMPL_GROUPCODE        varchar(4)    null,
    HOMEDEPT_CODE         varchar(10)   null,
    EMPL_STATUS           varchar(35)   null,
    LASTWORK_DATE         date          null
);

create table API_STUDENT_COURSES
(
    TERM_CODE varchar(6)  not null,
    CRN       varchar(5)  not null,
    SUBJ_CODE varchar(4)  not null,
    CRSE_NUMB varchar(5)  not null,
    SEQ_NUMB  varchar(3)  not null,
    STU_ID    varchar(50) null
)
    charset = utf8mb4;

create index idx_CRN
    on API_STUDENT_COURSES (CRN);

create index idx_CourseDetails
    on API_STUDENT_COURSES (SUBJ_CODE, CRSE_NUMB, SEQ_NUMB);

create index idx_STU_ID
    on API_STUDENT_COURSES (STU_ID);

create table API_STUDENTS
(
    TERM_CODE             varchar(6)     not null,
    STU_ID                text           null,
    STU_FIRST_NAME        text           null,
    STU_MI_NAME           text           null,
    STU_LAST_NAME         text           null,
    STU_USERNAME          text           null,
    STU_EMAIL             text           null,
    STU_STS_CODE          varchar(2)     not null,
    STU_STS_TERM_CODE_EFF varchar(6)     not null,
    STU_CUM_GPA_SU_TERM   varchar(6)     null,
    STU_CUM_GPA_SU        decimal(10, 2) null,
    STU_CLASS_CODE        varchar(10)    null,
    STU_FACULTY_CODE      varchar(10)    null,
    STU_PROGRAM_CODE      varchar(10)    null
)
    charset = utf8mb4;

create table API_TAS
(
    TERM_CODE           varchar(6)    null,
    CRN                 varchar(5)    null,
    SUBJ_CODE           varchar(4)    null,
    CRSE_NUMB           varchar(5)    null,
    SEQ_NUMB            varchar(3)    null,
    PRIMARY_IND         varchar(1)    null,
    TA_PERCENT_RESPONSE int(3)        null,
    TA_ID               varchar(9)    null,
    TA_FIRST_NAME       varchar(60)   null,
    TA_MI_NAME          varchar(60)   null,
    TA_LAST_NAME        varchar(60)   null,
    TA_USERNAME         varchar(4000) null,
    TA_EMAIL            varchar(4000) null,
    EMPL_GROUPCODE      varchar(4)    null,
    HOMEDEPT_CODE       varchar(10)   null,
    EMPL_STATUS         varchar(35)   null,
    LASTWORK_DATE       date          null
);

