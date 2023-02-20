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
		  CURLOPT_CUSTOMREQUEST => 'GET',
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
			return json_decode($response);
		}
	}
}