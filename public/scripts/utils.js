//cuando se termina el proceso de envío de emails que notifica al celular phoneNotifications que se terminó el provceso
var phoneNotifications = "99723666";
//var phoneNotifications = "91249709"; //desarrollo

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

function notifyProcessFinished(message){
	console.log("Por envíar el mensaje que confirma que se terminó el proceso.");
	sendAsyncPost("sendMessage", {message: message, phone:phoneNotifications })
	.then((response)=>{console.log("se notificó el hecho de haber terminado el envio de archivos.");})
	.catch((response)=>{
		console.log(response);
		console.log("NO se notificó el hecho de haber terminado el envio de archivos.");
	});
}