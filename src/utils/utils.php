<?php

class utils{

	public function whatsappApiConection($path, $data){
		//$data ejemplo "id=1&content=".$message."&to=598".$mobilePhone
		$data .= "&token=".TOKEN_API;

		error_log(date("ymd His")." ".URL_WHATSAPP_API.$path." ".$data);

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => URL_WHATSAPP_API.$path,//'https://sigecom.uy/middleware/public/txt',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => $data,//'id=1&content=mensaje%200919&to=92459188&token=45ek2wrhgr3rg33m',
		  CURLOPT_HTTPHEADER => array(
		    'Content-Type: application/x-www-form-urlencoded',
		    'Cookie: PHPSESSID=9a1b26da908cb5b5a6a0c1049121a947'
		  ),
		));

		$response = curl_exec($curl);
		$responsejson = json_decode($response);

		curl_close($curl);

		if ( isset($responsejson->result) ){
			return $responsejson;
		}else{
			$response = new \stdClass();
			$response->result = 0;
			return $response;
		}
	}

	public function whatsappApiConectionPost($path, $data){
		//$data ejemplo "id=1&content=".$message."&to=598".$mobilePhone
		$data .= "&token=".TOKEN_API;

		error_log(date("ymd His")." ".URL_WHATSAPP_API.$path." ".$data);

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => URL_WHATSAPP_API.$path,//'https://sigecom.uy/middleware/public/txt',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => $data,//'id=1&content=mensaje%200919&to=92459188&token=45ek2wrhgr3rg33m',
		  CURLOPT_HTTPHEADER => array(
		    'Content-Type: application/x-www-form-urlencoded',
		    'Cookie: PHPSESSID=9a1b26da908cb5b5a6a0c1049121a947'
		  ),
		));

		$response = curl_exec($curl);
		$responsejson = json_decode($response);

		curl_close($curl);

		if ( isset($responsejson->result) ){
			return $responsejson;
		}else{
			$response = new \stdClass();
			$response->result = 0;
			return $response;
		}
	}



	public function whatsapp($path, $data){

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, URL_WHATSAPP_API.$path );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_POST, 1);
		curl_setopt( $ch, CURLOPT_TIMEOUT_MS, 14950);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $ch );
		$responsejson = json_decode($response);

		curl_close($ch);

		if ( isset($responsejson->result) ){
			return $responsejson;
		}else{
			$response = new \stdClass();
			$response->result = 0;
			return $response;
		}

	}

}