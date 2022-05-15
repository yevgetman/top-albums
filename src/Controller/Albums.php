<?php

namespace App\Controller;

use App\Services\JSONResponse;
use App\Services\Utilities;
use App\Services\Curl;
use App\Services\Hash;

class Albums extends BaseController {

	protected $req, $db;
	private $endpoint = 'https://itunes.apple.com/us/rss/topalbums/limit=100/json',
			$json, $curl;

	public function __construct(BaseController $Base, Curl $curl, JSONResponse $json, Utilities $u){
		
		$this->req = $Base->req;
		$this->db = $Base->db;
		$this->json = $json;
		$this->curl = $curl;
		$this->u = $u;
	}

    public function fetch(){

       	/* 
			Request pattern: 	
			[PROTO]://[DOMAIN]/get/<from_table>/<field_0>/ ... /<field_n>/?whereKey_0=whereVal_0& ... &whereKey_n=whereVal_n&operator=[and/or]
			* omitting field slugs will fetch all fields
		*/

    	$req = $this->req->uri;
    	$query = $this->u->compileQuery($req['slug'],$req['keys']);

    	$fields = $query['columns'];
    	$conditions = $query['conditions'];

    	$sql = 'SELECT ' . $fields . ' FROM albums ' . $conditions['sql'];

    	$albums = $this->db->select($sql,$conditions['val']);

    	if(!is_array($albums)){ // SQL error
    	
    		$this->json->res['response']['error'][] = $albums;
    	
    	} else if(count($albums) === 0){ // null result

    		$this->json->res['response']['message'] = 'your query returned no results.';

    	} else { // query matched records

	    	// since we're storing values in database as JSON, we need to decode them 
	    	for($i=0; $i < count($albums); $i++){
	    		foreach ($albums[$i] as $key => $value) {
	    			$albums[$i][$key] = $value;
	    		}
	    	}

	    	$this->json->res['response']['status'] = 1;
    	} 

    	return $this->json->response($albums);
    }

    public function add(){

    	if(!$this->validate_key()){
    		$this->json->res['response']['error'][] = 'unauthorized';
    		return $this->json->response();
    	}

    	// album name & artist are minimally required
	    $null = $this->u->check_null([
	      'name' => isset($_POST['name']) ? $_POST['name'] : '',
	      'artist' => isset($_POST['artist']) ? $_POST['artist'] : '',
	    ]);

	    if(count($null) > 0) foreach($null as $k=>$n) $this->json->res['response']['error'][] = $n . ' is required';
	    else {

	    	// check for invalid POST parameters and show warning messages
	    	foreach ($_POST as $key => $val) {
	    		if(!in_array($key,['name','artist','image','price','rights','link','category','releaseDate'])){
	    			$this->json->res['response']['message'] .= "invalid property '" . $key . "' will be ignored. ";
	    		}
	    	}

	    	// collect and sanitize the album data
	    	$album_data = [
			/*
				Note: $this->u->cleanforSQL() function removes script tags and performs some basic sanitizing of POST data. Additional sanitizing is mediated using SQL prepared statements. Never trust user input!
			*/
				'name' => $this->u->cleanforSQL($_POST['name']),
				'artist' => $this->u->cleanforSQL($_POST['artist']),
				'image' => isset($_POST['image']) ? $this->u->cleanforSQL($_POST['image']) : '',
				'price' =>  isset($_POST['price']) ? $this->u->cleanforSQL($_POST['price']) : '',
				'rights' => isset($_POST['rights']) ? $this->u->cleanforSQL($_POST['rights']) : '',
				'link' => isset($_POST['link']) ? $this->u->cleanforSQL($_POST['link']) : '',
				'category' => isset($_POST['category']) ? $this->u->cleanforSQL($_POST['category']) : '',
				'releaseDate' => isset($_POST['releaseDate']) ? $this->u->cleanforSQL($_POST['releaseDate']) : '',
			];
			
    		// create an unique album_id by encoding the name + artist as a base64 string - we can use this to check the id below and prevent saving duplicate entries
			$album_data['album_id'] = base64_encode($album_data['name'] . $album_data['artist']);

	    	if(count($this->db->select('SELECT * FROM albums WHERE album_id=?',[ $album_data['album_id'] ])) > 0){

	    		// album already exists
	    		$this->json->res['response']['error'][] = 'this album has already been added';
	    		
	    	} else {

				// prepare the SQL statement and data
				$to_insert = $this->u->compileInsert($album_data, 'albums');

				// run the query
				$result = $this->db->execute(
					$to_insert['sql'], // the SQL statement ("INSERT INTO... ")
					$to_insert['val']  // the data values array
				);

				if(!$result) {
					$this->json->res['response']['error'][] = 'error adding album with id #' . $album_data['album_id'];
				} else {

					$this->json->res['response']['status'] = 1;
	    			$this->json->res['response']['message'] .= "album '" . $album_data['name'] . "' by artist '" . $_POST['artist'] . "' has been saved successfully!";
				}
			}
	    }

    	return $this->json->response();
    }

    public function update($album_id){

    	if(!$this->validate_key()){
    		$this->json->res['response']['error'][] = 'unauthorized';
    		return $this->json->response();
    	}

    	if($album_id === null){
    		$this->json->res['response']['error'][] = 'album id is required';
    		return $this->json->response();
    	}

    	$record_to_update = $this->db->select('SELECT id FROM albums WHERE album_id=?',[ $album_id ]);
    	if(count($record_to_update) === 0){

    		// album id does not match any records

    		$this->json->res['response']['error'][] = 'album record with id ' . $album_id . ' not found.';
    		return $this->json->response();    		
    	}

    	$properties_to_update = [];

    	// check for invalid POST parameters and show warning messages
    	foreach ($_POST as $key => $value) {
    		if(!in_array($key,['name','artist','image','price','rights','link','category','releaseDate'])){
    			$this->json->res['response']['message'] .= "invalid property '" . $key . "' will be ignored. ";
    		} else {
    			// collect all valid properties to be updated 
    			$properties_to_update[$key] = $value;
    		}
    	}

    	if(count($properties_to_update) === 0){

    		// record with album id found but no valid properties to update were found in the request body

    		$this->json->res['response']['message'] = "no properties to update. record not updated.";

    	} else {

    		// update the record..

			$to_update = $this->u->compileUpdate(
				$properties_to_update, 
				[ 'key' => 'id', 'val' => $record_to_update[0]['id'] ], 
				'albums'
			);

			$result = $this->db->execute(
				$to_update['sql'], // the SQL statement ("UPDATE albums... ")
				$to_update['val']  // the data values array
			);

			if(!$result) {
				$this->json->res['response']['error'][] = 'error updating album with id #' . $album_id;
			} else {
				$this->json->res['response']['status'] = 1;
			   	$this->json->res['response']['message'] .= "album record has been successfully updated.";
			}
		}

		return $this->json->response();
    }

    public function delete($album_id){

    	if(!$this->validate_key()){
    		$this->json->res['response']['error'][] = 'unauthorized';
    		return $this->json->response();
    	}

    	// delete all album records if no album_id provided
    	if($album_id === null){
    		if($this->db->execute('DELETE FROM albums')){
				$this->json->res['response']['status'] = 1;
	    		$this->json->res['response']['message'] = "all album records have been successfully deleted.";
	    		return $this->json->response();
    		}

    	} else if(!empty($album_id)){ // using empty() here to exclude falsey values of album_id

    		// delete a particular record by album_id

	    	$record_to_delete = $this->db->select('SELECT id FROM albums WHERE album_id=?',[ $album_id ]);
			if(count($record_to_delete) === 0){

				// record with album_id not found

				$this->json->res['response']['error'][] = 'album with id ' . $album_id . ' not found.';

			} else {

				// a record with matching album_id was found

				$id = $record_to_delete[0]['id']; // the row id of the matched album record

				if($this->db->execute('DELETE FROM albums WHERE id=?',[ $id ])){

					$this->json->res['response']['status'] = 1;
		    		$this->json->res['response']['message'] = "album record has been successfully deleted.";
				}
			}
		}
    	
    	return $this->json->response();
    }

    public function refresh(){

    	if(!$this->validate_key()){
    		$this->json->res['response']['error'][] = 'unauthorized';
    		return $this->json->response();
    	}

    	// make a CURL request to grab album data
    	$response = $this->curl->send('GET', $this->endpoint);

    	// CURL request failed
    	if(isset($response['error']) && $response['error'] === true){

    		$this->json->res['response']['error'][] = 'error retrieving albums: request failed'; 
    		return $this->json->response();

    	} else { // CURL request successful

    		$data = json_decode($response,true);
    		if(!isset($data['feed']['entry'])){ // request successful but album data not found

    			$this->json->res['response']['error'][] = 'error retrieving albums: album data not found';

    		} else { // album data present

    			$albums = $data['feed']['entry'];

    			// keep track of number of albums added & updated
    			$albums_added = 0; 
    			$albums_updated = 0; 

    			foreach ($albums as $album) { // loop through the albums

    				// get the (presumably unique) album id
    				$id = !empty($album['id']['attributes']['im:id']) ? $album['id']['attributes']['im:id'] : null;

    				if($id){

    					$album_data = [

							'album_id' => $id,

							'name' => isset($album['im:name']['label']) ? $album['im:name']['label'] : '',
							
							// image data is arranged as an object with several version of the same image in increasing size (thumbnail, medium, full-size) - here were are saving the largest of the images; the last index
							'image' => isset($album['im:image']) ? $album['im:image'][count($album['im:image'])-1]['label'] : '',

							'price' => isset($album['im:price']['label']) ? $album['im:price']['label'] : '',
							'rights' => isset($album['rights']['label']) ? utf8_encode($album['rights']['label']) : '',
							'link' => isset($album['link']['attributes']['href']) ? $album['link']['attributes']['href'] : '',
							'artist' => isset($album['im:artist']['label']) ? $album['im:artist']['label'] : '',
							'category' => isset($album['category']['attributes']['label']) ? $album['category']['attributes']['label'] : '',
							'releaseDate' => isset($album['im:releaseDate']['label']) ? $album['im:releaseDate']['label'] : '',
    					];

    					// check if album with this id already exists in database
    					if(count($this->db->select('SELECT * FROM albums WHERE album_id=?',[ $id ])) === 0){

    						// album does not exist - create a new record

    						// prepare the SQL and values
    						$to_insert = $this->u->compileInsert($album_data, 'albums');

    						// run the query
    						$result = $this->db->execute(
    							$to_insert['sql'], // the SQL statement ("INSERT INTO... ")
    							$to_insert['val']  // the data values array
    						);

    						if(!$result) {
								$this->json->res['response']['error'][] = 'error adding album with id #' . $id;
							} else $albums_added++;

    					} else {

    						// album already exists - update the album record

    						unset($album_data['album_id']); // remove the album id from array (we dont need to update it)

    						$to_update = $this->u->compileUpdate(
    							$album_data, 
    							[ 'key' => 'album_id', 'val' => $id ], 
    							'albums'
    						);

    						$result = $this->db->execute(
    							$to_update['sql'], // the SQL statement ("UPDATE albums... ")
    							$to_update['val']  // the data values array
    						);

    						if(!$result) {
								$this->json->res['response']['error'][] = 'error updating album with id #' . $id;
							} else $albums_updated++;
    					}
    				}
    			}
   
    			if(count($this->json->res['response']['error']) === 0){

    				$this->json->res['response']['status'] = 1;
    				$this->json->res['response']['message'] = ($albums_added > 0 ? $albums_added . ' albums added. ' : '') . ($albums_updated > 0 ? $albums_updated . ' albums updated.' : '');
    			}
    			
    			return $this->json->response();
    		} 

    	}

    	return $this->json->response();
    }

    public function generate_key(){

    	$api_key = [ 'key' => $this->u->rando(16) ];

    	$to_insert = $this->u->compileInsert($api_key, 'api_keys');

		if(!$this->db->execute(
			$to_insert['sql'], // the SQL statement ("INSERT INTO... ")
			$to_insert['val']  // the data values array
		)) {

			$this->json->res['response']['error'][] = 'error creating api key';

		} else {

			$this->json->res['response']['status'] = 1;
			$this->json->res['response']['message'] = 'api key has been created successfully!';
		}

    	return $this->json->response($api_key);
    }

 	private function validate_key(){

		$key = !empty($_GET['api_key']) ? $_GET['api_key'] : null;

		if(!$key) return false;

		return count($this->db->select('SELECT id FROM api_keys WHERE `key`=?',[ $key ])) === 1;
	}

}

?>