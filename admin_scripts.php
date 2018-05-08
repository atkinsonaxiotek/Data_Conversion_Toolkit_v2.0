<?php

/********************************************************************************
Function: getXMLpathsUsingXMLReader
One time function used to get a list of xml paths from a sample Colorado XML file.
*********************************************************************************/

function getXMLpathsUsingXMLReader() {
    $file = "uploads/voaco_live_VOA_Export_13_CedarViewI.xml";
    $reader = new XMLReader();
    $reader->open($file);
    $i=0;
    $currentDepth = 0;
    $temporaryPath = array();
    while( $reader->read() ) {
//    while( $i<6000 ) {
//        $reader->read();
        if($reader->nodeType == 1) {   // 1 means that the node is a "Start element" tag.
            
            if ($reader->depth == $currentDepth) {
                //echo "<br>same depth!!!<br>";
                array_pop($temporaryPath);
                array_push($temporaryPath,$reader->name);
            }
            if ($reader->depth < $currentDepth) {
                echo "<br>SHALLOWER!!!<br>";
                while ($reader->depth < $currentDepth) {
                    array_pop($temporaryPath);
                    $currentDepth -= 1;
                }
                array_pop($temporaryPath);
                array_push($temporaryPath,$reader->name);
            }
            if ($reader->depth > $currentDepth) {
                echo "<br>DEEPER!!!<br>";
                array_push($temporaryPath,$reader->name);
                $currentDepth = $reader->depth;
            }
            echo $i . " - ";
            echo "Depth: " . $reader->depth;
            echo "<br>";
            echo " Name: " . $reader->name;
            echo "<br>";
            //echo "<br> value: " . $reader->value;
            print_r($temporaryPath);
            echo "<br>";
            $XMLPathString = implode("/",$temporaryPath);
            echo $XMLPathString;
            $croppedXMLPathString = substr($XMLPathString,8); //remove "records/"
            echo $croppedXMLPathString;
            echo "<br>";
            writeArbitraryStringToDatabaseTable($croppedXMLPathString,"Path","XMLPaths");
        }
        $i += 1;
    }
    $reader->close();
}

/********************************************************************************
Function: getXMLpathsUsingDOM   --too slow!!!! --not used--
Takes two arguments, a MySQLi result object and an optional column index.
Returns the ith column as an array.
*********************************************************************************/
/*
function getXMLpathsUsingDOM() {
    // Create a new DOMDocument instance
    $dom = new DOMDocument;

    // Load the XML
    echo "Start loading at " . date("h:i:sa") . "<br>";
    $dom->load('uploads/voaco_live_VOA_Export_13_CedarViewI.xml');
    

    // Print XPath for each element
    echo "Finished loading, start getting Xpaths at " . date("h:i:sa") . "<br>";
    $xpaths = array();
    foreach ($dom->getElementsByTagName('*') as $node) {
        $xpath = $node->getNodePath();
        array_push($xpaths,$xpath);
        //echo $node->getNodePath() . "<br>";
    }
    echo "Finished at " . date("h:i:sa") . "<br>";
}
*/

/********************************************************************************
Function: loadDataSourceList
One time function used to fill the AffiliatesOfVOA table.
*********************************************************************************/

function loadDataSourceList() {
    $filename = "samples/ListOfAffiliatesVOA.txt";
    $file = fopen($filename, "r");
    connectToDatabase();
    $listOfAffiliateDescriptions = array();
    while(!feof($file)) {
	$currentLine = trim(fgets($file),"\r\n");
        echo $currentLine;
        echo "<br>" . strlen($currentLine) . "<br>";
        if ($currentLine != '') {
            array_push($listOfAffiliateDescriptions,$currentLine);
            writeArbitraryStringToDatabaseTable($currentLine,"Description","AffiliatesOfVOA");
        }
    }
    print_r($listOfAffiliateDescriptions);
    fclose($file);
}

