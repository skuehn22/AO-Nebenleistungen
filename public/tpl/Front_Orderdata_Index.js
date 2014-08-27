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
    var element = document.getElementById("formOrderData");
    var formOrderData = $(element);
    formOrderData.attr('action', "/front/orderdata/edit/status/3/agb/agb");
    formOrderData.submit();

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

