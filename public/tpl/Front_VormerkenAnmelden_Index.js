/**
 * Unterstützung des Anmeldeformulares
 *
 * + Kontrolle Formular ''
 * + Kontrolle Passwort
 * + Kontrolle E-Mail
 *
 *
 */

$(document).ready(function() {

    $("#formPersonalData").validationEngine(); // Anmelde Formular
    $("#formLogin").validationEngine();  // Login Formular

    $("#email1").blur(function(){
        kontrolleAufVorhandeneMailadresse(this);
    });

    $("#email").blur(function(){
        kontrolleAufVorhandeneMailadresse(this);
    });
});

function kontrolleAufVorhandeneMailadresse(input){

    var email = input.value;
    var inputFeldNameId = input.id;

    if(email.length == 0)
        return;

    $.ajax({
		url: "/front/vormerken-anmelden/mailadresse/",
		type: "POST",
		data: {
		    mail: email
		},
        success: function(benutzerVorhanden)
        {

            // Formular 'Anmeldung', mailAdresseVorhanden
            if(benutzerVorhanden == 'true' && inputFeldNameId == 'email1'){
                $("#email1").val('');
                $('#email1').validationEngine('showPrompt', mailAdresseVorhanden, 'pass');
                $("#email2").val('');
            }
            // Formular 'Login', mailAdresseNichtVorhanden
            else if(benutzerVorhanden != 'true' && inputFeldNameId == 'email'){
                $("#email").val('');
                $('#email').validationEngine('showPrompt', mailAdresseNichtVorhanden, 'pass');
            }

        }
	});
}