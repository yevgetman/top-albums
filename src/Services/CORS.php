<?php

namespace App\Services;

class CORS {

	private $default_policy = [
		'Access-Control-Allow-Origin' => '*',
		'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE',
		'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept, Auth, Client'
	];

	public function set_headers(&$response, $cors_policy = []){

		// @response (Object) (required) - instance of Symfony repsonse object (Symfony\Component\HttpFoundation\Response)
		// @cors_policy (array) (optional) - an optional object of key-values describing a custom CORS policy. If missing $this.default_policy will be used

		if(count($cors_policy) > 0){
			foreach ($cors_policy as $k => $val) {
				$response->headers->set($k, $val);
			}
		} else {
			$response->headers->set('Access-Control-Allow-Origin', $this->default_policy['Access-Control-Allow-Origin']);
			$response->headers->set('Access-Control-Allow-Methods', $this->default_policy['Access-Control-Allow-Methods']);
			$response->headers->set('Access-Control-Allow-Headers', $this->default_policy['Access-Control-Allow-Headers']);
		}
	}
}
?>