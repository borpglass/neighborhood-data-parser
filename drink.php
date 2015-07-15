<?php

//Make the script not conk out as it guzzles pdfs
ini_set('memory_limit', '512M');
//Instantiate our pdfdrinker objet
$drinker = new pdfdrinker;
//Tell the pdfdrinker where to find its files
$files = $drinker->getfilelist(getcwd(),'.pdf.txt');
//Get a big recursive array of all our pdf data. Each file should be read from the start through the string "AGE 0"
$bigarray = $drinker->makebigsoda($files,"","AGE 0");
//Do some custom stuff to get just the bits we want. (See custom function below for detailed logic.)
$bigarray = customclean($bigarray);

//print_r($bigarray);

//Dump it all to a csv

$csvpointer = fopen('output.csv','w');
fwrite($csvpointer,implode(',',array_keys($bigarray[0]))."\n");
foreach($bigarray as $fields){
	fputcsv($csvpointer,$fields);
}
fclose($csvpointer);





//This if a one-off function for a specific use case, unlike the reusable pdfdrinker functions.
//It returns a nicely formatted array of racial (and maybe eventually other demographic) info for each place/pdf. 
function customclean($input){
	$output = array();
	foreach($input as $k=>$v){
		$better = trim($v);
		if(strpos($better,'TOTAL POPULATION') !== false){
			$temporary[] = $better;
		}
	}
	foreach($temporary as $k=>$v){
		$bigchunks = explode('TOTAL POPULATION',$v);
		$firstchunkrows = explode("\n",$bigchunks[0]);
		$bigchunks[0] = trim($firstchunkrows[0]);
		//if($bigchunks[0] == 'Sandtown-Winchester' || $bigchunks[0] == 'Perkins Homes'){
		//print_r($bigchunks)
			$output[$k]['neighborhood-statistical-area'] = trim($bigchunks[0]);
			$asoneline = preg_replace('/\n/msi', ' ', $bigchunks[2]);
			preg_match_all('/[0-9,-.]+\s/',$asoneline,$smallchunks);	
			$output[$k]['twenty-ten-overall-population'] = $smallchunks[0][0];
			$output[$k]['twenty-ten-white-percentage'] = $smallchunks[0][8];
			$output[$k]['twenty-ten-black-percentage'] = $smallchunks[0][9];
			$output[$k]['twenty-ten-american-indian-percentage'] = $smallchunks[0][10];
			$output[$k]['twenty-ten-asian-percentage'] = $smallchunks[0][11];
			$output[$k]['twenty-ten-other-percentage'] = $smallchunks[0][12];
			$output[$k]['twenty-ten-mixed-percentage'] = $smallchunks[0][13];
			$output[$k]['twenty-ten-hispanic-percentage'] = $smallchunks[0][14];
		//}
	}


	return($output);
}







//Tried to make most of this at least a bit reusable for other situations.
//So it includes a tweakable function for grabbing all local .pdf.txt files, and one for putting a predefined slice of 
//each file into an array.
//Logic partially borrowed/modded from 
//https://github.com/borpglass/simple-sequential-scraper-tools/blob/master/smashbigtables.php

class pdfdrinker{ 
	//This class assumes you ahve already run pdftotext on a bunch of files.
	//These tasks allow you to clean up the output into a format that is useful -- usually csv.
	public function getfilelist($dir='', $extension = '.pdf.txt'){
			//This function returns a list of files, the better for us to do things with them. 
			$this->extension = $extension;
			$this->skipfirstrow = true;
			$dir = $dir != '' ? $dir : getcwd();
			$allfiles = scandir($dir);
			$ourfiles = array();
			foreach($allfiles as $index => $filename){
				if(strpos($filename,$extension) !== false){
					$ourfiles[] = $filename;
				}else{
					//print("\nDiscarding file $filename");
				}
			}
			$existingrows = array();
			return($ourfiles);
	}
	public function makebigsoda($files,$startstring="",$endstring=""){
	//This function will concatenate a bunch of pieces of our converted pdfs into an array of more manageable items. 
	//The first instance of $startstring will denote the beginning of what we want from each file.
	//The first instance of $endstring will denote the end of what we want in each file.
		$output = array();
		foreach($files as $k => $v){
			$contents = file_get_contents($v);
			if($startstring != ""){
				$clipped = explode($startstring,$contents);
				$clipped = array_pop($clipped);
			}else{
				$clipped = $contents;
			}
			if($endstring != ""){
				$clipped = explode($endstring,$clipped);
				$clipped = array_shift($clipped);
			}else{
				$clipped = $clipped;
			}
			$output[] = $clipped;
		}
		return($output);
	}
}


?>
