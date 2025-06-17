<?php

require_once '../src/clases/contracts.php';
require_once '../src/utils/handle_date_time.php';
require_once '../src/utils/generate_excel.php';
require_once '../src/utils/utils.php';

class ctr_contracts{

	public function exportExcelContract(){
		$response = new \stdClass();

		$responseGetContracts = contracts::getAllContracts();
		if($responseGetContracts->result == 2){
			$excelBase64 = generateExcel::createExcel($responseGetContracts->listResult);
			if(!is_null($excelBase64)){
				$response->result = 2;
				$response->excel = $excelBase64;
			}else{
				$response->result = 0;
				$response->message = "Ocurrió un error y el archivo excel no fue generado.";
			}
		}else return $responseGetContracts;

		return $response;
	}

	public function getGroupsInformation(){
		return contracts::getGroupsInformation();
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

//funcion a la que llegan los zip sean pdf o xml
	public function loadFileToSend($nameFile, $typeFile, $dataFile){
		$response = new \stdClass();
		//ctr_contracts::clearFolder();

		$folderPath = dirname(dirname(__DIR__)) . "/public/files/";
		$zip_Array = explode(";base64,", $dataFile);
		$zip_contents = base64_decode($zip_Array[1]);
		$file = $folderPath . $nameFile;
		file_put_contents($file, $zip_contents);

		//DETALLE_FACTURAS
		if(strstr($nameFile, "detalle_facturas") != FALSE){
			$zip = new ZipArchive();
			$descompressFile = $zip->open($file);
			if($descompressFile === TRUE){
				$zip->extractTo($folderPath);
				$zip->close();
				unlink($folderPath . $nameFile);
			}
		}

		if(file_exists($folderPath . "Facturas_Movil.zip") === TRUE){
			// se tendría que borrar la carpeta de pdf
			error_log("se tendría que borrar la carpeta de pdf");
			// ctr_contracts::clearFolderPath(["public", "files", "movil"]);

			$zipMovil = new ZipArchive();
			$descompressFile = $zipMovil->open($folderPath . "Facturas_Movil.zip");
			if ($descompressFile === TRUE){
				$zipMovil->extractTo($folderPath . "movil/");
				$zipMovil->close();
				unlink($folderPath . "Facturas_Movil.zip");
				if(file_exists($folderPath . "Facturas_Fija.zip"))
					unlink($folderPath . "Facturas_Fija.zip");
				return ctr_contracts::processMovilDir($folderPath . "movil/");
			}
		}else{
			//xml
			//se tendría que borrar la carpeta de contratos
			error_log("se tendría que borrar la carpeta de contratos");
			// ctr_contracts::clearFolderPath(["public", "files", "contratos"]);

			//como se sube zip xml se ponen en null los importes anteriores
			// $resultSetAmountContracts = ctr_contracts::setCeroAllAmountContracts();
			// if( $resultSetAmountContracts->result != 2){
			// 	return $resultSetAmountContracts;
			// }

			$zip = new ZipArchive;
			$descompressFile = $zip->open($file);
			if ($descompressFile === TRUE){
				$zip->extractTo($folderPath . "contratos/");
				$zip->close();
				unlink($folderPath . $nameFile);
				return ctr_contracts::processContractDir($folderPath . "contratos/");
			}
		}
	}

//PROCESO PARA CARGAR TODOS LOS DATOS QUE LLEGAN EN EL ZIP DE PDF
	public function processMovilDir($folderPath){
		$response = new \stdClass();

		$listDir = array_diff(scandir($folderPath), array('..', '.'));
		if(sizeof($listDir) > 0){
			$countNew = 0;
			$countOld = 0;
			$arrayContractNotEntered = array();
			foreach ($listDir as $key => $value) {
				// error_log("VALUE: $value");
				$arrayName = explode("_", $value);
				$numberContract = explode("." ,$arrayName[sizeof($arrayName) - 1])[0];
				// error_log("CONTRATO PROCESADO: $numberContract");
				$responseGetContract = contracts::getContractWithNumber($numberContract);
				if($responseGetContract->result == 1){
					$responseInsert = contracts::createNewContract(null, null, null, $numberContract, null, null);
					if($responseInsert->result == 2){
						$countNew++;
						$responseUpdate = contracts::setLastFile($numberContract, $value);		
					}
				}else{
					$responseInsert = contracts::setLastFile($numberContract, $value);
					if($responseInsert->result == 2)
						$countOld++;
				}
			}

			$response->result = 2;
			if($countNew > 0)
				$response->message = "Se descomprimió el archivo y se encontraron " . $countNew . " contratos nuevos + ".$countOld;
			else
				$response->message = "Se descomprimió el archivo y los ".$countOld." contratos estan preparados para ser enviados.";
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y el archivo ingresado no fue descomprimido.";
		}

		return $response;
	}

//PROCESO PARA CARGAR TODOS LOS DATOS QUE LLEGAN EN EL ZIP DE XML
	public function processContractDir($folderPath){
		$response = new \stdClass();

		$listDir = array_diff(scandir($folderPath), array('..', '.'));
		if(sizeof($listDir) > 0){
			$countNew = 0;
			$arrayContractNotEntered = array();
			foreach ($listDir as $key => $value) {
				$fileContent = simplexml_load_file($folderPath . $value);
				if(!is_null($fileContent)){
					$headDetail = $fileContent->Cabezal->IdGenerador;
					$obj = '@attributes';
					$numberMobile = $fileContent->Detalles->Parte->Seccion->Grupo;
					$numberMobile = json_decode(json_encode($numberMobile));
					$numberMobile = filter_var($numberMobile->{$obj}->nombre, FILTER_SANITIZE_NUMBER_INT);
					$responseGetContract = contracts::getContractWithNumber($headDetail->NroContrato);
					//busca el numero de contrato en la base de datos, si el contrato no se encuentra registrado lo registra
					$countNew++;
					if($responseGetContract->result == 2){
						$contract = $responseGetContract->objectResult;
						if(strlen($numberMobile) < 5)
							$numberMobile = null;
						$responseUpdate = contracts::updateContract($contract->id, $contract->usuario, $contract->email, $numberMobile, $headDetail->NroContrato, $contract->grupo, $contract->celularEnvio, $headDetail->ImporteTotFactura);
						if($responseUpdate->result != 2)
							$arrayContractNotEntered[] = $value;
					}elseif ($responseGetContract->result == 1) {
						if(strlen($headDetail->NroContrato) != 0){
							$responseInsert = contracts::createNewContract(null, null, $numberMobile, $headDetail->NroContrato, null, null);
							if($responseInsert->result != 2)
								$arrayContractNotEntered[] = $value;
						}
					}
				}
			}
			if(sizeof($arrayContractNotEntered) == 0){
				$response->result = 2;
				$response->message = "Todos los archivos fueron procesados y se actualizó la información de los ".$countNew." contratos.";
			}else{
				$response->result = 1;
				$response->message = "Algunos archivos no fueron procesados y la información de sus contratos no fue actualizada.";
			}
		}

		// ctr_contracts::clearDirContract($folderPath);

		return $response;
	}

	public function setCeroAllAmountContracts(){
		return contracts::setCeroAllAmountContracts();
	}

//SE LLAMA A ESTA FUNCIÓN CUANDO SE QUIEREN BORRAR DATOS DE PDF
	public function clearUltimoArchivoContracts(){
		return contracts::clearUltimoArchivoContracts();
	}

	public function clearDirContract($folderPath){
		$listDir = scandir($folderPath);
		foreach ($listDir as $key => $value) {
			if(!is_dir($value))
				unlink($folderPath . $value);
		}
	}

	public function notifyOneContract($idContract, $vencimiento){
		$response = new \stdClass();
		$notificado = false;

		$folderPath = dirname(dirname(__DIR__)). DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "movil";
		if(file_exists($folderPath)){
			$listDir = array_diff(scandir($folderPath), array('..', '.'));
			if(sizeof($listDir) > 0){

				$lastNotification = handleDateTime::getDateLastNotification();
				$responseGetContract = contracts::getContractWithID($idContract);
				if($responseGetContract->result == 2){
					$folderPath .= DIRECTORY_SEPARATOR;
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
									$phoneNumber = $responseGetContract->objectResult->celular;
									$userName = $responseGetContract->objectResult->usuario;
									$amount = $responseGetContract->objectResult->importe;
									// echo $folderPath . $value;
									$responseMovil = json_decode(ctr_contracts::sendWhatsApp($phoneNumber, $userName, base64_encode(file_get_contents($folderPath . $value)), $value, $tempMobileNumber, $amount, $vencimiento));
									if($responseMovil->sent == TRUE){
										//var_dump("1",$value);exit;
										contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, $value);
										$notificado = true;
									}else{
										$response->result = 0;
										$response->message = "Ocurrió un error al notificar a través de WhatsApp.";
										return $response;
									}
								}
							}

							if($responseGetContract->objectResult->enviarEmail == 1){
								if(!is_null($responseGetContract->objectResult->email)){
									$phoneNumber = $responseGetContract->objectResult->celular;
									$userName = $responseGetContract->objectResult->usuario;
									$amount = $responseGetContract->objectResult->importe;
									$responseEmail = contracts::sendMail($phoneNumber, $userName, $folderPath, $value, $numberContract, $responseGetContract->objectResult->email, $amount);
									return $responseEmail;
									if($responseEmail){
										//var_dump("2", $value);exit;
										contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, $value);
										$notificado = true;
									}else{
										$response->result = 0;
										$response->message = "Ocurrió un error al notificar a través del correo.";
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
			$response = ctr_contracts::notifyOneContractWithoutPdf($idContract, $vencimiento);
		}
		if(!$notificado) // Al final de todo pregunto si se notifico el usuario y si no, itento notificarlo pero sin PDF
			$response = ctr_contracts::notifyOneContractWithoutPdf($idContract, $vencimiento);
		return $response;
	}

	public function notifyAllContract($vencimiento){
		$response = new \stdClass();

		$folderPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "movil";
		if(file_exists($folderPath)){

			$listDir = array_diff(scandir($folderPath), array('..', '.'));
			if(sizeof($listDir) > 0){
				// var_dump("tengo pdfs");
				$lastNotification = handleDateTime::getDateLastNotification();
				$arrayErrors = array();
				$folderPath .= DIRECTORY_SEPARATOR;
				foreach ($listDir as $key => $value) {

					$arrayName = explode("_", $value);
					$numberContract = explode("." ,$arrayName[sizeof($arrayName) - 1])[0];
					$responseGetContract = contracts::getContractWithNumber($numberContract);
					if($responseGetContract->result == 2){

						if(is_null($responseGetContract->objectResult->ultimoArchivo) || strcmp($responseGetContract->objectResult->ultimoArchivo, $value) != 0){
							if(is_null($responseGetContract->objectResult->fechaNotificacion) || strcmp($responseGetContract->objectResult->fechaNotificacion, $lastNotification) != 0){
								if($responseGetContract->objectResult->enviarEmail == 1){
									if(!is_null($responseGetContract->objectResult->email)){
										$phoneNumber = $responseGetContract->objectResult->celular;
										$userName = $responseGetContract->objectResult->usuario;
										$amount = $responseGetContract->objectResult->importe;
										//var_dump($responseGetContract);exit;
										$resultSendEmail = contracts::sendMail($phoneNumber, $userName, $folderPath, $value, $numberContract, $responseGetContract->objectResult->email, $amount);
										if($resultSendEmail){
											//var_dump("3", $responseGetContract->objectResult->id, $lastNotification, $value);exit;
											contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, $value);
											//sleep(1);
										}else $arrayErrors[] = $responseGetContract->objectResult->usuario;
									}
								}

								if($responseGetContract->objectResult->enviarCelular == 1){
									//var_dump("acá 3 celu");

									$tempMobileNumber = null;
									if(!is_null($responseGetContract->objectResult->celularEnvio))
										$tempMobileNumber = $responseGetContract->objectResult->celularEnvio;
									else if(!is_null($responseGetContract->objectResult->celular))
										$tempMobileNumber = $responseGetContract->objectResult->celular;

									if(!is_null($tempMobileNumber)){
										$phoneNumber = $responseGetContract->objectResult->celular;
										$userName = $responseGetContract->objectResult->usuario;
										$amount = $responseGetContract->objectResult->importe;
										if(isset($amount)){ // SI no hay monto (NO ESPECIFICADO) no se envia NADA
											$responseSent = json_decode(ctr_contracts::sendWhatsApp($phoneNumber, $userName, base64_encode(file_get_contents($folderPath . $value)), $value, $tempMobileNumber, $amount, $vencimiento));
											if($responseSent->sent == TRUE){
												//var_dump("4", $value);exit;
												contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, $value);
												//sleep(5);
											}else $arrayErrors[] = $responseGetContract->objectResult->usuario;
										}
									}
								}
							}
						}/*else {
							var_dump("te olvidaste de cambiar o borrar el nombre del ultimo archivo pdf que se envio");exit;
						}*/
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
			// $response = ctr_contracts::notifyAllContractWithoutPdf($vencimiento);
		}else{
			// echo "El archivo NO existe loquito";
			// exit;
		}
		
		// Una vez notificados todos los clientes que tienen PDF, se notifica todos los que no tienen PDF pero si Importe y fecha de ultima notificacion distina al mes actual
		$response = ctr_contracts::notifyAllContractWithoutPdf($vencimiento);

		return $response;
	}

	public function notifyOneContractWithoutPdf($idContract, $vencimiento = null){
		$response = new \stdClass();
		//ver en la tabla si de ese id de contrato se envia notif por correo o mail
		$lastNotification = handleDateTime::getDateLastNotification();
		$responseGetContract = contracts::getContractWithID($idContract);
		// $expiredDate = handleDateTime::getFechaVencimiento();
		if(is_null($vencimiento))
			$expiredDate = handleDateTime::getFechaVencimiento();
		else
			$expiredDate = substr($vencimiento, 8, 2) . "-" . substr($vencimiento, 5, 2) . "-" . substr($vencimiento, 0, 4);
		if( $responseGetContract && $responseGetContract->result == 2){
			if($responseGetContract->objectResult->enviarEmail == 1){
				if(!is_null($responseGetContract->objectResult->email)){
					$servicio = $responseGetContract->objectResult->celular;
					$usuario = $responseGetContract->objectResult->usuario;
					$responseEmail = contracts::sendMailWithoutPdf($servicio, $usuario, $responseGetContract->objectResult->contrato, $responseGetContract->objectResult->email, $responseGetContract->objectResult->importe,$expiredDate);
					if($responseEmail){
						//var_dump("6", $value);exit;
						contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, null);
						//sleep(1);
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
					$phoneNumber = $responseGetContract->objectResult->celular;
					$userName = $responseGetContract->objectResult->usuario;
					// $responseMovil = null;
					if(!is_null($responseGetContract->objectResult->importe)){ // Si el importe es NULL, no se envia nada
						$responseMovil = json_decode(ctr_contracts::sendWhatsAppWithoutPdf($phoneNumber, $userName, $tempMobileNumber, $responseGetContract->objectResult->importe, $vencimiento));

						if($responseMovil->sent == TRUE){
							//var_dump("7", $value);exit;
							contracts::setLastNotification($responseGetContract->objectResult->id, $lastNotification, null);
							//sleep(5);
						}else $arrayErrors[] = $responseGetContract->objectResult->usuario;
					} else {
						$responseMovil = new \stdClass();
						$responseMovil->sent = FALSE;
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

	public function notifyAllContractWithoutPdf($vencimiento){
		$response = new \stdClass();
		$arrayErrors = array();
		//por sql traer todos los id de contratos que tienen envio por celular o mail activado e importe mayor a cero y distinto de nulL
		$allNumberContracts = contracts::getAllContractsToNotify();
		if ( isset($allNumberContracts) ){
			if ( $allNumberContracts->result == 2 ){
				foreach ($allNumberContracts->listResult as $key => $value) {
					$responseNotifyOne = ctr_contracts::notifyOneContractWithoutPdf($value['id'], $vencimiento);
					if ( $responseNotifyOne && $responseNotifyOne->result != 2){
						$arrayErrors[] = $responseNotifyOne->message;
					}else $response = $responseNotifyOne;
				}
			}else return $allNumberContracts;
		}else {
			$response->result = 0;
			$response->message = "No se encontró resultado de los contratos a notificar.";
		}
		return $response;
	}

	public function clearFolder(){
		$path = dirname(dirname(__DIR__)) . "/public/files/";
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

//FUNCION QUE BORRA TODO EL CONTENIDO DE UNA CARPETA QUE SE PASA POR PARAMETRO EN FORMATO DE ARRAY
//EJ ['public', 'files', 'contratos']
	public function clearFolderPath($path){
		$contractController = new ctr_contracts();
		$dir = dirname(dirname(__DIR__));
		foreach ($path as $value) {
			$dir .= DIRECTORY_SEPARATOR . $value;
		}

		if (!file_exists($dir)) {
	        return true;
	    }

	    if (!is_dir($dir)) {
	        return unlink($dir);
	    }

	    foreach (scandir($dir) as $item) {
	        if ($item == '.' || $item == '..') {
	            continue;
	        }

	        array_push($path, $item);
	        //var_dump($path);
	        if (!$contractController->clearFolderPath($path)) {
	            return false;
	        }else {
	        	array_pop($path);
	    		//var_dump("pop",$path);
	        }
	    }
	    return rmdir($dir);
	}

	function sendWhatsApp($phoneNumber, $userName, $dataFile, $nameFile, $mobilePhone, $amount, $vencimiento = null) {
		$response = new \stdClass();
		$utils = new utils();

		$sessionUserName = $_SESSION['ADMIN']['USER'];

		//$data = "id=".WHATSAPP_API_USER."&to=598".$mobilePhone."&content=".$pdf.'&mimetype=application/pdf&name='.$nameFile;
		//depende del importe que se tenga se agrega en el mensaje o no
		if ( is_null($amount) ) {
			if(is_null($vencimiento))
				$message = 'Antel 0'.$phoneNumber.' '.$userName.', vence: '. handleDateTime::getFechaVencimiento();
			else
				$message = 'Antel 0'.$phoneNumber.' '.$userName.', vence: '. substr($vencimiento, 8, 2) . "-" . substr($vencimiento, 5, 2) . "-" . substr($vencimiento, 0, 4);
		} else if ( $amount == 0 ) {
			// $message = 'Antel 0'.$phoneNumber.' '.$userName.', importe: $'.$amount; //.' vence: '. handleDateTime::getFechaVencimiento();
			$message = 'Antel 0'.$phoneNumber.' '.$userName.', importe: $'.$amount.' vence: '. substr($vencimiento, 8, 2) . "-" . substr($vencimiento, 5, 2) . "-" . substr($vencimiento, 0, 4);
		} else {
			if(is_null($vencimiento))
				$message = 'Antel 0'.$phoneNumber.' '.$userName.', importe: $'.$amount.' vence: '. handleDateTime::getFechaVencimiento();
			else
				$message = 'Antel 0'.$phoneNumber.' '.$userName.', importe: $'.$amount.' vence: '. substr($vencimiento, 8, 2) . "-" . substr($vencimiento, 5, 2) . "-" . substr($vencimiento, 0, 4);
		}
		
		$route = 'imgAndText';

		// Explode the string by spaces
		$parts = explode(' ', $message);

		// Form the new string using the second and last parts
		$nameFile = "Detalle_" . $parts[1] . "_" . $parts[count($parts) - 1];

		$data = http_build_query(
		    array(
		        'id'       => WHATSAPP_API_USER,
		        'content'  => trim($dataFile),
		        'to'       => "598" . trim($mobilePhone),
		        'name'     => trim($nameFile),
		        'text'     => trim($message),
		        'mimetype' => 'application/pdf',
		        'token'    => '45ek2wrhgr3rg33m'
		    )
		);


		$responseCurl = $utils->whatsapp($route, $data);

		// $datatexto = "id=".WHATSAPP_API_USER."&content=".$message."&to=598".$mobilePhone;
		// $responseCurlTexto = $utils->whatsappApiConection("txt", $datatexto);

		//-----------------------------------------------------------------------
		// var_dump($responseCurl->result);
		if($responseCurl->result == 2){
			$response->sent = TRUE;

			$logFile = fopen(LOG_PATHFILE.date("Ymd").".log", 'a') or die("Error creando archivo");
			fwrite($logFile, "\n".date("d/m/Y H:i:s ")."El usuario en sesion ".$sessionUserName. " se envió pdf y texto (en mensaje unico) a ". $mobilePhone);
			fclose($logFile);

			return json_encode($response);
		} else {
			$response->sent = FALSE;

			$logFile = fopen(LOG_PATHFILE.date("Ymd").".log", 'a') or die("Error creando archivo");
			fwrite($logFile, "\n".date("d/m/Y H:i:s ")."El usuario en sesion ".$sessionUserName. " No se envió pdf y texto (en mensaje unico) a ". $mobilePhone);
			fclose($logFile);

			return json_encode($response);
		}

		// if($responseCurl->result == 2 && $responseCurlTexto->result == 2){
		// 	$response->sent = TRUE;

		// 	$logFile = fopen(LOG_PATHFILE.date("Ymd").".log", 'a') or die("Error creando archivo");
		// 	fwrite($logFile, "\n".date("d/m/Y H:i:s ")."El usuario en sesion ".$sessionUserName. " se envió pdf y se envió texto a ". $mobilePhone);
		// 	fclose($logFile);

		// 	return json_encode($response);
		// }else if($responseCurl->result != 2 && $responseCurlTexto->result != 2){
		// 	$response->sent = FALSE;

		// 	$logFile = fopen(LOG_PATHFILE.date("Ymd").".log", 'a') or die("Error creando archivo");
		// 	fwrite($logFile, "\n".date("d/m/Y H:i:s ")."El usuario en sesion ".$sessionUserName. " No se envió pdf, NO se envió texto a ". $mobilePhone);
		// 	fclose($logFile);

		// 	return json_encode($response);
		// }else{
		// 	$response->sent = TRUE;

		// 	$logFile = fopen(LOG_PATHFILE.date("Ymd").".log", 'a') or die("Error creando archivo");
		// 	fwrite($logFile, "\n".date("d/m/Y H:i:s ")."El usuario en sesion ".$sessionUserName. "  envió pdf o texto a ". $mobilePhone);
		// 	fclose($logFile);

		// 	return json_encode($response);
		// }


	}

	function getListWAGroups() {
		$utils = new utils();
		$data = "id=".WHATSAPP_API_USER;
		//$responseCurl = $utils->whatsappApiConection("client/groups", $data);
		var_dump("En desarrollo");exit;

		if($responseCurl->result == 2 ){


			//depende del importe que se tenga se agrega en el mensaje o no
			if ( is_null($amount) )
				$message = 'Antel 0'.$phoneNumber.' '.$userName.', vence: '. handleDateTime::getFechaVencimiento();
			else if ( $amount == 0 )
				$message = 'Antel 0'.$phoneNumber.' '.$userName.', importe: $'.$amount; //.' vence: '. handleDateTime::getFechaVencimiento();
			else
				$message = 'Antel 0'.$phoneNumber.' '.$userName.', importe: $'.$amount.' vence: '. handleDateTime::getFechaVencimiento();

			$data = "id=".WHATSAPP_API_USER."&content=".$message."&to=598".$mobilePhone;
			$responseCurl = $utils->whatsappApiConection("txt", $data);
			if ( $responseCurl->result == 2 ){
				$response->sent = TRUE;

				$sessionUserName = $_SESSION['ADMIN']['USER'];
				$logFile = fopen(LOG_PATHFILE.date("Ymd").".log", 'a') or die("Error creando archivo");
				fwrite($logFile, "\n".date("d/m/Y H:i:s ")."El usuario en sesion ".$sessionUserName. " envió pdf y mensaje por whatsapp a ". $mobilePhone);
				fclose($logFile);
			}else $response->sent = FALSE;
		}
	}

	function sendWhatsAppWithoutPdf($phoneNumber, $userName, $mobilePhone, $amount, $vencimiento = null) {
		$response = new \stdClass();
		$utils = new utils();

		// $message = 'Antel 0'.$phoneNumber.' '.$userName.', importe: $'.$amount.' vence: '. handleDateTime::getFechaVencimiento();

		if(is_null($vencimiento))
			$message = 'Antel 0'.$phoneNumber.' '.$userName.', importe: $'.$amount.' vence: '. handleDateTime::getFechaVencimiento();
		else
			$message = 'Antel 0'.$phoneNumber.' '.$userName.', importe: $'.$amount.' vence: '. substr($vencimiento, 8, 2) . "-" . substr($vencimiento, 5, 2) . "-" . substr($vencimiento, 0, 4);

		$data = "id=".WHATSAPP_API_USER."&content=".$message."&to=598".$mobilePhone;
		$responseCurl = $utils->whatsappApiConection("txt", $data);

		if ( $responseCurl->result == 2 ){
			$response->sent = TRUE;
			$sessionUserName = $_SESSION['ADMIN']['USER'];
			$logFile = fopen(LOG_PATHFILE.date("Ymd").".log", 'a') or die("Error creando archivo");
			fwrite($logFile, "\n".date("d/m/Y H:i:s ")."El usuario en sesion ".$sessionUserName. " envió whatsapp a ". $mobilePhone);
			fclose($logFile);
		}else $response->sent = FALSE;

		return json_encode($response);
	}

	function sendWhatsAppNotification($mobilePhone, $message) {
		$response = new \stdClass();
		$utils = new utils();

		$data = "id=".WHATSAPP_API_USER."&content=".$message."&to=".$mobilePhone;
		$responseCurl = $utils->whatsappApiConection("txt", $data);
		if ( $responseCurl->result == 2 ){
			$response->sent = TRUE;
			return $response;
		}else {
			$response->sent = FALSE;
			return $response;
		}
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
			$responseUpdateContract = contracts::updateContract($idContract, $name, $email, $mobile, $contract, $group, $mobileToSend, $responseGetContract->objectResult->importe);
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

	public function deleteContractSelected($idContract){
		$response = new \stdClass();

		$responseGetContract = contracts::getContractWithID($idContract);
		if($responseGetContract->result == 2){
			$responseDeleteContract = contracts::deleteContractSelected($idContract);
			if($responseDeleteContract->result == 2){
				$response->result = 2;
				$response->message = "El contrato seleccionado fue borrado correctamente";
			}else{
				$response->result = 0;
				$response->message = "El contrato seleccionado no fue borrado por un error interno.";
			}
		}else return $responseGetContract;

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

		if(strcmp($group,"0") == 0)
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