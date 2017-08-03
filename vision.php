<?php

include_once("config.php");

$datafile = $datadir . $inputfile;

$images = getCSV($datafile,$csvdelimiter);
if (array_key_exists("created_time_unix", $images[0])) {
    /* This is facebook specific */	
    $fields = array("filename");
} else {
    $fields = array();
}
$fields = array_merge($fields, array("gv_annotation","gv_ss_adult","gv_ss_spoof","gv_ss_medical","gv_ss_violence","gv_tags","gv_web_entities", "gv_web_full_matching_images", "gv_web_partial_matching_images", "gv_web_visually_similar_images", "gv_web_pages_with_matching_images"));

$newheader = array_merge(array_keys($images[0]), $fields);

$fp = fopen($outputsdir . "processed_" . $inputfile, "w");
fwrite($fp, "\xEF\xBB\xBF" . implode($csvdelimiter, $newheader) . "\n");

echo "working on " . count($images) . " images: ";

for($i = 0; $i < count($images); $i++) {

    if (strlen($images[$i][$urlcolumn]) == 0) {
        echo ($i + 1) . " - this row does not seem to have an image URL. Did you configure the column name and delimiter right (see config.php)? Hint: don't use Excel.\n";
        continue;
    }
	
	echo ($i + 1) . " (" . $images[$i][$urlcolumn] . "): ";

	$info = getImageBinary($images[$i][$urlcolumn]);

    if (array_key_exists("created_time_unix", $images[$i])) {

        /* This is facebook specific */	

    	$images[$i]["created_time"] = date("Y-m-d H:i:s", $images[$i]["created_time_unix"]);
	
    	preg_match_all("/.+\/(.+?)\?/",$images[$i][$urlcolumn],$out);
    	$images[$i]["filename"] = $out[1][0];

    }
	
	$images[$i]["gv_annotation"] = clean($info->responses[0]->textAnnotations[0]->description);
	$images[$i]["gv_ss_adult"] = $info->responses[0]->safeSearchAnnotation->adult;
	$images[$i]["gv_ss_spoof"] = $info->responses[0]->safeSearchAnnotation->spoof;
	$images[$i]["gv_ss_medical"] = $info->responses[0]->safeSearchAnnotation->medical;
	$images[$i]["gv_ss_violence"] = $info->responses[0]->safeSearchAnnotation->violence;

	$labels = array();
	foreach($info->responses[0]->labelAnnotations as $annotation) {
		$labels[] = $annotation->description . "(" . $annotation->score . ")";
	}
		
	$images[$i]["gv_labels"] = implode(",", $labels);
    
    $entities = array();
    foreach ($info->responses[0]->webDetection->webEntities as $annotation) {
		$entities[] = $annotation->description . "(" . $annotation->score . ")";
    }
    $images[$i]["gv_web_entities"] = implode(",", $entities);

    $branches = array( 'fullMatchingImages' => 'gv_web_full_matching_images',
                       'partialMatchingImages' => 'gv_web_partial_matching_images',
                       'visuallySimilarImages' => 'gv_web_visually_similar_images',
                       'pagesWithMatchingImages' => 'gv_web_pages_with_matching_images'
                     );

    foreach ($branches as $branch => $csvfield) {
        $urls = array();
        foreach ($info->responses[0]->webDetection->$branch as $annotation) {
            $urls[] = str_replace(",", "%2C", $annotation->url);
        }
        $images[$i][$csvfield] = implode(",", $urls);
    }
	
	fputcsv($fp,$images[$i],$csvdelimiter,"\"","\\");
	
	$images[$i] = "";
}


function getImageBinary($image_url) {

	global $apikey,$jsondir;

	$jsonfn = $jsondir . sha1($image_url) . ".json";

	if (!file_exists($jsonfn)) {

        echo "downloading";

		// read image from URL and encode base64 to directly send in the request
		$image_base64 = base64_encode(file_get_contents($image_url));

        echo " .. analysing .. ";

		$cvurl = 'https://vision.googleapis.com/v1/images:annotate?key=' . $apikey;
		
		$request_json = '{
			"requests": [
				{
					"image": {
						"content": "'.$image_base64.'"
					},
					"features": [
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
							"type": "WEB_DETECTION"
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
	
		file_put_contents($jsonfn, $json_response);

        echo "done\n";
		
	} else {

        echo "using cached content (remove all files in the cache folder if you see this message and the tool is not working yet)\n"; 
		
		$json_response = file_get_contents($jsonfn);
	}
		
	return json_decode($json_response);
}


function getCSV($filename,$delimiter = ",") {
	
	if(!file_exists($filename) || !is_readable($filename)) { return false; }
	
	$header = null;
	$data = array();
	if(($handle = fopen($filename,"r")) !== false) {
		while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
			if(!$header) {
				$row[0] = preg_replace("/\xEF\xBB\xBF/", "", $row[0]);			// delete UTF-8 BOM (it's put into the file again at write)
				$header = $row;
			} else {
				$data[] = array_combine($header, $row);
			}
		}
		fclose($handle);
	}
	return $data;
}


function clean($text) {
	
	$text = preg_replace("/[\n\t\r]/"," ", $text);
	
	return $text;
}
	
?>
