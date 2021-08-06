<?php

require_once '../src/clases/contracts.php';
require_once '../src/utils/handle_date_time.php';

class ctr_contracts{

	public function getGroupsContract(){
		$response = new \stdClass();

		$responseGetGroups = contracts::getGroupsContract();
		if($responseGetGroups->result == 2){
			$response->result = 2;
			$response->listGroups = $responseGetGroups->listResult;
		}else return $responseGetGroups;
		return $response;
	}

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
					if(is_null($responseGetContract->objectResult->celularEnvio) && is_null($responseGetContract->objectResult->celular)){
						$response->result = 0;
						$response->message = "Debe contar con el número de celular o celular de envio para activar las notificaciones por WhatsApp.";
						return $response;
					}
					$newStatus = 1;
				}
			}

			$responseUpdateStatus = null;
			if(strcmp($typeNotification, "EMAIL") == 0)
				$responseUpdateStatus = contracts::changeNotificationStatusEmail($idContract, $newStatus);
			else if(strcmp($typeNotification, "MOBILE") == 0)
				$responseUpdateStatus = contracts::changeNotificationStatusMobile($idContract, $newStatus);

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
			$countNew = 0;
			$arrayContractNotEntered = array();
			foreach ($listDir as $key => $value) {
				$arrayName = explode("_", $value);
				$numberContract = explode("." ,$arrayName[sizeof($arrayName) - 1])[0];
				$responseGetContract = contracts::getContractWithNumber($numberContract);
				if($responseGetContract->result == 1){
					$responseInsert = contracts::createNewContract(null, null, null, $numberContract, null, null);
					if($responseInsert->result == 2)
						$countNew++;
				}
			}

			$response->result = 2;
			if($countNew > 0)
				$response->message = "Se descomprimió el archivo y se encontraron " . $countNew . " contratos nuevos";
			else
				$response->message = "Se descomprimió el archivo y los contratos estan preparados para ser enviados.";
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y los archivos no se pueden leer.";
		}

		return $response;
	}

	public function notifyOneContract($idContract){
		$response = new \stdClass();

		$folderPath = dirname(dirname(__DIR__)) . "/public/pdfs/movil/";
		if(file_exists($folderPath)){
			$listDir = array_diff(scandir($folderPath), array('..', '.'));
			if(sizeof($listDir) > 0){

				$lastNotification = handleDateTime::getDateLastNotification();
				$responseGetContract = contracts::getContractWithID($idContract);
				if($responseGetContract->result == 2){
					foreach($listDir as $key => $value){
						$arrayName = explode("_", $value);
						$numberContract = explode("." ,$arrayName[sizeof($arrayName) - 1])[0];
						$responseEmail = null;
						$responseMovil = null;

						if(strcmp($responseGetContract->objectResult->contrato, $numberContract) == 0){
							if($responseGetContract->objectResult->enviarCelular == 1){
								$tempMobileNumber = null;
								if(!is_null($responseGetContract->objectResult->celularEnvio))
									$tempMobileNumber = $responseGetContract->objectResult->celularEnvio;
								else if(!is_null($responseGetContract->objectResult->celular))
									$tempMobileNumber = $responseGetContract->objectResult->celular;

								if(!is_null($tempMobileNumber)){
									$responseMovil = json_decode(ctr_contracts::sendWhatsApp(base64_encode(file_get_contents($folderPath . $value)), $value, $tempMobileNumber));
									if($responseMovil->sent == TRUE){
										contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, $value);
									}else{
										$response->result = 0;
										$response->message = "Ocurrió un error y pudo notificar al usuario a traves de WhatsApp.";
										return $response;
									}
								}
							}

							if($responseGetContract->objectResult->enviarEmail == 1){
								if(!is_null($responseGetContract->objectResult->email)){
									$responseEmail = contracts::sendMail($folderPath, $value, $numberContract, $responseGetContract->objectResult->email);
									if($responseEmail){
										contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, $value);
									}else{
										$response->result = 0;
										$response->message = "Ocurrió un error y pudo notificar al usuario a traves del correo.";
										return $response;
									}
								}
							}

							if($responseGetContract->objectResult->enviarEmail == 1 && $responseGetContract->objectResult->enviarCelular == 1){
								if($responseEmail == TRUE && $responseMovil->sent == TRUE){
									$response->result = 2;
									$response->message = "La factura fue enviado correctamente a través de WhatsApp y correo.";
								}else if($responseEmail == FALSE && $responseMovil->sent == FALSE){
									$response->result = 0;
									$response->message = "Ocurrió un error y el usuario no fue notificado a través de WhatsApp o correo.";
								}else{
									$response->result = 1;
									if($responseEmail == FALSE)
										$response->message = "La factura fue enviada a través de WhatsApp, el envio por correo falló.";
									else
										$response->message = "La factura fue enviada a través del correo, el envio por WhatsApp falló.";
								}
							}else if($responseGetContract->objectResult->enviarEmail == 1){
								if($responseEmail == TRUE){
									$response->result = 2;
									$response->message = "La factura fue enviada correctamente por correo.";
								}else{
									$response->result = 0;
									$response->message = "Ocurrió un error y la factura no fue enviada a través del correo.";
								}
							}else if($responseGetContract->objectResult->enviarCelular == 1){
								if($responseMovil->sent == TRUE){
									$response->result = 2;
									$response->message = "La factura fue enviada correctamente por WhatsApp.";
								}else{
									$response->result = 0;
									$response->message = "Ocurrió un error y la factura no fue enviada a través de WhatsApp.";
								}
							}

							return $response;
						}
					}
					$response->result = 0;
					$response->message = "El contrato correspondiente a " . $responseGetContract->objectResult->usuario . " no se encuentra dentro de los archivos cargados.";
				}else return $responseGetContract;
			}else{
				$response->result = 0;
				$response->message = "No se encontraron los archivos del zip cargado.";
			}
		}else{
			$response->result = 0;
			$response->message = "Debe cargar nuevos contratos para enviar.";
		}

		return $response;
	}

	public function notifyAllContract(){
		$response = new \stdClass();

		$folderPath = dirname(dirname(__DIR__)) . "/public/pdfs/movil";
		if(file_exists($folderPath)){

			$listDir = array_diff(scandir($folderPath), array('..', '.'));
			if(sizeof($listDir) > 0){
				$lastNotification = handleDateTime::getDateLastNotification();
				$arrayErrors = array();
				foreach ($listDir as $key => $value) {

					$arrayName = explode("_", $value);
					$numberContract = explode("." ,$arrayName[sizeof($arrayName) - 1])[0];
					$responseGetContract = contracts::getContractWithNumber($numberContract);
					if($responseGetContract->result == 2){

						$folderPath .= '/';
						if(is_null($responseGetContract->objectResult->ultimoArchivo) || strcmp($responseGetContract->objectResult->ultimoArchivo, $value) != 0){
							if($responseGetContract->objectResult->enviarEmail == 1){
								if(!is_null($responseGetContract->objectResult->email)){
									$resultSendEmail = contracts::sendMail($folderPath, $value, $numberContract, $responseGetContract->objectResult->email);
									if($resultSendEmail){
										contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, $value);
										sleep(4);
									}else $arrayErrors[] = $responseGetContract->objectResult->usuario;
								}
							}

							if($responseGetContract->objectResult->enviarCelular == 1){
								$tempMobileNumber = null;
								if(!is_null($responseGetContract->objectResult->celularEnvio))
									$tempMobileNumber = $responseGetContract->objectResult->celularEnvio;
								else if(!is_null($responseGetContract->objectResult->celular))
									$tempMobileNumber = $responseGetContract->objectResult->celular;

								if(!is_null($tempMobileNumber)){
									$responseSent = json_decode(ctr_contracts::sendWhatsApp(base64_encode(file_get_contents($folderPath . $value)), $value, $tempMobileNumber));
									if($responseSent->sent == TRUE){
										contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, $value);
										sleep(12);
									}else $arrayErrors[] = $responseGetContract->objectResult->usuario;
								}
							}
						}
					}
				}

				if(sizeof($arrayErrors) == 0){
					$response->result = 2;
					$response->message = "Todos los contratos contratos con notificaciones activas fueron enviados correctamente.";
				}else{
					$response->result = 1;
					$response->message = "Algunas facturas no fueron enviadas a sus respectivos clientes.";
				}
			}else{
				$response->result = 0;
				$response->message = "Ocurrió un error y los archivos no se pueden leer.";
			}
		}else{
			$response->result = 0;
			$response->message = "Debe cargar nuevos contratos para enviar.";
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

	public function getListContracts($lastId, $textToSearch, $group, $checkedActive){
		$response = new \stdClass();

		if($group == 0)
			$group = null;

		if($checkedActive == 0)
			$checkedActive = null;

		$responseGetContracts = contracts::getListContracts($lastId, $textToSearch, $group, $checkedActive);
		if($responseGetContracts->result == 2){
			$response->result = 2;
			$response->listResult = $responseGetContracts->listResult;
			$response->lastId = $responseGetContracts->lastId;
		}else return $responseGetContracts;
		return $response;
	}
}