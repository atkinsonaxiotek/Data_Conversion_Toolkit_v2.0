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
$original_filename = $_SESSION["original_filename"];
$new_dir = "new_files/";
$new_file = $new_dir . "new_" . substr($original_filename,0,-3) . "xml";
$_SESSION["specName"] = $_POST["specificationName"];


$_SESSION["ID_Mapping"] = mapOldIDsToNewIDs($_SESSION["affiliateProviderIDsAndNames"]);
$_SESSION["arrayOfUnwantedTags"] = $_POST['tagsToRemove'];
        
writeSpecsToDatabase($_SESSION["arrayOfUnwantedTags"],$_SESSION["ID_Mapping"],$_SESSION["specName"]);

if(empty($_SESSION["arrayOfUnwantedTags"]))  {
    DisplayMsg("You didn't select any tags. No sections will be removed from your file.","I");
  }
  else  {
    $N = count($_SESSION["arrayOfUnwantedTags"]);
    echo '<div class="alert alert-info alert-dismissible">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Info:&nbsp</strong> You selected ' . $N . ' sections(s) for removal: <br />';
 
    for($i=0; $i < $N; $i++)    {
      echo($i+1 . ". " . $_SESSION["arrayOfUnwantedTags"][$i] . " <br />");
    }
    echo '</div>';
  }

echo 'Your providers will be replaced according to this mapping. <br />';
echo '<table>';
echo '<tr><th><b>Previous Value - Affiliate</b></th><th><b>New Value - National</b></th></tr>';
for($x = 0; $x < $_SESSION["numberOfOldProviders"]; $x++) {
    //This section displays all rows
/*  echo '<tr><td>';
    echo $_SESSION["affiliateProviderIDsAndNames"][$x][1] . ' (' . $_SESSION["affiliateProviderIDsAndNames"][$x][0] . ')';
    echo '</td><td>';
    if(array_key_exists($_SESSION["affiliateProviderIDsAndNames"][$x][0],$_SESSION["ID_Mapping"])){
        echo $_SESSION["voanlMapping"][$_SESSION["ID_Mapping"][$_SESSION["affiliateProviderIDsAndNames"][$x][0]]] . ' (' . $_SESSION["ID_Mapping"][$_SESSION["affiliateProviderIDsAndNames"][$x][0]] . ')';
        }
    echo '</td></tr>';
*/
    //This section displays only the rows with valid mapping.
    if(array_key_exists($_SESSION["affiliateProviderIDsAndNames"][$x][0],$_SESSION["ID_Mapping"])){
        echo '<tr><td>';
        echo $_SESSION["affiliateProviderIDsAndNames"][$x][1] . ' (' . $_SESSION["affiliateProviderIDsAndNames"][$x][0] . ')';
        echo '</td><td>';
        echo $_SESSION["voanlMapping"][$_SESSION["ID_Mapping"][$_SESSION["affiliateProviderIDsAndNames"][$x][0]]] . ' (' . $_SESSION["ID_Mapping"][$_SESSION["affiliateProviderIDsAndNames"][$x][0]] . ')';
        echo '</td></tr>';
    }
}
echo '</table>';

?>
Next, output new XML file.                                                                   
<form action="xml_handler7.php" method="post">
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