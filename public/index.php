<?php
if (PHP_SAPI == 'cli-server') {
	$url  = parse_url($_SERVER['REQUEST_URI']);
	$file = __DIR__ . $url['path'];
	if (is_file($file)) {
		return false;
	}
}

if (file_exists('/home/byguyqrd/utils/vendor/autoload.php') ){
	require '/home/byguyqrd/utils/vendor/autoload.php';
}else{
	require 'C:/xampp/htdocs/utils/vendor/autoload.php';
}

session_start();

$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

$dependencies = require __DIR__ . '/../src/dependencies.php';
$dependencies($app);

$middleware = require __DIR__ . '/../src/middleware.php';
$middleware($app);

$container = $app->getContainer();

$container['view']= function($container){
	$view = new \Slim\Views\Twig('../templates',[
		'cache'=> false
	]);

	$view->addExtension(new \Slim\Views\TwigExtension(
		$container->router,
		$container->request->getUri()
	));
	$view->addExtension(new \Twig_Extension_Debug());
	return $view;

};

$routes = require_once __DIR__ . '/../src/routes.php';

$routes($app);

$app->run();
