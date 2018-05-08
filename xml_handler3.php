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
                require 'php_libraries/xml_handler_functions.php';
                
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

									<h3>Begin processing of XML file.</h3>
                                                                                <?php
                                                                                    
                                                                                    $_SESSION["logfile"] = fopen($_SESSION["log_fileLocation"], "a");

                                                                                    //Get data from file
                                                                                    $_SESSION["affiliateProviderIDsAndNames"] = getOldProviderIDsAndNames($_SESSION["target_filename"]);
                                                                                    $_SESSION["numberOfOldProviders"] = count($_SESSION["affiliateProviderIDsAndNames"]);

                                                                                    //Get data from database   //List from Anne, currently only shows Massachusetts names.
                                                                                                               // 20180417 - Table now includes MA, WY, PR, NJDV, function takes DataSource as parameter.
                                                                                    $_SESSION["voanlProviderIDsAndNames"] = getNewProviderIDsAndNames();
                                                                                    $_SESSION["numberOfNewProviders"] = count($_SESSION["voanlProviderIDsAndNames"]);

                                                                                    //generate associative arrays.
                                                                                    $_SESSION["affiliateMapping"] = turnTwoDimensionalArrayIntoAssociativeArray($_SESSION["affiliateProviderIDsAndNames"]);
                                                                                    $_SESSION["voanlMapping"] = turnTwoDimensionalArrayIntoAssociativeArray($_SESSION["voanlProviderIDsAndNames"]);

                                                                                    //Get list of top level tags from xml file.
                                                                                    $_SESSION["XML_Tags"] = getTopLevelTags($_SESSION["target_filename"]);
                                                                                    $_SESSION["numberOfTags"] = count($_SESSION["XML_Tags"]);
                                                                                    
                                                                                    DisplayMsg("Data Import complete.","S");

                                                                                ?>
                                                                        
                                                                        Next, process the file.<br>
                                                                             <form action="xml_handler4.php" method="post">
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