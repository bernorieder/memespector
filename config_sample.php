<?php

$inputfile = "thelistcontainingtheurls.tab";							// filename for the input file, CSV or TSV

$urlcolumn = "imageurl";												// name of the column that contains the image URL? 
$csvdelimiter = "\t"; 													// the column delimiter

// three folders the script needs, create in the same directory and make sure they can be written to
$datadir = getcwd() . "/data/";
$jsondir = getcwd() . "/cache/";
$outputsdir = getcwd() . "/outputs/";

$apikey = "";					// your api key

ignore_user_abort(false);
set_time_limit(3600*5);
ini_set("memory_limit","100M");
ini_set("error_reporting",1);	
	
?>