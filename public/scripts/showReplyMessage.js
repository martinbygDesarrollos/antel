function showReplyMessage(typeMessage, message, title, currentModal){
	if(currentModal)
		$('#' + currentModal).modal('hide');

	$('#modalMessageResponse').html(message);
	$('#modalTitleResponse').html(title);
	$('#modalColourResponse').removeClass('alert-success');
	$('#modalColourResponse').removeClass('alert-warning');
	$('#modalColourResponse').removeClass('alert-danger');

	if(typeMessage == 0)
		$('#modalColourResponse').addClass('alert-danger');
	else if(typeMessage == 2)
		$('#modalColourResponse').addClass('alert-success');
	else if(typeMessage == 1)
		$('#modalColourResponse').addClass('alert-warning');

	$('#modalButtonResponse').off('click');
	$('#modalButtonResponse').click(function(){
		$('#modalResponse').modal('hide');
		if(currentModal && typeMessage != 2){
			$('#' + currentModal).modal();
		}
	});

	$("#modalResponse").modal();
}