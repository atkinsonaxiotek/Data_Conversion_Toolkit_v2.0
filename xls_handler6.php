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
                
                $title = "Step 6: Confirm and Process";
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

									<h3>Review the mapping specification, and initiate final processing. </h3>
                                                                            <?php

                                                                            $_SESSION["logfile"] = fopen($_SESSION["log_fileLocation"], "a");

                                                                            //Set variables
                                                                            $_SESSION["specName"] = $_POST["specificationName"];

                                                                            $_SESSION["ID_Mapping"] = mapSMIDsTovoanlIDs($_SESSION["smProviderIDsAndNames"]);
                                                                            writeProviderMappingSpecsToDatabase($_SESSION["ID_Mapping"],$_SESSION["specName"]);
                                                                            writeXMLPathSpecsToDatabase($_SESSION["specName"]);
                                                                            
//Complete the Provider Dedupe/mapping in the Database.
$conn = connectToDatabase();
$sql = "CALL setProviderUUIDtoNewNames('" . $_SESSION["jobName"] . "', '" . $_SESSION["BuildingColumnName"] . "', '" . $_SESSION["dataSource"] . "')";
if (mysqli_query($conn, $sql)) {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", DupeID sql statement executed:\n\t" . $sql . "\n");
}
else {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", DupeID_Error: " . mysqli_error($conn) . ".\n\t" . $sql . "\n");
    echo 'DupeID_Error - ' . mysqli_error($conn);
}
//$conn->next_result();
mysqli_close($conn);

                                                                            ?>
                                                                            Next, output new XML file. 
                                                                            <form action="xls_handler7.php" method="post">
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