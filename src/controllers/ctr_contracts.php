<?php

require_once '../src/clases/contracts.php';

class ctr_contracts{

	public function getContractToShow($idContract){
		$response = new \stdClass();

		$responseGetContract = contracts::getContractToShow($idContract);
		if($responseGetContract->result == 2){
			$response->result = 2;
			$response->contract = $responseGetContract->objectResult;
		}else return $responseGetContract;

		return $response;
	}

	public function changeNotificationStatus($idContract, $typeNotification){
		$response = new \stdClass();

		$responseGetContract = contracts::getContractWithID($idContract);
		if($responseGetContract->result == 2){
			$newStatus = 0;
			if(strcmp($typeNotification, "EMAIL") == 0){
				if($responseGetContract->objectResult->enviarEmail == 0){
					if(is_null($responseGetContract->objectResult->email)){
						$response->result = 0;
						$response->message = "Debe ingresar un email para activar las notificaciones por correo.";
						return $response;
					}
					$newStatus = 1;
				}
			}else if(strcmp($typeNotification, "MOBILE") == 0){
				if($responseGetContract->objectResult->enviarCelular == 0){
					if(is_null($responseGetContract->objectResult->celularEnvio)){
						$response->result = 0;
						$response->message = "Debe ingresar un celular de envio para activar las notificaciones por WhatsApp.";
						return $response;
					}
					$newStatus = 1;
				}
			}

			$responseUpdateStatus = null;
			if(strcmp($typeNotification, "EMAIL") == 0)
				$responseUpdateStatus = contracts::changeNotificationStatusEmail($idContract, $newStatus, $email);
			else if(strcmp($typeNotification, "MOBILE") == 0)
				$responseUpdateStatus = contracts::changeNotificationStatusMobile($idContract, $newStatus, $mobile);

			if(!is_null($responseUpdateStatus)){
				if($responseUpdateStatus->result == 2){
					$notification = "correo";
					if(strcmp($typeNotification, "MOBILE") == 0)
						$notification = "WhatsApp";

					$response->result = 2;
					if($newStatus == 1)
						$response->message = "Se activó el envio de notificaciones por ". $notification .".";
					else
						$response->message = "Se desactivo el envio de notificaciones por ". $notification .".";
				}else{
					$response->result = 0;
					$response->message = "Ocurrió un error y no se pudo cambiar el estado de la notificación por ". $notification ." en la base de datos.";
				}
			}else{
				$response->result = 0;
				$response->message = "No se detecto la notificacion que desea modificar.";
			}
		}else return $responseGetContract;

		return $response;
	}

	public function loadFileToSend($nameFile, $typeFile, $dataFile){
		$response = new \stdClass();

		ctr_contracts::clearFolder();

		$folderPath = dirname(dirname(__DIR__)) . "/public/pdfs/";
		$zip_Array = explode(";base64,", $dataFile);
		$zip_contents = base64_decode($zip_Array[1]);
		$file = $folderPath . $nameFile;
		file_put_contents($file, $zip_contents);

		if(strstr($nameFile, "detalle_facturas") != FALSE){
			$zip = new ZipArchive();
			$descompressFile = $zip->open($file);
			if($descompressFile == TRUE){
				$zip->extractTo($folderPath);
				$zip->deleteName($folderPath . $nameFile);
				$zip->close();
				$zip = new ZipArchive;
				$descompressFile = $zip->open($folderPath . "Facturas_Movil.zip");
				if($descompressFile == TRUE){
					$zip->extractTo($folderPath . "/movil/");
					$zip->deleteName($folderPath . "Facturas_Movil.zip");
					$zip->close();
				}else{
					$response->result = 1;
				}
			}
		}else if(strcmp($nameFile, "Facturas_Movil.zip") == 0){
			$zip = new ZipArchive;
			$descompressFile = $zip->open($file);
			if ($descompressFile === TRUE){
				$zip->extractTo($folderPath . "/movil/");
				$zip->close();
				$zip->deleteName($folderPath . "Facturas_Movil.zip");
				$zip->close();

			}
		}

		$listDir = array_diff(scandir($folderPath . "/movil/"), array('..', '.'));
		if(sizeof($listDir) > 0){
			$arrayErrors = array();
			$arrayContractNotEntered = array();
			foreach ($listDir as $key => $value) {
				$arrayName = explode("_", $value);
				$numberContract = explode("." ,$arrayName[sizeof($arrayName) - 1])[0];
				$responseGetContract = contracts::getContractWithNumber($numberContract);
				if($responseGetContract->result == 2){
					$currentFolder = $folderPath . "/movil/";
					if($responseGetContract->objectResult->enviarEmail == 1){
						if(!is_null($responseGetContract->objectResult->email)){
							$resultSendEmail = contracts::sendMail($currentFolder, $value, $numberContract);
							if(!$resultSendEmail)
								$arrayErrors[] = $responseGetContract->objectResult->usuario;
						}
					}

					if($responseGetContract->objectResult->enviarCelular == 1){
						if(!is_null($responseGetContract->objectResult->celularEnvio)){
							$responseSent = json_decode(ctr_contracts::sendWhatsApp(base64_encode(file_get_contents($currentFolder . $value)), $value, $responseGetContract->objectResult->celularEnvio));
							if($responseSent->sent == FALSE)
								$arrayErrors[] = $responseGetContract->objectResult->usuario;
						}
					}
				}else{
					contracts::createNewContract(null, null, null, $numberContract, null, null);
					$arrayContractNotEntered[] = $numberContract;
				}
			}

			if(sizeof($arrayErrors) == 0){
				$response->result = 2;
				$resultNewContracts = "";
				if(sizeof($arrayContractNotEntered) != 0)
					$resultNewContracts = " Se insertaron " . sizeof($arrayContractNotEntered) .  " contratos nuevos en la base de datos.";
				$response->message = "Todos los contratos contratos con notificaciones activas fueron enviados correctamente." . $resultNewContracts;
			}else{
				$response->result = 1;
				$response->message = "Algunas facturas no fueron enviadas a sus respectivos clientes.";
			}
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y los archivos no se pueden leer.";
		}

		return $response;
	}

	public function clearFolder(){
		$path = dirname(dirname(__DIR__)) . "/public/pdfs/";
		$folders = glob($path . '/*');
		foreach($folders AS $file){
			if(is_dir($file)){
				$movilFolders = glob($path . '/movil/*');
				foreach ($movilFolders AS $subFile){
					unlink($subFile);
				}
				rmdir($file);
			}else unlink($file);
		}
	}


	function sendWhatsApp($dataFile, $nameFile, $mobilePhone) {
		$url = 'https://api.chat-api.com/instance312895/sendFile?token=45ek2wrhgr3rg33m';

		$json = '{
			"body": "data:application/pdf;base64,' . $dataFile . '",
			"filename": "' . $nameFile . '",
			"phone": 598'. $mobilePhone . '
		}';

		$opciones = array('http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/json',
				'content' => $json
			)
		);

		$context = stream_context_create($opciones);
		$result = file_get_contents($url, false, $context);
		return $result;
	}

	public function updateContract($idContract, $name, $email, $mobile, $contract, $group, $mobileToSend){
		$response = new \stdClass();

		$responseGetContract = contracts::getContractWithID($idContract);
		if($responseGetContract->result == 2){
			if(strlen($email) > 1){
				if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
					$response->result = 1;
					$response->message = "La dirección de correo ingresada no es valida.";
					return $response;
				}
			}else $email = null;

			if(strlen($mobileToSend) < 1)
				$mobileToSend = null;
			$responseUpdateContract = contracts::updateContract($idContract, $name, $email, $mobile, $contract, $group, $mobileToSend);
			if($responseUpdateContract->result == 2){
				$response->result = 2;
				$response->message = "El contracto fue modificado correctamente.";
				$responseGetUpdatedContract = ctr_contracts::getContractToShow($responseGetContract->objectResult->id);
				if($responseGetUpdatedContract->result == 2)
					$response->contract = $responseGetUpdatedContract->contract;
			}else return $responseUpdateContract;
		}else return $responseGetContract;

		return $response;
	}

	public function validateContractDontRepeat($idContract, $contract){
		$response = new \stdClass();

		$responseGetContract = contracts::validateContractDontRepeat($idContract, $contract);
		if($responseGetContract->result == 1){
			$response->result = 2;
		}else if($responseGetContract->result == 2){
			$response->result = 0;
			$response->message = "El número de contrato ingresado corresponde al usuario ". $responseGetContract->objectResult->usuario ." registrado en la base de datos.";
		}else return $responseGetContract;

		return $response;
	}

	public function getContractWithID($idContract){
		$response = new \stdClass();

		$responseGetContract = contracts::getContractWithID($idContract);
		if($responseGetContract->result == 2){
			$response->result = 2;
			$response->contract = $responseGetContract->objectResult;
		}else{
			$response->result = 0;
			$response->message = "EL contrato seleccionado no fue encontrado en la base de datos.";
		}

		return $response;
	}

	public function createNewContract($name, $email, $mobile, $contract, $group, $mobileToSend){
		$response = new \stdClass();

		if(strlen($email) > 0){
			if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
				$response->result = 1;
				$response->message = "La dirección de correo ingresada no es valida.";
				return $response;
			}
		}else $email = null;

		if(strlen($mobileToSend) < 1)
			$mobileToSend = null;

		$responseGetContract = contracts::getContractWithNumber($contract);
		if($responseGetContract->result == 1){
			$responseInsertContract = contracts::createNewContract($name, $email, $mobile, $contract, $group, $mobileToSend);
			if($responseInsertContract->result == 2){
				$response->result = 2;
				$response->message = "El contrato fue creado correctamente.";
				$responseGetUpdatedContract = ctr_contracts::getContractToShow($responseInsertContract->id);
				if($responseGetUpdatedContract->result == 2)
					$response->contract = $responseGetUpdatedContract->contract;
			}else{
				$response->result = 0;
				$response->message = "Ocurrió un error y el contrato ingresado no fue cargado en la base de datos.";
			}
		}else{
			$response->result = 0;
			$response->message = "El contrato que intenta ingresar ya fue cargado en la base de datos.";
		}

		return $response;
	}

	public function validateContractDoesntExist($contract){
		$response = new \stdClass();

		$responseGetContract = contracts::getContractWithNumber($contract);
		if($responseGetContract->result == 2){
			$response->result = 0;
			$response->message = "El número de contrato que intenta ingresar ya fue cargado en la base de datos anteriormente.";
		}else if($responseGetContract->result == 1){
			$response->result = 2;
		}else return $responseGetContract;

		return $response;
	}

	public function getListContracts($lastId, $textToSearch){
		$response = new \stdClass();

		$responseGetContracts = contracts::getListContracts($lastId, $textToSearch);
		if($responseGetContracts->result == 2){
			$response->result = 2;
			$response->listResult = $responseGetContracts->listResult;
			$response->lastId = $responseGetContracts->lastId;
		}else return $responseGetContracts;
		return $response;
	}
}