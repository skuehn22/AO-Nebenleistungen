var formularFeldMailadresse = null;

$(document).ready(function () {

    // Kontrolle Formular während Submit
    $("#personaldata").click(function () {
        // Formular ist valid
        if ($("#formPersonalData").validationEngine('validate')) {
            $("#formPersonalData").validationEngine().submit();
        }
        // Formular nicht valid
        else {
            $("#formPersonalData").validationEngine('detach');
            $("#formPersonalData").validationEngine().submit();
        }
    });

    // Kontrolle Formular Personendaten
    var kontrolle = $("#pruefenPersonalData").validationEngine();

    // Kontrolle Formular Anmeldung
    var kontrolle = $("#loginForm").validationEngine();

    // Kontrolle das Mailadrese noch nicht vorhanden ist
//    $("#email").blur(function () {
//        formularFeldMailadresse = 'email';
//        kontrolleAufVorhandeneMailadresse(this);
//    });

    // Kontrolle das Mailadresse vorhanden ist
//    $("#login_email").blur(function () {
//        formularFeldMailadresse = 'login_email';
//        kontrolleAufVorhandeneMailadresse(this);
//    });

});

//function kontrolleAufVorhandeneMailadresse(input) {
//    $.ajax({
//        url: "/front/personaldata/checkexistmail/",
//        type: "POST",
//        data: {
//            mail: input.value
//        },
//        success: function (response) {
//
//            // doppelte Mailadresse, doppelteEmailAdresse
//            if(formularFeldMailadresse == 'email' && response != '0' ){
//                $("#email").val('');
//                $('#email').validationEngine('showPrompt', doppelteEmailAdresse, 'pass');
//                $("#email_repeat").val('');
//            }
//
//            // unbekannte Mailadresse, unbekanntEmailAdresse
//            if(formularFeldMailadresse == 'login_email' && response == '0' ){
//                $("#login_email").val('');
//                $('#login_email').validationEngine('showPrompt', unbekanntEmailAdresse, 'pass');
//                $("#login_passwort").val('');
//            }
//        }
//    });
//}