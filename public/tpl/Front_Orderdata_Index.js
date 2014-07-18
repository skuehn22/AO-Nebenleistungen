$(document).ready(function() {
	$("#formOrderData").validationEngine();

    $("#vormerken").click(vormerken);
    $("#bearbeiten").click(bearbeiten);
});

// vormerken der Buchung
function vormerken(){
    var formular = $("#formOrderData");
    formular.attr('action', "/front/orderdata-vormerken/edit/status/2");

    return true;
}

// kostenpflichtig buchen
function buchenAgbAnzeigen()
{
    $.ajax({
        url: "/front/Agb/fenster/",
        type: "POST",
        success: function (response)
        {
            var mask = $("#mask");

            var styles = {
                padding: '10px',
                width: '1000px',
                height: '700px',
                overflow: 'scroll',
                backgroundColor: '#fff'
            };

            mask.css(styles);
            mask.html(response);

            var modalStyle = {
                close: false
            };

            mask.modal(modalStyle);
        }
    });

    return;
}

function buchen()
{
    var formular = $("#formOrderData");
    formular.attr('action', "/front/orderdata/edit/status/3");

    return true;
}



// zum Warenkorb / bearbeiten
function bearbeiten(){
    var formular = $("#formOrderData");
    formular.attr('action', "/front/warenkorb/");

    return true;
}

