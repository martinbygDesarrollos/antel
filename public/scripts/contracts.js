let lastId = 0;
let textToSearch = null;
let groupSelected = 0;
let checkedActive = 0;

/////////////////////////////////////////////////////////
//obtener datos de contratos
function getListContracts(){
	let response = sendPost("getListContracts", {lastId: lastId, textToSearch: textToSearch, group: groupSelected, checkedActive: checkedActive});
	if(response.result == 2){
		if(lastId != response.lastId)
			lastId = response.lastId;
		let list = response.listResult;
		for(let i = 0; i < list.length; i++){
			//console.log(list[i]);
			let row = createRow(list[i].id, list[i].grupo, list[i].usuario, list[i].contrato, list[i].importe, list[i].celular, list[i].celularEnvio, list[i].enviarCelular, list[i].email, list[i].enviarEmail, list[i].fechaNotificacion, list[i].ultimoArchivo);
			$('#tbodyContracts').append(row);
		}
	}else if(response.result == 0){
		showReplyMessage(response.result, response.message, "Listar contratos", null);
	}
}

function createRow(id, group, user, contract, importe, mobilePhone, mobilePhoneToSend, activeMobile, email, activeEmail, dateNotification, ultimoArchivo){

	let row = "<tr id='" + id + "' >";
	row += "<td class='text-right'>" + contract + "</td>";
	row += "<td class='text-right'>" + importe + "</td>";
	row += "<td class='text-right'>" + user + "</td>";
	row += "<td class='text-center'>" + group + "</td>";
	row += "<td class='text-right'>" + mobilePhone + "</td>";
	row += "<td class='text-right'>" + mobilePhoneToSend + "</td>";

	isCheckedM = '';
	if(activeMobile == 1)
		isCheckedM = 'checked';
	row += "<td class='text-center'><label class='switch'><input type='checkbox' id='mobile" + id + "' onchange='changeNotificationStatusMobile(" + id + ")' " + isCheckedM +"><span class='slider round'></span></label></td>";
	row += "<td class='text-right'>" + email + "</td>";

	isCheckedE = '';
	if(activeEmail == 1)
		isCheckedE = 'checked';

	row += "<td class='text-center'><label class='switch'><input type='checkbox' id='email" + id + "' onchange='changeNotificationStatusEmail(" + id + ")' " + isCheckedE +"><span class='slider round'></span></label></td>";
	row += "<td class='text-right'>" + dateNotification + "</td>";

	//boton para descargar pdf
	if (ultimoArchivo)
		row += "<td class='text-right'><button class='btn btn-link' onclick='downloadFile(`"+ultimoArchivo+"`)'><i class='fas fa-download'></i></button></td>";
	else
		row += "<td class='text-right'><button class='btn btn-link' title='No hay archivo cargado' disabled><i class='fas fa-download'></i></button></td>";

	let titleToolTip = "Enviar factura.";
	if(user != "No especificado.")
		titleToolTip = "Modificar información de " + user + ".";

	row += "<td class='text-center text-primary'><button class='btn btn-link' onclick='showModalUpdateContract(" + id + ")' data-toggle='tooltip' data-placement='right' title='" + titleToolTip + "'><i class='fas fa-user-edit'></i></button>";

	titleToolTip = "Enviar factura.";
	if(user != "No especificado.")
		titleToolTip = "Enviar factura a " + user + ".";

	row += "<button class='btn btn-link' onclick='showModalSendOneNotificacion(" + id + ")' data-toggle='tooltip' data-placement='right' title='" + titleToolTip + "'><i class='fas fa-paper-plane'></i></button>";
	row += "<button class='btn btn-link' onclick='showModalDeleteContract(" + id + ")' data-toggle='tooltip' data-placement='right' title='Borrar contrato'><i class='fas fa-trash-alt'></i></button>";

	row += "</td></tr>";

	return row;
}

/////////////////////////////////////////////////////////
//descargar documento
function generateExcel(){
	let response = sendPost("generateExcel", null);
	if(response.result == 2){
		const linkSource = `data:application/vnd.ms-excel;base64,${ response.excel }`;
		const downloadLink = document.createElement("a");
		const fileName = "contratos_antel.xlsx";
		downloadLink.href = linkSource;
		downloadLink.download = fileName;
		downloadLink.click();
	}
}

/////////////////////////////////////////////////////////
//eliminar contrato
function showModalDeleteContract(idContract){
	$('#modalDeleteContract').modal();
	$('#buttonConfirmDelete').off('click');
	$('#buttonConfirmDelete').click(function(){
		deleteContractSelected(idContract);
	});
}

function deleteContractSelected(idContract){
	let response = sendPost('deleteContractSelected', {idContract: idContract});
	showReplyMessage(response.result, response.message, "Borrar contrato", "modalDeleteContract");
	if(response.result == 2)
		$('#' + idContract).remove();
}

/////////////////////////////////////////////////////////
//filtros de busqueda
function filterGroup(){
	let selectGroup = $('#selectGroup');

	if(selectGroup.is(':visible')){
		groupSelected = selectGroup.val();
		lastId = 0;
		$('#tbodyContracts').empty();
		getListContracts();
	}
}

function filterNotificationActive(){
	let check = $('#inputCheckActive').is(':checked');
	if(check)
		checkedActive = 1;
	else
		checkedActive = 0;

	lastId = 0;
	$('#tbodyContracts').empty();
	getListContracts();
}

/////////////////////////////////////////////////////////
//enviar pdf de un contrato
function showModalSendOneNotificacion(idContract){
	let response = sendPost('getContractWithID', {idContract: idContract});
	console.log(response);
	if(response.result == 2){
		if(response.contract.enviarEmail == 1 || response.contract.enviarCelular == 1){
			$('#modalSendNotification').modal('hide');
			$('#modalOnLoad').modal({backdrop: 'static', keyboard: false})
			$('#modalOnLoad').modal();
			$('#textModalOnLoad').html("Se esta enviando el contrato al cliente " + response.contract.usuario + "...");

			sendAsyncPost('notifyOneContract', {idContract: idContract})
			.then(function(response){
				$('#modalOnLoad').modal('hide');
				showReplyMessage(response.result, response.message, "Enviar notificación", null);
				if ( response.result == 2 ){
					console.log("El proceso de envío de ANTEL, terminó correctamente.");
					notifyProcessFinished("El proceso de envío de ANTEL, terminó correctamente.");
				}else{
					console.log("El proceso de envío de ANTEL, terminó con error.");
					console.log(response);
					notifyProcessFinished("El proceso de envío de ANTEL, terminó con error. "+response.message);
				}
			})
			.catch(function(response){
				console.log("El proceso de envío de ANTEL, terminó con error.");
				console.log(response);
				notifyProcessFinished("El proceso de envío de ANTEL, terminó con error. "+response.message);
				$('#modalOnLoad').modal('hide');
				showReplyMessage(response.result, response.message, "Enviar notificación", null);
			})
		}else showReplyMessage(1, "El usuario seleccionado no tiene un medio de notificación activo.", "Notificaciones desactivadas", null);
	}else showReplyMessage(response.result, response.message, "Contrato no encontrado", null);
}
/////////////////////////////////////////////////////////
//enviar pdf de todos los contratos
function sendNotificaion(){
	$('#modalSendNotification').modal('hide');
	$('#modalOnLoad').modal({backdrop: 'static', keyboard: false})
	$('#modalOnLoad').modal();
	$('#textModalOnLoad').html("Se están enviando los contratos...");
	sendAsyncPost("notifyAllContract", null)
	.then(function(response){
		console.log(response);
		if ( response.result == 2 ){
			console.log("El proceso de envío de ANTEL, terminó correctamente.");
			notifyProcessFinished("El proceso de envío de ANTEL, terminó correctamente.");
		}else{
			console.log("El proceso de envío de ANTEL, terminó con error.");
			console.log(response);
			notifyProcessFinished("El proceso de envío de ANTEL, terminó con error. "+response.message);
		}
		$('#modalOnLoad').modal('hide');
		showReplyMessage(response.result, response.message, "Enviar notificación", null);
	})
	.catch(function(response){
		console.log("El proceso de envío de ANTEL, terminó con error.");
		notifyProcessFinished("El proceso de envío de ANTEL, terminó con error. "+response.message);
		console.log(response);
		$('#modalOnLoad').modal('hide');
		showReplyMessage(response.result, response.message, "Enviar notificación", null);
	})
}

/////////////////////////////////////////////////////////
//subir archivos
function sendFile(){
	let file = $('#inputFileToLoad').prop('files');
	if(file.length != 0){

		let typeFile = file[0].type;
		let nameFile = file[0].name;

		$('#modalLoadFile').modal('hide');
		$('#modalOnLoad').modal({backdrop: 'static', keyboard: false})
		$('#modalOnLoad').modal();
		$('#textModalOnLoad').html("Se está descomprimiendo el archivo...");
		getBase64(file[0]).then(function(value){
			let data = {
				nameFile: nameFile,
				typeFile: typeFile,
				data: value
			}
			sendAsyncPost("loadFileToSend", data)
			.then(function(response){
				$('#modalOnLoad').modal('hide');
				showReplyMessage(response.result, response.message, "Enviar facturas", null);
				if(response.result != 0){
					lastId = 0;
					$('#tbodyContracts').empty();
					getListContracts();
				}

			})
			.catch(function(){
				$('#modalOnLoad').modal('hide');
				showReplyMessage(0, "Ocurrió un error por lo que no pudo finalizar la operacion correctamente.", "Enviar facturas", null);
			});
		});
	}else showReplyMessage(1, "Debe seleccionar el .zip 'detalle_facturas' o 'facturas_movil'", "Archivo zip requerido", "modalLoadFile");
}

/////////////////////////////////////////////////////////
//activar/desactivar notificaciones
function changeNotificationStatusEmail(idContract){
	let response = sendPost('changeNotificationStatus', {idContract: idContract, typeNotification: "EMAIL"});
	if(response.result != 2){
		showReplyMessage(response.result, response.message, "Notificaciones", null);
		if($('#email' + idContract).is(':checked'))
			$('#email' + idContract).prop('checked', false);
		else
			$('#email' + idContract).prop('checked', true);
	}
}

function changeNotificationStatusMobile(idContract){
	let response = sendPost('changeNotificationStatus', {idContract: idContract, typeNotification: "MOBILE"});
	if(response.result != 2){
		showReplyMessage(response.result, response.message, "Notificaciones", null);
		if($('#mobile' + idContract).is(':checked'))
			$('#mobile' + idContract).prop('checked', false);
		else
			$('#mobile' + idContract).prop('checked', true);
	}
}

/////////////////////////////////////////////////////////
//busqueda
function searchUser(){
	let textTemp = $('#inputToSearch').val() || null;
	if(textTemp){
		if(textTemp.length > 2){
			$('#tbodyContracts').empty();
			textToSearch = textTemp;
			lastId = 0;
			getListContracts();
			return;
		}
	}
	$('#tbodyContracts').empty();
	textToSearch = null;
	lastId = 0;
	getListContracts();
}

/////////////////////////////////////////////////////////
//cambiar datos de contratos
function showModalUpdateContract(idContract){
	let responseGetContract = sendPost('getContractWithID', {idContract: idContract});
	if(responseGetContract.result){
		$('#modalContract').modal();

		$('#inputNameContract').val(responseGetContract.contract.usuario);
		$('#inputMobileContract').val(responseGetContract.contract.celular);
		$('#inputContractContract').val(responseGetContract.contract.contrato);
		$('#inputGroupContract').val(responseGetContract.contract.grupo);
		$('#inputEmailNotificationContract').val(responseGetContract.contract.email);
		$('#inputMobileNotificationContract').val(responseGetContract.contract.celularEnvio);

		$('#modalTitleContract').html("Modificar contrato");

		$('#buttonModalContract').off('click');
		$('#buttonModalContract').click(function(){
			updateContract(idContract);
		});
	}else showReplyMessage(responseGetContract.result, responseGetContract.message, "Modificar contrato", null);
}

function updateContract(idContract){
	let name = $('#inputNameContract').val() || null;
	let email = $('#inputEmailNotificationContract').val() || null;
	let mobile = $('#inputMobileContract').val() || null;
	let contract = $('#inputContractContract').val() || null;
	let group = $('#inputGroupContract').val() || null;
	let mobileToSend = $('#inputMobileNotificationContract').val() || null;

	let validateData = validateDataToSend(name, mobile, mobileToSend, contract, group);
	if(!validateData){
		let responseDontRepeatContract = sendPost('validateContractDontRepeat', {idContract: idContract, contract: contract});
		if(responseDontRepeatContract.result == 2){
			let data = {
				idContract: idContract,
				name: name,
				email: email,
				mobile: mobile,
				contract: contract,
				group: group,
				mobileToSend: mobileToSend,
			}
			let responseUpdate = sendPost('updateContract', data);
			showReplyMessage(responseUpdate.result, responseUpdate.message, "Modificar contrato", "modalContract");
			if(responseUpdate.result == 2){
				let updated = responseUpdate.contract;
				let row = createRow(updated.id, updated.grupo, updated.usuario, updated.contrato, updated.importe, updated.celular, updated.celularEnvio, updated.enviarCelular, updated.email, updated.enviarEmail, updated.fechaNotificacion, updated.ultimoArchivo);
				$('#' + idContract).replaceWith(row);
			}
		}else showReplyMessage(responseDontRepeatContract.result, responseDontRepeatContract.message, "Modificar contrato", "modalContract");
	}else{
		showReplyMessage(1, validateData, "Información no valida", "modalContract");
	}
}

/////////////////////////////////////////////////////////
//crear contrato nuevo
function openModalNewContract(){
	$('#modalContract').modal();
	$('#modalTitleContract').html("Agregar contrato");
	$('#buttonModalContract').off('click');
	$('#buttonModalContract').click(function(){
		createNewContract();
	});

	cleanUpContract();
}

$('#modalContract').on('shown.bs.modal', function(){
	$('#inputNameContract').focus();
});

function createNewContract(){
	let name = $('#inputNameContract').val() || null;
	let email = $('#inputEmailNotificationContract').val() || null;
	let mobile = $('#inputMobileContract').val() || null;
	let contract = $('#inputContractContract').val() || null;
	let group = $('#inputGroupContract').val() || null;
	let mobileToSend = $('#inputMobileNotificationContract').val() || null;

	let validateData = validateDataToSend(name, mobile, mobileToSend, contract, group);
	if(!validateData){
		let responseValidateContract = sendPost('validateContractDoesntExist', {contract: contract});
		if(responseValidateContract.result == 2){
			let data = {
				name: name,
				email: email,
				mobile: mobile,
				contract: contract,
				group: group,
				mobileToSend: mobileToSend,
			}
			let responseCreateContract = sendPost('createNewContract', data);
			showReplyMessage(responseCreateContract.result, responseCreateContract.message, "Crear contrato", "modalContract");
			if(responseCreateContract.result == 2){
				let created = responseCreateContract.contract;
				let row = createRow(created.id, created.grupo, created.usuario, created.contrato, created.importe, created.celular, created.celularEnvio, created.enviarCelular, created.email, created.enviarEmail, created.fechaNotificacion, created.ultimoArchivo);
				$('#tbodyContracts').prepend(row);
				cleanUpContract();
			}
		}else{
			showReplyMessage(responseValidateContract.result, responseValidateContract.message, "Contrato ya ingresado", "modalContract");
		}
	}else{
		showReplyMessage(1, validateData, "Información no valida", "modalContract");
	}
}

function validateDataToSend(name, mobile, mobileToSend, contract, group){
	let errorMessage = null;

	if(name){
		if(name.length < 5){
			errorMessage = "El nombre debe tener al menos 6 caracteres para ser considerado valido";
			return errorMessage;
		}
	}else{
		errorMessage = "Debe ingresar el usuario del contrato";
		return errorMessage;
	}

	if(mobile){
		if(mobile.length < 8 || mobile.length > 9){
			errorMessage = "La longitud del celular ingresado no es valida";
			return errorMessage;
		}
	}else{
		errorMessage = "Debe ingresar el celular del contrato";
		return errorMessage;
	}

	if(mobileToSend){
		if(mobileToSend.length < 8 || mobileToSend.length > 9){
			errorMessage = "La longitud del celular de envío ingresado no es valida";
			return errorMessage;
		}
	}

	if(contract){
		if(contract.length < 5){
			errorMessage = "La longitud del contrato ingresado no es valida";
			return errorMessage;
		}
	}else{
		errorMessage = "Debe ingresar el número de contrato";
		return errorMessage;
	}
	if(group == null){
		errorMessage = "Debe ingresar un grupo para el contrato";
		return errorMessage;
	}
}

function cleanUpContract(){
	$('#inputNameContract').val("");
	$('#inputEmailContract').val("");
	$('#inputMobileContract').val("");
	$('#inputContractContract').val("");
	$('#inputGroupContract').val("");
	$('#inputEmailNotificationContract').val("");
	$('#inputMobileNotificationContract').val("");
}

/////////////////////////////////////////////////////////
//enviar documentos
function getBase64(file) {
	return new Promise((resolve, reject) => {
		const reader = new FileReader();
		reader.readAsDataURL(file);
		reader.onload = () => resolve(reader.result);
		reader.onerror = error => reject(error);
	});
}

/////////////////////////////////////////////////////////
//tabs formularios
function keyEnterPress(eventEnter, value, size){
	if(eventEnter.keyCode == 13){
		if(eventEnter.srcElement.id == "inputNameContract")
			$('#inputContractContract').focus();
		else if(eventEnter.srcElement.id == "inputContractContract")
			$('#inputMobileContract').focus();
		else if(eventEnter.srcElement.id == "inputMobileContract")
			$('#inputGroupContract').focus();
		else if(eventEnter.srcElement.id == "inputGroupContract")
			$('#inputMobileNotificationContract').focus();
		else if(eventEnter.srcElement.id == "inputMobileNotificationContract")
			$('#inputEmailNotificationContract').focus();
		else if(eventEnter.srcElement.id == "inputEmailNotificationContract")
			$('#buttonModalContract').click();
	}else if(value != null && value.length == size) {
		return false;
	}
}

function downloadFile( path ){

	let nameFile = path.substring(0, path.length -4)
	window.location.href = getSiteURL() + 'downloadFile.php?n='+nameFile;

}