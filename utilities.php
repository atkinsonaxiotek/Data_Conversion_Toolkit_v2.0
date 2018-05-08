<?php

/********************************************************************************
Function: uploadFile
Takes three arguments. The name of the _POST object, the filename by which to save to uploaded file, and the allowed extension.
Uploads the file. Does not upload in any of these cases:
	1. The file is larger than some limit.
	2. The file is not of a certain type.
 * Returns True if the file gets uploaded.
*********************************************************************************/

function uploadFile($POSTname,$targetFilename,$extension) {
        
	// Allow only certain file formats
	$fileType = pathinfo($_FILES[$POSTname]["name"],PATHINFO_EXTENSION);
	if($fileType != $extension) {
		DisplayMsg("Only ." . $extension . " files are allowed. Try again.","W");
                fwrite($_SESSION["logfile"],"File is not a ." . $extension . " file.\n");
                return 0;
	}
	// Check file size
	if ($_FILES[$POSTname]["size"] > 30000000) { // 30 MB limit. Arbitrary, can be changed.
		DisplayMsg("Your file is too large. Contact your administrator to raise the 30 MB limit.","W");
                fwrite($_SESSION["logfile"],"File is larger than 30 MB limit.\n");
                return 0;
	}
	// Try to upload file
	if (move_uploaded_file($_FILES[$POSTname]["tmp_name"], $targetFilename)) {
                DisplayMsg('The file <b>'. $_SESSION["original_filename"] . '</b> has been <b><i>uploaded</i></b>.',"S");
                fwrite($_SESSION["logfile"],"The file '" . $_SESSION["original_filename"] . "' was successfully uploaded at " . date("h:i:sa") . ".\n");
                return 1;
        } else {
                DisplayMsg("Sorry, there was an error uploading your file.","D");
                fwrite($_SESSION["logfile"],"There was an error uploading the file.\n");
                return 0;
        }
}

/********************************************************************************
Function: connectToDatabase
Connects to the axiotek_voamapping database.
*********************************************************************************/

function connectToDatabase() {
	$servername = "NETDRV03";
	$username = "datkinson";
	$password = ""; //put password here
	$database = "axiotek_voamapping";
	
	// Create connection
	$axio_conn = new mysqli($servername, $username, $password, $database);

	// Check connection
	if ($axio_conn->connect_error) {
		die("Connection failed: " . $axio_conn->connect_error);
	} 
	//echo "Connected successfully";		include system info button? database name.
	return $axio_conn;
	
}

/********************************************************************************
Function: startLogFile
Takes one argument, a filename. (modified on 20180312 to take a uniqueFilename with no extension)
Creates a log file in the logs directory and begins logging info.
The logfile is kept open and the handle to the logfile is stored as a session variable. Actually that doesn't work, you have to open a filehandle on each new page.
*********************************************************************************/

function startLogFile($uniqueFilename) {
	$_SESSION["log_filename"] = $uniqueFilename . ".log";
	$_SESSION["log_fileLocation"] = "logs/" . $_SESSION["log_filename"];
	$logfile = fopen($_SESSION["log_fileLocation"], "w");
        fwrite($logfile,"Today is " . date('l jS \of F Y h:i:s A') . ".\n");
	fwrite($logfile,"Currently executing script: " . $_SERVER['PHP_SELF'] . "\n");
	fwrite($logfile,"Name of host server: " . $_SERVER['SERVER_NAME'] . "\n");
	fwrite($logfile,"User IP Adress: " . $_SERVER['REMOTE_ADDR'] . "\n");
	fwrite($logfile,"Host header: " . $_SERVER['HTTP_HOST'] . "\n");
	fwrite($logfile,"HTTP Referer: " . $_SERVER['HTTP_REFERER'] . "\n");
	fwrite($logfile,"HTTP User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n");
	fwrite($logfile,"Script path: " . $_SERVER['SCRIPT_NAME'] . "\n");
	//fwrite($logfile,"Uploaded file: " . $targetFileName . "\n");             //broken, needs originalFilename parameter, or just delete.
        if ($_SESSION["dataSource"]) {
            fwrite($logfile,"Data source: " . $_SESSION["dataSource"] . "\n");
        }
        return $logfile;
}

/********************************************************************************
Function: flipDiagonally  ----https://stackoverflow.com/questions/797251/transposing-multidimensional-arrays-in-php

*********************************************************************************/

function flipDiagonally($arr) {
    $out = array();
    foreach ($arr as $key => $subarr) {
        foreach ($subarr as $subkey => $subvalue) {
            $out[$subkey][$key] = $subvalue;
        }
    }
    return $out;
}

/********************************************************************************
Function: writeArbitraryStringsToDatabaseTable  ----not currently used.
Takes two data strings and two column names and writes them into the table.
*********************************************************************************/

function writeArbitraryStringsToDatabaseTable($dataValues,$columnNames, $tableName) {
	$conn = connectToDatabase();
	
        $sql = 'INSERT INTO ' . $tableName . ' (`' . $columnNames[0] . ", " . $columnNames[1] . ') VALUES ("' . $dataValues[0] . '", "' . $dataValues[1] . '")';
        if (mysqli_query($conn, $sql)) {
                echo "New row added successfully: " . $dataValues[0] . '", "' . $dataValues[1] . "<br />";
        } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
	mysqli_close($conn);
}

/********************************************************************************
Function: writeArbitraryStringToDatabaseTable
Takes an arbitrary string and a columnname/tablename and writes the value into the table.
*********************************************************************************/

function writeArbitraryStringToDatabaseTable($data,$columnName,$tableName) {
	$conn = connectToDatabase();
        $sql = 'INSERT INTO ' . $tableName . ' (`' . $columnName . '`) VALUES ("' . $data . '")';
        if (mysqli_query($conn, $sql)) {
                echo "New row added successfully: " . $data . "<br />";
        } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
	mysqli_close($conn);
}

/********************************************************************************
Function: writeXMLrowToFile
Parameters-
$mappingMultiArray: A 2d array mapping SM objects (first two columns) to XML paths (subsequent columns).
$numberOfIndents: Used in three ways
 * 1. to compare the depth of the previous tag to the current tag
 * 2. to indent the xml tag appropriately, 
 * 2. to get the correct tagname in the xml path from the multi array
$fileHandle: file to be written to.
$i: iterates over the list of SM Object names
$row: associative array mapping SM-names to data.

Takes a 2d SM-to-XML mapping array, an integer, a file handle, an index, and an associative array mapping SM-Names to data.
Writes the data from the ith SM object between xml tags defined by the 2d array and the number of indents.
*********************************************************************************/

function writeXMLrowToFile($mappingMultiArray,$numberOfIndents,$fileHandle,$i,$row) {
        $content = $row[$mappingMultiArray[0][$i]];
        
        //"$numberOfIndents+1" equals the current xml depth, because the mapping array index is one integer ahead of the depth.
        
        //SHALLOWER
        if($mappingMultiArray[$numberOfIndents+1][$i] == "") {  //equivalently, if depth of XML mapping is less than $numberOfIndents
            //close tags before writing new data in new sections
            while($mappingMultiArray[$numberOfIndents+1][$i] == "") {  //equivalently, if depth of XML mapping is less than $numberOfIndents
                $numberOfIndents -=1;
                fwrite($fileHandle,str_repeat("\t",$numberOfIndents));
                fwrite($fileHandle,"</" . array_pop($_SESSION["temporaryPath"]) . ">\n");
            }
        }
        //SAME DEPTH
        if($mappingMultiArray[$numberOfIndents+2][$i] == "") {  //equivalently, if depth of XML mapping is not greater than $numberOfIndents
            //get appropriate tagname
            $tagName = $mappingMultiArray[$numberOfIndents+1][$i];
            //write line
            fwrite($fileHandle,str_repeat("\t",$numberOfIndents));
            fwrite($fileHandle,"<" . $tagName . ">");
            fwrite($fileHandle,$content);
            fwrite($fileHandle,"</" . $tagName . ">\n");
        }
        //DEEPER
        if($mappingMultiArray[$numberOfIndents+2][$i] != "") {  //equivalently, if depth of XML mapping is greater than $numberOfIndents
            while($mappingMultiArray[$numberOfIndents+2][$i] != "") {  //same condition
            //open new tag category
                fwrite($fileHandle,str_repeat("\t",$numberOfIndents));
                fwrite($fileHandle,"<" . $mappingMultiArray[$numberOfIndents+1][$i] . ">\n");
                array_push($_SESSION["temporaryPath"],$mappingMultiArray[$numberOfIndents+1][$i]);
                $numberOfIndents +=1;
            }
            //get appropriate tagname
            $tagName = $mappingMultiArray[$numberOfIndents+1][$i];
            //write line
            fwrite($fileHandle,str_repeat("\t",$numberOfIndents));
            fwrite($fileHandle,"<" . $tagName . ">");
            fwrite($fileHandle,$content);
            fwrite($fileHandle,"</" . $tagName . ">\n");
        }
        return $numberOfIndents;
	//fwrite($_SESSION["logfile"],$tagName . " written at " . date("h:i:sa") . "\n");
}

/********************************************************************************
Function: loadArbitraryColumnsFromDatabase
Takes an array of column names and a table name. Returns the MySQLi result object.
 * Optionally includes an associative array defining a WHERE clause.
 * Optionally includes an array defining an ORDER BY clause.
 * WHERE uses AND
*********************************************************************************/

function loadArbitraryColumnsFromDatabase($columns,$tablename,$whereClause = "",$orderByClause = "") {
	$conn = connectToDatabase();
        
        $N = count($columns);
        
	//Run query  //improve someday, remove $N and use $orderByClause logic instead.
	$sql = 'SELECT ';
	for ($i = 0; $i < $N; $i++) {
            $sql .= '`' . $columns[$i] . '`';
            if ($i<$N-1) {
                $sql .= ", ";
            }
        }
        $sql .= " FROM `" . $tablename . "`";
        if($whereClause) {
            $sql .= ' WHERE `';
            foreach ($whereClause as $columnname => $value) {
                $sql .= $columnname . '`="' . $value . '" AND `';
            }
            $sql = rtrim($sql,'AND `');
        }
        if($orderByClause) {
            $sql .= ' ORDER BY ';
            foreach ($orderByClause as $columnname) {
                $sql .= '`' . $columnname . '`, ';
            }
            $sql = rtrim($sql,', ');
        }
        //echo $sql;
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
Function: loadArbitraryColumnsFromDatabaseLeftJoins   Nothing here yet----------- 2/27/18   in progress 3/16
Takes an array of column names and a table name. Returns the MySQLi result object.
 * Optionally includes an associative array defining a WHERE clause.
 * WHERE uses OR
*********************************************************************************/

function loadArbitraryColumnsFromDatabaseLeftJoins($columns,$tables,$firstTable,$firstKeyColumn,$secondTable,$secondKeyColumn,$whereClause = "") {
	$conn = connectToDatabase();
        
        $N = count($columns);
        
	//Run query
	$sql = 'SELECT ';
	for ($i = 0; $i < $N; $i++) {
            $sql .= '`' . $tables[$i] . '`.`' . $columns[$i] . '`';
            if ($i<$N-1) {
                $sql .= ", ";
            }
        }
        $sql .= " FROM `" . $firstTable . "`";
        
        $sql .= " LEFT JOIN `" . $secondTable . "` ON `" . $firstTable . "`.`" . $firstKeyColumn . "`=`" . $secondTable . "`.`" . $secondKeyColumn . "`";
        
        
        if($whereClause) {
            $sql .= ' WHERE `';
            foreach ($whereClause as $columnname => $value) {
                $sql .= $columnname . '`="' . $value . '" OR `';
            }
            $sql = rtrim($sql,'OR `');
        }
        echo $sql;/*
	$result = mysqli_query($conn, $sql);
        if ($result) {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", sql statement executed:\n\t" . $sql . "\n");
        }
        else {
            fwrite($_SESSION["logfile"],"At " . date("h:i:sa") . ", Error: " . mysqli_error($conn) . ".\n\t" . $sql . "\n");
            echo 'Error - ' . mysqli_error($conn);
        }
        
	mysqli_close($conn);
	return $result;*/
}

/********************************************************************************
Function: loadSpecNamesFromDatabase
Takes a tablename.
Connects to the database and returns an array of distinct saved specnames from that table.
*********************************************************************************/

function loadSpecNamesFromDatabase($tablename) {
	$conn = connectToDatabase();
		
	//Run query
	$sql = 'SELECT DISTINCT SpecName FROM ';
        $sql .= $tablename;
	$result = mysqli_query($conn, $sql);
	
	//Build array
	$specNames = array();
	if (mysqli_num_rows($result) > 0) {
		while($row = mysqli_fetch_assoc($result)) {
			array_push($specNames,$row["SpecName"]);
		}
	} else {
		echo "0 results";
	}
	mysqli_close($conn);
	return $specNames;
	
}

/********************************************************************************
Function: mysqliColumnsToMultiArray
Takes a MySQLi result object.
Returns a multidimensional array of the columns.
 * This is needed because mysqliColumnToArray cannot be called multiple times on one result object.
*********************************************************************************/

function mysqliColumnsToMultiArray($mysqliResult) {
    
	$resultsArray = array();
        if (mysqli_num_rows($mysqliResult) > 0) {
            $row = mysqli_fetch_row($mysqliResult);
            $numberOfColumns = count($row);
            for ($i = 0; $i < $numberOfColumns; $i++) {
                array_push($resultsArray,array());
                array_push($resultsArray[$i],$row[$i]);
            }
            while($row = mysqli_fetch_row($mysqliResult)) {
                for ($i = 0; $i < $numberOfColumns; $i++) {
                    array_push($resultsArray[$i],$row[$i]);
                }
            }
	}
	return $resultsArray;
}

/********************************************************************************
Function: mysqliColumnToArray
Takes two arguments, a MySQLi result object and an optional column index.
Returns the ith column as an array.
*********************************************************************************/

function mysqliColumnToArray($mysqliResult,$i=0) {
    
	$resultsArray = array();
        //echo mysqli_num_rows($mysqliResult);
        if (mysqli_num_rows($mysqliResult) > 0) {
		while($row = mysqli_fetch_row($mysqliResult)) {
			array_push($resultsArray,$row[$i]);
                        //echo "<br>";
                        //print_r($row);
		}
	}
        
	return $resultsArray;
}

/********************************************************************************
Function: mapOldIDsToOldNames   -obsolete?
Takes two arguments, a list of affiliate provider IDs, and a list of affilate provider names.
Returns an associative array. ID -> Name
*********************************************************************************/

function mapOldIDsToOldNames($affiliateIDs,$affiliateNames) {
	$affiliateMapping = array();
	if(count($affiliateIDs)!=count($affiliateNames)) {
		die("Error, different number of names and IDs are not the same!"); //Remove eventually. Maybe combine the two get* functions...
	}
	for ($i = 0; $i < $_SESSION["numberOfOldProviders"]; $i++) {
		$affiliateMapping[$affiliateIDs[$i]] = $affiliateNames[$i];
	}
	return $affiliateMapping;
}

/********************************************************************************
Function: turn2DArrayIntoAssociativeArray
Takes a 2D array of voanl provider IDs and voanl provider names.
Returns an associative array. ID -> Name
*********************************************************************************/

function turnTwoDimensionalArrayIntoAssociativeArray($twoDimensionalArray) {
	$associativeArray = array();
	for ($i = 0; $i < count($twoDimensionalArray); $i++) {
		$associativeArray[$twoDimensionalArray[$i][0]] = $twoDimensionalArray[$i][1];
	}
	return $associativeArray;
}

/********************************************************************************
Function: mapNewIDsToNewNames -obsolete?
Takes a 2D array of voanl provider IDs and voanl provider names.
Returns an associative array. ID -> Name
*********************************************************************************/

function mapNewIDsToNewNames($voanl2DArray) {
	$voanlMapping = array();
	for ($i = 0; $i < count($voanl2DArray); $i++) {
		$voanlMapping[$voanl2DArray[$i][1]] = $voanl2DArray[$i][0];
	}
	return $voanlMapping;
}

/********************************************************************************
Function: mapOldIDsToNewIDs
Takes one argument, a list of affiliate provider IDs.
Returns an associative array mapping the list to the new IDs provided by the user on the previous page.
Only includes non-null selections, to avoid overwriting existing XML data.
*********************************************************************************/
/*
function mapOldIDsToNewIDs($old_IDs) {
	$providerIDMapping = array();
	for ($i = 0; $i < $_SESSION["numberOfOldProviders"]; $i++) {
		if(strlen($_POST[$old_IDs[$i]])>0) {
			$providerIDMapping[$old_IDs[$i]] = $_POST[$old_IDs[$i]];
		}
	}
	return $providerIDMapping;
}
*/
/********************************************************************************
Function: mapSMObjectsToBowmanSchema -obsolete
Takes one argument, a list of SM object fullnames.
Returns an associative array mapping each SM object to an array of XML tags selected by the user.
*********************************************************************************/
/*
function mapSMObjectsToBowmanSchema($fullSMNames) {
	$SMObjectMapping = array();
	for ($i = 0; $i < $_SESSION["numberOfVisibleSMObjects"]; $i++) {
            $SMObjectMapping[$fullSMNames[$i]] = array($_POST["SMObject" . $i . "BowmanLevel0"],$_POST["SMObject" . $i . "BowmanLevel1"],$_POST["SMObject" . $i . "BowmanLevel2"]);
	}
	return $SMObjectMapping;
}
*/
/********************************************************************************
Function: uploadOrFindExisting   ----Obsolete as of 20180318----- 
                                 ----Filenames are now unique (with timestamp), so the target_filename will not already exist on the server.-----
Takes three arguments. The name of the _POST object, the filename by which to save to uploaded file, and the correct extension.
Uploads the file. Does not upload in any of these cases:
	1. A file with that name already exists in the target directory on the server.
	2. The file is larger than some limit.
	3. The file is not of a certain type.
 * Returns True if the file exists or gets uploaded.
*********************************************************************************/
/*
function uploadOrFindExisting($POSTname,$targetFilename,$extension) {
	$fileType = pathinfo(basename($_FILES[$POSTname]["name"]),PATHINFO_EXTENSION);
        
	// Allow only certain file formats
	if($fileType != $extension) {
		echo "Only ." . $extension . " files are allowed. Try again.<br />";
                fwrite($_SESSION["logfile"],"File is not a ." . $extension . " file.\n");
                return 0;
	}
	// Check file size
	if ($_FILES[$POSTname]["size"] > 30000000) { // 30 MB limit. Arbitrary, can be changed.
		echo "Your file is too large. Contact your administrator to raise the 30 MB limit.<br />";
                fwrite($_SESSION["logfile"],"File is larger than 30 MB limit.\n");
                return 0;
	}
	// Check if file already exists
	if (file_exists($targetFilename)) {
                fwrite($_SESSION["logfile"],"The file '" . $_SESSION["original_filename"]. "' already exists!\n");
                fwrite($_SESSION["logfile"],"We will use the existing file.\n");
		echo 'The file "'. $_SESSION["original_filename"]. '" already exists!<br />';
		echo "We will use the existing file.<br />";
                return 1;
	}
	// Try to upload file
	if (move_uploaded_file($_FILES[$POSTname]["tmp_name"], $targetFilename)) {
                echo 'The file "'. $_SESSION["original_filename"] . '" has been uploaded.<br /><br />';
                fwrite($_SESSION["logfile"],"The file '" . $_SESSION["original_filename"] . "' was successfully uploaded at " . date("h:i:sa") . ".\n");
                return 1;
        } else {
                echo "Sorry, there was an error uploading your file.<br /><br />";
                fwrite($_SESSION["logfile"],"There was an error uploading the file.\n");
                return 0;
        }
}
*/
/********************************************************************************
Function: createProviderMapping_v2		obsolete?
Takes one argument, a list of affiliate provider IDs.
Returns an associative array mapping the list to the new IDs provided by the user on the previous page.
*********************************************************************************/

//function createProviderMapping_v2($old_IDs) {
//	$providerIDMapping = array();
//	for ($i = 0; $i < $_SESSION["numberOfOldProviders"]; $i++) {
//		$providerIDMapping[$old_IDs[$i]] = $_POST[$old_IDs[$i]];
//	}
//	return $providerIDMapping;
//}

/********************************************************************************
Function: createProviderMapping -----------Obsolete--------------
Takes one argument, a list of affiliate provider names.
Returns an associative array mapping the list to the new names provided by the user on the previous page.
*********************************************************************************/

//function createProviderMapping($old_names) {
//	$providerMapping = array();
//	for ($i = 0; $i < $_SESSION["numberOfOldProviders"]; $i++) {
//		$providerMapping[$old_names[$i]] = $_POST[$i];
//	}
//	return $providerMapping;
//}


//
//
///////////////////////////////////////////////////////////////////////
////XMLReaderX
///////////////////////////////////////////////////////////////////////
//
//
//use \XMLReader;
//class XMLReaderX extends XMLReader
//{
///**
//	 * depth of the previous node
//	 *
//	 * @var int
//	 */
//protected $_previousDepth = 0;
//
///**
//	 * list of the parsed nodes
//	 *
//	 * @var array
//	 */
//protected $_nodesParsed = array();
//
///**
//	 * keep track of the node types
//	 *
//	 * @var array
//	 */
//protected $_nodesType = array();
//
///**
//	 * keeps track of the node number
//	 *
//	 * @var array
//	 */
//protected $_nodesCount = array();
//
///**
//	 * list of nodes that matter for XPath
//	 *
//	 * @var array
//	 */
//protected $_referencedNodeTypes = array(
//	 parent::ELEMENT,
//	 parent::ATTRIBUTE,
//	 parent::TEXT,
//	 parent::CDATA,
//	 parent::COMMENT
//);
//
///**
//	 * keep track of all the parsed paths
//	 *
//	 * @var array
//	 */
//protected $_parsedPaths = array();
//
///**
//	 * Move to next node in document
//	 *
//	 * @throws XMLReaderException
//	 * @link http://php.net/manual/en/xmlreader.read.php
//	 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
//	 */
//public function read()
//{
//	 $read = parent::read();
//	
//	 if(in_array($this->nodeType, $this->_referencedNodeTypes)) {
//		 if($this->depth < $this->_previousDepth) {
//			 if(!isset($this->_nodesParsed[$this->depth])) {
//				 throw new \Exception('Missing items in $_nodesParsed');
//			 }
//			 if(!isset($this->_nodesCount[$this->depth])) {
//				 throw new \Exception('Missing items in $_nodesCounter');
//			 }
//			 if(!isset($this->_nodesType[$this->depth])) {
//				 throw new \Exception('Missing items in $_nodesType');
//			 }
//			 $this->_nodesParsed	 = array_slice($this->_nodesParsed, 0, $this->depth + 1, true);
//			 $this->_nodesCount = array_slice($this->_nodesCount, 0, $this->depth + 1, true);
//			 $this->_nodesType	 = array_slice($this->_nodesType, 0, $this->depth + 1, true);
//		 }
//		 if(isset($this->_nodesParsed[$this->depth])
//			 && $this->localName == $this->_nodesParsed[$this->depth]
//			 && $this->nodeType == $this->_nodesType[$this->depth])
//		 {
//			 $this->_nodesCount[$this->depth] = $this->_nodesCount[$this->depth] + 1;
//		 } else {
//			 $this->_nodesParsed[$this->depth] = $this->localName;
//			 $this->_nodesType[$this->depth]	 = $this->nodeType;
//			
//			 $logPath = $this->_getLogPath();
//			 if(isset($this->_parsedPaths[$logPath])) {
//				 $this->_nodesCount[$this->depth] = $this->_parsedPaths[$logPath] + 1;
//			 } else {
//				 $this->_nodesCount[$this->depth] = 1; // first node is 1, not 0
//			 }
//		 }
//		
//		 if($this->nodeType == parent::ELEMENT) {
//			 $this->_parsedPaths[$this->_getLogPath()] = $this->_nodesCount[$this->depth];
//		 }
//		
//		 $this->_previousDepth = $this->depth;
//	 }
//	
//	 return $read;
//}
//	
///**
//	 * getNodePath()
//	 *
//	 * @return string XPath of the current node
//	 */
//public function getNodePath()
//{
//	 if(count($this->_nodesCount) != count($this->_nodesParsed)
//		 && count($this->_nodesCount) != count($this->_nodesType))
//	 {
//		 throw new \Exception('Counts do not match');
//	 }
//	
//	 $nodePath = '';
//	 foreach ($this->_nodesParsed as $depth => $nodeName) {
//		 switch ($this->_nodesType[$depth]) {
//			 case parent::ELEMENT:
//				 $nodePath .= '/' . $nodeName . '[' . $this->_nodesCount[$depth] . ']';
//				 break;
//			
//			 case parent::ATTRIBUTE:
//				 $nodePath .= '[@' . $nodeName . ']';
//				 break;
//			 case parent::TEXT:
//			 case parent::CDATA:
//				 $nodePath .= '/text()';
//				 break;
//			 case parent::COMMENT:
//				 $nodePath .= '/comment()';
//				 break;
//			 default:
//				 throw new \Exception('Unknown node type');
//				 break;
//		 }
//	 }
//	 return $nodePath;
//}
//
///**
//	 * get the path of the actual node for logging
//	 *
//	 * @return string
//	 */
//protected function _getLogPath()
//{
//	 $path = '';
//	
//	 $localCopy = $this->_nodesParsed;
//	 if(isset($localCopy[$this->depth])) {
//		 unset($localCopy[$this->depth]);
//	 }
//	
//	 foreach ($localCopy as $depth => $nodeName) {
//		 $path .= '/' . $nodeName . '[' . $this->_nodesCount[$depth] . ']';
//	 }
//	 $path .= '/' . $this->localName;
//	
//	 return $path;
//}
//}

?>