<?php

/********************************************************************************
Function: createCSVfilesFromXLS
Takes an xls file with 8 or more sheets. Creates 6 csv files for the first 6 sheets and writes them to the new_files directory.
 * Ignoring Res. Logs and Census for now.
*********************************************************************************/

function createCSVfilesFromXLS($filename) {
	require 'Classes/PHPExcel/IOFactory.php';
        $filenameWithCSVExtension = $filename . ".csv";
        
        //Initialize reader and create object
        $objReader = PHPExcel_IOFactory::createReader('Excel5');
        $objReader->setReadDataOnly(true);
        $file = "uploads/" . $filename . ".xls";
        $listOfSheetNames = $objReader->listWorksheetNames($file);
        
        for($sheetNumber = 0; $sheetNumber <= 5; $sheetNumber++) {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", begin loading " . $listOfSheetNames[$sheetNumber] . ".\n");
            //create object
            $objReader->setLoadSheetsOnly($listOfSheetNames[$sheetNumber]); 
            $objPHPExcel = $objReader->load($file);
            
            //Initialize writer
            $objWriter = new PHPExcel_Writer_CSV($objPHPExcel);
            $objWriter->setDelimiter(',');
            $objWriter->setEnclosure('"');
            $objWriter->setLineEnding("\r\n");
            
            //write new files
            $targetDirectory = 'intermediate_files/';
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", begin writing " . $listOfSheetNames[$sheetNumber] . " to csv.\n");
            $objWriter->save($targetDirectory . $sheetNumber . $filenameWithCSVExtension);
            $objPHPExcel->disconnectWorksheets();   //to save memory space
            unset($objPHPExcel);
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", finished with " . $listOfSheetNames[$sheetNumber] . ".\n");
            
            //Send value of $sheetNumber to jquery progress bar.
        }
}

/********************************************************************************
Function: createTablefromCSV
Reads the header of a csv file and creates a table with those field names.
Used to setup the database, but not used during operation of the app.
Returns the name of the csv sheet.
 * Also, as a byproduct, adds any new SM objects to the table of SM sheet/object names.
 * Also, as a byproduct, stores the building column name as a session variable.
*********************************************************************************/

function createTablefromCSV($filename,$linesToIgnore) {
	$conn = connectToDatabase();
        
	//get header names
        $headerArray = getCSVHeader($filename,$linesToIgnore);  //function defined directly below
        
        //get sheet name
        if ($linesToIgnore==9) {
            $sheetName = getSheetName($filename,7);  //function defined below getCSVHeader
        }
        else {
            $sheetName = getSheetName($filename,0);
        }
        
        //Drop table if exists, using stored procedure.
        $sqltabledrop = "CALL TableDrop('" . $sheetName  . "')";
        if (mysqli_query($conn, $sqltabledrop)) {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", table dropped: " . $sheetName . ".\n");
        }
        else {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", DropError: " . mysqli_error($conn) . ".\n");
            echo 'DropError - ' . mysqli_error($conn);
        }
        
        //generate sql for column names
        $columns="ID int NOT NULL AUTO_INCREMENT, "; //ID int NOT NULL AUTO_INCREMENT, 
        $i=1;
        foreach($headerArray as $columnName) {
            if(strlen($columnName)>0) {
                $columns .= "`$columnName` varchar(250)" . ', ';         //Add column name to CREATE TABLE statement
                
                //also store SM column names and sheet names in different table:
                $currentColumns = loadSM_ObjectsFromDatabase();    //default parameter loads all objects
                if(!in_array(array($columnName,$sheetName), $currentColumns)) {
                    writeSM_ObjectToDatabase($columnName,$sheetName);
                    fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", New SM Object added to Database: " . $columnName . $sheetName . ".\n");
                }
            }
            else{
                $columns .= "dummy" . $i . " varchar(250)" . ', ';         //Add dummy column name to CREATE TABLE statement
                $i +=1;
            }
            // also store the building column name as a session variable.
            if(substr($columnName, 0, 10) == 'Building N') {
                $_SESSION["BuildingColumnName"] = $columnName;
                //echo $_SESSION["BuildingColumnName"];
            }
        }
        $columns .= "PRIMARY KEY (ID)";
        
        //create table
	$sql = " CREATE TABLE `" . $sheetName . "` ( " . $columns . " )";
        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", SQL command executed: " . $sql . "\n");
        if (mysqli_query($conn, $sql)) {
            //echo '<br /.>';
            DisplayMsg("Success. Table Created: " . $sheetName);
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", table " . $sheetName . " created successfully.\n");
        }
        else {
            echo '<br /.>';
            DisplayMsg('Error - ' . mysqli_error($conn) . $sheetName,"D");
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", error: " . mysqli_error($conn) . "\n");
        }
	mysqli_close($conn);
        return $sheetName;
}

/********************************************************************************
Function: getCSVHeader
Takes a csv file and a number of lines (n) to ignore. Reads the (n+1)th line of the csv file and returns an array of header names.
*********************************************************************************/

function getCSVHeader($file,$n) {
	$csvfile = fopen($file, "r");
        for ($i = 0; $i <= $n; $i++) {
            $headerRow = fgetcsv($csvfile);
        } 
        fclose($csvfile);
	return $headerRow;
}

/********************************************************************************
Function: getSheetName
///Takes a csv file and a number of lines (n) to ignore. Reads the (n+1)th line of the csv file and returns the first element.
*********************************************************************************/

function getSheetName($file,$n) {
	$csvfile = fopen($file, "r");
        for ($i = 0; $i <= $n; $i++) {
            $tableNameRow = fgetcsv($csvfile);
        } 
        fclose($csvfile);
	return $tableNameRow[0];
}

/********************************************************************************
Function: writeSM_ObjectToDatabase
Takes two strings and writes them into the SM_Objects table.
*********************************************************************************/

function writeSM_ObjectToDatabase($columnName,$sheetName) {
	$conn = connectToDatabase();
	
        $sql = 'INSERT INTO SM_Objects (SM_Object_Name, SheetName) VALUES ("' . $columnName . '", "' . $sheetName . '")';
        if (mysqli_query($conn, $sql)) {
                echo "New SM Object added successfully: " . $columnName . "<br />";
        } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
	mysqli_close($conn);
}

/********************************************************************************
Function: importDatafromCSV
Takes a csv filename and an integer, and loads data into the corresponding table.
Deletes any rows where the SM ID column is null.
Also deletes the csv file.
*********************************************************************************/

function importDatafromCSV($filename,$sheetIndex) {
	$conn = connectToDatabase();
        $skiplines = $_SESSION["linesToIgnoreArray"];
        $headerArray = getCSVHeader($filename,$skiplines[$sheetIndex]);
        $tablename = $_SESSION["sheetnames"][$sheetIndex];
        
        //generate sql for data import
        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", begin importing data from " . $filename . " into " . $tablename . "\n");
        $sql = "LOAD DATA LOCAL INFILE '$filename'
        INTO TABLE `";
        $sql .= $tablename . "` FIELDS TERMINATED BY ','
        OPTIONALLY ENCLOSED BY '\"' 
        LINES TERMINATED BY '\\r\\n'
        IGNORE ";
        $sql .= $skiplines[$sheetIndex]+1 . " LINES 
        (";
        
        $i=1;
        foreach($headerArray as $columnName) {
            if(strlen($columnName)>0) {
                $sql .= "`$columnName`, ";
            }
            else{
                $sql .= "dummy" . $i . ', ';
                $i +=1;
            }
        }
        $sql = rtrim($sql,", ") . ")";

        //import data to table.
        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", SQL command executed: " . $sql . "\n");
        if (mysqli_query($conn, $sql)) {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Success!\n");
        }
        else {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Error - " . mysqli_error($conn) . "\n");
        }
        
        //Delete rows with no SM_ID
        $sql = "CALL `axiotek_voamapping`.`TableCleanup`('" . $tablename . "', 1, '`SM ID`', '')";
        fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", SQL command executed: " . $sql . "\n");
        if (mysqli_query($conn, $sql)) {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Success!\n");
        }
        else {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Error - " . mysqli_error($conn) . "\n");
        }
        
	unlink($filename); //done with intermediate file, remove it from intermediate directory.
	mysqli_close($conn);
        
}

/********************************************************************************
Function: loadSM_ObjectsFromDatabase
Connects to the database and returns an array of pairs of object names and sheetnames
Returns all pairs by default. If the optional parameter equals true, invisible objects will not be returned.
*********************************************************************************/

function loadSM_ObjectsFromDatabase($onlyVisibleObjects = false) {
	$conn = connectToDatabase();
		
	//Run query
	$sql = 'SELECT SM_Object_Name, SheetName FROM SM_Objects';
        if ($onlyVisibleObjects === true) {
            $sql .= ' WHERE Visible=1';
        }
	$result = mysqli_query($conn, $sql);
	
	//Build array
	$objectNamesAndSheetNames = array();
	if (mysqli_num_rows($result) > 0) {
		while($row = mysqli_fetch_assoc($result)) {
			array_push($objectNamesAndSheetNames,array($row["SM_Object_Name"],$row["SheetName"]));
		}
	} else {
		echo "0 results"; //delete this row eventually
	}
	mysqli_close($conn);
	return $objectNamesAndSheetNames;
}

/********************************************************************************
Function: loadXMLPathSpecsFromDatabase
Takes two arguments, the name of a set of saved specifications, and a blank mapping array.
Returns an associative array of previously mapped provider ID.
*********************************************************************************/

function loadXMLPathSpecsFromDatabase($specName, $mappingArray) {
	$conn = connectToDatabase();
	
	//Run query
	$sql = 'SELECT SMColumn, SMSheet, XMLPath FROM Mapping_XML_Paths WHERE SpecName="' . $specName . '"';
	$result = mysqli_query($conn, $sql);
	
	//Build array
	if (mysqli_num_rows($result) > 0) {
		while($row = mysqli_fetch_assoc($result)) {
			$mappingArray[$row["SMSheet"] . "." . $row["SMColumn"]] = $row["XMLPath"];
		}
	}
	mysqli_close($conn);
	return $mappingArray;
}

/********************************************************************************
Function: writeXMLPathSpecsToDatabase
Takes one variable, a name by which to save the specifications.
Deletes any existing specifications stored using that name in the Mapping_XML_Paths table.
Writes the specifications to the Mapping_XML_Paths table in the database.
*********************************************************************************/

function writeXMLPathSpecsToDatabase($specName) {
	$conn = connectToDatabase();
	
	//Remove any rows with same specname
	$sql = 'DELETE FROM Mapping_XML_Paths WHERE SpecName="' . $specName . '"';
	if (mysqli_query($conn, $sql)) {
            DisplayMsg("Overwriting any previous specifications named: <b>" . $specName . "</b>");
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Overwriting any previous specifications named: " . $specName . ".\n");
	} else {
            DisplayMsg("Error: " . $sql . "<br>" . mysqli_error($conn),"D");
	}
	
        for ($i = 0; $i < $_SESSION["numberOfVisibleSMObjects"]; $i++) {
            //Parse xmlpath string
            $xmlpath = $_POST["SMObjectFullName" . $i];
            $xmlpathArray = explode("/", $xmlpath);
            $pathLength = count($xmlpathArray);
            
            //Define Columns
            $sql = 'INSERT INTO Mapping_XML_Paths (SMColumn, SMSheet, XMLPath, ';
            for ($j = 1; $j <= $pathLength; $j++) {
                $sql .= "Level" . $j . ', ';
            }
            $sql .= 'SpecName) VALUES ("';
            
            //Insert Values
            $sql .= $_SESSION["visible_SM_Objects"][$i][0] . '", "';
            $sql .= $_SESSION["visible_SM_Objects"][$i][1] . '", "';
            $sql .= $xmlpath . '", "';
            for ($j = 0; $j < $pathLength; $j++) {
                $sql .= $xmlpathArray[$j] . '", "';
            }
            $sql .= $specName . '")';
            
            if (mysqli_query($conn, $sql)) {   //For now, suppress browser output for blank mappings.  Eventually require valid value from list to store in the database.
               if(strlen($_POST["SMObjectFullName" . $i])>0) {
                    DisplayMsg("New " . $specName . " mapping record created successfully: " . $_SESSION["listOfSMFullObjectNames"][$i] . " -- maps to -- " . $_POST["SMObjectFullName" . $i]);
                  }
                fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", New " . $specName . " mapping record created successfully: " . $_SESSION["listOfSMFullObjectNames"][$i] . " -- maps to -- &lt");
                fwrite($_SESSION["logfile"],$_POST["SMObjectFullName" . $i] . "&gt.\n");
                    
            } else {
                    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
            }
	}
	mysqli_close($conn);
}

/********************************************************************************
Function: writeProviderMappingSpecsToDatabase       4/18/18
Takes aa provider mapping and a name by which to save the specifications.
Deletes any existing specifications (tags/mapping) stored using that name in the Specifications table.
Writes the specifications to the Specifications table in the database.
*********************************************************************************/

function writeProviderMappingSpecsToDatabase($mapping,$specName) {
	$conn = connectToDatabase();
	//split this into separate functions eventually, put the remove outside the insert function. Or maybe not, since xmlpathspecs are in different table.
        
	//Remove any rows with same specname
	$sql = 'DELETE FROM Specifications WHERE SpecName="' . $specName . '"';
	if (mysqli_query($conn, $sql)) {
		DisplayMsg("Overwriting any previous specifications named: <b>" . $specName . "</b><br />","I");
                fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Overwriting any previous specifications named: " . $specName . ".\n");
	} else {
		echo "Error: " . $sql . "<br>" . mysqli_error($conn);
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
Function: getDefaultProviderMapping     20180423
Data provided by Anne. Returns a pair (array) of 2 dimensional arrays containing provider IDs and Names.
 * modified to include dataSource 20180417
*********************************************************************************/

function getDefaultProviderMapping() {
	$conn = connectToDatabase();
	
	//Run query
	$sql = "SELECT ID, SecurManageName, NewProviderName, ProviderID FROM ProviderIDs WHERE Organization='" . $_SESSION["dataSource"] . "'";
	$result = mysqli_query($conn, $sql);
	
	//Build array
	$voanlProviderIDsAndNames = array();
	$smProviderIDsAndNames = array();
	if (mysqli_num_rows($result) > 0) {
		while($row = mysqli_fetch_assoc($result)) {
			array_push($voanlProviderIDsAndNames,array($row["ProviderID"],$row["NewProviderName"]));
			array_push($smProviderIDsAndNames,array($row["ID"],$row["SecurManageName"]));
		}
	} else {
//		echo "0 results";
	}
	mysqli_close($conn);
	return array($voanlProviderIDsAndNames,$smProviderIDsAndNames);

}

/********************************************************************************
Function: mapSMIDsTovoanlIDs  -- copied from mapOldIDsToNewIDs 20180425
Takes one argument, a 2d array of affiliate provider IDs and Names.
Returns an associative array mapping the old IDs to the new IDs provided by the user on the previous page.
Only includes non-null selections, to avoid overwriting existing XML data.
 * Modified 20180321 to only include 
*********************************************************************************/

function mapSMIDsTovoanlIDs($oldProviders) {
	$providerIDMapping = array();
	for ($i = 0; $i < count($oldProviders); $i++) {
		//if(strlen($_POST["NewProviderNameID" . $i])>0) { //obsolete 20180321
		if(in_array($_POST["NewProviderNameID" . $i],$_SESSION["listOfFullvoanlProviderNames"])) {
                    $explodeName = explode(" (",$_POST["NewProviderNameID" . $i]);
                    $newID = end($explodeName);
                    $providerIDMapping[$oldProviders[$i][0]] = rtrim($newID,")");
		}
	}
	return $providerIDMapping;
}

/********************************************************************************
Function: startNewXMLFile
Takes one parameter, the filename of the new xml file. Creates the file and writes the first 2 lines.
*********************************************************************************/

function startNewXMLFile($new_XML_file) {
	$newfile = fopen($new_XML_file, "w");

	fwrite($newfile,'<?xml version="1.0" encoding="UTF-8"?>' . "\n");
	fwrite($newfile,"<records>\n");
        
	fwrite($_SESSION["logfile"],"Begin writing XML file at " . date("h:i:sa") . "\n\n");
	fclose($newfile);
}

/********************************************************************************
Function: writeProviderRecords  ~~~~~~~~~~~````````````In progress``````````````~~~~~~~~~~~~~~
Takes two arguments, a filename and a saved specifications name. Appends the Provider records to the xml file in progress.
*********************************************************************************/

function writeProviderRecords($new_XML_file,$specname) {
	$conn = connectToDatabase();
	$newfile = fopen($new_XML_file, "a");
        
        fwrite($newfile,"\t<providerRecords>\n");
        
        //get providers from SyncPointIDs table
        $sql = "SELECT DISTINCT ClientUID as UUID, SystemID FROM SyncPointIDs WHERE JobName = '" . $_SESSION["jobName"] . "' AND TagType = 'Provider'"; //ClientUID means general UID
        $providerData = mysqli_query($conn, $sql);
        if (mysqli_num_rows($providerData) > 0) {
            while($row = mysqli_fetch_assoc($providerData)) {
                //
                fwrite($newfile,"\t\t<Provider record_id=" . '"' . $row["SystemID"]);
                fwrite($newfile,'" date_added="' . date("Y-m-d\Th:i:sP",$_SESSION["jobStartTime"])); //Change this to different date eventually?
                fwrite($newfile,'" system_id="' . $row["SystemID"]);
                fwrite($newfile,'" date_updated="' . date("Y-m-d\Th:i:sP",$_SESSION["jobStartTime"]) . '"' . ">\n");
                
                fwrite($newfile,"\t\t\t<active>true</active>\n");
                
                fwrite($newfile,"\t\t\t<name>");
                $strippedName = substr($row['UUID'],strlen($_SESSION["dataSource"]));
                fwrite($newfile,$strippedName);
                fwrite($newfile,"</name>\n");
                
                //Put more Provider data elements here
                
                fwrite($newfile,"\t\t</Provider>\n");
            }
	}
        fwrite($newfile,"\t</providerRecords>\n");	
	fwrite($_SESSION["logfile"],"Finished writing Provider Records at " . date("h:i:sa") . "\n\n");
	fclose($newfile);
}

/********************************************************************************
Function: writeClientRecords_old  __________OBSOLETE_____________
Takes two arguments, a filename and a saved specifications name. Appends the client records to the xml file in progress.
*********************************************************************************/
/*
function writeClientRecords_old($new_XML_file,$specname) {
	$conn = connectToDatabase();
	$newfile = fopen($new_XML_file, "a");
        
        fwrite($newfile,"\t<clientRecords>\n");
        
        $whereClientAndIntake = array('SpecName'=>$specname, 'Level1'=>"clientRecords", 'SMSheet'=>"Intake");
        $mappingColumns = array("SMColumn","SMSheet","Level1","Level2","Level3","Level4","Level5","Level6");
        $tableOfRelevantMappingNames = loadArbitraryColumnsFromDatabase($mappingColumns,"Mapping_XML_Paths",$whereClientAndIntake);
        $multiArrayOfRelevantMappingNames = mysqliColumnsToMultiArray($tableOfRelevantMappingNames); //function to turn result into array, in utilities.php
        $arrayOfRelevantSMObjectNames = $multiArrayOfRelevantMappingNames[0];
        $numberOfRelevantSMObjectNames = count($arrayOfRelevantSMObjectNames);
        $arrayOfRelevantLevel3Names = $multiArrayOfRelevantMappingNames[4];
        $arrayOfRelevantLevel4Names = $multiArrayOfRelevantMappingNames[5];
        $arrayOfRelevantLevel5Names = $multiArrayOfRelevantMappingNames[6];
        $arrayOfRelevantLevel6Names = $multiArrayOfRelevantMappingNames[7];
        
        //get data from Intake table
        $clientData = loadArbitraryColumnsFromDatabase($arrayOfRelevantSMObjectNames,"Intake");
        if (mysqli_num_rows($clientData) > 0) {
            while($row = mysqli_fetch_assoc($clientData)) {
                fwrite($newfile,"\t\t<Client record_id=" . '"' . $row["SM ID"] . '"' . ">\n");  //Hardcode SM ID as record_id
                //write client data
                for($i=0;$i<$numberOfRelevantSMObjectNames;$i++) {
                    if ($arrayOfRelevantLevel3Names[$i] != "assessmentData") {
                        writeXMLrowToFile($arrayOfRelevantLevel3Names[$i],$row[$arrayOfRelevantSMObjectNames[$i]],3,$newfile);
                    }
                }
                //write assessment data
                fwrite($newfile,"\t\t\t<assessssmentData>\n");
                for($i=0;$i<$numberOfRelevantSMObjectNames;$i++) {
                    if ($arrayOfRelevantLevel3Names[$i] == "assessmentData") {
                        writeXMLrowToFile($arrayOfRelevantLevel4Names[$i],$row[$arrayOfRelevantSMObjectNames[$i]],4,$newfile);
                    }
                }
                fwrite($newfile,"\t\t\t</assessssmentData>\n");
                fwrite($newfile,"\t\t</Client>\n");
            }
	}
        fwrite($newfile,"\t</clientRecords>\n");
	fwrite($_SESSION["logfile"],"Finished writing Client Records at " . date("h:i:sa") . "\n\n");
	fclose($newfile);
}
*/
/********************************************************************************
Function: loadIntakeLeftJoinSyncPointIDs
Takes an array of column names and a table name. Returns the MySQLi result object.
*********************************************************************************/

function loadIntakeLeftJoinSyncPointIDs($columns, $tablename) {
	$conn = connectToDatabase();
        
        $N = count($columns);
        
	//Run query for selected elements
	$sql = 'SELECT ';
	for ($i = 0; $i < $N; $i++) {
            $sql .= '`' . $columns[$i] . '`, ';
        }
        //Also get formatted arrival date.
        $sql .= '(DATE_FORMAT(STR_TO_DATE(`Arrival Date`, "%c/%d/%y %l:%i %p"), "%Y-%m-%dT%T")) as `Format Arrival Date`, SyncPointIDs.`SystemID`';
        $sql .= " FROM `" . $tablename . "`";
        $sql .= " LEFT JOIN SyncPointIDs ON `" . $tablename . "`.UUID=SyncPointIDs.ClientUID";
        $sql .= " WHERE SyncPointIDs.JobName = '" . $_SESSION["jobName"] . "'";       //ideally use jobID instead of JobName. Doesn't matter because name is unique.
        echo $sql;
        
	$result = mysqli_query($conn, $sql);
        if ($result) {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", sql statement executed:\n\t" . $sql . "\n");
        }
        else {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Error: " . mysqli_error($conn) . ".\n\t" . $sql . "\n");
            echo 'Error - ' . mysqli_error($conn);
        }
        
	mysqli_close($conn);
	return $result;
}

/********************************************************************************
Function: writeClientRecords
Takes two arguments, a filename and a saved specifications name. Appends the client records to the xml file in progress.
*********************************************************************************/

function writeClientRecords($new_XML_file,$specname) {
	$conn = connectToDatabase();
	$newfile = fopen($new_XML_file, "a");
        
        fwrite($newfile,"\t<clientRecords>\n");
        
        //-----------First, load list of relevant columns to load, with mapping
        //define WHERE clause
        $whereClientAndIntake = array('SpecName'=>$specname, 'Level1'=>"clientRecords", 'SMSheet'=>"Intake");
        //Define list of columns to load
        $mappingColumns = array("SMColumn","SMSheet","Level1","Level2","Level3","Level4","Level5","Level6");
        //define sort priority
        $orderingColumns = array("Level1","Level2","Level3","Level4","Level5","Level6");
        //get mapping
        $tableOfRelevantMappings = loadArbitraryColumnsFromDatabase($mappingColumns,"Mapping_XML_Paths",$whereClientAndIntake,$orderingColumns);
        $multiArrayOfRelevantMappings = mysqliColumnsToMultiArray($tableOfRelevantMappings); //function to turn result into array, in utilities.php
        //put first column in an array, and count its elements.
        $arrayOfRelevantSMObjectNames = $multiArrayOfRelevantMappings[0];
        $numberOfRelevantSMObjectNames = count($arrayOfRelevantSMObjectNames);
        //put UUID in the array, after counting. It should not be counted in the iterations of the loop below.
        //array_push($arrayOfRelevantSMObjectNames,"UUID");   //cancel for now.
        
        //-----------Then, load data from intake table, and write file.
        //$clientData = loadArbitraryColumnsFromDatabase($arrayOfRelevantSMObjectNames,"Intake");
        $clientData = loadIntakeLeftJoinSyncPointIDs($arrayOfRelevantSMObjectNames,"Intake");
        if (mysqli_num_rows($clientData) > 0) {
            while($row = mysqli_fetch_assoc($clientData)) {
                //each row corresponds to one client record
                fwrite($newfile,"\t\t<Client record_id=" . '"' . $row["SystemID"]);
                fwrite($newfile,'" date_added="' . $row["Format Arrival Date"]);
                fwrite($newfile,'" system_id="' . $row["SystemID"]);
                fwrite($newfile,'" date_updated="' . date("Y-m-d\Th:i:sP",$_SESSION["jobStartTime"]) . '"' . ">\n");
                
                //fwrite($newfile,"\t\t\t<UUID>" . $row["UUID"] . "</UUID>\n");  //Hardcode UUID as child object.   //Cancel for now.
                $numberOfIndents = 3; // << records << clientRecords << Client
                $_SESSION["temporaryPath"] = array("Client");
                for($i=0;$i<$numberOfRelevantSMObjectNames;$i++) {
                    //each column in the row corresponds to one line of data in the new xml file.
                    $newNumberOfIndents = writeXMLrowToFile($multiArrayOfRelevantMappings,$numberOfIndents,$newfile,$i,$row);
                    $numberOfIndents = $newNumberOfIndents;
                }
                //if necessary, close remaining tags before closing Client Tag
                while($numberOfIndents > 3) {  //equivalently, if depth of XML mapping is less than $numberOfIndents
                    $numberOfIndents -=1;
                    fwrite($newfile,str_repeat("\t",$numberOfIndents));
                    fwrite($newfile,"</" . array_pop($_SESSION["temporaryPath"]) . ">\n");
                }
                fwrite($newfile,"\t\t</Client>\n");
            }
	}
        fwrite($newfile,"\t</clientRecords>\n");
	fwrite($_SESSION["logfile"],"Finished writing Client Records at " . date("h:i:sa") . "\n\n");
	fclose($newfile);
}

/********************************************************************************
Function: writeEntryExitRecords  ~~~~~~~~~~~````````````In progress``````````````~~~~~~~~~~~~~~
Takes two arguments, a filename and a saved specifications name. Appends the EE records to the xml file in progress.
*********************************************************************************/

function writeEntryExitRecords($new_XML_file,$specname) {
	$conn = connectToDatabase();
	$newfile = fopen($new_XML_file, "a");
        
        fwrite($newfile,"\t<entryExitRecords>\n");
/*       -----------This section is probably pointless----------
        $whereEE = array('SpecName'=>$specname, 'Level1'=>"entryExitRecords");
        $mappingColumns = array("SMColumn","SMSheet","Level1","Level2","Level3","Level4","Level5","Level6");
        $tableOfRelevantMappingNames = loadArbitraryColumnsFromDatabase($mappingColumns,"Mapping_XML_Paths",$whereEE);
        $multiArrayOfRelevantMappingNames = mysqliColumnsToMultiArray($tableOfRelevantMappingNames); //function to turn result into array, in utilities.php
        $arrayOfRelevantSMObjectColumnNames = $multiArrayOfRelevantMappingNames[0];
        $numberOfRelevantSMObjectNames = count($arrayOfRelevantSMObjectColumnNames);
        $test=array();
        for($i=0;$i<$numberOfRelevantSMObjectNames;$i++) {
            array_push($test,$multiArrayOfRelevantMappingNames[1][$i] . '.`' .  $multiArrayOfRelevantMappingNames[0][$i] . '`');
        }
*/

        //get data from various tables
        $sql = 'SELECT Intake.`SM ID`, (DATE_FORMAT(STR_TO_DATE(`Arrival Date`, "%c/%d/%y %l:%i %p"), "%Y-%m-%dT%T")) as `Format Arrival Date`,
                (DATE_FORMAT(STR_TO_DATE(`Close Out Date`, "%m/%d/%Y %H:%i"), "%Y-%m-%dT%T")) as `Format Close Out Date`,
                `Close Out Date`, `Close Out Reason`, Closeouts.ID, SyncPointIDs.`SystemID`
                FROM Intake LEFT JOIN SyncPointIDs ON Intake.UUID=SyncPointIDs.ClientUID ';
        $sql .= "LEFT JOIN Closeouts ON Intake.`SM ID`=Closeouts.`SM ID` 
                where SyncPointIDs.JobName='" . $_SESSION["jobName"] . "'
                order by `SM ID`;";
        $entryExitData = mysqli_query($conn, $sql);
        if (mysqli_num_rows($entryExitData) > 0) {
            while($row = mysqli_fetch_assoc($entryExitData)) {
                fwrite($newfile,"\t\t<EntryExit>\n");  //replace this line with below, modified (client, systemID, etc), to write dates and ids.
/*              fwrite($newfile,"\t\t<Client record_id=" . '"' . $row["SystemID"]);
                fwrite($newfile,'" date_added="' . $row["Format Arrival Date"]);
                fwrite($newfile,'" system_id="' . $row["SystemID"]);
                fwrite($newfile,'" date_updated="' . date("Y-m-d\Th:i:sP",$_SESSION["jobStartTime"]) . '"' . ">\n");
*/
                fwrite($newfile,"\t\t\t<client>");
                fwrite($newfile,$row['SystemID']);
                fwrite($newfile,"</client>\n");
                fwrite($newfile,"\t\t\t<entryDate>");
                fwrite($newfile,$row['Format Arrival Date']);
                fwrite($newfile,"</entryDate>\n");
                fwrite($newfile,"\t\t\t<exitDate>");
                fwrite($newfile,$row['Format Close Out Date']);
                fwrite($newfile,"</exitDate>\n");
                fwrite($newfile,"\t\t\t<reasonLeavingValue>");
                fwrite($newfile,$row['Close Out Reason']);
                fwrite($newfile,"</reasonLeavingValue>\n");
                
                //Put Provider Creating here.  Getting the provider mapping will not be trivial. may require new stored proc, or modify 
                //most recent stored proc to temporarily store the provider name mapping in a permanent table.
                
                fwrite($newfile,"\t\t</EntryExit>\n");
            }
	}
        fwrite($newfile,"\t</entryExitRecords>\n");	
	fwrite($_SESSION["logfile"],"Finished writing Entry Exit Records at " . date("h:i:sa") . "\n\n");
	fclose($newfile);
}

/********************************************************************************
Function: finishXMLFile
Takes one parameter, the filename of the xml file in progress. Completes the file.
*********************************************************************************/

function finishXMLFile($new_XML_file) {
	$newfile = fopen($new_XML_file, "a");

	fwrite($newfile,"</records>\n");
	fwrite($_SESSION["logfile"],"Data source = ". $_SESSION["dataSource"] ."  File modification completed successfully at " . date("h:i:sa") . "\n\n");
	fclose($newfile);
}



?>