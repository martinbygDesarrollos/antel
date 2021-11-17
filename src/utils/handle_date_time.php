<?php


class handleDateTime{

	public function getDateLastNotification(){
		date_default_timezone_set('America/Montevideo');
		$date = date('Y-m-d');
		return substr($date, 0, 4) . substr($date, 5, 2);
	}

	public function formatDateBarWithMonth($dateInt){
		return substr($dateInt, 4, 2) . "/" . substr($dateInt, 0, 4);
	}


	public function getFechaVencimiento(){
		date_default_timezone_set('America/Montevideo');
		$date = date('Y-m-d', strtotime("+ 1 day", strtotime(date('Y-m-d'))));
		return "23-" . substr($date, 5, 2)  . "-" . substr($date, 0, 4);
	}
}