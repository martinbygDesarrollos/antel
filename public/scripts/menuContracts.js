/////////////////////////////////////////////////////////
//VARIABLES

/////////////////////////////////////////////////////////
//FUNCIONES SIN NOMBRE

/////////////////////////////////////////////////////////
//FUNCIONES CON NOMBRE

//boton para borrar pdfs del sistema y la columna de ultimo  archivo de los contratos
function deleteAllPdfContracts(){
	let thenColumnLastFile = false;
	let thenFile = false;

	showReplyMessage(1, "Borrar todos los documentos anteriores?", "", null);
	$("#modalButtonResponse").click(()=>{
		console.log("a borrar pdfs");
		$('#btnDeletePdfContracts').prop('disabled', true);

		folderPath = ["public", "files", "movil"];
		sendAsyncPost("clearFolderContracts", {path: folderPath})
		.then((response)=>{
			console.log("respuesta movil");
			console.log(response);
			thenFile = true;
			if ( thenColumnLastFile && thenFile ){
				$('#btnDeletePdfContracts').prop('disabled', false);
				console.log("desbloquear");
				window.location.reload();
			}
		})
		sendAsyncPost("clearUltimoArchivoContracts")
		.then((response)=>{
			console.log("respuesta columna ultimo archivo");
			console.log(response);
			thenColumnLastFile = true;
			if ( thenColumnLastFile && thenFile ){
				$('#btnDeletePdfContracts').prop('disabled', false);
				console.log("desbloquear");
				window.location.reload();
			}
		})
	});
}

//borrar datos que tienen que ver con el zim xml
function deleteAllAmountContracts(){
	let thenAmounts = false;
	let thenFile = false;

	showReplyMessage(1, "Está seguro de borrar todos los importes y archivos xml que se encuentren?", "Confirme", null);
	$("#modalButtonResponse").click(()=>{
		$('#btnDeleteAmountContracts').prop('disabled', true);

		console.log("a borrar importes");
		console.log("a borrar xml");

		sendAsyncPost("deleteAllAmountContracts")
		.then((response)=>{
			console.log(response);
			thenAmounts = true;
			if ( thenAmounts && thenFile ){
				$('#btnDeleteAmountContracts').prop('disabled', false);
				console.log("desbloquear");
				window.location.reload();
			}
			if ( response.result != 2 ){
				showReplyMessage(response.result, response.message, "Contratos", null);
			}
		})
		folderPath = ["public", "files", "contratos"];
		sendAsyncPost("clearFolderContracts", {path: folderPath})
		.then((response)=>{
			console.log("respuesta contratos");
			console.log(response);
			thenFile = true;
			if ( thenAmounts && thenFile ){
				$('#btnDeleteAmountContracts').prop('disabled', false);
				console.log("desbloquear");
				window.location.reload();
			}
		})
	});
}