<?php

use Slim\App;
use Slim\Http\Response;

return function (App $app) {

    $routesU = require_once __DIR__ . "/../src/routes_users.php";
    $routesC = require_once __DIR__ . "/../src/routes_contracts.php";

    $container = $app->getContainer();

    $routesU($app);
    $routesC($app);

    $app->get('/', function ($request, $response, $args) use ($container) {
        if(isset($_SESSION['ADMIN'])){
            $responseFunction = ctr_users::validateCurrentSession();
            if($responseFunction->result == 2){
                $args['session'] = $_SESSION['ADMIN'];
                return $this->view->render($response, "index.twig", $args);
            }
        }
        return $response->withRedirect('iniciar-sesion');
    })->setName("Start");
};
