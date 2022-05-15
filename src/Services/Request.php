<?php

namespace App\Services;

class Request {

	public 	$uri, $IP, $HEADERS, $PROTOCOL, $DOMAIN, $METHOD;

	public function __construct(){

		$this->PROTOCOL = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
		$this->data = json_decode(file_get_contents('php://input'),true);
		$this->METHOD = $_SERVER['REQUEST_METHOD'];
		$this->DOMAIN = $_SERVER['HTTP_HOST'];
		$this->IP = $_SERVER['REMOTE_ADDR'];
		$this->HEADERS = getallheaders();
		$this->uri = $this->parseURI();
	}

	private function parseURI(){

		// URL pattern: //[HOST]/[module]/[slug_0]/[...]/[slug_n]?[key_0=val_0]&[...]&[key_n=val_n] 

		$parsedURI = [ 'module' => '', 'slug' => [], 'keys' => [] ];

		$uri_arr = explode('?',$_SERVER['REQUEST_URI']); 
		$URI = isset($uri_arr[0]) ? $uri_arr[0] : null; 	// URI 
		$KEYS = isset($uri_arr[1]) ? $uri_arr[1] : null; 	// Key values

		$j=0; 
		foreach(explode('/',$URI) as $slug){ // Parse URI for module and slugs		 
			if(empty($slug)) continue;
			if($j > 0) $parsedURI['slug'][] = $slug;
			else $parsedURI['module'] = $slug; 
			$j++;
		}
		
		if(!empty($KEYS)){ // Get key values
			$KEYS = explode('&',$KEYS); 
			foreach($KEYS as $val){
				$get = explode('=',$val); 
				$parsedURI['keys'][] = [ $get[0],$get[1] ];
			}
		}

		return $parsedURI;
	}

}

?>