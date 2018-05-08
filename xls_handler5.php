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


//-------------------------------------start Provider Mapping---------------------------------------------------------------------------------------------

//Get list of providers from SM Intake sheet.
$conn = connectToDatabase();
$sqlGetSMProviderNames = "SELECT DISTINCT ProviderIDs.ID, `" . $_SESSION["BuildingColumnName"] . "`  FROM Intake "
        . "LEFT JOIN ProviderIDs ON `" . $_SESSION["BuildingColumnName"] . "`=SecurManageName";
if (mysqli_query($conn, $sqlGetSMProviderNames)) {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", ProviderNamesFromIntakeSheet sql statement executed:\n\t" . $sqlGetSMProviderNames . "\n");
}
else {
    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", ProviderNamesFromIntakeSheet_Error: " . mysqli_error($conn) . ".\n\t" . $sqlGetSMProviderNames . "\n");
    echo 'UUID_Error - ' . mysqli_error($conn);
}
//transpose to match mapping array below.
$_SESSION["providerNamesFromSMIntakeSheet"] = flipDiagonally(mysqliColumnsToMultiArray(mysqli_query($conn, $sqlGetSMProviderNames)));
/*echo "<br>";
echo "from file:";
echo "<br>";
print_r($_SESSION["providerNamesFromSMIntakeSheet"]);
echo "<br>";*/
mysqli_close($conn);


//get the default provider mapping from the ProviderIDs table 
$defaultProviderMapping = getDefaultProviderMapping(); //20180417 - Table includes MA, WY, PR, NJDV, function uses dataSource session variable.
$_SESSION["voanlProviderIDsAndNames"] = $defaultProviderMapping[0];
//$_SESSION["smProviderIDsAndNames"] = $defaultProviderMapping[1];   -this reads from Anne Table, it's better to get info from input file.
$_SESSION["smProviderIDsAndNames"] = $_SESSION["providerNamesFromSMIntakeSheet"];
/*
echo "<br>";
echo "from DB";
echo "<br>";
print_r($_SESSION["smProviderIDsAndNames"]);
echo "<br>";*/
//These two integers may by different.
$_SESSION["numberOfvoanlProviders"] = count($_SESSION["voanlProviderIDsAndNames"]);
$_SESSION["numberOfsmProviders"] = count($_SESSION["smProviderIDsAndNames"]);
//generate associative arrays.
$_SESSION["smProviderMapping"] = turnTwoDimensionalArrayIntoAssociativeArray($_SESSION["smProviderIDsAndNames"]);
$_SESSION["voanlProviderMapping"] = turnTwoDimensionalArrayIntoAssociativeArray($_SESSION["voanlProviderIDsAndNames"]);

//concatenate SM Provider Names/IDs.
$_SESSION["listOfFullsmProviderNames"] = array();
for($x = 0; $x < $_SESSION["numberOfsmProviders"]; $x++) {
    $fullProviderName = $_SESSION["smProviderIDsAndNames"][$x][1] . ' (' . $_SESSION["smProviderIDsAndNames"][$x][0] . ')';
    array_push($_SESSION["listOfFullsmProviderNames"],$fullProviderName);
}
//initialize blank mapping array. This is necessary in order to display the input form if no specifications will be loaded.
$providerMappingSpecs = array();
for($x = 0; $x < $_SESSION["numberOfsmProviders"]; $x++) {
    $providerMappingSpecs[$_SESSION["smProviderIDsAndNames"][$x][0]] = "";
}
//Get New Provider Names/IDs into a string. For display in jquery autocomplete
$_SESSION["listOfFullvoanlProviderNames"] = array();
for($x = 0; $x < $_SESSION["numberOfvoanlProviders"]; $x++) {
    $fullProviderName = $_SESSION["voanlProviderIDsAndNames"][$x][1] . ' (' . $_SESSION["voanlProviderIDsAndNames"][$x][0] . ')';
    array_push($_SESSION["listOfFullvoanlProviderNames"],$fullProviderName);
}
$stringOfNewProviders = implode('", "',$_SESSION["listOfFullvoanlProviderNames"]);

//Load user specs into mapping array, if necessary.
if($_POST['selectedSpecName']!="") {
        $providerMappingSpecs = loadProviderMappingSpecsFromDatabase($_POST['selectedSpecName'],$providerMappingSpecs);
//            echo "<br> provider mapping <br>";
//            print_r($providerMappingSpecs);
        DisplayMsg('The following provider mapping specification was loaded: <b>' . $_POST['selectedSpecName'] . '</b>',"S");
        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", The following provider mapping specification has been loaded: " . $_POST['selectedSpecName'] . "\n");
}
else {
    $providerMappingSpecs = loadDefaultProviderMappingSpecsFromProviderIDsTable($_POST['selectedSpecName'],$providerMappingSpecs);
}
//---------------------------------------end provider mapping
//----------------------------------------------------------------------------------------------------------------------------------
//---------------------------------------Start XMLPath Mapping
//Get visible SM Object Names. Optional parameter true loads only the objects set to visible.
$_SESSION["visible_SM_Objects"] = loadSM_ObjectsFromDatabase(true);
$_SESSION["numberOfVisibleSMObjects"] = count($_SESSION["visible_SM_Objects"]);

//concatenate SM Object Names and initialize blank mapping array.
$_SESSION["listOfSMFullObjectNames"] = array();
$XMLMappingSpecs = array();
for($x = 0; $x < $_SESSION["numberOfVisibleSMObjects"]; $x++) {
    $fullObjectName = $_SESSION["visible_SM_Objects"][$x][1] . '.' . $_SESSION["visible_SM_Objects"][$x][0];
    //append full object name to list:
    array_push($_SESSION["listOfSMFullObjectNames"],$fullObjectName);
    //create blank mapping association in mapping array, this is necessary in order to display the input form if no specifications will be loaded.
    $XMLMappingSpecs[$fullObjectName] = "";
}

//Get all SM Object Names into a string, for displaying in jQuery autocomplete  //not used yet, maybe use it if "add mapping" button starts working
$_SESSION["all_SM_Objects"] = loadSM_ObjectsFromDatabase();
$_SESSION["numberOfAllSMObjects"] = count($_SESSION["all_SM_Objects"]);
$_SESSION["listOfAllSMFullObjectNames"] = array();
for($x = 0; $x < $_SESSION["numberOfAllSMObjects"]; $x++) {
    $fullObjectName = $_SESSION["all_SM_Objects"][$x][1] . '.' . $_SESSION["all_SM_Objects"][$x][0];
    //append full object name to list:
    array_push($_SESSION["listOfAllSMFullObjectNames"],$fullObjectName);
    //create blank mapping association in mapping array, this is necessary in order to display the input form if no specifications will be loaded.
    $XMLMappingSpecs[$fullObjectName] = "";
}
$stringOfAllSMObjects = implode('", "',$_SESSION["listOfAllSMFullObjectNames"]);

//Get XML paths into a string, for displaying in jQuery autocomplete.
$columnOfXMLPaths = loadArbitraryColumnsFromDatabase(array("Path"),"DistinctXMLPaths");
$arrayOfXMLPaths = mysqliColumnToArray($columnOfXMLPaths);
$stringOfXMLPaths = implode('", "',$arrayOfXMLPaths);
$stringOfEscapedXMLPaths = implode('&quot;, &quot;',$arrayOfXMLPaths);

//Load user specs into mapping array, if necessary.
if($_POST['selectedSpecName']!="") {
	$XMLMappingSpecs = loadXMLPathSpecsFromDatabase($_POST['selectedSpecName'],$XMLMappingSpecs);
//        echo "<br> xml mapping <br>";
//        print_r($XMLMappingSpecs);
        DisplayMsg('The following xmlPath mapping specification was loaded: <b>' . $_POST['selectedSpecName'] . '</b>');
        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", The following xmlPath mapping specification has been loaded: " . $_POST['selectedSpecName'] . "\n");
}
//-----------------------------------end XMLPath mapping-----------------------------------------------------------------------------------------------
?>

<!--Create user input form:-->
<link rel="stylesheet" href="stylesheets/jquerySheet.css">
<script src="//code.jquery.com/jquery-1.12.4.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript">
function addRowOptionToSpecTable() {
//      Not Working Yet!!!!!
//    var right_td_input = '<input type="text" id="autocomplete50" name="SMObjectFullName' + arguments[0] + '" value="">';
    var jquery_scripts = '<link rel="stylesheet" href="stylesheets/jquerySheet.css"><script src="//code.jquery.com/jquery-1.12.4.js"></script' + '><script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script' + '>';
    var right_td_input = '<input type="text" class="ui-autocomplete-input" id="autocomplete50" name="SMObjectFullName' + arguments[0] + '" value="" autocomplete="off">';
    var right_td_script = '<script>$( "#autocomplete' + arguments[0] + '" ).autocomplete({source: [ "' + arguments[1] + '" ]});</script' + '>';
    document.getElementById("specTable").insertRow(-1).innerHTML = jquery_scripts + '<td>1</td><td>' + right_td_input + right_td_script + '</td>';
}
</script>          

<form action="xls_handler6.php" method="post">
    <br />Configure the provider mapping. Type <b>spacebar in each textbox</b> to see the list of available values.<br />
    <?php
    echo '<table>';
    echo '<tr><th>Providers from file - "' . $_SESSION["original_filename"] . '"</th><th>Destination Provider</th></tr>';
    for($x = 0; $x < $_SESSION["numberOfsmProviders"]; $x++) {
        //Create mapping row #x.
        echo '<tr>';
        //Display SM Objects in left column
        echo '<td>';
        echo $_SESSION["smProviderIDsAndNames"][$x][1] . ' (' . $_SESSION["smProviderIDsAndNames"][$x][0] . ')';
        echo '</td>';
        //create input with autocomplete
        echo '<td>';
        $defaultNewID = $providerMappingSpecs[$_SESSION["smProviderIDsAndNames"][$x][0]]; //get mapped id into variable
        $defaultValue = '';
        if($defaultNewID) {  //If mapping spec has been loaded for this provider, generate full name for default display.
            $defaultNewName = $_SESSION["voanlProviderMapping"][$defaultNewID];
            $defaultValue = $defaultNewName . ' (' . $defaultNewID . ')';
        }
        echo '<input type="text" id="provider_autocomplete' . $x . '" name="NewProviderNameID' . $x . '" value="' . $defaultValue . '" style="width: 600px">';

        echo '<script>';
        echo '$( "#provider_autocomplete' . $x . '" ).autocomplete({';
        echo '  source: [ "' . $stringOfNewProviders . '" ]';
        echo '});';
        echo '</script>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
    ?>
    
    <hr />Configure the object mapping. Type a <b>forward slash "/" in each textbox</b> to see the list of available values.<br />
    <?php
    echo '<table id="specTable">';
    echo '<tr><th>SecurManage Object</th><th>XML Path</th></tr>';
    for($x = 0; $x < $_SESSION["numberOfVisibleSMObjects"]; $x++) {
        //Create mapping row #x.
        echo '<tr>';
        //Display SM Objects in left column
        echo '<td>';
        echo $_SESSION["listOfSMFullObjectNames"][$x];
        echo '</td>';
        //create input with autocomplete
        echo '<td>';
        echo '<input type="text" id="autocomplete' . $x . '" name="SMObjectFullName' . $x . '" value="' . $XMLMappingSpecs[$_SESSION["listOfSMFullObjectNames"][$x]] . '" style="width: 600px">';
        echo '<script>$( "#autocomplete' . $x . '" ).autocomplete({source: [ "' . $stringOfXMLPaths . '" ]});</script>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
// Add mapping row, not working yet.    
//    echo '<br><button type="button" onclick="addRowOptionToSpecTable(50,' . "'" .  $stringOfEscapedXMLPaths . "'" . ')">Insert new row</button> '
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