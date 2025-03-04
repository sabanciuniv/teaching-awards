CREATE TABLE AcademicYear_Table (
    YearID INT PRIMARY KEY AUTO_INCREMENT,
    Academic_year INT NOT NULL,
    Start_date_time DATETIME NOT NULL,
    End_Date_time DATETIME NOT NULL
);

CREATE TABLE Category_Table (
    CategoryID VARCHAR(10) PRIMARY KEY,
    CategoryCode VARCHAR(10) NOT NULL,
    CategoryDescription TEXT
);

CREATE TABLE Courses_Table (
    CourseID INT PRIMARY KEY AUTO_INCREMENT,
    CourseName VARCHAR(255) NOT NULL,
    Subject_Code VARCHAR(10) NOT NULL,
    Course_Number VARCHAR(10) NOT NULL,
    Section VARCHAR(10),
    CRN VARCHAR(20) UNIQUE,
    CategoryID VARCHAR(10),
    YearID INT,
    Term VARCHAR(10),
    FOREIGN KEY (CategoryID) REFERENCES Category_Table(CategoryID),
    FOREIGN KEY (YearID) REFERENCES AcademicYear_Table(YearID)
);

CREATE TABLE Admin_Table (
    AdminSuUsername VARCHAR(50) PRIMARY KEY,
    Role VARCHAR(50) NOT NULL
);

CREATE TABLE Student_Table (
    StudentID INT PRIMARY KEY AUTO_INCREMENT,
    YearID INT,
    StudentFullName VARCHAR(255) NOT NULL,
    SuNET_Username VARCHAR(100) UNIQUE,
    Class VARCHAR(50),
    Faculty VARCHAR(50),
    Mail VARCHAR(255),
    Department VARCHAR(100),
    LectureList TEXT,
    CGPA FLOAT,
    VoteUsage BOOLEAN DEFAULT 0,
    FOREIGN KEY (YearID) REFERENCES AcademicYear_Table(YearID)
);

CREATE TABLE Student_Course_Relation (
    StuCourseID INT PRIMARY KEY AUTO_INCREMENT,
    StudentID INT,
    CourseID INT,
    EnrollmentStatus ENUM('enrolled', 'dropped'),
    FOREIGN KEY (StudentID) REFERENCES Student_Table(StudentID),
    FOREIGN KEY (CourseID) REFERENCES Courses_Table(CourseID)
);

CREATE TABLE Candidate_Table (
    id INT PRIMARY KEY AUTO_INCREMENT,
    SU_ID VARCHAR(50) UNIQUE,
    Name VARCHAR(255),
    Mail VARCHAR(255),
    Role ENUM('TA', 'Instructor'),
    YearID INT,
    Status ENUM('active', 'inactive'),
    Status_description TEXT,
    FOREIGN KEY (YearID) REFERENCES AcademicYear_Table(YearID)
);

CREATE TABLE Candidate_Course_Relation (
    CandidateCourseID INT PRIMARY KEY AUTO_INCREMENT,
    CourseID INT,
    id INT,
    Academic_Year INT,
    FOREIGN KEY (CourseID) REFERENCES Courses_Table(CourseID),
    FOREIGN KEY (id) REFERENCES Candidate_Table(id)
);

CREATE TABLE Votes_Table (
    AcademicYear INT,
    VoterID INT,
    CandidateID INT,
    CategoryID VARCHAR(10),
    Points INT,
    Rank INT,
    FOREIGN KEY (AcademicYear) REFERENCES AcademicYear_Table(YearID),
    FOREIGN KEY (VoterID) REFERENCES Student_Table(StudentID),
    FOREIGN KEY (CandidateID) REFERENCES Candidate_Table(id),
    FOREIGN KEY (CategoryID) REFERENCES Category_Table(CategoryID)
);

CREATE TABLE Nomination_Table (
    nominationID INT PRIMARY KEY AUTO_INCREMENT,
    SUnetUsername VARCHAR(100),
    NomineeName VARCHAR(100),
    NomineeSurname VARCHAR(100),
    ReferenceLetter TEXT,
    YearID INT,
    FOREIGN KEY (YearID) REFERENCES AcademicYear_Table(YearID)
);

CREATE TABLE AdditionalDocuments_Table (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    NominationID INT,
    DocumentType VARCHAR(50),
    DocumentName VARCHAR(255),
    DocumentOriginalName VARCHAR(255),
    FOREIGN KEY (NominationID) REFERENCES Nomination_Table(nominationID)
);

CREATE TABLE WinnerList_Table (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    YearID INT,
    WinnerID INT,
    Rank INT,
    CategoryID VARCHAR(10),
    FOREIGN KEY (YearID) REFERENCES AcademicYear_Table(YearID),
    FOREIGN KEY (WinnerID) REFERENCES Candidate_Table(id),
    FOREIGN KEY (CategoryID) REFERENCES Category_Table(CategoryID)
);

CREATE TABLE Student_Category_Relation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    categoryID INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES Student_Table(id) ON DELETE CASCADE,
    FOREIGN KEY (categoryID) REFERENCES Category_Table(CategoryID) ON DELETE CASCADE
);

