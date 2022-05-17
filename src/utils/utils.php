<?php

class utils{

	public function whatsappApiConection($path, $data){
		//$data ejemplo "id=1&content=".$message."&to=598".$mobilePhone

		$curlPetition = curl_init(URL_WHATSAPP_API.$path);
		curl_setopt($curlPetition, CURLOPT_URL, URL_WHATSAPP_API.$path);
		curl_setopt($curlPetition, CURLOPT_POST, true);
		curl_setopt($curlPetition, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curlPetition, CURLOPT_RETURNTRANSFER, true);
		$responseCurl =  curl_exec($curlPetition);
		curl_close($curlPetition);

		return json_decode($responseCurl);
	}
}