<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'controllers/ctr_users.php';

return function (App $app){
	$container = $app->getContainer();

	$app->get('/iniciar-sesion', function ($request, $response, $args) use ($container) {
		return $this->view->render($response, "login.twig", $args);
	})->setName("Login");

	$app->get('/cerrar-session', function($request, $response, $args) use($container){
		$responseFunction = ctr_users::signOut();
		if($responseFunction->result == 2)
			return $response->withRedirect('iniciar-sesion');
		else
			return $response->withRedirect('/');
	})->setName('LogOut');

	$app->post('/login', function(Request $request, Response $response){
		$data = $request->getParams();
		$nickName = $data['nickName'];
		$password = $data['password'];
		$responseFunction = ctr_users::login($nickName, sha1($password));
		return json_encode($responseFunction);
	});


}

?>