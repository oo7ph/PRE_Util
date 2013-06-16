<?php

class PRE_Util{

	/** Custom Map Function that re-keys to numbers */
	static function numbered_map($function, $in) {  
		$out = array();  
		$i=0;
		foreach($in as $key=>$item) {  
			$out[] = call_user_func($function,$item,$key,$i++);  
		}  
		return $out;  
	} 
	/** Custom Map Function */
	static function map($function, $in) {  
		$out = array();  
		$i=0;
		foreach($in as $key=>$item) {  
			$out[$key] = call_user_func($function,$item,$key,$i++);  
		}  
		return $out;  
	} 
	
	/** read an entire file out to a string */
	static function freadAll($handle){
		fseek($handle, 0);
		$out= "";
		while (!feof($handle)) {
			$out .= fread($handle, 8192);
		}
		return $out;
	}
	
	/** flattens an array one level */
	static function flat($arr){
		if(!empty($arr)){
			return call_user_func_array ("array_merge", $arr);
		}
		return array();
	}
	/** Takes one key and pushes it out and takes the rest of the array and puts it into a normal array as the second key in the primary array */
	static function bump($key,$arr){
		$bumpedArr[$key] = $arr[$key];
		unset ($arr[$key]);
		$bumpedArr[] = $arr;
		return $bumpedArr;
	}
	
	/** flattens an array one level while maintaining keys */
	static function array_kmerge ($array) { 
		reset($array);
		$start=0;
		while ($tmp = each($array)) 
		{ 
			if(count($tmp['value']) > 0) 
			{ 
				$k[$tmp['key']] = array_keys($tmp['value']); 
				$v[$tmp['key']] = array_values($tmp['value']); 
			} 
		} 
		while($tmp = each($k)) 
		{ 
			for ($i = $start; $i < $start+count($tmp['value']); $i ++)$r[$tmp['value'][$i-$start]] = $v[$tmp['key']][$i-$start]; 
			$start = count($tmp['value']); 
		} 
		return $r; 
	}
	
	/** Pull out one key in a 2 level array */
	static function pluck($key, $input) { 
		if (is_array($key) || !is_array($input)) return array(); 
		$array = array(); 
		foreach($input as $v) { 
			if(array_key_exists($key, $v)) $array[]=$v[$key]; 
		} 
		return $array; 
	}

	/** Outputs an array with whitespace respected, usually used for debugging */
	static function prettyArray($arr){
		echo "<pre>";
		print_r($arr);
		echo "</pre>";
		return $arr;
	}
	
	/** goes one layer deep to check an array for dupes */
	static function distinctArray($in){
		$out = array();  
		foreach($in as $item) {  
			$hash = hash('md5', implode(",",$item));
			if (!array_key_exists($hash,$out)){
			$out[$hash] = $item;
			}
		} 
		return array_values($out);
	}
	
	/** convert a csv file to an array */
	static function csvToArray($csvInput,$hasHead=true,$delim="\t",$enclosure='|'){
	//check if its before the cutoff and not a folder
	$tmpFile = self::mkTemp();
	$temp = fopen($tmpFile,"r+");
	fwrite($temp, $csvInput);
	fseek($temp, 0);
	$Outputcsv=array();
	//Parse CSV
	if ($hasHead){
		$i = 0;
		$line_num = 0;
		while(($row = fgetcsv($temp, 0,$delim, $enclosure))) {
			if($line_num++ == 0) {
				$headers = $row;
			}
			else{
				$j = 0;
				foreach($row as $value) {
					$header_name = $headers[$j++];
					$Outputcsv[$line_num][$header_name] = $value;
				}
			}
			
		}
	}
	else{
		while (!feof($temp)) {
			array_push($Outputcsv, fgetcsv($temp, 0, "\t", '"'));
		}
	}
	fclose($temp);
	@unlink($tmpFile);
	$Outputcsv = array_filter($Outputcsv,function(&$lines){return (count($lines)>1);});
	return $Outputcsv;

	}
	
	/** Converts an array to a csv */
	static function arrayToCsv ($arrInput,$hasHeader=true,$delim="\t",$enclosure='"'){
		$temp = tmpfile();
		$csv = '';
		if ($hasHeader){fputcsv($temp,array_keys($arrInput[0]),$delim,$enclosure);}
		foreach ($arrInput as $line){
			fputcsv($temp,$line,$delim,$enclosure);
		}
		$csv = self::freadAll($temp);
		//encode as utf8 and output
		$csv = utf8_encode ( $csv); 
		//filter out blank lines
		return $csv;
	}
	
	/** Convert XML to a array */
	static function xmlToArray($obj) {
		$output = self::xmlToArrayHelper($obj);
		switch (key($output)){
			case "xmldata": 
				return PRE_Util::flat($output);
			case "0":
				return $output;
			default:
				return array(PRE_Util::flat($output));
		}
	} 
	private static function xmlToArrayHelper($obj, $level=0) {
		$items = array();
		
		if(!is_object($obj)) return $items;
			
		$child = (array)$obj;
		
		if(sizeof($child)>1) {
			foreach($child as $aa=>$bb) {
				if(is_array($bb)) {
					foreach($bb as $ee=>$ff) {
						if(!is_object($ff)) {
							$items[$aa][$ee] = $ff;
						} else
						if(get_class($ff)=='SimpleXMLElement') {
							$items[$aa][$ee] = self::xmlToArrayHelper($ff,$level+1);
						}
					}
				} else
				if(!is_object($bb)) {
					$items[$aa] = $bb;
				} else
				if(get_class($bb)=='SimpleXMLElement') {
					$items[$aa] = self::xmlToArrayHelper($bb,$level+1);
				}
			}
		} else
		if(sizeof($child)>0) {
			foreach($child as $aa=>$bb) {
				if(!is_array($bb)&&!is_object($bb)) {
					$items[$aa] = $bb;
				} else
				if(is_object($bb)) {
					$items[$aa] = self::xmlToArrayHelper($bb,$level+1);
				} else {
					foreach($bb as $cc=>$dd) {
						if(!is_object($dd)) {
							$items[$obj->getName()][$cc] = $dd;
						} else
						if(get_class($dd)=='SimpleXMLElement') {
							$items[$obj->getName()][$cc] = self::xmlToArrayHelper($dd,$level+1);
						}
					}
				}
			}
		}
		return $items;
	}
	
	/**
	 * Build A XML Data Set
	 *
	 * @param array $data Associative Array containing values to be parsed into an XML Data Set(s)
	 * @param string $startElement Root Opening Tag, default fx_request
	 * @param string $xml_version XML Version, default 1.0
	 * @param string $xml_encoding XML Encoding, default UTF-8
	 * @return string XML String containig values
	 * @return mixed Boolean false on failure, string XML result on success
	 */
	static function arrayToXML($data, $startElement = 'fx_request', $xml_version = '1.0', $xml_encoding = 'UTF-8'){
		if(!is_array($data)){
			$err = 'Invalid variable type supplied, expected array not found on line '.__LINE__." in Class: ".__CLASS__." Method: ".__METHOD__;
			trigger_error($err);
			if($this->_debug) echo $err;
			return false; //return false error occurred
		}
		$xml = new XmlWriter();
		$xml->openMemory();
		$xml->startDocument($xml_version, $xml_encoding);
		if(preg_match('/((?<=\s).*(?==))/', $startElement, $attrKeyMatch)){
			preg_match('/(?<==).*/', $startElement, $attrValueMatch);
			// Main Key
			preg_match('/.*(?=\s)/', $startElement, $mainKeyMatch);
			$xml->startElement($mainKeyMatch[0]);
				$xml->startAttribute($attrKeyMatch[0]);
					$xml->text($attrValueMatch[0]);
				$xml->endAttribute();
				self::xmlWrite($xml, $data);
			$xml->endElement();
		}
		else{
			$xml->startElement($startElement);
			self::xmlWrite($xml, $data);
			$xml->endElement();//write end element
		}

		//Return the XML results
		return $xml->outputMemory(true); 
	}
	
	/**
	* Write XML as per Associative Array
	* @param object $xml XMLWriter Object
	* @param array $data Associative Data Array
	*/
	static function xmlWrite(XMLWriter $xml, $data){
		foreach($data as $key => $value){
			if(is_numeric($key)){
				self::xmlWrite($xml, $value);
				continue;
			}
			if(is_array($value)){
				$xml->startElement($key);
				self::xmlWrite($xml, $value);
				$xml->endElement();
				continue;
			}
			// This is to handle keys with attributs ie <test currency="USD">
			if(preg_match('/((?<=\s).*(?==))/', $key, $attrKeyMatch)){
				preg_match('/(?<==).*/', $key, $attrValueMatch);
				// Main Key
				preg_match('/.*(?=\s)/', $key, $mainKeyMatch);
				$xml->startElement($mainKeyMatch[0]);
					$xml->startAttribute($attrKeyMatch[0]);
						$xml->text($attrValueMatch[0]);
					$xml->endAttribute();
					$xml->text($value);
				$xml->endElement();
				continue;
			}
			$xml->writeElement($key, $value);
		}
	}
	
	/** Make temporary file */
	static function mkTemp($prefix="tmp",$dir="/tmp", $suffix = '' ){
		return tempnam($dir,$prefix."_".session_id()."_".$suffix);
	}
	
	/** Get read to a string a text file contained within a zip file */
	static function getTxtFileFromZip ($zipPath,$txtFile){
		$content = '';
		$i=0;
		$z = new ZipArchive();
		if ($z->open($zipPath)) {
			$fp = $z->getStream("$txtFile.txt");
			if(!$fp) exit("Could Not Open Zip");
			 while (!feof($fp)) {
				$content .= fread($fp, 2);
			} 
			$z->close();
			fclose($fp);
		}
		return $content;
	}
	
	static function chunkProcessCsv($arr,$func,$chunk=100,$delim=", "){
		$chunks = self::map(function($chunk)use($delim){return implode($delim, $chunk);}, array_chunk($arr, $chunk));
		return self::map($func,$chunks);
	}
	
	static function chunkProcess($arr,$func,$chunk=10, $preserve_keys = false){
		$chunks = array_chunk($arr, $chunk, $preserve_keys);
		return self::map($func,$chunks);
	}

	static function parseRequestHeaders() {
		$headers = array();
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) <> 'HTTP_') {
				continue;
			}
			$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$headers[$header] = $value;
		}
		return $headers;
	}

	/** Returns true if the X-Debug HTTP header, or a debug GET or POST variable is set to the string "true". */
	static function isDebug() {
		$headers = self::parseRequestHeaders();
		return array_key_exists("debug", $_GET) && $_GET["debug"] == "true" || 
			   array_key_exists("debug", $_POST) && $_POST["debug"] == "true" || 
			   array_key_exists('X-Debug', $headers) && $headers['X-Debug'] == "true";
	}

	/** Gets the the relative path to the current directory. */
	static function getRelativeDir() {
		return str_replace("\\", "/", dirname($_SERVER['PHP_SELF']));
	}
	
	/**
	 * 
	 * Compares assoc array field=>val pairs to an array of valid fields and returns only pairs that intersect
	 * @param array $params field=>value pairs
	 * @param array $validParams array of valid fields
	 * @return returns intersection
	 */
	static function validParams ($params,$validParams){
		// Compare valid fields to Non-valid fields
		$validParams = array_flip($validParams);
		$validData = array_intersect_key($params, $validParams);
		return $validData;
	}
	
	/**
	 * Converts string mm/dd/yyyy to mysql time yyyy-mm-dd
	 */
	static function dateToMySql($date){
		return date('Y-m-d H:i:s', strtotime($date));
	}
	
	static function mySqlToDate($date){
		return date( 'm/d/Y', strtotime($date));
	}
	
	/**
	 * 
	 * Is formatted yyyy-mm-dd
	 * @param string $value
	 */
	static function isMysqlDate($value){
		return preg_match('/^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/',$value);
	}
	
	/**
	 * 
	 * Is formatted mm/dd/yyyy
	 * @param string $value
	 */
	static function isStandardDate($value){
		return preg_match('/^(0[1-9]|1[012])[\/](0[1-9]|[12][0-9]|3[01])[\/](19|20)\d\d$/',$value);
	}
	
	/**
	 * 
	 * Evaluates a string and if it matches mm/dd/yyyy it converts it to mysql date format otherwise passes it through
	 * @param string $value
	 */
	static function evalStringDateToMySql($value){
		return self::isStandardDate($value) ? self::dateToMySql($value) : $value;
	}
	
	/**
	 * returns the diffrence in days between 2 unix time stamp
	 */
	static function dateDiff($start,$end){
  		$diff = $end - $start;
  		return floor($diff / 86400);
	}
	
	/**
	 * Returns first item in an array
	 */
	static function first ($arr){
		return self::flat(array_slice($arr, 0, 1,true));
	}
	
	/**
	 * For a given function evaluates a 2 teir array and returns all internal arrays that evaluate true for the callback function
	 */
	static function wFilter($f,$arr){
		return array_filter(
			function($line,$key)use($f){
				PRE_Util::any($f,$arr);
			}, 
		$arr);	
	}
	
	/**
	 * Evaluates an array to see if any value in the array evals for true.
	 */
//	static function any($f,$arr){
//		$evalTo = false;
//		foreach (PRE_Util::map(function($value, $key) use($f) {return $f($value, $key=null);}, $arr) as $isTrue){
//			$evalTo = $isTrue;
//			if($isTrue) {
//				break;
//			}
//		} 
//		return $evalTo ? true : false;
//	}
	
	/**
	 * Evaluates an array to see if all values in the array evaluate to true for the given function (which takes a value and optional key).
	 */
	static function any($f,$arr) {
		
		foreach($arr as $key=>$item) {
			if(call_user_func($f, $item, $key)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Evaluates an array to see if all values in the array evaluate to true for the given function (which takes a value and optional key).
	 */
	static function all($f,$arr) {
		
		foreach($arr as $key=>$item) {
			if(!call_user_func($f, $item, $key)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Evaluates an input for empty and returns input if true else returns null
	 */
	static function nullify($input){
		return empty($input) ? null:$input;
	}
	
	/**
	 * Returns an array array('success'=>false,'message'=>$message)
	 */
	static function message($message,$success=false){
		return array('success'=>(boolean)$success,'message'=>$message);
	}
	
	/**
	 * Take an array of objects and group them by a given key.
	 * @return An array with keys equal to each unique value of the grouped key, and values equal to an array of objects that fit into each group.
	 */
	static function group($arr, $groupBy){
		$groups = array();
		foreach($arr as $item) {
			$val = $item[$groupBy];
			$groups[$val][] =$item;
		}
		return $groups;
	}
	
	/**
	 * 
	 * Because PHP can be dumb, I needed an elegant way to index into functions that return an array. Here it is.
	 * @param function $function A function that returns an array
	 * @param array $indexArr An ordered array of indexes
	 */
	static function indexInto($function,$indexArr){
		$indexedIntoFunction = $function;
		foreach ($indexArr as $index){
			$indexedIntoFunction = $indexedIntoFunction[$index];
		}
		return $indexedIntoFunction;
	}
	
	/**
	 * random string generator
	 */
	static function randomStr($length = 10)
	{      
	    $chars = 'bcdfghjklmnprstvwxzaeiou1234567890';
	    $result = '';
	    for ($p = 0; $p < $length; $p++)
	    {
	        $result .= ($p%2) ? $chars[mt_rand(19, 33)] : $chars[mt_rand(0, 18)];
	    }
	    
	    return $result;
	}
	
	/**
	 * Takes a veritcal data set and makes it horizontal
	 * @param arrray $data data to be translated
	 * @param string $key field to be used as the key for the translated data
	 * @param string $fieldName name of the field to be used as the field name
	 * @param string $fieldValue name of the field to be used as the field value
	 */
	public static function invertData($data, $key, $fieldName, $fieldValue){
		return PRE_Util::flat(PRE_Util::map(function($line)use($key, $fieldName, $fieldValue){
			return array($line[$fieldName]=>$line[$fieldValue]);
		}, $data));
	}
	
	/** Iterates through an array of arrays and removes all keys that match keys in the excluded array **/
	static function exclude($arrayOfRecords,$exclude){
		return PRE_Util::numbered_map(function($record)use($exclude){
			foreach ($exclude as $field){
				unset($record[$field]);
			}
			return $record;
		}, $arrayOfRecords);
	}
	
	/** Checks to see if a given array is associative **/
	static function isAssociativeArr($arr){
		return key($arr) !== 0;
	}
	
	/** Outputs a log file **/
	static function log ($message, $filePath){
		$output = "** [" . strftime('%T %Y-%m-%d') . "] " . $message . "\n";
		self::writeToFile($output, $filePath);
	}
	
	static function writeToFile($string, $filePath){
		$fh = fopen($filePath, 'a') or die("Cannot open/create log file.");
		fwrite($fh, $string);
		fclose($fh);
		return $filePath;
	}
	
	static function deleteFiles($files){
		foreach ($files as $file){
			unlink($file);
		}
	}
}
?>
