var startDatum = 0;
var minimaleAnzahl = false;
var maximaleAnzahl = false;

var rechenKlasse = null;

$(document).ready(function() {
    $("#formHotelsearch").validationEngine();

    // setzt aktuelles Datum
    setAktuellesDatum();

    // Klasse zur Berechnung
    rechenKlasse = new berechneNaechte();

    // Datepicker An und Abreise
    buildDatepicker();
});

function kontrolleAnzahlNaechte()
{
    var naechte = $("#days").val();

    if((naechte == "") || (naechte == "0") ){
        $("#endeDatepicker").validationEngine('showPrompt', hinweisDatumseingabe);

        return false;
    }
    else{
        $("#endeDatepicker").validationEngine('hide');

        return true;
    }

}

function setAktuellesDatum(){
    var datum = new Date();

    // Berechnung Tag
    var dd = datum.getDate();
    if(dd < 10)
        dd = '0' + dd;

    // Berechnung Monat
    var mm = datum.getMonth() + 1;
    if(mm < 10)
        mm = '0' + mm;

    // Berechnung Jahr
    var yyyy = datum.getYear() - 100 + 2000;

    if(language == 'de')
        $("#startDatepicker").val(dd + "." + mm + "." + yyyy);
    else
        $("#startDatepicker").val(dd + "/" + mm + "/" + yyyy);

    return;
}


function buildDatepicker()
{
	$.datepicker.regional['de'] = {
        minDate: new Date(),
		clearText: 'löschen', clearStatus: 'aktuelles Datum löschen',
        closeText: 'schließen', closeStatus: 'ohne Änderungen schließen',
        prevText: '&#x3c;zurück', prevStatus: 'letzten Monat zeigen',
        nextText: 'Vor&#x3e;', nextStatus: 'nächsten Monat zeigen',
        currentText: 'heute', currentStatus: '',
        monthNames: ['Januar','Februar','März','April','Mai','Juni',
        'Juli','August','September','Oktober','November','Dezember'],
        monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
        'Jul','Aug','Sep','Okt','Nov','Dez'],
        monthStatus: 'anderen Monat anzeigen', yearStatus: 'anderes Jahr anzeigen',
        weekHeader: 'Wo', weekStatus: 'Woche des Monats',
        dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
        dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
        dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
        dayStatus: 'Setze DD als ersten Wochentag', dateStatus: 'Wähle D, M d',
        dateFormat: 'dd.mm.yy', firstDay: 1,
        altFormat: "dd.mm.yy",
        initStatus: 'Wähle ein Datum'};

	$.datepicker.regional['eng'] = {
        minDate: new Date(),
        altFormat: "dd/mm/yy"
	};

    $.datepicker.setDefaults($.datepicker.regional[language]);

    // Datepicker Anreisedatum
    $("#datepickerStart").datepicker({
        isRTL: false,
        firstDay: 1,
        altField: '#startDatepicker',
        onSelect: function(date){
            rechenKlasse.work();
        },
        beforeShow: function(input, datepicker){
            var test = datepicker;
        }
    });

    // Datepicker Abreisedatum
    $("#datepickerEnde").datepicker({
        isRTL: false,
        firstDay: 1,
        altField: '#endeDatepicker',
        onSelect: function(date){
            rechenKlasse.work();
        }
    });

    return;
}


var berechneNaechte = function(){
    // Beginn private
    var self = null;

    var StartDatum = false;
    var EndeDatum = false;

    var sekundenStart = null;
    var sekundenEnde = null;

    // Beginn public
    return{

        work: function(){

            StartDatum = $('#datepickerStart').datepicker("getDate");
            EndeDatum = $('#datepickerEnde').datepicker("getDate");

            if(!StartDatum || !EndeDatum)
                return;

            // wenn Start und Enddatum
            this.berechneUebernachtungen();

            sekundenStart = null;
            sekundenEnde = null;

            return;
        },

        berechneUebernachtungen: function(){
            this.berechneSekunden();

            // wenn Enddatum < Anfangsdatum
            if(sekundenEnde <= sekundenStart){
                this.naechsteTagImMonat();
                $("#days").val('1');
                $("#anzeigeNächte").html(1);

                return;
            }

            // berechne Naechte
            this.bererchneNaechte();

            return;
        },

        bererchneNaechte: function(){
            var uebernachtungen = (sekundenEnde - sekundenStart) / 86400;
            uebernachtungen = parseInt(uebernachtungen);
            $("#days").val(uebernachtungen);
            $("#anzeigeNächte").html(uebernachtungen);

            return;
        },

        berechneSekunden: function(){
            sekundenStart = StartDatum.getTime() / 1000;
            sekundenEnde = EndeDatum.getTime() / 1000;

            return;
        },

        naechsteTagImMonat: function(){
            var tag = StartDatum.getDate();
            var monat = StartDatum.getMonth();
            var jahr = StartDatum.getFullYear();

            tag++;

            var neuesEndDatum = new Date(jahr, monat, tag);
            $('#datepickerEnde').datepicker("setDate", neuesEndDatum);
            $('#datepickerEnde').datepicker("refresh");

            return;
        }
    }
}