<!DOCTYPE HTML>

<html>
	 <head>
                <meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
                                
                <!-- CDN -->
                <!-- Latest compiled and minified CSS -->
                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

                <!-- jQuery library -->
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

                <!-- Latest compiled JavaScript -->
                <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script> 
                
                 <!-- Local CSS -->
		<!--[if lte IE 8]><script src="assets/js/ie/html5shiv.js"></script><![endif]-->
		<link rel="stylesheet" href="assets/css/main.css" />
		<!--[if lte IE 8]><link rel="stylesheet" href="assets/css/ie8.css" /><![endif]-->
                
                <!-- Local Scripts -->
                <!-- <script src="assets/js/jquery.min.js"></script> -->
                <script src="assets/js/jquery.dropotron.min.js"></script>
                <script src="assets/js/skel.min.js"></script>
                <script src="assets/js/util.js"></script>
                <!--[if lte IE 8]><script src="assets/js/ie/respond.min.js"></script><![endif]-->
                <script src="assets/js/main.js"></script>
                                        
              <?php
                require 'php_libraries/utilities.php';
                require 'php_libraries/xls_handler_functions.php';
                require 'php_libraries/xml_handler_functions.php';
                require 'php_libraries/msgHandler.php';
                
                $title = "Step 3: Process";
                echo '<title>' . $title . '</title>';
                include "login_chk.php";
                ?>
                
	</head>
        
	<body class="no-sidebar">
		<div id="page-wrapper">

			<!-- Header -->
				<div id="header-wrapper">
					<header id="header" class="container">

						<!-- Logo -->
							<div id="logo">
								<img src="images/logo-national-color" alt="VOA Logo" style="width:300px;">
								<!--<span>Conversion Toolkit</span>-->
							</div>

                                                    <?php
                                                    include "nav.php";
                                                   ?> 

					</header>
				</div>

			<!-- Main -->
				<div id="main-wrapper">
					<div class="container">
						<div id="content">

							<!-- Content -->
								<article>
                                                                        
									<h2><?php echo $title;?></h2>

									<h3>Begin processing of XLS file.</h3>
<?php

$_SESSION["logfile"] = fopen($_SESSION["log_fileLocation"], "a");
//$_SESSION["logfile"] = fopen($_SESSION["log_filename"], "a");
//$logfile = fopen($_SESSION["log_fileLocation"], "a");




//Generate 6 CSV files
createCSVfilesFromXLS($_SESSION["uniqueFilename"]);

//create 6 tables in database (Ignore Res Logs and Census for now.) This also records any new SM_Objects into the SM_Objects table.
$_SESSION["sheetnames"] = array();
$_SESSION["linesToIgnoreArray"] = array(9,2,2,2,2,1);  //This line assumes consistent SM export format.  //Prior to March2018, format was: array(9,2,3,2,2,1)
for($x = 0; $x < 6; $x++) {
    $tablename = createTablefromCSV("intermediate_files/" . $x . $_SESSION["uniqueFilename"] . ".csv",$_SESSION["linesToIgnoreArray"][$x]);
    array_push($_SESSION["sheetnames"],$tablename);
}
//Load data into tables, remove rows with null 'SM ID', delete csv file.
for($x = 0; $x < 6; $x++) {
    importDatafromCSV("intermediate_files/" . $x . $_SESSION["uniqueFilename"] . ".csv", $x);
}
//Create UUID and UUIDen columns in Intake table and populate them with concat UUID and encrypted blob.
$conn = connectToDatabase();
$sqlCreateUUIDs = "CALL UpdateIntake('" . $_SESSION["sheetnames"][0] . "', '" . $_SESSION["dataSource"] . "', 0)";
if (mysqli_query($conn, $sqlCreateUUIDs)) {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", UUID sql statement executed:\n\t" . $sqlCreateUUIDs . "\n");
}
else {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", UUID_Error: " . mysqli_error($conn) . ".\n\t" . $sqlCreateUUIDs . "\n");
    echo 'UUID_Error - ' . mysqli_error($conn);
}
$conn->next_result();
mysqli_close($conn);

//Create clients in SyncPointIDs table. 
//still need to put tablename as parameter.
$conn = connectToDatabase();
$sqlCreateSystemIDs = "CALL PopulateSyncPointIDsTable('" . $_SESSION["jobName"] . "', '" . $_SESSION["BuildingColumnName"] . "', '" . $_SESSION["dataSource"] . "')";
if (mysqli_query($conn, $sqlCreateSystemIDs)) {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", SystemID sql statement executed:\n\t" . $sqlCreateSystemIDs . "\n");
}
else {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", SystemID_Error: " . mysqli_error($conn) . ".\n\t" . $sqlCreateSystemIDs . "\n");
    echo 'SystemID_Error - ' . mysqli_error($conn);
}
//$conn->next_result();
mysqli_close($conn);

//Set SyncPointID of duplicate clients to the same (earliest) value
$conn = connectToDatabase();
$sqlSetDupeIDs = "CALL SetSyncPointIDsForDuplicateClients()";
if (mysqli_query($conn, $sqlSetDupeIDs)) {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", DupeID sql statement executed:\n\t" . $sqlSetDupeIDs . "\n");
}
else {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", DupeID_Error: " . mysqli_error($conn) . ".\n\t" . $sqlSetDupeIDs . "\n");
    echo 'DupeID_Error - ' . mysqli_error($conn);
}
//$conn->next_result();
mysqli_close($conn);

DisplayMsg("Data Import complete.","I");
        
?>
                                                                         Next, process the file.<br>
                                                                        <form action="xls_handler4.php" method="post">
                                                                            <input type="submit" value="Continue" name="submit">
                                                                        </form>

								</article>

						</div>
					</div>
				</div>

			<!-- Footer -->
				
                        <?php
                            include "footer.php";
                        ?>
				
		</div>

	</body>
</html>