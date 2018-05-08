<?php

/********************************************************************************
Function: getOldProviderIDsAndNames
Takes a target filename. Reads the XML file and returns an 2d array of provider IDs and Names from the recordset_id attribute of each <provider> tag,
and the <name> tag inside each <provider> tag.
Requires a well-formed XML file in which each Provider tag contains a name tag.
*********************************************************************************/

function getOldProviderIDsAndNames($target) {
	$xmlfile = fopen($target, "r");
//	$providerNames = array();
//	$providerIDs = array();
	$providerIDsAndNames = array();
	$readname = 0;								//only read names inside provider tags
//	for($x = 0; $x <= 10000; $x++) {  			// for testing the first part of large files
	while(!feof($xmlfile)) {
		$currentLine = fgets($xmlfile);
		if (strpos($currentLine,"<Provider ")==6){	
			$providerID = substr($currentLine,strpos($currentLine,'record_id="')+11,strpos($currentLine,'" date_added')-27);
//			array_push($providerIDs,$providerID);	
			$readname = 1;
		}
		if ($readname == 1 and substr($currentLine,0,15)=="         <name>") {
			$providerName = substr($currentLine,strpos($currentLine,">")+1,strpos($currentLine,"</")-15);
//			array_push($providerNames,$providerName);
                        //$providerIDsAndNames[$providerID] = $providerName;
			array_push($providerIDsAndNames,array($providerID,$providerName));
			$readname = 0;
		}
	}
	fclose($xmlfile);
	return $providerIDsAndNames;
}

/********************************************************************************
Function: getNewProviderIDsAndNames
Reads from AxioTek MySQL database.
Data provided by Anne. Returns a 2 dimensional array of provider IDs and Names.
 * modified to include dataSource 20180417
*********************************************************************************/

function getNewProviderIDsAndNames() {
	$conn = connectToDatabase();
	
	//Run query
	$sql = "SELECT DISTINCT NewProviderName, ProviderID FROM ProviderIDs WHERE Organization='" . $_SESSION["dataSource"] . "'";
	$result = mysqli_query($conn, $sql);
	
	//Build array
	$providerIDsAndNames = array();
	if (mysqli_num_rows($result) > 0) {
		while($row = mysqli_fetch_assoc($result)) {
			array_push($providerIDsAndNames,array($row["ProviderID"],$row["NewProviderName"]));
		}
	} else {
//		echo "0 results";
	}
	mysqli_close($conn);
	return $providerIDsAndNames;

}

/********************************************************************************
Function: getTopLevelTags
Takes a target filename. Returns an array of XML tags that were preceded by exactly 1 indent (3 spaces).
Used to produce the list of checkboxes that is displayed for user input.
*********************************************************************************/

function getTopLevelTags($target) {
	$xmlfile = fopen($target, "r");
	$topLevelTags = array();
//	for($x = 0; $x <= 10000; $x++) {  			// for testing the first part of large files
	while(!feof($xmlfile)) {
		$currentLine = fgets($xmlfile);
		if (strpos($currentLine,"<")==3){		//could be generalized to deal with lower levels, since indents are multiples of 3 spaces
			$xmltag = substr($currentLine,strpos($currentLine,"<")+1,strpos($currentLine,">")-4);
			if (substr($xmltag,0,1)!="/") {		//exclude closing XML tags
				array_push($topLevelTags,$xmltag);
			}
		}
	}
	fclose($xmlfile);
	return $topLevelTags;
}

/********************************************************************************
Function: loadTagSpecsFromDatabase
Takes one argument, the name of a set of saved specifications.
Returns an array of previously saved selected checkboxes.
*********************************************************************************/

function loadTagSpecsFromDatabase($specName) {
	$conn = connectToDatabase();
	
	//Run query
	$sql = 'SELECT ControlLabel FROM Specifications WHERE SpecName="' . $specName . '" AND ControlCategory="xmlTopLevelTag"';
	$result = mysqli_query($conn, $sql);
	
	//Build array
	$checkedTags = array();
	if (mysqli_num_rows($result) > 0) {
		while($row = mysqli_fetch_assoc($result)) {
			array_push($checkedTags,$row["ControlLabel"]);
		}
	} else {
//		echo "0 results";
	}
	mysqli_close($conn);
	return $checkedTags;
}

/********************************************************************************
Function: loadDefaultProviderMappingSpecsFromProviderIDsTable
 * Need description.
Takes one argument, the name of a set of saved specifications.
Returns an associative array of previously mapped provider ID.
*********************************************************************************/

function loadDefaultProviderMappingSpecsFromProviderIDsTable($specName, $mappingArray) {
	$conn = connectToDatabase();
	
	//Run query
	$sql = 'SELECT ID, ProviderID FROM ProviderIDs WHERE Organization="' . $_SESSION["dataSource"] . '"';
	$result = mysqli_query($conn, $sql);
	
	//Build array
	if (mysqli_num_rows($result) > 0) {
		while($row = mysqli_fetch_assoc($result)) {
			$mappingArray[$row["ID"]] = $row["ProviderID"];
		}
	} else {
//		echo "0 results";
	}
	mysqli_close($conn);
	return $mappingArray;
}

/********************************************************************************
Function: loadProviderMappingSpecsFromDatabase
Takes one argument, the name of a set of saved specifications.
Returns an associative array of previously mapped provider ID.
*********************************************************************************/

function loadProviderMappingSpecsFromDatabase($specName, $mappingArray) {
	$conn = connectToDatabase();
	
	//Run query
	$sql = 'SELECT ControlLabel, ControlValue FROM Specifications WHERE SpecName="' . $specName . '" AND ControlCategory="providerMapping"';
	$result = mysqli_query($conn, $sql);
	
	//Build array
	if (mysqli_num_rows($result) > 0) {
		while($row = mysqli_fetch_assoc($result)) {
			$mappingArray[$row["ControlLabel"]] = $row["ControlValue"];
		}
	} else {
//		echo "0 results";
	}
	mysqli_close($conn);
	return $mappingArray;
}

/********************************************************************************
Function: mapOldIDsToNewIDs
Takes one argument, a 2d array of affiliate provider IDs and Names.
Returns an associative array mapping the old IDs to the new IDs provided by the user on the previous page.
Only includes non-null selections, to avoid overwriting existing XML data.
 * Modified 20180321 to only include 
*********************************************************************************/

function mapOldIDsToNewIDs($oldProviders) {
	$providerIDMapping = array();
	for ($i = 0; $i < count($oldProviders); $i++) {
		//if(strlen($_POST["NewProviderNameID" . $i])>0) { //obsolete 20180321
		if(in_array($_POST["NewProviderNameID" . $i],$_SESSION["listOfFullNewProviderNames"])) {
                    $explodeName = explode(" (",$_POST["NewProviderNameID" . $i]);
                    $newID = end($explodeName);
                    $providerIDMapping[$oldProviders[$i][0]] = rtrim($newID,")");
		}
	}
	return $providerIDMapping;
}

/********************************************************************************
Function: mapOldNamesToNewIDs   --difficult to use properly
Takes one argument, a 2d array of affiliate provider IDs and Names.
Returns an associative array mapping the old names to the new IDs provided by the user on the previous page.
Only includes non-null selections, to avoid overwriting existing XML data.
 * Modified 20180321 to only include 
*********************************************************************************/

function mapOldNamesToNewIDs($oldProviders) {
	$providerIDMapping = array();
	for ($i = 0; $i < $_SESSION["numberOfOldProviders"]; $i++) {
		//if(strlen($_POST["NewProviderNameID" . $i])>0) { //obsolete 20180321
		if(in_array($_POST["NewProviderNameID" . $i],$_SESSION["listOfFullNewProviderNames"])) {
                    $explodeName = explode(" (",$_POST["NewProviderNameID" . $i]);
                    $newID = end($explodeName);
                    $providerIDMapping[$oldProviders[$i][1]] = rtrim($newID,")");
		}
	}
	return $providerIDMapping;
}

/********************************************************************************
Function: writeSpecsToDatabase
Takes an array of unwanted tags, a provider mapping, and a name by which to save the specifications.
Deletes any existing specifications (tags/mapping) stored using that name in the Specifications table.
Writes the specifications to the Specifications table in the database.
*********************************************************************************/

function writeSpecsToDatabase($tags,$mapping,$specName) {
	$conn = connectToDatabase();
	
	//Remove any rows with same specname
	$sql = 'DELETE FROM Specifications WHERE SpecName="' . $specName . '"';
	if (mysqli_query($conn, $sql)) {
		DisplayMsg("Overwriting any previous specifications named: <b>" . $specName . "</b><br />","I");
                fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Overwriting any previous specifications named: " . $specName . ".\n");
	} else {
		echo "Error: " . $sql . "<br>" . mysqli_error($conn);
	}
	
	//Insert tag checkboxes
	$N = count($tags);
	for ($i = 0; $i < $N; $i++) {
		$sql_part1 = 'INSERT INTO Specifications (ControlType, ControlValue, ControlLabel, ControlCategory, SpecName) VALUES ("CheckBox", "TRUE", "';
		$sql_part2 = $tags[$i] . '", "xmlTopLevelTag", "' . $specName . '")';
		$sql = $sql_part1 . $sql_part2;
		if (mysqli_query($conn, $sql)) {
			//echo "New xmltag record created successfully: " . $tags[$i] . "<br />";
                        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", New xmltag record created successfully: " . $tags[$i] . ".\n");
			
		} else {
			echo "Error: " . $sql . "<br>" . mysqli_error($conn);
		}
	}

	//Insert provider mapping
	foreach($mapping as $old => $new) {
		if (strlen($new)>0) {
			$sql_part1 = 'INSERT INTO Specifications (ControlType, ControlValue, ControlLabel, ControlCategory, SpecName) VALUES ("DropDown", "';
			$sql_part2 = $new . '", "' . $old . '", "providerMapping", "' . $specName . '")';
			$sql = $sql_part1 . $sql_part2;
			if (mysqli_query($conn, $sql)) {
				//echo "New mapping record created successfully: " . $old . " becomes " . $new . "<br />";
                                fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", New " . $specName . " mapping record created successfully: " . $old . " -- maps to -- " . $new . ".\n");
			} else {
				echo "Error: " . $sql . "<br>" . mysqli_error($conn);
			}
		}
	}
	
	mysqli_close($conn);
}

/********************************************************************************
Function: removeTopLevelSections
Takes a target filename, a new filename, a list of top-level XML tags, and a log filename. Writes a new XML file with corresponding sections removed.
Deletes the target file from the server when done.
*********************************************************************************/

function removeTopLevelSections($target_XML_file,$new_XML_file,$tags,$log_filename) {
	$xmlfile = fopen($target_XML_file, "r");
	$newfile = fopen($new_XML_file, "w");
	$logfile = fopen($log_filename, "a");
	$lineCounter = 0;
	$tagCounter = 0;
	$currentTag = $tags[$tagCounter];
        array_push($tags,""); //to avoid "undefined offset" error in final iteration of ---$currentTag = $tags[$tagCounter];--- at line 214 presently.
	$writingSwitch = 1;
//	for($x = 0; $x <= 10000; $x++) {		//	for testing large files

	while(!feof($xmlfile)) {
            $lineCounter++;
            $currentLine = fgets($xmlfile);
            //If line contains opening tag, turn off writing.
            if (strpos($currentLine,$currentTag . ">") == 4) { // If the tagname begins at character number 5 (should be preceded by "   <")
                $writingSwitch = 0;
                fwrite($logfile,"At " . date("h:i:sa") . ", writing was turned off at line " . $lineCounter . ":\n");
                fwrite($logfile,$currentLine);
            }
            if ($writingSwitch == 1) {
                fwrite($newfile,$currentLine);
            }
            //If line contains closing tag, turn on writing and increment counter.
            if (strpos($currentLine,$currentTag . ">") == 5) { // If the tagname begins at character number 6 (should be preceded by "   </")
                $tagCounter++;
                $currentTag = $tags[$tagCounter];
                $writingSwitch = 1;
                fwrite($logfile,"At " . date("h:i:sa") . ", writing was turned on after line " . $lineCounter . ":\n");
                fwrite($logfile,$currentLine . "\n");
            }
	}
	fwrite($logfile,"Intermediate step completed at " . date("h:i:sa") . "\n\n");
	fclose($xmlfile);
	fclose($newfile);
	fclose($logfile);
	//unlink($target_XML_file); //done with original file, remove it from uploads directory.
}

/********************************************************************************
Function: changeProviderNamesAndIDs   Created 11/16/17 
Takes a target filename, a new filename, and a logfile. Writes a new XML file with the provider <name> tags and IDs replaced.
!!!!!    If the value of the new provider is null, the old provider is not replaced.
Deletes the target file from the server when done.
 * Modified 2/7/18 - all mappings are now session variables.
*********************************************************************************/

function changeProviderNamesAndIDs($target_XML_file,$new_XML_file,$log_filename) {
	$xmlfile = fopen($target_XML_file, "r");
	$newfile = fopen($new_XML_file, "w");
	$logfile = fopen($log_filename, "a");
	$lineCounter = 0;
	//$providerCounter = 0;
	//$currentProvider = $_SESSION["affiliateProviderIDs"][$providerCounter];
	$providerNameSwitch = 0;
//	for($x = 0; $x <= 10000; $x++) {		//	for testing large files
	while(!feof($xmlfile)) {
            $lineCounter++;
            $currentLine = fgets($xmlfile);
            //If line is a provider tag, replace the IDs in the attributes.
            if (strpos($currentLine,"<Provider ")==6){
                $lineWritten=False;
                //Check line for each provider ID, Replace record_id
                foreach($_SESSION["ID_Mapping"] as $old => $new) {
                    if (strpos($currentLine,'"' . $old . '"')!==False){
                        $tempLine=substr_replace($currentLine,$new,strpos($currentLine,$old),strlen($old));
                        //If line still contains this provider ID, Replace system_id.
                        if (strpos($tempLine,$old)!==False){
                            $newLine=substr_replace($tempLine,$new,strpos($tempLine,$old),strlen($old));
                        } else {$newLine=$tempLine;}
                        fwrite($newfile,$newLine);
                        fwrite($logfile,"The provider ID was changed at line " . $lineCounter . ":\n");
                        fwrite($logfile,$currentLine);
                        fwrite($logfile,"was replaced with:\n");
                        fwrite($logfile,$newLine . "\n");
                        $providerNameSwitch = 1;
                        $lineWritten=True;
                        break;
                    }
                }
                if($lineWritten==False) {  	//if no changes were made, write the line without modification
                    fwrite($newfile,$currentLine);
                    fwrite($logfile,"No modification to provider ID at line " . $lineCounter . ":\n");
                    fwrite($logfile,$currentLine);
                }
            }
            //If line is a name tag, check the list of old mapped names and replace it.
            elseif (substr($currentLine,0,15)=="         <name>") {
                $lineWritten=False;
                foreach($_SESSION["affiliateMapping"] as $ID => $name) {
                    if (strpos($currentLine,'>' . $name . '<')!==False){	//If line contains this name
                        if (array_key_exists($ID,$_SESSION["ID_Mapping"])){	//If the provider has been mapped
                            $newLine=substr_replace($currentLine,$_SESSION["voanlMapping"][$_SESSION["ID_Mapping"][$ID]],strpos($currentLine,$name),strlen($name));
                            fwrite($newfile,$newLine);
                            fwrite($logfile,"The provider name was changed at line " . $lineCounter . ":\n");
                            fwrite($logfile,$currentLine);
                            fwrite($logfile,"was replaced with:\n");
                            fwrite($logfile,$newLine . "\n");
                            $lineWritten=True;
                            break;
                        }
                    }
                }
                if($lineWritten==False) {  
                    fwrite($newfile,$currentLine);	//write line without modification
                    fwrite($logfile,"No modification to name at line " . $lineCounter . ":\n");
                    fwrite($logfile,$currentLine);
                }
            }
            //In all other lines, search for old IDs and replace according to mapping.
            else {
                $lineWritten=False;
                foreach($_SESSION["ID_Mapping"] as $old => $new) {
                    if (strpos($currentLine,'>' . $old . '<')!==False){   //If line contains this ID, replace record_id
                        $newLine=substr_replace($currentLine,$new,strpos($currentLine,$old),strlen($old));
                        fwrite($newfile,$newLine);
                        fwrite($logfile,"The provider ID was changed at line " . $lineCounter . ":\n");
                        fwrite($logfile,$currentLine);
                        fwrite($logfile,"was replaced with:\n");
                        fwrite($logfile,$newLine . "\n");
                        $lineWritten=True;
                        break;
                    }
                }
                if($lineWritten==False) {  
                    fwrite($newfile,$currentLine);	//write line without modification
                }
            }
	}
	fwrite($logfile,"File modification completed successfully at " . date("h:i:sa") . "\n\n");
	fclose($xmlfile);
	fclose($newfile);
	fclose($logfile);
	//unlink($target_XML_file); //done with intermediate file, remove it from intermediate directory.
}



?>