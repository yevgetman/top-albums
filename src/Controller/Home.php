<?php 

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class Home extends BaseController {

    public function index() {

    	$data = [ 'test' => 'hello home' ];

        $res = $this->renderView('home.html.twig', $data);

        return new Response($res);
    }
}
 
?>