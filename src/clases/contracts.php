<?php

require_once '../src/connection/openConnection.php';

class contracts{

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

	public function updateContract($idContract, $name, $email, $mobile, $contract, $group, $mobileToSend){
		return DataBase::sendQuery("UPDATE contratos SET grupo = ?, usuario = ?, contrato = ?, celular = ?, celularEnvio = ?, email = ? WHERE id = ?", array('sssiisi', $group, $name, $contract, $mobile, $mobileToSend, $email, $idContract), "BOOLE");
	}

	public function validateContractDontRepeat($idContract, $contract){
		return DataBase::sendQuery("SELECT * FROM contratos WHERE contrato = ? AND id != ? ", array('si', $contract, $idContract), "OBJECT");
	}

	public function getContractWithID($idContract){
		return DataBase::sendQuery("SELECT * FROM contratos WHERE id = ?", array('i', $idContract), "OBJECT");
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

	public function getListContracts($lastId, $textToSearch){
		if($lastId == 0) $lastId = contracts::getMaxID();

		$sqlToSearch = "";
		if(strlen($textToSearch) > 0){
			if(ctype_digit($textToSearch))
				$sqlToSearch = " AND contrato LIKE '" . $textToSearch . "%'";
			else
				$sqlToSearch = " AND usuario LIKE '" . $textToSearch . "%'";
		}

		$responseQuery = DataBase::sendQuery("SELECT * FROM contratos WHERE id < ? " . $sqlToSearch . " ORDER BY id DESC LIMIT 14", array('i', $lastId), "LIST");
		if($responseQuery->result == 2){
			$newLastId = $lastId;
			$arrayResult = array();
			$notSpecified = "No especificado.";
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

				$arrayResult[] = $row;
			}
			$responseQuery->listResult = $arrayResult;
			$responseQuery->lastId = $newLastId;
		}else if($responseQuery->result == 1){
			$responseQuery->message = "Actualmente no hay contratos ingresados en la base de datos.";
		}

		return $responseQuery;
	}

	public function sendMail($filePath, $fileName, $contract){
		$file = $filePath . $fileName;

		$mailTo = 'martin@hit.com.uy';
		$subject = 'Contrato N° ' . $contract;
		$message = 'Factura por contrato Antel';
		$content = file_get_contents($file);
		$content = chunk_split(base64_encode($content));

		$separator = md5(time());

		$eol = "\r\n";

		$headers = "From: antel.byg.uy <info@antel.byg.uy>" . $eol;
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

		if(mail($mailTo, $subject, $body, $headers))
			return true;
		else
			return false;
	}

	public function setMobilePhoneFormat($number){
		return "0" . substr($number, 0, 2) . " " . substr($number, 2, 3) . " " . substr($number, 5, 3);
	}
}