$(document).ready(function () {

    // geheimer Button Offlinebucher
    $("#offlinebucher").click(function(){
        var attr = $("#formPersonalData").attr('action');

        if(attr == '/front/registrierung/create/offlinekunde/1'){
            $("#formPersonalData").attr('action','/front/registrierung/create/offlinekunde/2');
            $("#informationOfflinekunde").html('x');
        }
        else{
            $("#formPersonalData").attr('action','/front/registrierung/create/offlinekunde/1');
            $("#informationOfflinekunde").html(' ');
        }
    });

    // verhindern der Kontrolle des Inline Editieren
    $("#formPersonalData").validationEngine('attach', {
        binded: true
    });

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

    $("#pruefenPersonalData").validationEngine();

    // Kontrolle auf doppelte Mailadresse
    $("#email").blur(function () {
        kontrolleAufVorhandeneMailadresse(this);
    });

});

function kontrolleAufVorhandeneMailadresse(input) {
    $.ajax({
        url: "/front/registrierung/existiert-mailadresse/",
        type: "POST",
        data: {
            mail: input.value
        },
        success: function (response) {

            // leere Mailadresse, leereEmailAdresse
            if (response == '1414') {
                $("#email").val();
                $("#email_repeat").val('');
                $('#email').validationEngine('showPrompt', leereEmailAdresse, 'pass');
            }

            // doppelte Mailadresse, doppelteEmailAdresse
            if (response == '1416') {
                $("#email").val();
                $("#email_repeat").val('');
                $('#email').validationEngine('showPrompt', doppelteEmailAdresse, 'pass');
            }

            // keine Mailadresse, keineEmailAdresse
            if (response == '1415') {
                $("#email").val();
                $("#email_repeat").val('');
                $('#email').validationEngine('showPrompt', keineEmailAdresse, 'pass');
            }
        }
    });
}