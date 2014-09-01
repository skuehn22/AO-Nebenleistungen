var allClearDialog;
var singleClear;
var singleProduct;

$(document).ready(function(){

    // abbrechen löschen
    $(".allClearNo").click(function(){
        $(".infoWarnung").css('visibility','hidden');
        $('#mask').hide();

        return false; // verhindert submit
    });

    // löschen aller Einträge
    $(".warenkorb_leeren").click(function(){
        gesamtenWarenkorbLeeren(this);

        return false;
    });

    // löschen einer Rate
    $(".singleRateButton").click(function(){
        clearSingleRate(this);

        return false;
    });

    // löschen eines Hotel Produktes
    $(".singleProductButton").click(function(){
        clearSingleProduct(this);

        return false;
    });

    // löschen eines Programmes
    $(".singleProgrammButton").click(function(){
        clearSingleProgramm(this, 'loeschen');

        return false;
    });

     // stornieren eines Programmes
    $(".stornoProgrammButton").click(function(){
        stornoSingleProgramm(this, 'stornieren');

        return false;
    });

    // Gruppenname
    $("#gruppenname").attr('description',informationGruppenname);

    $("#gruppenname").mouseover(function(){
        var gruppenbeschreibung = $("#" + this.id).attr('description');
        $('#' + this.id).validationEngine('showPrompt', gruppenbeschreibung, 'pass', true);
    });

    $("#gruppenname").mouseout(function(){
        $('#gruppenname').validationEngine('hideAll');
    });

    // Buchungshinweis
    $("#buchungshinweis").attr('description',informationBuchungshinweis);

    $("#buchungshinweis").mouseover(function(){
        var buchungshinweisbeschreibung = $("#" + this.id).attr('description');
        $('#' + this.id).validationEngine('showPrompt', buchungshinweisbeschreibung, 'pass', true);
    });

    $("#buchungshinweis").mouseout(function(){
        $('#buchungshinweis').validationEngine('hideAll');
    });

});

// gesamten Warenkorb leeren
function gesamtenWarenkorbLeeren(button){

    var boxWidth = $("#allClear").width();
    var boxHeight = $("#allClear").height();

    var lage = $(button).offset();

    hoehe = lage.top - (boxHeight / 2);
    breite = lage.left - (boxWidth / 2);

    var action = "/front/stornierung/warenkorb-delete/";
    $("#allClearAction").attr('action', action);

    $("#allClear").animate({left: breite, top: hoehe},'slow');

    showMask();

    $("#allClear").css('visibility','visible');

    return;
}

// Bestätigungsabfrage löschen einer Rate
function clearSingleRate(rate){

    var lage = $(rate).position();

    var id = rate.id;
    var action = "/front/warenkorb/loeschensinglerate/buchungstabelle/" + id + "/";
    $("#singleClearAction").attr('action', action);

    box = boxMittig("#singleClear", lage.left, lage.top);
    showMask();

    $("#singleClear").css('visibility','visible');

    return;
}

// Bestätigungsabfrage löschen eines Hotel Produktes
function
    clearSingleProduct(product){

    var lage = $(product).position();

    var teile = product.id.split('_');

    // ermitteln ID des produktes
    var idZusatzprodukt = teile[1];

    // ermitteln ID der Teilrechnung
    var idTeilrechnung = teile[2];

    var action = "/front/warenkorb/delete/idZusatzprodukt/" + idZusatzprodukt + "/teilrechnungId/" + idTeilrechnung;
    $("#singleClearProduct").attr('action', action);

    box = boxMittig("#singleProduct", lage.left, lage.top);
    showMask();

    $("#singleProduct").css('visibility','visible');

    return;
}

// löschen eines Programmes
function clearSingleProgramm(programm, action){

    var tblProgrammbuchungId = programm.id;
    var lage = $(programm).position();

    // Action der Form
    var action = "/front/stornierung/artikel-delete/bereich/1/idBuchungstabelle/" + tblProgrammbuchungId + "/typ/loeschen";
    $("#singleClearProgramm").attr('action', action);

    // Betreffzeile
    $("#programmAction").html('Wollen Sie dieses Programm wirklich löschen?');


    boxMittig("#singleProgramm", lage.left, lage.top);
    showMask();

    $("#singleProgramm").css('visibility','visible');

    return;
}

// stornieren eines Programmes
function stornoSingleProgramm(programm, action){

    var tblProgrammbuchungId = programm.id;
    var lage = $(programm).position();

    // Action der Form
    var action = "/front/stornierung/artikel-delete/bereich/1/idBuchungstabelle/" + tblProgrammbuchungId + "/typ/stornieren";
    $("#singleClearProgramm").attr('action', action);

    // Betreffzeile
    $("#programmAction").html('Teilstornierungen bestätigen Sie bitte durch eine abschließende Anpassung Ihrer Buchung.<br><br> ' +
        'Wollen Sie diesen Programmpunkt wirklich stornieren?');


    boxMittig("#singleProgramm", lage.left, lage.top);
    showMask();

    $("#singleProgramm").css('visibility','visible');

    return;
}

// Bild mittig zentrieren
// und sichtbar im Screen
function boxMittig(boxId, left, top)
{
    var breite = $(boxId).innerWidth();
    $(boxId).css({left: left - breite - 10, top: top});

    return;
}