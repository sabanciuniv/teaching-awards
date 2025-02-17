<?php
require_once 'api/authMiddleware.php';
$config = include('config.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Participation Report</title>

    <!-- Bootstrap and FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Grid.js Theme -->
    <link href="https://cdn.jsdelivr.net/npm/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
    
    <style>
        body { 
            overflow: auto; 
        }
        .title { 
            text-align: center; 
            margin: 20px 0; 
            font-size: 24px; 
            font-weight: bold; 
        }
        .action-container {
            position: fixed; /* Stick to the bottom */
            bottom: 20px;    /* Distance from the bottom of the page */
            right: 20px;     /* Full width to center the button */
        }
        .return-button {
            background-color: #007bff; 
            color: white; 
            border: none; 
            padding: 10px 15px;
            font-size: 14px; 
            border-radius: 5px; 
            cursor: pointer; 
            transition: background-color 0.3s ease;
        }
        .return-button:hover { 
            background-color: #0056b3; 
        }
    </style>
</head>
<body>
    <div class="title">Voting Participation Report</div>
    <div class="action-container">
        <button 
            class="return-button" 
            onclick="window.location.href='reportPage.php'">
            Return to Category Page
        </button>
    </div>

    <div class="gridjs-example-basic" style="margin: 20px;"></div>

    <!-- Load JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>
    
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        fetch("./api/getVotingParticipation.php")
            .then(response => response.json())
            .then(participationData => {
                console.log("Participation Data: ", participationData);

                const gridjsBasicElement = document.querySelector(".gridjs-example-basic");
                if (gridjsBasicElement) {
                    new gridjs.Grid({
                        className: { table: 'table' },
                        columns: [
                            "Academic Year",
                            "Students Voted",
                            "Total Students",
                            "Participation Percentage"
                        ],
                        data: participationData.map(row => [
                            row.AcademicYear, // Fixed Key Name
                            row.OyVeren, // Students Voted
                            row.ToplamKisi, // Total Students
                            `${row.OyKullanimOrani}%` // Participation Percentage
                        ]),
                        pagination: { limit: 8, summary: true },
                        sort: true,
                        search: true,
                        resizable: true,
                        style: { table: { borderCollapse: 'collapse', margin: '0 auto' } }
                    }).render(gridjsBasicElement);
                }

                // Export to CSV
                const exportToCSV = () => {
                    const headers = ["Academic Year", "Students Voted", "Total Students", "Participation Percentage"];
                    const rows = participationData.map(row => [
                        row.AcademicYear,
                        row.OyVeren,
                        row.ToplamKisi,
                        `${row.OyKullanimOrani}%`
                    ].join(";"));
                    const csvContent = "\uFEFF" + [headers.join(";"), ...rows].join("\n");

                    const encodedUri = "data:text/csv;charset=utf-8," + encodeURI(csvContent);
                    const link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", "voting_participation_report.csv");
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                };

                const downloadButton = document.createElement("button");
                downloadButton.textContent = "Download CSV";
                downloadButton.style.margin = "20px auto";
                downloadButton.style.display = "block";
                downloadButton.addEventListener("click", exportToCSV);
                document.body.insertBefore(downloadButton, gridjsBasicElement);
            })
            .catch(error => {
                console.error("Error fetching data: ", error);
            });
    });
    </script>

</body>
</html>
