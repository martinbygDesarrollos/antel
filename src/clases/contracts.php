<?php

require_once '../src/connection/openConnection.php';

class contracts{

	public function getAllContracts(){
		$responseQuery = DataBase::sendQuery("SELECT * FROM contratos", null, "LIST");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontraron contratos que listar.";

		return $responseQuery;
	}

	public function getGroupsInformation(){
		$responseQuery = DataBase::sendQuery("SELECT DISTINCT grupo, COUNT(grupo) as cantGrupo FROM contratos WHERE grupo IS NOT NULL GROUP BY grupo", null, "LIST");
		if($responseQuery->result ==2){
			$totContracts = 0;
			foreach ($responseQuery->listResult as $key => $row) {
				$totContracts += $row['cantGrupo'];
			}
			$responseQuery->totContracts = $totContracts;
		}else if($responseQuery->result == 1)
		$responseQuery->message = "No se obtuvieron registrso por contratos de la base de datos.";

		return $responseQuery;
	}

	public function setLastNotification($idContract, $dateInt, $nameFile){
		return DataBase::sendQuery("UPDATE contratos SET fechaNotificacion = ? , ultimoArchivo = ? WHERE id = ?", array('isi', $dateInt, $nameFile, $idContract), "BOOLE");
	}

	public function getContractToShow($idContract){
		$responseQuery = DataBase::sendQuery("SELECT * FROM contratos WHERE id = ? ", array('i', $idContract), "OBJECT");
		if($responseQuery->result == 2){
			$responseQuery->objectResult->celular = contracts::setMobilePhoneFormat($responseQuery->objectResult->celular);
			if(is_null($responseQuery->objectResult->celularEnvio))
				$responseQuery->objectResult->celularEnvio = "No especificado";
			else
				$responseQuery->objectResult->celularEnvio = contracts::setMobilePhoneFormat($responseQuery->objectResult->celularEnvio);

			if(is_null($responseQuery->objectResult->email))
				$responseQuery->objectResult->email = "No especificado";

			if(is_null($responseQuery->objectResult->fechaNotificacion))
				$responseQuery->objectResult->fechaNotificacion = "No notificado";
			else $responseQuery->objectResult->fechaNotificacion = handleDateTime::formatDateBarWithMonth($responseQuery->objectResult->fechaNotificacion);

			if(is_null($responseQuery->objectResult->importe))
				$responseQuery->objectResult->importe = "No especificado";
			else $responseQuery->objectResult->importe = "$ " . number_format($responseQuery->objectResult->importe, 2,",",".");

		}else if($responseQuery->result == 1){
			$responseQuery->message = "El contrato seleccionado no fue encontrado en la base de datos.";
		}

		return $responseQuery;
	}

	public function changeNotificationStatusEmail($idContract, $newStatus){
		return DataBase::sendQuery("UPDATE contratos SET enviarEmail = ? WHERE id = ?", array('is', $newStatus, $idContract), "BOOLE");
	}

	public function changeNotificationStatusMobile($idContract, $newStatus){
		return DataBase::sendQuery("UPDATE contratos SET enviarCelular = ? WHERE id = ?", array('is', $newStatus, $idContract), "BOOLE");
	}

	public function validateContractDontRepeat($idContract, $contract){
		return DataBase::sendQuery("SELECT * FROM contratos WHERE contrato = ? AND id != ? ", array('si', $contract, $idContract), "OBJECT");
	}

	public function getContractWithID($idContract){
		$responseQuery = DataBase::sendQuery("SELECT * FROM contratos WHERE id = ?", array('i', $idContract), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "El identificador de contrato seleccionado no corresponde a uno registrado en la base de datos.";

		return $responseQuery;
	}

	public function createNewContract($name, $email, $mobile, $contract, $group, $mobileToSend){
		$statusMobile = 1;
		if(is_null($mobileToSend))
			$statusMobile = 0;

		$statusEmail = 1;
		if(is_null($email))
			$statusEmail = 0;

		return DataBase::sendQuery("INSERT INTO contratos(grupo, usuario, contrato, celular, celularEnvio, enviarCelular, email, enviarEmail) VALUES(?,?,?,?,?,?,?,?)", array('sssiiisi', $group, $name, $contract, $mobile, $mobileToSend, $statusMobile, $email, $statusEmail), "BOOLE");
	}

	public function updateContract($idContract, $name, $email, $mobile, $contract, $group, $mobileToSend, $importe){
		return DataBase::sendQuery("UPDATE contratos SET grupo = ?, usuario = ?, contrato = ?, celular = ?, celularEnvio = ?, email = ?, importe = ? WHERE id = ?", array('sssiisdi', $group, $name, $contract, $mobile, $mobileToSend, $email, $importe, $idContract), "BOOLE");
	}

	public function deleteContractSelected($idContract){
		return DataBase::sendQuery("DELETE FROM contratos WHERE id = ?", array('i', $idContract), "BOOLE");
	}

	public function getContractWithNumber($contract){
		$responseQuery = DataBase::sendQuery("SELECT * FROM contratos WHERE contrato = ?", array('s', $contract), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "El número de contrato seleccionado no corresponde a uno existente en la base de datos.";

		return $responseQuery;
	}
	
	public function getMaxID(){
		$responseQuery = DataBase::sendQuery("SELECT MAX(id) AS lastID FROM contratos", null, "OBJECT");
		if($responseQuery->result == 2) return $responseQuery->objectResult->lastID + 1;
	}

	public function getListContracts($lastId, $textToSearch, $group, $checkedActive){
		if($lastId == 0) $lastId = contracts::getMaxID();

		$sqlToSearch = "";
		if(strlen($textToSearch) > 0){
			foreach ( explode(" ", $textToSearch) as $value ) {
				$sqlToSearch .= " AND (usuario LIKE '%". $value ."%' or CONCAT(contrato,'') LIKE '%". $value ."%' or CONCAT(celular,'') LIKE '%". $value ."%' or CONCAT(importe,'') LIKE '%". $value ."%')";
			}
		}

		$sqlNotification = "";
		if(!is_null($checkedActive))
			$sqlNotification = " AND (enviarCelular = 0 AND enviarEmail = 0)";

		$sqlGroup = "";
		if(!is_null($group))
			$sqlGroup = " AND grupo LIKE '" . $group ."' ";

		$responseQuery = DataBase::sendQuery("SELECT * FROM contratos WHERE id < ? " . $sqlToSearch . $sqlNotification . $sqlGroup . " ORDER BY id DESC LIMIT 14", array('i', $lastId), "LIST");
		if($responseQuery->result == 2){
			$newLastId = $lastId;
			$arrayResult = array();
			$notSpecified = "No especificado";
			foreach ($responseQuery->listResult as $key => $row) {
				if($newLastId > $row['id']) $newLastId = $row['id'];

				if(is_null($row['grupo']))
					$row['grupo'] = "";

				if(is_null($row['usuario']))
					$row['usuario'] = $notSpecified;

				if(is_null($row['celular']))
					$row['celular'] = $notSpecified;
				else
					$row['celular'] = contracts::setMobilePhoneFormat($row['celular']);

				if(is_null($row['email']))
					$row['email'] = $notSpecified;

				if(is_null($row['celularEnvio']))
					$row['celularEnvio'] = $notSpecified;
				else
					$row['celularEnvio'] = contracts::setMobilePhoneFormat($row['celularEnvio']);

				if(is_null($row['fechaNotificacion']))
					$row['fechaNotificacion'] = "No notificado";
				else
					$row['fechaNotificacion'] = handleDateTime::formatDateBarWithMonth($row['fechaNotificacion']);

				if(is_null($row['importe']))
					$row['importe'] = 'No especificado';
				else $row['importe'] = "$ " . number_format($row['importe'],2,",",".");

				$arrayResult[] = $row;
			}
			$responseQuery->listResult = $arrayResult;
			$responseQuery->lastId = $newLastId;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay contratos ingresados en la base de datos.";
		}

		return $responseQuery;
	}

	public function sendMail($phoneNumber, $userName, $filePath, $fileName, $contract, $mailTo, $amount){
		$file = $filePath . $fileName;

		$separator = md5(time());
		$eol = "\r\n";

		//amount es el importe del contrato este mes, por lo que puede ser que amount sea null, 0 u otro numero
		$subject = 'Contrato N° ' . $contract;
		$date = handleDateTime::getFechaVencimiento();
		$message = "";
		if ( is_null($amount) )
			$message = 'Antel 0'.$phoneNumber.' '.$userName.', vence ' . $date;
		else if ( $amount == 0 ){
			$message = 'Antel 0'.$phoneNumber.' '.$userName.', importe $'.$amount.', vence ' . $date;
		}
		else{
			$message = 'Antel 0'.$phoneNumber.' '.$userName.', importe $'.$amount.', vence ' . $date;
		}

		$content = file_get_contents($file);
		$content = chunk_split(base64_encode($content));

		$headers = "From: antel.byg.uy <antel@byg.uy>" . $eol;
		$headers .= "MIME-Version: 1.0" . $eol;
		$headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol;
		$headers .= "Content-Transfer-Encoding: 7bit" . $eol;
		$headers .= "This is a MIME encoded message." . $eol;

		$body = "--" . $separator . $eol;
		$body .= "Content-Type: text/plain; charset=\"iso-8859-1\"" . $eol;
		$body .= "Content-Transfer-Encoding: 8bit" . $eol;
		$body .= $message . $eol;

		$body .= "--" . $separator . $eol;
		$body .= "Content-Type: application/octet-stream; name=\"" . $fileName . "\"" . $eol;
		$body .= "Content-Transfer-Encoding: base64" . $eol;
		$body .= "Content-Disposition: attachment" . $eol;
		$body .= $content . $eol;
		$body .= "--" . $separator . "--";

		if(mail($mailTo, $subject, $body, $headers)){
			$sessionUserName = $_SESSION['ADMIN']['USER'];
			$logFile = fopen(LOG_PATHFILE, 'a') or die("Error creando archivo");
			fwrite($logFile, "\n".date("d/m/Y H:i:s ")."El usuario en sesion ".$sessionUserName. " envió correo a ". $mailTo);
			fclose($logFile);
			return TRUE;
		}
		else
			return FALSE;
	}

	public function sendMailWithoutPdf($servicio, $usuario, $contract, $mailTo, $amount, $expiredDate){
		$separator = md5(time());
		$eol = "\r\n";

		$subject = 'Contrato N° ' . $contract;
		$message = 'Antel 0'. $servicio .' '.$usuario.', importe: $'. $amount.', vence: ' .$expiredDate. '. ';

		$header  = 'MIME-Version: 1.0' . "r\n";
		$header .= 'Content-type:text/html; charset=UTF-8' . "\r\n";
		$header .= "From: antel.byg.uy <antel@byg.uy>" . "\r\n";

		$body = '<html>' .
		'<head>' .
		'<title>Antel</title>' .
		'</head>' .
		'<body><p>'.$message.'</p></body>' .
		'</html>';

		if(mail($mailTo, $subject, $body, $header)){
			$sessionUserName = $_SESSION['ADMIN']['USER'];
			$logFile = fopen(LOG_PATHFILE, 'a') or die("Error creando archivo");
			fwrite($logFile, "\n".date("d/m/Y H:i:s ")."El usuario en sesion ".$sessionUserName. " envió correo a ". $mailTo);
			fclose($logFile);
			return TRUE;
		}
		else
			return FALSE;
	}

	public function setMobilePhoneFormat($number){
		return "0" . substr($number, 0, 2) . " " . substr($number, 2, 3) . " " . substr($number, 5, 3);
	}

	public function setCeroAllAmountContracts(){
		$responseQuery = DataBase::sendQuery("UPDATE `contratos` SET `importe` = null", array(), "BOOLE");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se pudo actualizar el importe de los contratos.";

		return $responseQuery;
	}

	public function clearUltimoArchivoContracts(){
		$responseQuery = DataBase::sendQuery("UPDATE `contratos` SET `ultimoArchivo` = null", array(), "BOOLE");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se pudo actualizar el importe de los contratos.";

		return $responseQuery;
	}

	public function getAllContractsToNotify(){

		$lastNotification = handleDateTime::getDateLastNotification();
		$responseQuery = DataBase::sendQuery("SELECT * FROM `contratos` WHERE (enviarCelular = 1 OR enviarEmail = 1) and ( fechaNotificacion IS NULL or fechaNotificacion < ?)", array('s', $lastNotification), "LIST");
		if($responseQuery->result == 1)
			$responseQuery->message = "No se encontraron contratos.";

		return $responseQuery;
	}
}