<?php

namespace App\Services;

class Curl {

	public function __construct(){}

	public function send($request_type, $url, $headers = [], $body = false, $return = true){

		// $url (string) - url where request will be sent
		// $headers (array) - array of headers as per cURL specification
		// $body (string) - string representation of request body
		// $request_type - GET, POST, PUT etc. 

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
		if(count($headers) > 0) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		if($body !== false) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		$result = curl_exec($ch);

		if(curl_errno($ch)) {

		    return [ 'error' => true, 'error_msg' => curl_error($ch) ];
		} 

		curl_close($ch);

		return $result;
	}

}

?>