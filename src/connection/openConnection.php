<?php
include_once "../src/config.php";
if (class_exists('DataBase'))
	return;

class DataBase {
	public static function connection(){

		static $connection = null;
		if (null === $connection) {
			$connection = new mysqli(DB_HOST, DB_USR, DB_PASS, DB_DB)
			or die("No se puede conectar con el servidor");
		}
		$connection->set_charset("utf8");
		return $connection;
	}

	public function sendQuery($sql, $params, $tipoRetorno){
		$response = new \stdClass();

		$connection = DataBase::connection();
		if($connection){
			$query = $connection->prepare($sql);

			$paramsTemp = array();
			if($params){
				foreach($params as $key => $value)
					$paramsTemp[$key] = &$params[$key];

				call_user_func_array(array($query, 'bind_param'), $paramsTemp);
			}

			if($query->execute()){
				$result = $query->get_result();

				if($tipoRetorno == "LIST"){
					$arrayResult = array();
					while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
						$arrayResult[] = $row;
					}
					if(sizeof($arrayResult) > 0){
						$response->result = 2;
						$response->listResult = $arrayResult;
					}else $response->result = 1;
				}else if($tipoRetorno == "OBJECT") {
					$objectResult = $result->fetch_object();
					if(!is_null($objectResult)){
						$response->result = 2;
						$response->objectResult = $objectResult;
					}else $response->result = 1;
				}else if($tipoRetorno == "BOOLE"){
					$response->result = 2;
					$response->id = $connection->insert_id;
				}
			}else{
				$response->result = 0;
				if(strpos($query->error, "Duplicate") !== false){
					$msjError = $query->error;
					$msjError = str_replace("Duplicate entry", "BASE DE DATOS: El valor ", $msjError);
					$msjError = str_replace(" for key", " ya fue ingresado previamente para el campo ", $msjError);
					$response->message = $msjError . "(dato único)";
				}else if(strpos($query->error, "Column") !== false){
					$msjError = $query->error;
					$msjError = str_replace("Column", "BASE DE DATOS: La columna", $msjError);
					$msjError = str_replace("cannot be", "no puede ser", $msjError);
					$response->message = $msjError;
				}else{
					$response->message = "BASE DE DATOS: " . $query->error;
				}
			}
		}else{
			$response->result = 0;
			$response->message = "Ocurrió un error y no se pudo acceder a la base de datos del sistema.";
		}
		return $response;
	}
}
