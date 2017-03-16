# memespector

A simple script for using Google's Vision API. Takes a comma- or tab-separated file containing a column with image URLs as input, sends images to the Vision API and puts the detected annotations back into the list.

## Installation

Follow the following steps for installation on a php equipped machine:

1. Download, unzip, and place the script files in some directory.
2. In the same directory, create three folders (“cache”,”data”,”outputs”) and make sure they can be written to (e.g. via chmod on Unix-like systems).
3. Go to apis.google.com and get an API key for Google’s Vision API.
4. Rename the config\_sample.php file to config.php and make the following edits:
	* insert the file name containing your image URLs (local or online) into the value of the $inputfile;
	* specify the name of the column containing the URLs in the $urlcolumn variable; 
	* specify the column delimiter in the $csvdelimiter variable (“\t” for tab-separated files or “,” for comma-separated files);
	* put your API key into the $apikey variable;

## Execution

Run the script in a terminal using by typing “php vision.php”. The script should start counting up to the number of images and store the answers from the Vision API in the cache folder. If the script is interrupted, it will retrieve the data from these files instead of hitting the API again. When finished, the script will write a new file into the outputs directory with the API results added as new columns.

## Credits

Written by Bernhard Rieder, University of Amsterdam, https://github.com/bernorieder/, http://thepoliticsofsystems.net

This is as-is software, no support is provided.
