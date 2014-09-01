/**
 * schalten Sichtbarkeit der Programmvarianten
 */
function sichtbarkeitProgrammvarianten(){
    var j = 0;

    for(var i = 0; i <= anzahlPreisvarianten; i++){
        var anzahl = $("#" + i).val();
        anzahl = parseInt(anzahl);

        var nummerProgrammvariante = $("#programmvariante_" + i).val();
        nummerProgrammvariante = parseInt(nummerProgrammvariante);

        if(anzahl > 0 && nummerProgrammvariante > 0){
            j = i + 1;
            $("#zeile" + j).show();
        }

    }
}

function positionBuchung(){
    var buchungsblock = $("#buchungsblock");
    var offsetBuchungsblock = buchungsblock.offset();
    var heightBuchungsblock = buchungsblock.height();
    // var heightBuchungsblock = buchungsblock.innerHeight();
    var positionBuchungsblockUnten = offsetBuchungsblock.top + heightBuchungsblock;

    // console.log("Höhe Buchungsblock: " + heightBuchungsblock);

    var buchung = $("#buchung");
    var offsetBuchung = buchung.offset();
    // var heightBuchung = buchung.height();
    var heightBuchung = buchung.innerHeight();
    var positionBuchungUnten = offsetBuchung.top + heightBuchung;

    var diffHoehe = positionBuchungsblockUnten - positionBuchungUnten;
    buchung.height(heightBuchung + diffHoehe);

    return;
}

// Reaktion auf wechsel der Personenanzahl
function wechselPersonenanzahl(rowId, anzahl){
  //alert(rowId);


    //var selectedIndex = $("#select" + "programmvariante_" + rowId).attr("selectedIndex");
    //var selectedIndex = $ddlHeader.attr('selectedIndex', 0);

    var selectedIndex = $("#programmvariante_" + rowId).prop("selectedIndex");
    //alert(selectedIndex);

    //var idid = $("#programmvariante_1 option:selected").attr("value");
    //alert(idid);


      //selectedIndex = "2";
//    if(selectedIndex == 0 && gebuchtesDatum == 0){
//        var leerPreis = kaufmRunden(0.00);
//        $("#preis_" + rowId).html(leerPreis);
//        $("#einzelpreis_" + rowId).html(leerPreis);
//
//        return false;
//    }
//
//    selectedIndex--;

   //alert(selectedIndex);

   //select#programmvariante_1999

    var einzelPreis = preiseProgrammVarianten[selectedIndex];
    //alert(einzelPreis);

    // berechneter Gesamtpreis
    var gesamtPreisNummerisch = parseInt(anzahl) * parseFloat(einzelPreis);
    var gesamtPreis = kaufmRunden(gesamtPreisNummerisch);
    $("#preis_" + rowId).html(gesamtPreis);

    // Einzelpreis
    einzelPreis = kaufmRunden(einzelPreis);
    $("#einzelpreis_" + rowId).html(einzelPreis);





    return gesamtPreisNummerisch;
}

// Verrechnung der Buchungspauschale
function buchungspauschaleVerrechnen(gesamtpreis)
{
    var buchungspauschaleFloat = parseFloat(preisBuchungspauschale);
    var gesamtpreisFloat = parseFloat(gesamtpreis);

    if(buchungspauschale == '2'){
        gesamtpreis = gesamtpreisFloat + buchungspauschaleFloat;
    }

    return gesamtpreis;
}

// Berechnung des Gesamtpreises
// darstellen Absende Button
function berechnungGesamtpreis(){
    var gesamtpreis = 0;
    var gesamtAnzahl = 0;

    for(var i = 0; i < 5; i++){
        var anzahl = $("#" + i).val();
        anzahl = parseInt(anzahl);
        gesamtAnzahl += anzahl;

        if(anzahl > 0)
            gesamtpreis += wechselPersonenanzahl(i, anzahl);
    }

    gesamtpreis = parseFloat(gesamtpreis);
    gesamtpreis = kaufmRunden(gesamtpreis);

    // Buchungspauschale = 2, wird verrechnet
    gesamtpreis = buchungspauschaleVerrechnen(gesamtpreis);

    $('#gesamtpreis').html(gesamtpreis);

    gesamtpreis = parseFloat(gesamtpreis);

    // Absendebutton
    if( (gesamtAnzahl > 0) || ((gesamtpreis > 0) || (gesamtpreis < 0)) )
        $("#submitButton").show();
    else
        $("#submitButton").hide();
}

function buildDatepicker(){
	$.datepicker.regional['de'] = {
        clearText: 'löschen', clearStatus: 'aktuelles Datum löschen',
        closeText: 'schließen', closeStatus: 'ohne Änderungen schließen',
        prevText: '&#x3c;zurück', prevStatus: 'letzten Monat zeigen',
        nextText: 'Vor&#x3e;', nextStatus: 'nächsten Monat zeigen',
        currentText: 'heute', currentStatus: '',
        monthNames: ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
        monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'],
        monthStatus: 'anderen Monat anzeigen',
        yearStatus: 'anderes Jahr anzeigen',
        weekHeader: 'Wo',
        weekStatus: 'Woche des Monats',
        dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
        dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
        dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
        dayStatus: 'Setze DD als ersten Wochentag', dateStatus: 'Wähle D, M d',
        dateFormat: 'dd.mm.yy',
        firstDay: 1,
        initStatus: 'Wähle ein Datum'};
	 
	$.datepicker.regional['en'] = {
	    dateFormat: 'dd/mm/YYYY'
    };
	 
    $.datepicker.setDefaults($.datepicker.regional[language]);

    // Darstellung der Tag im Datepicker
    $("#datepicker").datepicker({
    	minDate: new Date(fromJahr, fromMonat -1, fromTag),
        maxDate: new Date(toJahr, toMonat -1, toTag),
        isRTL: false,
        firstDay: 1,
        altField: '#alternateDatum',
        beforeShowDay: function(date){

            var monat = date.getMonth();
            monat++;

            // führende Null des Monats
            if(monat < 10)
                monat = "0" + monat;

            // Datum aus Datepicker
            var aktuell = date.getFullYear() + "-" + monat + "-" + date.getDate();

            // Sperrtage
            var flagSperrtag = true;
            for(var i = 0; i < sperrtage.length; i++){
                if(aktuell == sperrtage[i]){
                    flagSperrtag = false;
                }
            }

            // Reaktion auf vorhandenen Sperrtag
            if(flagSperrtag == false)
                return [flagSperrtag,""];

            // Geschäftstage
            var aktuellerGeschaeftstag = date.getDay();

            // Berücksichtigung Sonntag
            if(aktuellerGeschaeftstag == 0)
                aktuellerGeschaeftstag = 7;

            var kontrolleAktuellerGeschaeftstag = false;
            for(var i = 0; i < geschaeftstage.length; i++){
                if(aktuellerGeschaeftstag == geschaeftstage[i])
                    kontrolleAktuellerGeschaeftstag = true;
            }

            return [kontrolleAktuellerGeschaeftstag,""];
        },
        onSelect: function(date){
            $('#buchungsdatum').html(date);
            $("#datum").val(date);
            kontrolleOeffnungszeiten();
            ermittelnTageskapazitaet(date);
        }
    });

    // $('#datepicker').datepicker('setDate', null);
    
    return;
}

function ladenBestandsbuchung()
{
    if (gebuchtesDatum.length < 2)
        return;

    $('#datepicker').datepicker('setDate', gebuchtesDatum);

    return;
}

function kaufmRunden(x) {
	var k = (Math.round(x * 100) / 100).toString();
	k += (k.indexOf('.') == -1)? '.00' : '00';
    var wert = k.substring(0, k.indexOf('.') + 3);

    if(sprache == 'de')
        wert = wert.replace('.',',');
	
	return wert;
}

function ermittelnTageskapazitaet(datum)
{
    var anzahlProgrammbuchungen = 0;
    var obj = $(".anzahlProgramme");

    $.each(obj, function(wert1, inputField){

        var inputFieldId = inputField.id;
        var suche = '#' + inputFieldId;
        var eingabefeldAnzahlBuchungen = $(suche).val();
        anzahlProgrammbuchungen += parseInt(eingabefeldAnzahlBuchungen);
    });

    $.ajax({
        url: "/front/programmdetail/finde-Tageskapazitaet/",
        type: "POST",
        data: {
            programmId: programmId,
            datum: datum,
            anzahlProgrammbuchungen: anzahlProgrammbuchungen
        },
        success: function(programmKapazitaet){

            // Informationsblock Kapazität anzeigen
            $("#Kapazitaet").html(programmKapazitaet);

            // keine Kapazitaet
            if(programmKapazitaet < anzahlProgrammbuchungen){
                $("#Kapazitaet_Hinweis").css("visibility", "visible");
                $("#Kapazitaet_Hinweis_buchbar").html(programmKapazitaet);

                $.each(obj, function(wert1, inputField){
                    var inputFieldId = inputField.id;
                    var suche = '#' + inputFieldId;
                    var eingabefeldAnzahlBuchungen = $(suche).val('0');
                });
            }
            else{
                $("#Kapazitaet_Hinweis").css("visibility", "hidden");
            }

        }
    });
}

// Reaktion auf wechsel Programmvariante
function wechselProgrammvariante(id){
    //alert("wechselProgrammvariante");
    var teile = id.split('_');
    var idZeile = parseInt(teile[1]);
    var anzahl = $("#" + teile[1]).val();

    return wechselPersonenanzahl(idZeile, anzahl);
}

function selectPreisvarianteBestandsbuchung(){
    if(gebuchtePreisvariante == 0)
        return;

        $("#programmvariante_0").val(gebuchtePreisvariante);
}

function gebuchteZeit()
{
    if(zeitmanagerSelect == '0')
        return;

    if($("#zeitmanagerSelect")){
        $("#zeitmanagerSelect").val(zeitmanagerSelect);
    }
    else{
        $("#zeitmanagerStunde").val(zeitmanagerStunde);
        $("#zeitmanagerMinute").val(zeitmanagerMinute);
    }
}

function kontrolleOeffnungszeiten()
{
    if($("#zeitmanagerStunde")){
        $.ajax({
            url: '/front/programmdetail/kontrolle-oeffnungszeiten/',
            type: "POST",
            data: {
                stunde: $("#zeitmanagerStunde").val(),
                minute: $("#zeitmanagerMinute").val(),
                datum: $("#alternateDatum").val(),
                programmId: programmId
            },
            success: function(response)
            {
                if(response == ""){
                    $("#zeitmanagerStunde").val('0');
                    $("#zeitmanagerMinute").val('0');
                    $("#zeitmanagerStunde").validationEngine('showPrompt', hinweisOeffnungszeiten, true, true);
                }
            }
        });
    }

    return;
}

function kontrolleWarenkorb(){
    flagAbsenden = true;

    $(".anzahlProgramme").each(function(key , object){
        var anzahlProgramme = object.value;
        var idProgrammvariante = "#programmvariante_" + key;
        var valueProgrammvariante = $(idProgrammvariante).val();

        anzahlProgramme = parseInt(anzahlProgramme);
        valueProgrammvariante = parseInt(valueProgrammvariante);

        // anzeigen Fehler
        if(anzahlProgramme > 0 && valueProgrammvariante == 0){
            $(idProgrammvariante).validationEngine('showPrompt', hinweisAuswahlPreisvatiante, true, true);
            object.value = 0;
            flagAbsenden = false;
        }
    });

    $("form#formId").submit();
}

// *****************************************

$(document).ready(function() {

    // verhindern absenden Formular
    var flagAbsenden = false;
//    $("form#formId").submit(function(){
//        if(flagAbsenden == true)
//            $("form#formId").submit();
//    });

    // verstecken Tageskapazität
    $("#Kapazitaet_Hinweis").css({visibility: 'hidden'});

    // Erststart
	buildDatepicker();

    // Kontrolle Zeitangabe
    if($("#zeitmanagerStunde")){
        $("#zeitmanagerStunde").blur(function(){
            kontrolleOeffnungszeiten();
        });

        $("#zeitmanagerMinute").blur(function(){
            kontrolleOeffnungszeiten();
        });
    }

    // Sprachen Manager
    // Erstbelegung
    var spracheId = $("#sprachenmanagerSelect").val();
    $("#sprache").val(spracheId);

    $("#sprachenmanagerSelect").change(function(){
        spracheId = $("#sprachenmanagerSelect").val();
        $("#sprache").val(spracheId);
    });

    // laden Kalender für Bestandsbuchung
    ladenBestandsbuchung();

    // Preisvariante einer Bestandsbuchung anzeigen
    selectPreisvarianteBestandsbuchung();

    // Darstellung gebuchte Zeit Preismanager
    gebuchteZeit();

    // Überprüfen Formular
    $("#formID").validationEngine();

    // Kontrolle eingegebene Bestellung
    $("#warenkorb").click(function(){
        kontrolleWarenkorb();
    });

    // Tageskapazität Programm zum Erststart
    var tagesKapazitaetDatum = fromJahr + "-" + fromMonat + "-" + fromTag;
    ermittelnTageskapazitaet(tagesKapazitaetDatum);

    // Kontrolle der gewählten Anzahl Programme mit der Tageskapazität des Programmes
    $(".anzahlProgramme").bind("enterKey",function(elements){

        var gesamtanzahl = 0;

        $(".anzahlProgramme").each(function(durchlauf, element){

            var anzahl = $(element).val();
            anzahl = parseInt(anzahl, 10);

            if(isNaN(anzahl))
                anzahl = 0;

            gesamtanzahl += anzahl;
        });

        var tageskapazitaet = $("#Kapazitaet").html();
        tageskapazitaet = parseInt(tageskapazitaet);

        // wenn die Tageskapazität überschritten wurde
        if(gesamtanzahl > tageskapazitaet){
            $(".anzahlProgramme").each(function(durchlauf, element){
                $(element).val(0);
            });

            $(".preis").each(function(durchlauf, element){
                $(element).html(0);
            });

            $(".einzelpreis").each(function(durchlauf, element){
                $(element).html(0);
            });

            $("#Kapazitaet_Hinweis").css({
                visibility: 'visible'
            });

            $("#Kapazitaet_Hinweis_buchbar").html(tageskapazitaet);

            berechnungGesamtpreis();
        }
        else{
            $("#Kapazitaet_Hinweis").css({
                visibility: 'hidden'
            });
        }
    });

    // wenn Bestandsbuchung
    var alternateBestandsDatum = $("#alternateDatum").val();
    $("#datum").val(alternateBestandsDatum);

    // verstecken Absendeknopf / Submit Button Formular
    if(gebuchtesDatum == '0')
        $("#submitButton").hide();

    // Korrektur Darstellung Eingabe Personenanzahl
    $(".anzahlProgramme").blur(function()
    {
        var anzahl =  $(this).val();
        anzahl = parseInt(anzahl, 10);

        if(isNaN(anzahl))
            anzahl = 0;

        $(this).val(anzahl);
    });

    // wechsel der Personenanzahl
    $("input").change(function(){
        var rowId = this.id;
        var anzahl = this.value;

        // Anzeige neue Preisvariante wenn keine Bestandsbuchung
        if(wechselPersonenanzahl(rowId, anzahl)){
            if(gebuchtesDatum == 0)
                sichtbarkeitProgrammvarianten();
        }


        berechnungGesamtpreis();

        return;
    });

    // wechsel der Programmvariante
    $(".programmvarianteSelect").change(function(){
        var id = this.id;

        wechselProgrammvariante(id);
        sichtbarkeitProgrammvarianten();
        berechnungGesamtpreis();

    });

    // Positionierung Buchung - Block
    positionBuchung();

    // Anzeige gewähltes Buchungsdatum
    $('#buchungsdatum').html($("#alternateDatum").val());

    // verbergen ungenutzter Preisvarianten
    //for(var i = 1; i < anzahlPreisvarianten; i++){
        //$("#zeile" + i).hide();
    //}



    if (document.getElementById('programmvariante_0')) {
        document.getElementById('programmvariante_0').selectedIndex = 0;
    }
    if (document.getElementById('programmvariante_1')) {
        document.getElementById('programmvariante_1').selectedIndex = 1;
    }
    if (document.getElementById('programmvariante_2')) {
        document.getElementById('programmvariante_2').selectedIndex = 2;
    }
    if (document.getElementById('programmvariante_3')) {
        document.getElementById('programmvariante_3').selectedIndex = 3;
    }
    if (document.getElementById('programmvariante_4')) {
        document.getElementById('programmvariante_4').selectedIndex = 4;
    }
    if (document.getElementById('programmvariante_5')) {
        document.getElementById('programmvariante_5').selectedIndex = 5;
    }
    if (document.getElementById('programmvariante_6')) {
        document.getElementById('programmvariante_6').selectedIndex = 6;
    }
    if (document.getElementById('programmvariante_7')) {
        document.getElementById('programmvariante_7').selectedIndex = 7;
    }
    if (document.getElementById('programmvariante_8')) {
        document.getElementById('programmvariante_8').selectedIndex = 8;
    }
    if (document.getElementById('programmvariante_9')) {
        document.getElementById('programmvariante_9').selectedIndex = 9;
    }
    if (document.getElementById('programmvariante_10')) {
        document.getElementById('programmvariante_10').selectedIndex = 10;
    }

    if (document.getElementById('programmvariante_11')) {
        document.getElementById('programmvariante_11').selectedIndex = 11;
    }
    if (document.getElementById('programmvariante_12')) {
        document.getElementById('programmvariante_12').selectedIndex = 12;
    }
    if (document.getElementById('programmvariante_13')) {
        document.getElementById('programmvariante_13').selectedIndex = 13;
    }
    if (document.getElementById('programmvariante_14')) {
        document.getElementById('programmvariante_14').selectedIndex = 14;
    }
    if (document.getElementById('programmvariante_15')) {
        document.getElementById('programmvariante_15').selectedIndex = 15;
    }
    if (document.getElementById('programmvariante_16')) {
        document.getElementById('programmvariante_16').selectedIndex = 16;
    }
    if (document.getElementById('programmvariante_17')) {
        document.getElementById('programmvariante_17').selectedIndex = 17;
    }
    if (document.getElementById('programmvariante_18')) {
        document.getElementById('programmvariante_18').selectedIndex = 18;
    }
    if (document.getElementById('programmvariante_19')) {
        document.getElementById('programmvariante_19').selectedIndex = 19;
    }
    if (document.getElementById('programmvariante_20')) {
        document.getElementById('programmvariante_20').selectedIndex = 20;
    }
    if (document.getElementById('programmvariante_21')) {
        document.getElementById('programmvariante_21').selectedIndex = 21;
    }

    if (document.getElementById("einzelpreis_0")) {
        document.getElementById("einzelpreis_0").innerHTML= kaufmRunden(preiseProgrammVarianten[0]);
    }

    if (document.getElementById("einzelpreis_1")) {
        document.getElementById("einzelpreis_1").innerHTML= kaufmRunden(preiseProgrammVarianten[1]);
    }
    if (document.getElementById("einzelpreis_2")) {
        document.getElementById("einzelpreis_2").innerHTML= kaufmRunden(preiseProgrammVarianten[2]);
    }
    if (document.getElementById("einzelpreis_3")) {
        document.getElementById("einzelpreis_3").innerHTML= kaufmRunden(preiseProgrammVarianten[3]);
    }
    if (document.getElementById("einzelpreis_4")) {
        document.getElementById("einzelpreis_4").innerHTML= kaufmRunden(preiseProgrammVarianten[4]);
    }
    if (document.getElementById("einzelpreis_5")) {
        document.getElementById("einzelpreis_5").innerHTML= kaufmRunden(preiseProgrammVarianten[5]);
    }
    if (document.getElementById("einzelpreis_6")) {
        document.getElementById("einzelpreis_6").innerHTML= kaufmRunden(preiseProgrammVarianten[6]);
    }
    if (document.getElementById("einzelpreis_7")) {
        document.getElementById("einzelpreis_7").innerHTML= kaufmRunden(preiseProgrammVarianten[7]);
    }
    if (document.getElementById("einzelpreis_8")) {
        document.getElementById("einzelpreis_8").innerHTML= kaufmRunden(preiseProgrammVarianten[8]);
    }
    if (document.getElementById("einzelpreis_9")) {
        document.getElementById("einzelpreis_9").innerHTML= kaufmRunden(preiseProgrammVarianten[9]);
    }
    if (document.getElementById("einzelpreis_10")) {
        document.getElementById("einzelpreis_10").innerHTML= kaufmRunden(preiseProgrammVarianten[10]);
    }
    if (document.getElementById("einzelpreis_11")) {
        document.getElementById("einzelpreis_11").innerHTML= kaufmRunden(preiseProgrammVarianten[11]);
    }
    if (document.getElementById("einzelpreis_12")) {
        document.getElementById("einzelpreis_12").innerHTML= kaufmRunden(preiseProgrammVarianten[12]);
    }
    if (document.getElementById("einzelpreis_13")) {
        document.getElementById("einzelpreis_13").innerHTML= kaufmRunden(preiseProgrammVarianten[13]);
    }
    if (document.getElementById("einzelpreis_14")) {
        document.getElementById("einzelpreis_14").innerHTML= kaufmRunden(preiseProgrammVarianten[14]);
    }
    if (document.getElementById("einzelpreis_15")) {
        document.getElementById("einzelpreis_15").innerHTML= kaufmRunden(preiseProgrammVarianten[15]);
    }
    if (document.getElementById("einzelpreis_16")) {
        document.getElementById("einzelpreis_16").innerHTML= kaufmRunden(preiseProgrammVarianten[16]);
    }
    if (document.getElementById("einzelpreis_17")) {
        document.getElementById("einzelpreis_17").innerHTML= kaufmRunden(preiseProgrammVarianten[17]);
    }
    if (document.getElementById("einzelpreis_18")) {
        document.getElementById("einzelpreis_18").innerHTML= kaufmRunden(preiseProgrammVarianten[18]);
    }
    if (document.getElementById("einzelpreis_19")) {
        document.getElementById("einzelpreis_19").innerHTML= kaufmRunden(preiseProgrammVarianten[19]);
    }
    if (document.getElementById("einzelpreis_20")) {
        document.getElementById("einzelpreis_20").innerHTML= kaufmRunden(preiseProgrammVarianten[20]);
    }
    if (document.getElementById("einzelpreis_21")) {
        document.getElementById("einzelpreis_21").innerHTML= kaufmRunden(preiseProgrammVarianten[21]);
    }












    // Bestandsbuchung , Berechnung Preis und Sichtbarkeit Button
    if(!zeitmanagerSelect == '0'){
        berechnungGesamtpreis();
    }
});