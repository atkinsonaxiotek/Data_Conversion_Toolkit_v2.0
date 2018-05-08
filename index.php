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
                $title = "C-Tools Home";
                echo '<title>' . $title . '</title>';
                include "login_chk.php";
                ?>
                
	</head>
        
	<body class="right-sidebar">
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
						<div class="row 200%">
							<div class="8u 12u$(medium)">
								<div id="content">

									<!-- Content -->
										<article>

											<h2>Introduction</h2>

											<p>Welcome to the Volunteers of America, National (VOA) application for data conversion, Conversion Tools, or C-Tools for short.  Here you will find a set of tools for converting data between formats as well as tools for removing and/or remapping data elements within an existing format or during a conversion.</p>
                                                                                        <p>Step-by-step wizards break the process down into easy-to-understand steps and make it as simple as possible for the end-user.</p>
                                                                                        <p>New features and enhancements will be added on a regular basis. </p>
                                                                                        <p>When finished, please use the logout button at the top to close your session and clean-up any temporary data.  </p>
                                                                                      

											<h3>Tools Explanation</h3>
											<p>The available tools for your account are visible on the right.  Select a button to start the wizard and walk through the steps to complete the data transformation.  Resulting files will be available for download at the completion of the wizard.</p>
                                                                                        <p>Use the <a href="xml_handler.php">XML Files</a> wizard to trim XML content by tag type and/or remap/rename tags.  Save mapping templates for later use and generally prepare and validate the XML file for ServicePoint/SyncPoint usage.</p>
                                                                                        <p>Use the <a href="xls_handler.php">XLS/CSV Files</a> wizard to convert xls/csv data directly into xml.  Upload xls files, select the data elements to keep, map to specific xml tags, save mapping templates for later use and generally validate the xml file for ServicePoint/SyncPoint usage.</p>
                                                                                        <p>Read more about the file types supported here.</p>
										</article>
								</div>
							</div>
							<div class="4u 12u$(medium)">
								<div id="sidebar">

									<!-- Sidebar -->
										<section>
											<h3>Modify XML Files</h3>
                                                                                       	<footer>
                                                                                            Step 1: Create Job<br>
                                                                                            Step 2: Import File<br>
                                                                                            Step 3: Process File<br>
                                                                                            Step 4: Select Mapping<br>
                                                                                            Step 5: Save Mapping<br>
                                                                                            Step 6: Process Job<br>
                                                                                            Step 7: Collect Output<br>
                                                                                            <a href="xml_handler.php" class="button icon">XML Files</a>
											</footer>
										</section>

                                                                                <section>
                                                                                    <h3> Modify XLS Files</h3>
											<footer>
                                                                                            Step 1: Create Job<br>
                                                                                            Step 2: Import File<br>
                                                                                            Step 3: Process File<br>
                                                                                            Step 4: Select Mapping<br>
                                                                                            Step 5: Save Mapping<br>
                                                                                            Step 6: Process Job<br>
                                                                                            Step 7: Collect Output<br>
                                                                                            <a href="xls_handler.php" class="button icon">XLS/CSV Files</a>
											</footer>
										</section>

								</div>
							</div>
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