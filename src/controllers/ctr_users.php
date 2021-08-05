<?php

require_once '../src/clases/users.php';

class ctr_users{

	public function signOut(){
		$response = new \stdClass();

		if(session_destroy()){
			$response->result = 2;
			$response->message = "Cerró sesión correctamente.";
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y no se cerró sesión, intentelo nuevamente.";
		}

		return $response;
	}

	public function login($nickName, $password){
		$response = new \stdClass();

		$responseGetUser = users::getUserWithNickName($nickName);
		if($responseGetUser->result == 2){
			if(!is_null($responseGetUser->objectResult->pass)){
				if(strcmp($responseGetUser->objectResult->pass, $password) == 0){
					$responseUpdateSesion = users::updateUserToken($responseGetUser->objectResult->id);
					if($responseGetUser->result == 2){
						$response->result = 2;
					}else{
						$response->result = 0;
						$response->message = "Ocurrió un error y la sesión no fue ingresada correctamente.";
					}
				}else{
					$response->result = 0;
					$response->message = "El usuario y contraseña ingresados no coinciden.";
				}
			}else{
				$responseSignIn = users::updatePassword($responseGetUser->objectResult->id, $password);
				if($responseSignIn->result == 2){
					$responseUpdateFirstSession = users::updateUserToken($responseGetUser->objectResult->id);
					if($responseUpdateFirstSession->result == 2){
						$response->result = 2;
					}else{
						$response->result = 0;
						$response->message = "Ocurrió un error y no pudo iniciar sesión por primera vez en el sistema.";
					}
				}else return $responseSignIn;
			}
		}else return $responseGetUser;

		return $response;
	}

	public function validateCurrentSession(){
		$response = new \stdClass();

		if(isset($_SESSION['ADMIN'])){
			$session = $_SESSION['ADMIN'];
			$responseGetUser = users::getUserWithNickName($session['USER']);
			if($responseGetUser->result == 2){
				if(strcmp($responseGetUser->objectResult->token, $session['TOKEN']) == 0){
					$response->result = 2;
				}else{
					$response->result = 0;
					$response->message = "Su sesión caducó, vuelva a ingresar.";
				}
			}else{
				$response->result = 0;
				$response->message = "Ocurrió un error y el usuario de su sesión no fue encontrado en la base de datos.";
			}
		}else{
			$response->result = 0;
			$response->message = "No hay un usuario en sesión actualmente.";
		}

		if($response->result == 0)
			session_destroy();

		return $response;
	}

}