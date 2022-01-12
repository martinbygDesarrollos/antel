//cuando se termina el proceso de envío de emails que notifica al celular phoneNotifications que se terminó el provceso
var phoneNotifications = "92459188";

function validateEmail(email) {
	const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(String(email).toLowerCase());
}

function getSiteURL(){
	let url = window.location.href;
	if(url.includes("localhost"))
		return '/antel/public/';
	else
		return '/';
}