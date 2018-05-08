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

                $title = "Step 5: Complete Specification";
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

									<h3>Modify a new mapping specification, or edit an existing mapping specification.</h3>
<?php

$_SESSION["logfile"] = fopen($_SESSION["log_fileLocation"], "a");

////Get SM Object Names. Optional parameter true loads only the objects set to visible.
//$_SESSION["visible_SM_Objects"] = loadSM_ObjectsFromDatabase(true);
//$_SESSION["numberOfVisibleSMObjects"] = count($_SESSION["visible_SM_Objects"]);

//concatenate Old Provider Names/IDs.
$_SESSION["listOfFullOldProviderNames"] = array();
for($x = 0; $x < $_SESSION["numberOfOldProviders"]; $x++) {
    $fullProviderName = $_SESSION["affiliateProviderIDsAndNames"][$x][1] . ' (' . $_SESSION["affiliateProviderIDsAndNames"][$x][0] . ')';
    array_push($_SESSION["listOfFullOldProviderNames"],$fullProviderName);
}
//initialize blank mapping array. This is necessary in order to display the input form if no specifications will be loaded.
$providerMappingSpecs = array();
for($x = 0; $x < $_SESSION["numberOfOldProviders"]; $x++) {
    $providerMappingSpecs[$_SESSION["affiliateProviderIDsAndNames"][$x][0]] = "";
}

//Get New Provider Names/IDs into a string.
$_SESSION["listOfFullNewProviderNames"] = array();
for($x = 0; $x < $_SESSION["numberOfNewProviders"]; $x++) {
    $fullProviderName = $_SESSION["voanlProviderIDsAndNames"][$x][1] . ' (' . $_SESSION["voanlProviderIDsAndNames"][$x][0] . ')';
    array_push($_SESSION["listOfFullNewProviderNames"],$fullProviderName);
}
$stringOfNewProviders = implode('", "',$_SESSION["listOfFullNewProviderNames"]);

//Load user specs into mapping array, if necessary.
if($_POST['selectedSpecName']!="") {
	$tagSpecs = loadTagSpecsFromDatabase($_POST['selectedSpecName']);
	$providerMappingSpecs = loadProviderMappingSpecsFromDatabase($_POST['selectedSpecName'],$providerMappingSpecs);
	DisplayMsg('The following mapping specfication was loaded: <b>' . $_POST['selectedSpecName'] . '</b>',"S");
        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", The following set of specifications has been loaded: " . $_POST['selectedSpecName'] . "\n");
//        echo "<br> TagList <br>";
//        print_r($tagSpecs);
//        echo "<br> Mapping <br>";
//        print_r($providerMappingSpecs);
}

?>

<!--Create user input form:-->
<link rel="stylesheet" href="stylesheets/jquerySheet.css">
<script src="//code.jquery.com/jquery-1.12.4.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<form action="xml_handler6.php" method="post">
    Check the boxes corresponding to the top level elements that you wish to <b>exclude</b> from your new file:<br />
    <?php 
    //Create checkboxes for top level tags.
    for($x = 0; $x < $_SESSION["numberOfTags"]; $x++) {
        $xmlTag = $_SESSION["XML_Tags"][$x];
        echo '<input type="checkbox" name="tagsToRemove[]" value="' . $xmlTag . '"';
        if (in_array($xmlTag, $tagSpecs)) {
                echo " checked";
        }
        echo '>' . $xmlTag . '<br />';
    }
    ?>
    <hr /><br />Configure the provider mapping. Type <b>spacebar in each textbox</b> to see the list of available values.<br />
    <?php
    echo '<table>';
    echo '<tr><th>Providers from file - "' . $_SESSION["original_filename"] . '"</th><th>Destination Provider</th></tr>';
    for($x = 0; $x < $_SESSION["numberOfOldProviders"]; $x++) {
        //Create mapping row #x.
        echo '<tr>';
        //Display SM Objects in left column
        echo '<td>';
        echo $_SESSION["affiliateProviderIDsAndNames"][$x][1] . ' (' . $_SESSION["affiliateProviderIDsAndNames"][$x][0] . ')';
        echo '</td>';
        //create input with autocomplete
        echo '<td>';
        $defaultNewID = $providerMappingSpecs[$_SESSION["affiliateProviderIDsAndNames"][$x][0]]; //get mapped id into variable
        $defaultValue = '';
        if($defaultNewID) {  //If mapping spec has been loaded for this provider, generate full name for default display.
            $defaultNewName = $_SESSION["voanlMapping"][$defaultNewID];
            $defaultValue = $defaultNewName . ' (' . $defaultNewID . ')';
        }
        echo '<input type="text" width="500" id="autocomplete' . $x . '" name="NewProviderNameID' . $x . '" value="' . $defaultValue . '">';
        echo '</td>';

        echo '<script>';
        echo '$( "#autocomplete' . $x . '" ).autocomplete({';
        echo '  source: [ "' . $stringOfNewProviders . '" ]';
        echo '});';
        echo '</script>';
        //Still need to add default value option
        echo '</tr>';
    }
    echo '</table>';
    ?>
   Enter a name to save these specifications for future use. This will <b>overwrite</b> any specifications previously saved by that name.<br />
    <?php
    echo '<input type="text" value="' . $_POST['selectedSpecName'] . '" name="specificationName" required><br>';
    ?>
    <input type="submit" value="Save and Continue" name="submit">
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