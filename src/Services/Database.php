<?php

namespace App\Services;

class Database {

	private $conn;

	public function __construct($host, $name, $user, $pass){

		$this->conn = mysqli_connect($host,$user,$pass,$name) or die('error: cannot connect');
		$this->conn->query("SET NAMES 'utf8'");
	}

	public function bind($param,$stmt){

		$bind = [""];
		$type = "";

		foreach($param as $k => $val){

			switch(gettype($val)){

				case 'boolean': $bind[0] .= 'b'; break;
				case 'integer':	$bind[0] .= 'i'; break;
				case 'double':	$bind[0] .= 'd'; break;
				case 'string':	$bind[0] .= 's'; break;
				default: 		$bind[0] .= 's'; break;
			}

			$bind[] = &$param[$k];
		}

		call_user_func_array( array($stmt, 'bind_param'),  $bind );
	}

	public function fetch($stmt){    

		$data = [];
		$j = 0;
		
		$stmt->store_result();
	    $meta = $stmt->result_metadata();
	    
	    while($field = $meta->fetch_field()){
	        $var[] = &$d[$field->name]; 
	    }
	    
	    call_user_func_array(array($stmt, 'bind_result'), $var);
	    
	    while($stmt->fetch()){
	        $data[$j] = [];
	        foreach($d as $k=>$v) $data[$j][$k] = $v;
	        $j++;
	    }

		return $data;
	} 

	public function select($sql,$param = []){

		$stmt = $this->conn->prepare($sql);
		if(!$stmt) return($this->conn->error);

		if(count($param) > 0) $this->bind($param,$stmt);

		$stmt->execute();

		$data = $this->fetch($stmt); 

		$stmt->close();
		
		return $data;
	} 

	public function execute($sql,$param = []){

		$stmt = $this->conn->prepare($sql);

		if(!$stmt) return $stmt->error;

		if(count($param) > 0) $this->bind($param,$stmt);

		$stmt->execute();
		$stmt->close();
	
		$res = mysqli_insert_id($this->conn); 

		// $res will be numerical row id if SQL query was an insert, or respective output from any other mysql function 
		// return the row id for insert or 'true' indicating success

		return $res > 0 ? $res : true;
	} 

}

?>