<?php

header('Content-Type: application/json');

include_once("config.php");



$images = getCSV("images.tab","\t");				// test file, taken from Netvizz' image export for Facebook pages

for($i = 0; $i < count($images); $i++) {
	
	if($i == 1) { exit; }
	
	$info = getImageBinary($images[$i]["imageurl"]);
	
	print_r($info);
}

	

function getImageBinary($image_url) {

	global $apikey;

	$image_base64 = base64_encode(file_get_contents($image_url));
	
	$cvurl = 'https://vision.googleapis.com/v1/images:annotate?key=' . $apikey;
	$type = 'FACE_DETECTION';
	
	$request_json = '
	{
		"requests": [
			{
				"image": {
					"content": "'.$image_base64.'"
				},
				"features": [
					{
						"type": "FACE_DETECTION"
					},
					{
						"type": "LANDMARK_DETECTION"
					},
					{
						"type": "LOGO_DETECTION"
					},
					{
						"type": "LABEL_DETECTION"
					},
					{
						"type": "TEXT_DETECTION"
					},
					{
						"type": "SAFE_SEARCH_DETECTION"
					},
					{
						"type": "IMAGE_PROPERTIES"
					}
					
				]
			}
		]
	}';
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $cvurl);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $request_json);
	$json_response = curl_exec($curl);
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);
		
	return $json_response;
}


function getCSV($file,$delimiter) {
	
	$data = array();
	
	$lines = file($file);
	for($i = 0; $i < count($lines); $i++) {
		$lines[$i] = explode($delimiter, $lines[$i]);
	}
	
	for($i = 1; $i < count($lines); $i++) {
		$tmparray = array();
		for($j = 0; $j < count($lines[0]); $j++) {
			$tmparray[$lines[0][$j]] = $lines[$i][$j];
		}
		$data[] = $tmparray;
	}
	
	return $data;
}
	
?>