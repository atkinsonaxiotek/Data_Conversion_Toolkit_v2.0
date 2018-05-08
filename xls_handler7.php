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
                require 'php_libraries/msgHandler.php';
    
                $title = "Step 7: New File";
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

									<h3>Download your new files.</h3>
                                                                            <?php

                                                                            $_SESSION["logfile"] = fopen($_SESSION["log_fileLocation"], "a");

                                                                            //Set variables
                                                                            $new_file = "new_files/" . $_SESSION["uniqueFilename"] . ".xml";

                                                                            //writeMySQLtoXML($new_file,$_SESSION["specName"]); obsolete
                                                                            startNewXMLFile($new_file);
                                                                            writeProviderRecords($new_file,$_SESSION["specName"]);
                                                                            writeClientRecords($new_file,$_SESSION["specName"]);
                                                                            writeEntryExitRecords($new_file,$_SESSION["specName"]);
                                                                            finishXMLFile($new_file);

                                                                            //Display info for user
                                                                            DisplayMsg("<br>Your original file is: <b>" . $_SESSION["original_filename"] . "</b><br />Your new file is: <b>" . $_SESSION["uniqueFilename"] . ".xml</b>");

                                                                            //Display download links for user
                                                                            echo "Your new file has been written.<br />";
                                                                            echo '<a href="' . $new_file . '" download>Click here to download your new XML file.</a><br />';
                                                                            echo '<a href="' . $_SESSION["log_fileLocation"] . '" download>Click here to download your log file.</a><br />';

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