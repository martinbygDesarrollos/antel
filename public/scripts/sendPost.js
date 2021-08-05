function sendPost(nombreFuncion, parametros){
	var result = null;
	$.ajax({
		async: false,
		url: getSiteURL() + nombreFuncion,
		type: "POST",
		data: parametros,
		success: function (response) {
			response = response.trim();
			var response = jQuery.parseJSON(response);

			result =  response;
		},
		error: function (response) {
			result = "error"
		},
	});
	return result;
}

function sendAsyncPost(nombreFuncion, parametros){

	return new Promise( function(resolve, reject){
		$.ajax({
			async: true,
			url: getSiteURL() + nombreFuncion,
			type: "POST",
			data: parametros,
			success: function (response) {
				response = response.trim();
				var response = jQuery.parseJSON(response);

				resolve(response);
			},
			error: function (response) {
				result = "error"
			},
		});
	});
}