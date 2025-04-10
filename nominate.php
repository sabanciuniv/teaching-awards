<?php
session_start();
require_once 'api/authMiddleware.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Use impersonated username if impersonation is active
$usernameToUse = isset($_SESSION['impersonating']) && $_SESSION['impersonating'] === true
    ? $_SESSION['impersonated_user']
    : $_SESSION['user'];


ini_set('display_errors', 1);
error_reporting(E_ALL);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nominate - Teaching Awards</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Limitless Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        body {
            background-color: #f9f9f9;
            margin: 0;
            padding-top: 70px;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
        }

        .card {
            background-color: #f9f9f9;
            color: white;
            border: 1px solid #45748a;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            width: 100%;
        }

        .col {
            background-color: #f9f9f9;
            color: white;
            border: 1px solid #45748a;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            width: 100%;
        }

        .card-header {
            font-size: 1.3rem;
            font-weight: bold; 
            text-align: center; 
            padding: 15px; 
        }

        .card-body {
            font-size: 14px;
            color: #000;
        }

        .form-body {
            padding: 30px;
        }

        .form-control {
            color: var(--bs-secondary);
        }

        .file-input {
            position: relative;
            margin-top: 15px;
            color: #45748a;
        }

        .file-preview {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            background: #fff;
            position: relative;
        }

        .file-drop-zone {
            padding: 10px;
            text-align: center;
            border: 2px dashed #ddd;
            border-radius: 5px;
            background-color: #f8f9fa;
            cursor: pointer;
            color: #45748a; /* Custom text color */
        }

        .file-drop-zone p {
            color: #45748a;
            font-weight: bold;
        }

        .file-drop-zone.dragging {
            border-color: #45748a;
            background-color: #e6f4f9;
        }

        .file-preview-thumbnails {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .file-preview-frame {
            position: relative;
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            text-align: center;
            line-height: 100px;
        }

        .file-preview-frame img {
            max-width: 100%;
            max-height: 100%;
        }

        /* Custom remove buttons */
        .btn-remove-all,
        .btn-remove-file {
            position: absolute;
            top: 5px;
            right: 5px;
            z-index: 10;
            background: none;
            border: none;
            cursor: pointer;
        }
        .btn-remove-all i,
        .btn-remove-file i {
            color: gray;
            font-size: 1.2rem;
        }
        .btn-remove-all:hover i,
        .btn-remove-file:hover i {
            color: darkgray;
        }

        .file-caption-name {
            border: none;
            background: #45748a;
            padding: 10px;
        }

        .btn-file input[type="file"] {
            display: none;
        }

        /* Make both columns the same height */
        .row.align-items-stretch {
            height: auto; /* lets content define height */
        }
        .col-md-6.d-flex {
            display: flex !important;
        }
        .col-md-6.d-flex .card {
            flex: 1; /* fill available space equally */
        }
        
    </style>
</head>
<body>
    
    <?php include 'navbar.php'; ?>

    <!-- Nomination Form Card -->
    <div class="container mt-5">
        <div class="row align-items-stretch">
            <!-- Rules & Guidelines (Left) -->
            <div class="col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header bg-secondary text-white">
                        <strong>📜 Rules & Guidelines</strong>
                    </div>
                    <div class="card-body" style="font-size: 14px;">
                        <p><strong>Purpose:</strong> The Teaching Assistant Award was created to acknowledge Teaching Assistants who excel in their activities.</p>
                        <p><strong>Eligibility:</strong></p>
                        <ul>
                            <li>The nominee must be a current graduate student.</li>
                            <li>The nominee must be a TA in at least one course during the current academic year.</li>
                        </ul>

                        <p><strong>Criteria:</strong></p>
                        <ul>
                            <li>(a) Being nominated by more than one person</li>
                            <li>(b) Being nominated both by faculty and by students</li>
                            <li>(c) Course evaluation results</li>
                            <li>(d) Data/Feedback about their work in more than one course (from course instructors)</li>
                            <li>(e) Whether nomination letters have been provided individually if nominated by a group of students</li>
                            <li>(f) GPA of the nominee</li>
                        </ul>

                        <p><strong>For questions about the nomination guidelines and process, please contact:</strong></p>
                        <p>Deniz İnan - <a href="mailto:deniz.inan@sabanciuniv.edu">deniz.inan@sabanciuniv.edu</a></p>
                    </div>
                </div>
            </div>

            <!-- Nomination Form (Right) -->
            <div class="col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header bg-secondary text-white text-center">
                        Nomination Form
                    </div>
                    <div class="form-body">
                        <form id="nominationForm" action="submitNomination.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="year_id" id="year_id">
                            <?php
                            // Determine the correct username based on impersonation status
                            $usernameToUse = (isset($_SESSION['impersonating']) && $_SESSION['impersonating'] === true)
                                ? $_SESSION['impersonated_user']
                                : $_SESSION['user'];

                            ?>
  
                            <!-- Your Username -->
                            <div class="mb-3">
                                <label class="form-label text-secondary">Your Username</label>
                                <input type="text" name="SUnetUsername" class="form-control border-secondary text-secondary" 
                                    value="<?php echo htmlspecialchars($usernameToUse); ?>" readonly>

                            </div>

                            <!-- Nominee's Name -->
                            <div class="mb-3">
                                <label class="form-label text-secondary">Nominee's Name</label>
                                <input type="text" name="NomineeName" class="form-control text-secondary border-secondary" 
                                       placeholder="Enter nominee's name" required>
                            </div>
                            <!-- Nominee's Surname -->
                            <div class="mb-3">
                                <label class="form-label text-secondary">Nominee's Surname</label>
                                <input type="text" name="NomineeSurname" class="form-control text-secondary border-secondary" 
                                       placeholder="Enter nominee's surname" required>
                            </div>
                            
                            <!-- Upload References -->
                            <div class="file-input">
                                <div class="file-preview">
                                    <!-- Button to clear all files -->
                                    <button type="button" class="btn-remove-all" aria-label="Close" onclick="clearAllFiles()">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                    <div class="file-drop-zone clearfix" 
                                         id="fileDropZone"
                                         ondragover="handleDragOver(event)" 
                                         ondrop="handleFileDrop(event)">
                                        <p>Drag and drop files here, or click to select files</p>
                                        <div class="file-preview-thumbnails clearfix" id="fileThumbnails"></div>
                                    </div>
                                </div>
                                <input type="file" id="fileInput" name="ReferenceLetterFiles[]" multiple style="display: none;">
                                <div class="file-caption">
                                    <input type="text" class="file-caption-name form-control" id="fileCaption" readonly 
                                           placeholder="No file selected">
                                </div>
                            </div>

                            <!--Checkbox to accept rules and data sharing -->
                            <div class="form-check mt-4 mb-3">
                                <!-- Make the label text black -->
                                <input class="form-check-input" type="checkbox" id="rulesAccepted" name="rulesAccepted" value="true">
                                <label class="form-check-label" for="rulesAccepted" style="color: black;">
                                    I accept the rules and agree to share my data for the nomination process.
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-secondary bg-secondary text-white">
                                Submit <i class="icon-paperplane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="btn-view">
            <a href="previousNominations.php" class="btn btn-secondary text-white"><i class="fas fa-history"></i> View Previous Nominations</a>
        </div>

    </div>
    
    <!-- Bootstrap Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel">Something went wrong!</h5>
                </div>
                <div class="modal-body" id="errorModalMessage"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // =============== FILE UPLOAD LOGIC ===============
    let selectedFiles = [];

    document.getElementById("fileDropZone").addEventListener("click", function(event) {
        event.stopPropagation();
        document.getElementById("fileInput").click();
    });

    document.getElementById("fileInput").addEventListener("change", function(event) {
        addFilesToSelection(event.target.files);
    });

    function handleDragOver(event) {
        event.preventDefault();
        event.stopPropagation();
        document.getElementById("fileDropZone").classList.add("dragging");
    }

    function handleFileDrop(event) {
        event.preventDefault();
        event.stopPropagation();
        document.getElementById("fileDropZone").classList.remove("dragging");
        addFilesToSelection(event.dataTransfer.files);
    }

    function addFilesToSelection(files) {
        Array.from(files).forEach((file) => {
            if (!selectedFiles.some((f) => f.name === file.name && f.size === file.size)) {
                selectedFiles.push(file);
            }
        });
        updateFilePreview();
    }

    function updateFilePreview() {
        const fileThumbnails = document.getElementById("fileThumbnails");
        const fileCaption = document.getElementById("fileCaption");
        fileThumbnails.innerHTML = "";
        fileCaption.value = selectedFiles.length + " file(s) selected";

        selectedFiles.forEach((file, index) => {
            const frame = document.createElement("div");
            frame.className = "file-preview-frame";

            const ext = file.name.split('.').pop().toLowerCase(); // Get file extension
            const img = document.createElement("img");
            let fileIcon = ""; // Placeholder for non-image files

            if (["png", "jpg", "jpeg"].includes(ext)) {
                // If the file is an image, display the preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                frame.appendChild(img);
            } else {
                // Set appropriate file icons for other file types
                switch (ext) {
                    case "pdf":
                        fileIcon = '<i class="fas fa-file-pdf text-danger" style="font-size: 3rem;"></i>';
                        break;
                    case "doc":
                    case "docx":
                        fileIcon = '<i class="fas fa-file-word text-primary" style="font-size: 3rem;"></i>';
                        break;
                    case "ppt":
                    case "pptx":
                        fileIcon = '<i class="fas fa-file-powerpoint text-warning" style="font-size: 3rem;"></i>';
                        break;
                    case "txt":
                        fileIcon = '<i class="fas fa-file-alt text-muted" style="font-size: 3rem;"></i>';
                        break;
                    default:
                        fileIcon = '<i class="fas fa-file text-dark" style="font-size: 3rem;"></i>'; // Default file icon
                }
                frame.innerHTML = fileIcon; // Insert the icon into the frame
            }

            const removeButton = document.createElement("button");
            removeButton.type = "button";
            removeButton.className = "btn-remove-file";
            removeButton.ariaLabel = "Remove";
            removeButton.innerHTML = '<i class="fa fa-trash"></i>';
            removeButton.onclick = function (event) {
                event.stopPropagation();
                removeFile(index);
            };

            frame.appendChild(img);
            frame.appendChild(removeButton);
            fileThumbnails.appendChild(frame);
        });
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        updateFilePreview();
    }

    function clearAllFiles() {
        selectedFiles = [];
        updateFilePreview();
    }

    document.getElementById("fileDropZone").addEventListener("dragleave", () => {
        document.getElementById("fileDropZone").classList.remove("dragging");
    });

    // =============== FORM SUBMISSION LOGIC ===============
    document.getElementById("nominationForm").addEventListener("submit", function(event) {
        event.preventDefault();

        // Check if the user accepted the rules
        const checkbox = document.getElementById("rulesAccepted");
        if (!checkbox.checked) {
            showError("Please accept the rules before submitting.");
            return;
        }

        // Check if at least one file is uploaded
        if (selectedFiles.length === 0) {
            showError("Please upload at least one file.");
            return;
        }

        let formData = new FormData(this);

        selectedFiles.forEach((file) => {
            formData.append("ReferenceLetterFiles[]", file);
        });

        fetch(this.action, {
            method: this.method,
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log("Server response:", data);
            if (data.includes("Error")) {
                showError("Submission failed! Wrong file format.");
            } else {
                window.location.href = `thankYou.php?context=nominate`;
            }
        })
        .catch(error => {
            console.error("Error submitting the form:", error);
            showError("Error submitting the form. Please try again.");
        });
    });

    document.getElementById('nominationForm').addEventListener('submit', function () {
        document.querySelector('button[type="submit"]').disabled = false;
    });

    function showError(message) {
        document.getElementById("errorModalMessage").innerHTML = message;
        let errorModal = new bootstrap.Modal(document.getElementById("errorModal"));
        errorModal.show();

        document.getElementById("errorModal").addEventListener("hidden.bs.modal", function () {
            document.body.classList.remove("modal-open");
            document.querySelector(".modal-backdrop")?.remove();
        });
    }

    // =============== FETCH ACADEMIC YEAR ON PAGE LOAD ===============
    document.addEventListener("DOMContentLoaded", function () {
        fetch('api/getAcademicYear.php')
            .then(response => response.json())
            .then(data => {
                if (data.yearID) {
                    document.getElementById("year_id").value = data.yearID;
                } else {
                    console.error('Year not available');
                }
            })
            .catch(error => {
                console.error('Error fetching academic year');
            });
    });
    </script>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
