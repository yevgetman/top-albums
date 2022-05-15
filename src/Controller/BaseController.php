<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use App\Services\Utilities;
use App\Services\Database;
use App\Services\Request;

class BaseController extends Controller {

	protected $req, $db, $u;

	public function __construct(Request $req, Database $db, Utilities $u) { 

		$this->req = $req;
		$this->db = $db;
		$this->u = $u;
	}
  
}
 
?>