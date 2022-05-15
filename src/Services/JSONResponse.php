<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;
use App\Services\CORS;

class JSONResponse {

	public $res = [
		'response' => [ 'status' => 0, 'message' => '', 'error' => [] ],
		'data' => []
	];

	private $cors;

	public function __construct(CORS $cors) {
		$this->cors = $cors;
	}

	public function response($data = []){

		if(count($this->res['response']['error']) > 0){
			$this->res['response']['status'] = -1;
			if($this->res['response']['message'] === '') {
				$this->res['response']['message'] = 'error'; // default error message
			}
		} else {
			$this->res['data'] = $data;
			if($this->res['response']['message'] === '') {
				$this->res['response']['message'] = $this->res['response']['status'] === 1 ? 'success' : 'null response';
			}
		}

		$response = new Response(json_encode($this->res, JSON_PARTIAL_OUTPUT_ON_ERROR));
		$response->headers->set('Content-Type', 'application/json');
        $this->cors->set_headers($response);
        
        return $response;
	}
}
?>