<?php

namespace App\Services;

class Utilities {

	/* Debug */
	public function p($arr){
	
		echo '<pre>'; print_r($arr); echo '</pre>';
	}

	public function rando($n){
	
		/*
			generate a random string of length @n consisting of upper and lower case alphanumeric characters and integers
			@n (int) - length of string to be generated
			RETURNS: random string of length @n
		*/
		
		return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $n);
	} 

	/* SQL */
	public function compileUpdate($to_update,$selector,$table){

		$data = []; $val = [];
		$n_data = count($to_update);
				
		$sql = 'UPDATE '.$table.' SET ';
		foreach($to_update as $k=>$p) {
			$data[$k] = $p;
			$sql .= '`'.$k.'`=?, ';
			$val[] = $data[$k];
		}
		$sql .= '`modified`=?, `user_agent`=?, `last_ip`=? WHERE '.$selector['key'].'=?';

		$val[] = date('Y-m-d H:i:s'); 			// modified
		$val[] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";	// user_agent
		$val[] = $_SERVER['REMOTE_ADDR']; 		// last_ip
		$val[] = $selector['val'];				// selector

		return ['sql' => $sql, 'val' => $val ];
	} 

	public function compileInsert($to_insert,$table){

		$data = []; $val = []; 
		$n_data = count($to_insert);
		$now = date('Y-m-d H:i:s');

		$sql = 'INSERT INTO '.$table.'(';
		$j=0; foreach($to_insert as $k=>$p){
		  $data[$k] = $p;
		  $sql .= '`'.$k.'`,';
		  $val[$j] = $data[$k];
		  $j++;
		}

		$sql .= '`created`, `modified`, `user_agent`, `last_ip`) VALUES(';
		$j=0; while(true){
		  if($j < $n_data + 3) $sql.='?,';
		  else { $sql.='?)'; break; }
		  $j++;
		}

		$val[] = $now; 	// created
		$val[] = $now; 	// modified
		$val[] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";	// user_agent
		$val[] = $_SERVER['REMOTE_ADDR']; // last_ip

		return ['sql' => $sql, 'val' => $val ];
	} 

	public function compileQuery($slugs,$keys,$offset = 0){

		/*
			transform @slugs and @keys into SQL string containing comma-seperated columns and an array of associated key-values for use with prepared statements
			
			@slugs (array) - an array of column names to be returned
			@keys (array) - a nested array containing key-value pairs to be interpreted as 'WHERE' clauses along with optional 'operator' key whose value can be 'and', 'or', 'null' and be interpreted accordingly
			@offset (int) - integer indicating offset to @slugs array 

			RETURNS : [ 'columns' 	 => an array containing a comma-seperated sql string to be interprete as sql columns              
                        'conditions' => [ 	'sql' => 'WHERE clause SQL string'
                                        	'val' => 'accociated values to be used in prepared statements' ] ]
		*/

		$columns = [];
		$conditions = [ 'sql' => '', 'val' => [] ];

		// slugs (columns)
		for($i = 0+$offset; $i < count($slugs); $i++){
			$columns[] = $slugs[$i];
		}
		if(count($columns) > 0) {
			$csv = "";
			foreach($columns as $val) $csv.=$val.',';
			$columns = rtrim($csv,',');
		} else $columns = '*'; 

		// keys (conditions)
		if(count($keys) > 0){ $j=0;
			foreach($keys as $pair){
				if($pair[0]==='operator') {
					$op = ($pair[1]==='or') ? '||' : '&&';
					continue;
				}
				if($j>0) $conditions['sql'] .= ' && '.$pair[0].'=?';
				else $conditions['sql'] .= 'WHERE '.$pair[0].'=?';
				$conditions['val'][] = str_replace('%20',' ',$pair[1]);
				$j++;
			}
			$conditions['sql'] = isset($op) ? str_replace('&&',$op,$conditions['sql']) : $conditions['sql'];	
		}		

		return [ 'columns' => $columns, 'conditions' => $conditions ];
	}

	/* Form Data Processing */
	public function check_null($arr){

		$null = [];
		foreach($arr as $k => $val){
			if($val === 0 || $val === 0.0 || $val === '0') continue;
			if(empty($val) || is_null($val)) {
				$null[] = $k;
			}
		}
		return $null;
	}

	public function cleanforSQL($str){
		 
		$str = preg_replace('@&lt;script&gt;*?.*?&lt;/script&gt;@siu','',
			   preg_replace('@<script[^>]*?.*?</script>@siu','', $str)); 	// remove all javascript code
		$str = stripslashes($str); 											// remove excess slashes
		$str = trim($str); 													// remove outer whitespace
		return $str;	
	} 

	/* Validation */
	public function isEmail($str){
				
		return filter_var($str, FILTER_VALIDATE_EMAIL);
	}

	/* Date & Time */
	public function now(){
	
		return date('Y-m-d H:i:s');	
	} 

	public function add2Date($date,$add){
		/*
			@date : initial datetime 
			@add  : time to add to @date (ex. '+6 months')
		*/
		return date('Y-m-d H:i:s', strtotime($add, strtotime($date)));
	} 

	public function datetimeDiff($d1,$d2){
		
		$d1 = date_create($d1);
		$d2 = date_create($d2);
		$diff = json_decode(json_encode(date_diff($d1, $d2)), true);
		
		return($diff);
	} 

	public function daysAgo($t){
		
		/* Returns time since $t in days if greater than one day(s),
		   in minute(s), second(s) otherwise, with correct pluralization */
		
		$t_since = $this->datetimeDiff($t,$this->now());
		
		if($t_since['days'] > 0) {
			$plur = $t_since['days'] == 1 ? '' : 's'; 
			$t_since = $t_since['days'].' day'.$plur;
		
		} else if($t_since['h'] > 0) {
			$plur = $t_since['h'] == 1 ? '' : 's';
			$t_since = $t_since['h'].' hour'.$plur;
		
		} else if($t_since['i'] > 0) {
			$plur = $t_since['i'] == 1 ? '' : 's';
			$t_since = $t_since['i'].' minute'.$plur;
		
		} else if($t_since['s'] > 0) {
			$t_since = $t_since['s'] . ' seconds';
		}
		
		return $t_since;	
	} 

	public function datetimeFormat($date,$format){
		
		/*
			$date   : date to be converted
			$format : F j, Y = "Month date, Year" / Y-m-d H:i:s = "YYYY-mm-dd hh:mm:ss" see others at http://www.w3schools.com/php/func_date_date_format.asp
		*/
		
		$date = date_create($date);
		return date_format($date,$format);
	} 

	public function datetime_compare($a,$b) {
		
		// Custom compare function for sorting array of datetimes using usort() function
		
	    return strtotime($a) - strtotime($b); 
	}

	public function toURL($url) {

	   $url = preg_replace('~[^\\pL0-9_]+~u', '-', $url);
	   $url = trim($url, "-");
	   $url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
	   $url = strtolower($url);
	   $url = preg_replace('~[^-a-z0-9_]+~', '', $url);
	   return $url;
	}

	public function pathRegex($path){

		$end = substr($path, -1) === '/' && strlen($path) > 1 ? '?$' : '$';

		$path = str_replace('/', '\/', $path);
		$path = '^'.$path.$end;

		return $path;
	}
	
	/* File */
	public function file_save($dir = null, $filename = null, $max_file_size = 0, $allowed_types = [], $permissions = null, $dry_run = false){

		// Move uploaded file from $_FILES array to specified directory while setting filename, performing checks for MIME type, filesize, & set permissions
		//  ** Can handle multiple files from single or multiple input elements in any combination
		// @dir : relative path to directory where file is to be saved
		// @filename (optional) : desired filename WITHOUT extension. If none is provided, original filename will be used. "md5" flag can be set to generate a filename using md5 hash of the original   
		// @max_file_size (optional) : maximum allowable file size (bytes)
		// @allowed_types (optional) : an array containing the valid file type extension for this upload
		// @dry_run (optional) : do not move/save file and return array for each with filename and temporary locations 
	
		$files = [];
		$response = [ 'success' => [], 'errors'=> [] ];

		if(!$dir && !$dry_run) return ['success' => [], 'errors' => ['must provide @dir arguement']];

		if(isset($_FILES) && sizeof($_FILES) > 0) {

			// refactor files array to collect data from possible multiple elements
			foreach($_FILES as $name => $el){
				foreach($el as $k => $data){
					if(!is_array($data)) $files[$name][0][$k] = $data; // single file element	
					else { 	// multiple file element
						foreach($data as $i => $val){
							$files[$name][$i][$k] = $val;
						}
					}
				}
			}

			// validate data and save file
			foreach($files as $el_name => $el) {
				foreach($el as $file) {

					// skip files with upload errors
					if($file['error'] !== 0) {	
						$response['errors'][] = [ 'element_name'=>$el_name, 'file_name'=>$file['name'], 'error'=>'upload error' ];
						continue; 
					}

					// check valid file type
					$fileType = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
					if(sizeof($allowed_types) > 0 && !in_array($fileType, $allowed_types)){
						$response['errors'][] = [ 'element_name'=>$el_name, 'file_name'=>$file['name'], 'error'=>'invalid file type' ];
						continue;
					}

					// check file size
					if($max_file_size > 0 && $file['size'] > $max_file_size){
						$response['errors'][] = [ 'element_name'=>$el_name, 'file_name'=>$file['name'], 'error'=>'file too large' ];
						continue;
					}

					// determine file name
					if($filename) { 

						$name = $filename === 'md5' ? md5($file['name']).'.'.$fileType : $filename.'.'.$fileType;

					} else $name = $file['name'];

					if(!$dry_run){ // move file to location & set permissions
					
						if(move_uploaded_file($file['tmp_name'], $dir.$name)) {

							chmod($dir.$name, $permissions === null ? 0666 : $permissions ); 
							$response['success'][] = [ 'element_name'=>$el_name, 'location'=>$dir.$name ];
					
						} else $response['errors'][] = [ 'element_name'=>$el_name, 'file_name'=>$name, 'error'=>'move file error' ];

					} else if($dry_run){ // do not move file and return tmp file info

						$response['success'][] = [ 'location'=>$file['tmp_name'],'element_name'=>$el_name, 'file_name'=>$name, 'mime_type'=>$fileType ];
					} 
				}
			}
		}

		return $response;
	} 

	public function emptyDir($path_to_dir){
		// delete all files in directory reference by @path_to_dir
		array_map('unlink', array_filter((array) glob($path_to_dir."*")));
	}

}
	
?>