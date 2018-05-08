<?php
require 'php_libraries/utilities.php';
$conn = connectToDatabase();

$str_taskAndJobID = $_POST['taskAndID'];
$array_taskAndJobID = explode("_",$str_taskAndJobID);
$sql = 'CALL UpdateJobByID(' . $array_taskAndJobID[1] . ", '" . $array_taskAndJobID[0] . "')";
if (mysqli_query($conn, $sql)) {
    header("Location: http://netdrv03/voanational-dev02/admintools.php", true, 301);
    die();
}
else {
    echo $sql . "<br>";
    echo 'Error - ' . mysqli_error($conn) . "<br>";
    echo '<a href="http://netdrv03/voanational-dev02/admintools.php">Return to previous page</a>';
}

?>  