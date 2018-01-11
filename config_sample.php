<?php

// Dataset or project name, will be used to create folder and to name the output files.
$projectName = "PROJECT_NAME";

// !! CAREFUL !! Limit processing for testing (use 0 for unliminted processing)
$limit = 20;

// ----------------------------------------

// ** INPUT CONFIGURATION **

// Filename for the input file, CSV or TSV. Add full path for local folder if needed. It should be placed in the Data folder
$inputFile = "thelistcontainingtheurls.tab";

// Column header in CSV for the image file names or URL addresses, depending on whether files are local or remote. Header must be unique.
$imagesColumn = "imageurl";

// The column delimiter ("\t" for tab, "," for comma, etc.).
$csvDelimiter = "\t";

// * ONLY FOR PROCESSING LOCAL IMAGES *
// Set to TRUE if input file contains absolute paths to image files. Otherwise, script will look for subfolder 'IMG' on 'data' folder.
$absolutePath = FALSE;

// ----------------------------------------

// ** IMAGE DOWNLOAD CONFIGURATION **

// Set TRUE if images are found online. Otherwise, script will retrieve images from local path.
$imagesRemote = TRUE;

// ** Needed for Facebook images. LEAVE TRUE IF UNSURE **
// Set TRUE to process REMOTE images from local copies. Slower and with more network trafic, but useful when Google Vision API is unable to retrieve images by itself.
$forceBase64 = TRUE;

// Set TRUE if you want to make copies of the processed images
$saveImageCopy = FALSE;

// ---------------------------------------

// ** API REQUEST SETTINGS **

// Set TRUE or FALSE to activate Google Vision API modules (DO NOT CHANGE ORDER. IT MATTERS).
$moduleActivation = array(
	"SAFE_SEARCH_DETECTION"	=> FALSE,
	"LABEL_DETECTION"				=> FALSE,
	"TEXT_DETECTION"				=> FALSE,
	"WEB_DETECTION"					=> FALSE,
	"FACE_DETECTION"				=> FALSE
//TO BE FUTURELY IMPLEMENTED
	// "CROP_HINTS"
	// "IMAGE_PROPERTIES"
	// "LANDMARK_DETECTION"
	// "LOGO_DETECTION"
	);

//Limit maximum number of results per aspect
$maxResults = 10;

// !! CAREFUL !! Your Google Vision API key
$apiKey = "YOUR_API_KEY_HERE";

// ---------------------------------------

// ** INTERNAL SETTINGS (Probably no need to change) **

// Folders the script needs, create in the same directory and make sure they can be written to
$dataDir		= getcwd() . "/data/";
$jsonDir		= getcwd() . "/cache/";
$outputsDir = getcwd() . "/outputs/" . $projectName . "/";
$imgDir 		= $outputsDir . "IMG/";
$jsoncopyDir= $outputsDir . "cache_copy" . "/";
$inputImgDir= $dataDir . "IMG/";

if(!file_exists($outputsDir)) {
	mkdir($outputsDir);
}

if($saveImageCopy && !file_exists($imgDir)) {
	mkdir($imgDir);
}

if(!file_exists($jsoncopyDir)) {
	mkdir($jsoncopyDir);
}


ignore_user_abort(false);
set_time_limit(3600*5);
ini_set("memory_limit","100M");
ini_set("error_reporting",1);

?>
