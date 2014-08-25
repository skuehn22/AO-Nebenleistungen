$(document).ready(function() {
	$("#formPersonalData").validationEngine();	
	
//	$("#formPersonalData").validationEngine('attach', {
//        onSubmit: function(){
//			sendAjaxControlEmail();
//        }  
//      })
	
	$("#email_repeat").bind('blur', function(){

		if($("#email").val() != $("#email_repeat").val()){
			$("#email_repeat").val('');

			return;
		}

		var email = $("#email_repeat").val();
		if(email.length < 2)
			return;

		sendAjaxControlEmail();

	});
});

function hidePrompt(prompt){
	$(prompt).validationEngine('hide');
	
	return false;
}

function checkDoubleEmail(response){
	var antwort = eval(response);
	if(antwort[0].doubleEmail == true){
		$("#email").val('');
		$("#email_repeat").val('');

		$('#mail_info').validationEngine('showPrompt', doubleEmail, 'error');

		setTimeout('hidePrompt("#mail_info")', 3000);
	}

	return;
}

function sendAjaxControlEmail(){
	var email = $("#email").val();
	
	$.ajax({
		url: "/front/warenkorb/emailcontrol/",
		type: "POST",
		data: {
			email: email
		},
		success: checkDoubleEmail
	});
	
	return true;
}