function login(){
	let nickName = $('#inputNickName').val() || null;
	let password = $('#inputPassword').val() || null;

	if(nickName){
		if(password){
			let response = sendPost("login", {nickName: nickName, password: password});
			if(response.result == 2)
				window.location.href= getSiteURL();
			else
				showReplyMessage(response.result, response.message, "Iniciar sesión", null);
		}else showReplyMessage(1, "Debe ingresar la contraseña para iniciar sesión", "Contraseña campo requerido", null);
	}else showReplyMessage(1, "Debe ingresar el usuario para iniciar sesión", "Usuario campo requerido", null);
}

function keyEnterPress(eventEnter){
	if(eventEnter.keyCode == 13){
		if(eventEnter.srcElement.id == "inputNickName")
			$('#inputPassword').focus();
		else if(eventEnter.srcElement.id == "inputPassword")
			$('#buttonLogin').focus();
		else if(eventEnter.srcElement.id == "buttonLogin")
			$('#buttonLogin').click();
	}
}