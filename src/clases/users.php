<?php

class users{

	public function updatePassword($idUser, $password){
		return DataBase::sendQuery("UPDATE usuarios SET pass = ? WHERE id = ?", array('si', $password, $idUser), "BOOLE");
	}

	public function getUserWithID($idUser){
		$responseQuery = DataBase::sendQuery("SELECT * FROM usuarios WHERE id = ?", array('i', $idUser), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "El identificador ingresado no corresponde a un usuario registrado en la base de datos.";

		return $responseQuery;
	}

	public function getUserWithNickName($nickName){
		$responseQuery = DataBase::sendQuery("SELECT * FROM usuarios WHERE usuario = ?", array('s', $nickName), "OBJECT");
		if($responseQuery->result == 1)
			$responseQuery->message = "El nombre de usuario no corresponde a uno registrado en la base de datos.";

		return $responseQuery;
	}

	public function updateUserToken($idUser){
		$token = users::generateToken();
		$responseQuery = DataBase::sendQuery("UPDATE usuarios SET token = ? WHERE id = ?", array('si', $token, $idUser), "BOOLE");
		if($responseQuery->result == 2){
			$responseGetUser = users::getUserWithID($idUser);
			if($responseGetUser->result == 2){
				$sesion = array(
					"USER" => $responseGetUser->objectResult->usuario,
					"TOKEN" => $responseGetUser->objectResult->token
				);
				$_SESSION['ADMIN'] = $sesion;
				return $responseGetUser;
			}else return $responseGetUser;
		}else return $responseQuery;
	}

	public function generateToken(){
		$longitud = 150;
		return bin2hex(random_bytes(($longitud - ($longitud % 2)) / 2));
	}
}