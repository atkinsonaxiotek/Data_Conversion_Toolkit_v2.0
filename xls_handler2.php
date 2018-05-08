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
                require 'php_libraries/msgHandler.php';
                
                $title = "Step 2: Import";
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

                                                                        <h3>Validate and import the XLS file.</h3>
<?php

//Get data posted by user:           //This section must still be secured for SQL and HTML use
$_SESSION["original_filename"] = basename($_FILES["XLSfileToUpload"]["name"]);
$_SESSION["dataSource"] = $_POST["selectedDataSource"];
$_SESSION["jobName"] = $_POST["jobName"];
//$_SESSION["jobName_sql"] = //set to sql-safe value?

//Filename modification
$original_extension = pathinfo($_SESSION["original_filename"],PATHINFO_EXTENSION);
$_SESSION["jobStartTime"] = time();
$_SESSION["uniqueFilename"] = pathinfo($_SESSION["original_filename"],PATHINFO_FILENAME) . '_' . $_SESSION["jobStartTime"];

//Initiate log file:
$_SESSION["logfile"] = startLogFile($_SESSION["uniqueFilename"]);

//Upload file:
$_SESSION["target_filename"] = "uploads/" . $_SESSION["uniqueFilename"] . "." . $original_extension;
$filePresent = uploadFile("XLSfileToUpload",$_SESSION["target_filename"],"xls"); //this function calls echo original filename.
DisplayMsg('All files assocaited with job <b>' . $_SESSION["jobName"] . '</b> will have the following prefix: <i><b>' . $_SESSION["uniqueFilename"] . '</b></i>', "I");

//Call proc to record job in jobtable.
if ($filePresent) {
    $conn = connectToDatabase();
    $sqlCreateJob = "CALL CreateJob('" . $_SESSION["jobName"] . "', '" . $_SESSION["log_filename"] . "','In Progress', '" . $_SESSION["dataSource"] . "', '" . $original_extension . "')";
    if (mysqli_query($conn, $sqlCreateJob)) {
        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Create Job sql statement executed:\n\t" . $sqlCreateJob . "\n");
    }
    else {
        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Error Creating Job: " . mysqli_error($conn) . ".\n\t" . $sqlCreateJob . "\n");
        DisplayMsg('Error Creating Job - ' . mysqli_error($conn), "W");
    }
}

//Display button:
if ($filePresent) {
    echo "Next, import the file.<br>";
    echo '<form action="xls_handler3.php" method="post">';
    echo '    <input type="submit" value="Import" name="submit">';
    echo '</form>';
}
else {
    DisplayMsg("An error occurred.  Please check the logs and restart the wizard.", "D");
    echo '<form action="xls_handler.php" method="post">
            <p>Click to return to upload page:</p>
            <input type="submit" value="Restart" name="submit">
        </form>';
}
?>
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