<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

require_once 'controllers/ctr_contracts.php';
require_once 'controllers/ctr_users.php';


return function (App $app){
	$container = $app->getContainer();

	$app->get('/ver-contratos', function($request, $response, $args) use ($container){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$args['session'] = $_SESSION['ADMIN'];
			$args['responseGroups'] = ctr_contracts::getGroupsInformation();
			return $this->view->render($response, "contracts.twig", $args);
		}
		return $response->withRedirect('iniciar-sesion');
	})->setName("Contracts");

	$app->post('/generateExcel', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$responseFunction = ctr_contracts::exportExcelContract();
		}
		return json_encode($responseFunction);
	});

	$app->post('/getListContracts', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$lastId = $data['lastId'];
			$textToSearch = $data['textToSearch'];
			$group = $data['group'];
			$checkedActive = $data['checkedActive'];
			$responseFunction = ctr_contracts::getListContracts($lastId, $textToSearch, $group, $checkedActive);
		}
		return json_encode($responseFunction);
	});

	$app->post('/validateContractDoesntExist', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$contract = $data['contract'];
			$responseFunction = ctr_contracts::validateContractDoesntExist($contract);
		}
		return json_encode($responseFunction);
	});

	$app->post('/createNewContract', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$name = $data['name'];
			$email = $data['email'];
			$mobile = $data['mobile'];
			$contract = $data['contract'];
			$group = $data['group'];
			$mobileToSend = $data['mobileToSend'];

			$responseFunction = ctr_contracts::createNewContract($name, $email, $mobile, $contract, $group, $mobileToSend);
		}
		return json_encode($responseFunction);
	});

	$app->post('/getContractWithID', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$idContract = $data['idContract'];
			$responseFunction = ctr_contracts::getContractWithID($idContract);
		}
		return json_encode($responseFunction);
	});

	$app->post('/validateContractDontRepeat', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$idContract = $data['idContract'];
			$contract = $data['contract'];
			$responseFunction = ctr_contracts::validateContractDontRepeat($idContract, $contract);
		}
		return json_encode($responseFunction);
	});

	$app->post('/updateContract', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$idContract = $data['idContract'];
			$name = $data['name'];
			$email = $data['email'];
			$mobile = $data['mobile'];
			$contract = $data['contract'];
			$group = $data['group'];
			$mobileToSend = $data['mobileToSend'];
			$responseFunction = ctr_contracts::updateContract($idContract, $name, $email, $mobile, $contract, $group, $mobileToSend);
		}
		return json_encode($responseFunction);
	});

	$app->post('/deleteContractSelected', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$idContract = $data['idContract'];
			$responseFunction = ctr_contracts::deleteContractSelected($idContract);
		}
		return json_encode($responseFunction);
	});

	$app->post('/loadFileToSend', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$nameFile = $data['nameFile'];
			$typeFile = $data['typeFile'];
			$dataFile = $data['data'];
			$responseFunction = ctr_contracts::loadFileToSend($nameFile, $typeFile, $dataFile);
		}
		return json_encode($responseFunction);
	});

	$app->post('/notifyAllContract', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$responseFunction = ctr_contracts::notifyAllContract();
		}
		return json_encode($responseFunction);
	});

	$app->post('/notifyOneContract', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$idContract = $data['idContract'];
			$responseFunction = ctr_contracts::notifyOneContract($idContract);
		}
		return json_encode($responseFunction);
	});

	$app->post('/changeNotificationStatus', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$data = $request->getParams();
			$idContract = $data['idContract'];
			$typeNotification = $data['typeNotification'];
			$responseFunction = ctr_contracts::changeNotificationStatus($idContract, $typeNotification);
		}
		return json_encode($responseFunction);
	});

	$app->post('/clearFolderPDFs', function(Request $request, Response $response){
		$responseFunction = ctr_users::validateCurrentSession();
		if($responseFunction->result == 2){
			$responseFunction = ctr_contracts::clearFolder();
		}
		return json_encode($responseFunction);
	});
}

?>