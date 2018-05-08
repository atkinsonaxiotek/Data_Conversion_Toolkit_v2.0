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
                
                $title = "Step 1: Create Job";
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

                                                                         <h3>Name the job, select the affiliate, and select the file to upload.</h3>
                                                                         
                                                                        <?php
                                                                         $conn = connectToDatabase();
                                                                         $sql = "SELECT Code, AffiliateName FROM AffiliatesOfVOA";
                                                                         $tableOfDataSources = mysqli_query($conn, $sql);
                                                                         
                                                                         if ($tableOfDataSources == false) {                                                                                 
                                                                            DisplayMsg('Error Loading Affiliate List - ' . mysqli_error($conn),"D");
                                                                         };
                                                                         ?>
                                                                         
									<form action="xls_handler2.php" method="post" enctype="multipart/form-data">
                                                                            Name Your Job:<br>
                                                                            <input type="text" value="" name="jobName" required><br>
                                                                            Enter the data source:
                                                                                <?php

                                                                                if ($tableOfDataSources) {
                                                                                    
                                                                                    $listOfDataSources = mysqliColumnsToMultiArray($tableOfDataSources);

                                                                                    echo '<select name="selectedDataSource">';
                                                                                    
                                                                                    for($x = 0; $x < count($listOfDataSources[0]); $x++) {
                                                                                        echo '<option value="' . $listOfDataSources[0][$x] .  '">' . $listOfDataSources[0][$x] . ' (' . $listOfDataSources[1][$x] . ')</option>';
                                                                                    }
                                                                                    echo '</select>';
                                                                                }
                                                                                echo '<br>';
                                                                                ?>
                                                                            Select XLS file to upload:<br>
                                                                            <input type="file" name="XLSfileToUpload" id="XLSfileToUpload" style="width: 800px"><br>
                                                                            <input type="submit" value="Upload" name="submit">
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