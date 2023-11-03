<?php
include_once "../src/config.php";

$file_url = $_GET['n'];

header('Content-Type: application/pdf');
header("Content-disposition: attachment; filename=\"$file_url.pdf\"");
header('Pragma: no-cache');

readfile(PATH_FILE.$file_url.".pdf");

?>