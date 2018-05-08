<!DOCTYPE HTML>

<html>
	<head>
                <meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<!--[if lte IE 8]><script src="assets/js/ie/html5shiv.js"></script><![endif]-->
		<link rel="stylesheet" href="assets/css/main.css" />
		<!--[if lte IE 8]><link rel="stylesheet" href="assets/css/ie8.css" /><![endif]-->
                
                
		<!-- Scripts -->

			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/jquery.dropotron.min.js"></script>
			<script src="assets/js/skel.min.js"></script>
			<script src="assets/js/util.js"></script>
			<!--[if lte IE 8]><script src="assets/js/ie/respond.min.js"></script><![endif]-->
			<script src="assets/js/main.js"></script>
                        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
                                        
                <?php
                $title = "Admin Tools";
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
								<a href="index.php"><img src="images/logo-national-color" alt="VOA Logo" style="width:300px;"></a>
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

											<p>Coming Soon</p>

											<h3>More Tools explanation</h3>
											<p>Explain tools here</p>
<?php
require 'php_libraries/utilities.php';
$conn = connectToDatabase();
$sql = 'Select JobID, JobName, AffiliateDataSource, LogFileName, `Status`, StartingFileType FROM ConversionJobs ORDER BY `Status` DESC';
$jobData = mysqli_query($conn, $sql);

echo '<table style="font-size: small">';
echo '<tr><th>Job ID</th><th>Job Name</th><th>Affiliate</th><th>Log File</th><th>Job Status</th><th></th><th></th></tr>';
while($row = mysqli_fetch_assoc($jobData)) {
    //each row corresponds to one job record
    echo '<tr>';
    //Display SM Objects in left column
    echo '<td>';
    echo $row["JobID"];
    echo '</td>';
    echo '<td>';
    echo $row["JobName"];
    echo '</td>';
    echo '<td>';
    echo $row["AffiliateDataSource"];
    echo '</td>';
    echo '<td>';
    echo $row["LogFileName"];
    echo '</td>';
    echo '<td>';
    echo $row["Status"];
    echo '</td>';
    echo '<td>';
    if ($row["Status"]=='In Progress' and $row["StartingFileType"]=='xls') {  //for now, only show import button for xls files. (SecureManage)
        echo '<form action="ImportOrDeleteJob.php" method="post">';
        echo '<button type="submit" value="Imported_' . $row["JobID"] . '" name="taskAndID">Post</button>';
        echo '</form>';
    }
    echo '</td>';
    echo '<td>';
    if ($row["Status"]=='In Progress') {
        echo '<form action="ImportOrDeleteJob.php" method="post">';
        echo '<button type="submit" value="Deleted_' . $row["JobID"] . '" name="taskAndID">Delete</button>';
        echo '</form>';
    }
    echo '</td>';
/*    //create input with autocomplete
    echo '<td>';
    $defaultNewID = $providerMappingSpecs[$_SESSION["affiliateProviderIDsAndNames"][$x][0]]; //get mapped id into variable
    $defaultValue = '';
    if($defaultNewID) {  //If mapping spec has been loaded for this provider, generate full name for default display.
        $defaultNewName = $_SESSION["voanlMapping"][$defaultNewID];
        $defaultValue = $defaultNewName . ' (' . $defaultNewID . ')';
    }
    echo '<input type="text" id="autocomplete' . $x . '" name="NewProviderNameID' . $x . '" value="' . $defaultValue . '">';
    echo '</td>';

    echo '<script>';
    echo '$( "#autocomplete' . $x . '" ).autocomplete({';
    echo '  source: [ "' . $stringOfNewProviders . '" ]';
    echo '});';
    echo '</script>';*/
    echo '</tr>';
}
echo '</table>';
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