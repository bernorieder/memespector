<?php

include_once("config.php");

$datafile = $dataDir . $inputFile;
$images = getCSV($datafile,$csvDelimiter);

$fp = fopen($outputsDir . "processed_" . $inputFile, "w");

if (array_key_exists("created_time_unix", $images[0])) {
    /* This is facebook specific */
    $fields = array("original_filename");
} else {
    $fields = array();
}
$fields = array_merge($fields, array("image_id", "file_ext", "copy_filename","gv_ss_adult","gv_ss_spoof","gv_ss_medical","gv_ss_violence","gv_labels", "gv_text", "gv_web_entities", "gv_web_full_matching_images", "gv_web_partial_matching_images", "gv_web_pages_with_matching_images", "gv_web_visually_similar_images", "gv_face_joy", "gv_face_sorrow", "gv_face_anger", "gv_face_surprise"));

$newheader = array_merge(array_keys($images[0]), $fields);

fwrite($fp, "\xEF\xBB\xBF" . implode($csvDelimiter, $newheader) . "\n");

//Print information for user display. Set a few operational variables.
echo "\nGoogle Vision API enabled modules:\n";
foreach ($moduleActivation as $module => $status) {
	if($status) {
		echo " ." . $module . "\n";
	}
}
echo "\n• • • • •\n";

echo "Project name:\t" . $projectName . "\n";
echo "Input file:\t" . $inputFile . "\n";

$numImages = count($images);
echo "Images: \t" . count($images) . "\n";

if($limit > 0 && $limit <= $numImages) {
	$numImages = $limit;
	echo "Subset: " . $numImages . "\n• • • • •\n\n";
}

// For each image in the dataset

for($i = 0; $i < $numImages; $i++) {

    // Check if URL field contains something
    if (strlen($images[$i][$imagesColumn]) == 0) {
        echo ($i + 1) . "\n**ERROR**\nThis row does not seem to have an image URL. Did you configure the column name and delimiter right (see config.php)? Hint: don't use Excel.\n";
        continue;
    }

    // Generate hash from URL
    $imageID = sha1($images[$i][$imagesColumn]);

    if(!$imagesRemote && !$absolutePath) {
      $imagePath = $inputImgDir . $images[$i][$imagesColumn];
    }
    else {
      $imagePath = $images[$i][$imagesColumn];
    }

    echo "Image " . ($i + 1) . " of " . $numImages . "\n";
    echo "Path: " . $imagePath . "\n";
    echo "Hash: " . $imageID . "\n";

    // Specific tweaks for Facebook data. Extraction of file extension.
    if (array_key_exists("created_time_unix", $images[$i])) {
      /* This is facebook specific */
    	$images[$i]["created_time"] = date("Y-m-d H:i:s", $images[$i]["created_time_unix"]);

    	preg_match_all("/.+\/(.+?)\?/",$imagePath,$out);
    	$images[$i]["original_filename"] = $out[1][0];
      $ext = pathinfo($images[$i]["original_filename"], PATHINFO_EXTENSION);
    }
    else {
      $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
    }

    // Add hash and extension to CSV
    $images[$i]["image_id"] = $imageID;
    $images[$i]["file_ext"] = $ext;
    $images[$i]["copy_filename"] = $imageID . "." . $ext;

    // Make copy of image if set to do so
    if($saveImageCopy){
      $localFile = $imgDir . $imageID . "." . $ext;
      echo "Copy path: " . $localFile . "\n";
      if(!file_exists($localFile)){
        echo "\tCopying image...";
        copy($imagePath, $localFile);
        echo "done.\n";
      }
      else {
        echo "\tCopy already existed \n";
      }
    }

    // Process image (request to API)
    if ($forceBase64 && $saveImageCopy) {
      $info = processImage($localFile, $imageID);
    }
    else {
      $info = processImage($imagePath, $imageID);
    }

    // Catch error (specifically: image not retrievable by the API)
    $error= catchError($info);

    // Parse API json response and add it to the processed CSV
    foreach ($moduleActivation as $module => $status) {
  		if(!$status){
  			switch ($module) {
  					case 'LABEL_DETECTION':
  						$images[$i]["gv_labels"] = "UNDETECTED";
  						break;
  					case 'TEXT_DETECTION':
  						$images[$i]["gv_text"] = "UNDETECTED";
  						break;
  					case 'SAFE_SEARCH_DETECTION':
  						$images[$i]["gv_ss_adult"] = "UNDETECTED";
  						$images[$i]["gv_ss_spoof"] = "UNDETECTED";
  						$images[$i]["gv_ss_medical"] = "UNDETECTED";
  						$images[$i]["gv_ss_violence"] = "UNDETECTED";
  						break;
  					case 'WEB_DETECTION':
  						$images[$i]["gv_web_entities"] = "UNDETECTED";
  						$images[$i]["gv_web_full_matching_images"] = "UNDETECTED";
  						$images[$i]["gv_web_partial_matching_images"] = "UNDETECTED";
  						$images[$i]["gv_web_pages_matching_images"] = "UNDETECTED";
  						$images[$i]["gv_web_visually_similar_images"] = "UNDETECTED";
  						break;
  					case 'FACE_DETECTION':
  						$images[$i]["gv_face_joy"] = "UNDETECTED";
  						$images[$i]["gv_face_sorrow"] = "UNDETECTED";
  						$images[$i]["gv_face_anger"] = "UNDETECTED";
  						$images[$i]["gv_face_surprise"] = "UNDETECTED";
  						break;
  				}
  		}
  		else {
  			switch ($module) {
  				case 'LABEL_DETECTION':
  					$labels = array();
  					foreach ($info->responses[0]->labelAnnotations as $annotation) {
  						$labels[] = $annotation->description . "(" . $annotation->score . ")";
  					}
  					$images[$i]["gv_labels"] = implode(",", $labels);
  					break;
  				case 'TEXT_DETECTION':
  					$images[$i]["gv_text"] = clean($info->responses[0]->textAnnotations[0]->description);
  					break;
  				case 'SAFE_SEARCH_DETECTION':
  					$images[$i]["gv_ss_adult"] = $info->responses[0]->safeSearchAnnotation->adult;
  					$images[$i]["gv_ss_spoof"] = $info->responses[0]->safeSearchAnnotation->spoof;
  					$images[$i]["gv_ss_medical"] = $info->responses[0]->safeSearchAnnotation->medical;
  					$images[$i]["gv_ss_violence"] = $info->responses[0]->safeSearchAnnotation->violence;
  					break;
  				case 'WEB_DETECTION':
  					$entities = array();
  					foreach ($info->responses[0]->webDetection->webEntities as $annotation) {
  						$entities[] = $annotation->description . "(" . $annotation->score . ")";
  					}
  					$images[$i]["gv_web_entities"] = implode(",", $entities);

            $branches = array( 'fullMatchingImages' => 'gv_web_full_matching_images',
                               'partialMatchingImages' => 'gv_web_partial_matching_images',
                               'pagesWithMatchingImages' => 'gv_web_pages_with_matching_images',
                               'visuallySimilarImages' => 'gv_web_visually_similar_images'
                             );

            foreach ($branches as $branch => $csvfield) {
                $urls = array();
                foreach ($info->responses[0]->webDetection->$branch as $annotation) {
                    $urls[] = str_replace(",", "%2C", $annotation->url);
                }
                $images[$i][$csvfield] = implode(",", $urls);
            }
  					break;
  				case 'FACE_DETECTION':
  					$faces = array();
  					$joyHigh = "UNDETECTED";
  					$sorrowHigh = "UNDETECTED";
  					$angerHigh = "UNDETECTED";
  					$surpriseHigh = "UNDETECTED";
  					foreach($info->responses[0]->faceAnnotations as $annotation) {
  						$joyHigh = likelihoodCompare($joyHigh, $annotation->joyLikelihood);
  						$sorrowHigh = likelihoodCompare($sorrowHigh, $annotation->sorrowLikelihood);
  						$angerHigh = likelihoodCompare($angerHigh, $annotation->angerLikelihood);
  						$surpriseHigh = likelihoodCompare($surpriseHigh, $annotation->surpriseLikelihood);
  					}
  					$images[$i]["gv_face_joy"] = $joyHigh;
  					$images[$i]["gv_face_sorrow"] = $sorrowHigh;
  					$images[$i]["gv_face_anger"] = $angerHigh;
  					$images[$i]["gv_face_surprise"] = $surpriseHigh;
  					break;
  			}
  		}
  	}
  	echo "\n";
  	fputcsv($fp,$images[$i],$csvDelimiter,"\"","\\");
  	$images[$i] = "";
}

function processImage($imageUrl, $imageHash) {
  global $jsonDir,$jsoncopyDir;

  $jsonfn = $jsonDir . $imageHash . ".json";
  $jsoncopy = $jsoncopyDir . $imageHash . ".json";

  // Check if file has been processed and use cahed content if available
  if(file_exists($jsonfn)) {
    echo "\t**Using cached content (remove all files in the cache folder if you see this message and the tool is not working yet)**\n";
    $jsonResponse = file_get_contents($jsonfn);
    file_put_contents($jsoncopy, $jsonResponse);
  }
  else {
    $jsonResponse = getAnnotation($imageUrl, $imageHash);
    file_put_contents($jsonfn, $jsonResponse);
		file_put_contents($jsoncopy, $jsonResponse);
  }
  return json_decode($jsonResponse);
}

function getAnnotation($imageUrl, $imageID) {
  global $apiKey, $imagesRemote, $forceBase64, $saveImageCopy, $imgDir;

  // Create json request according to settings
  if($imagesRemote && !$forceBase64) {
    $jsonRequest = jsonRequestRemote($imageUrl);
  }
  else {
    if(!$absolutePath && !$imagesRemote) {
      $imageUrl = $inputImgDir . $imageUrl;
    }
    echo "\tEncoding base64...";
    $image_base64 = base64_encode(file_get_contents($imageUrl));
    $jsonRequest = jsonRequestBase64($image_base64);
    echo "done.\n";
  }

  // Submit request to API
  $cvurl = 'https://vision.googleapis.com/v1/images:annotate?key=' . $apiKey;
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $cvurl);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonRequest);
  echo "\tMaking API request...";
  $jsonResponse = curl_exec($curl);
  $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  curl_close($curl);
  echo "done. \n";
  return $jsonResponse;
}

function jsonRequestRemote($imageUrl) {
  $jsonRequest =   '{
      "requests": [
  			{
  				"image": {
  					"source": {
              "imageUri": "' . $imageUrl . '"
            }
  				},
  				"features": [' . jsonRequestFeatures() . ']
  			}
  		]
    }';
    return $jsonRequest;
}

function jsonRequestBase64($base64) {
  $jsonRequest =   '{
      "requests": [
  			{
          "image": {
						"content": "'.$base64.'"
					},
  				"features": [' . jsonRequestFeatures() . ']
  			}
  		]
    }';
  return $jsonRequest;
}

function jsonRequestFeatures() {
  global $moduleActivation, $maxResults;

  $jsonRequestFeatures = '';
  end($moduleActivation);
  $lastModuleKey = key($moduleActivation);

  foreach ($moduleActivation as $module => $status) {
    if(!$status) { continue; }
    $jsonRequestFeatures .= '
    {
      "type": "' . $module . '",
      "maxResults": ' . $maxResults . '
    }';
    if($module != $lastModuleKey) {
      $jsonRequestFeatures .= ',
      ';
    }
  }
  return $jsonRequestFeatures;
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

function likelihoodCompare($one, $two) {
	if($one=="UNDETECTED" && ($two=="UNKOWN" || $two=="VERY_UNLIKELY" || $two=="UNLIKELY" || $two=="POSSIBLE" || $two=="LIKELY"|| $two=="VERY_LIKELY")) {
		return $two;
		}
	else if($one=="UNKWOWN" && ($two=="VERY_UNLIKELY" || $two=="UNLIKELY" || $two=="POSSIBLE" || $two=="LIKELY"|| $two=="VERY_LIKELY")) {
		return $two;
		}
	else if ($one=="VERY_UNLIKELY" && ($two=="UNLIKELY" || $two=="POSSIBLE" || $two=="LIKELY"|| $two=="VERY_LIKELY")) {
		return $two;
		}
	else if ($one =="UNLIKELY" && ($two=="POSSIBLE" || $two=="LIKELY"|| $two=="VERY_LIKELY")) {
		return $two;
		}
	else if ($one =="POSSIBLE" && ($two=="LIKELY"|| $two=="VERY_LIKELY")) {
		return $two;
		}
	else if ($one =="LIKELY" && ($two=="VERY_LIKELY")) {
		return $two;
		}
	else {
		return $one;
		}
}

function catchError($jsonResponse) {
  foreach($jsonResponse->responses[0] as $error) {
    switch ($error->code) {
      case 7:
        echo "\n **PROCESSING ERROR** \nGoogle Vision API is unable to access the remote image. Try setting 'forceBase64' in configuration file to 'TRUE'. Script will be interrupted. \n\n";
        exit();
        break;
      default:
        break;
    }
  }
}

?>
