<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Vote Usage Data Table</title>
    <!-- Include Grid.js CSS -->
    	<!-- Global stylesheets -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	<link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->


	<!-- Core JS files -->
	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/global_assets/js/main/jquery.min.js"></script>
	<script src="assets/global_assets/js/main/bootstrap.bundle.min.js"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
	<script src="assets/js/app.js"></script>
	<script src="assets/js/custom.js"></script>
	<!-- /theme JS files -->
    
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

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            padding: 20px;
        }

        .form-title {
            text-align: center;
            margin-top: 40px; 
            margin-bottom: 30px; 
            font-size: 1.8rem;
            font-weight: bold;
            color: #3f51b5; 
        }

        .form-group label {
            font-weight: bold;
            color: #333;
        }

        .form-control {
            border-radius: 6px;
            background-color: #f7f7f9;
            border: 1px solid #ddd;
            padding: 10px;
        }

        .form-control:focus {
            border-color: #3f51b5; 
            box-shadow: 0 0 3px rgba(63, 81, 181, 0.5);
        }

        .btn-indigo {
            background-color: #3f51b5;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-indigo:hover {
            background-color: #303f9f; 
        }

        .icon-paperplane {
            margin-left: 8px;
        }

        .action-container {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin: 20px 0;
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
    <div class="title">Student Vote Usage Status</div>

    <div class="action-container">
        <button 
            class="return-button" 
            onclick="window.location.href='reportPage.php'">
            Return to Category Page
        </button>
    </div>
    <div class="gridjs-example-basic" style="margin: 20px;"></div>

    <!-- Include Grid.js JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/file-saver/dist/FileSaver.min.js"></script>

    <script>
        // Demo data
        const demoData = [
            [31254, "2023-2024", "Tankut Kayra", "kayra.ozerk", "Senior", "FENS", "kayra.ozerk@sabanciuniv.edu", "CS",   1,1,"-","-","-"],
            [1, "2021-2022", "John Doe", "john.doe", "freshman", "FENS", "john.doe@sabanciuniv.edu", "CS",  1,1,"-","-","-"],
            [2, "2020-2021", "Jane Smith", "jane.smith", "senior", "FMAN", "jane.smith@sabanciuniv.edu", "BUS",   0,1,"-","-","-"],
            [3, "2023-2024", "Ali Veli", "ali.veli", "sophomore", "FASS", "ali.veli@sabanciuniv.edu", "ART",  1,1,"-","-","-"],
            [4, "2022-2023", "Ayşe Yılmaz", "ayse.yilmaz", "junior", "FENS", "ayse.yilmaz@sabanciuniv.edu", "BIO",   0,1,"-","-","-"],
            [30199, "2024-2025", "Ilgın Simay Özcan", "ilgin.ozcan", "junior", "FENS", "ilgin.ozcan@sabanciuniv.edu", "IE",   0,1,"-","-","-"],
            [5, "2023-2024", "Mehmet Kara", "mehmet.kara", "prep", "FMAN", "mehmet.kara@sabanciuniv.edu", "PSY",  0,1,"-","-","-"]
        ];

        // Render Grid.js table
        const gridjsBasicElement = document.querySelector(".gridjs-example-basic");
        if (gridjsBasicElement) {
            const gridjsBasic = new gridjs.Grid({
                className: {
                    table: 'table'
                },
                columns: ["SU_ID", "Academic Year", "Name", "SUNET_USERNAME","Class","Faculty","Email","Program","A1 Vote","A2 Vote","B Vote","C Vote","D Vote"],
                data: demoData,
                pagination: true,
                sort: true,
                search: true,
                resizable: true,
                style: {
                    table: {
                        borderCollapse: 'collapse',
                        margin: '0 auto'
                    }
                },
                downloadCSV: true,
                downloadButton: {
                    text: 'Download Data'
                }
            });
            gridjsBasic.render(gridjsBasicElement);
        }

        // Export to Excel functionality
        const exportToExcel = () => {
    const headers = ["SU_ID", "Academic Year", "Name", "SUNET_USERNAME", "Class", "Faculty", "Email", "Program", "A1 Vote", "A2 Vote", "B Vote", "C Vote", "D Vote"];
    const rows = demoData.map(row => row.join(","));
    const csvContent = [headers.join(","), ...rows].join("\n"); // Correct variable name used here

    const encodedUri = "data:text/csv;charset=utf-8," + encodeURI(csvContent); // Fixed variable name
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "student_data.csv"); // File name for download
    document.body.appendChild(link); // Required for Firefox
    link.click();
    document.body.removeChild(link); // Clean up
};



        const downloadButton = document.createElement("button");
        downloadButton.textContent = "Download";
        downloadButton.style.margin = "20px auto";
        downloadButton.style.display = "block";
        downloadButton.addEventListener("click", exportToExcel);
        document.body.insertBefore(downloadButton, gridjsBasicElement);
    </script>
</body>
</html>
