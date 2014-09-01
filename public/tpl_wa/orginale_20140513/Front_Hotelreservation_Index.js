/**
 * Kontrolle der Formulares Eingabe der
 * Personenanzahl zu den Raten eines Hotels
 *
 * Created with JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 07.02.13
 * Time: 14:48
 */

var hotelreservation = function(){

    // Beginn private
    var mindestAnzahlGruppe = 10;
    var momentaneAnzahlPersonen = 0;
    var gesamtpreisZimmerEinerUebernachtung = 0;

    var berechneZimmerpreis = function(id, personenAnzahl, preis){
        // Bettenanzahl im Zimmer
        var bettenImZimmer = $("#bettenanzahl_" + id).html();
        bettenImZimmer = parseInt(bettenImZimmer, 10);

        var zimmerAnzahl = personenAnzahl / bettenImZimmer;
        zimmerAnzahl = Math.ceil(zimmerAnzahl);

        var zimmerpreis = zimmerAnzahl * preis;
        $("#zimmerPreis_" + id).html(zimmerpreis.toFixed(2));

        return;
    }

    // Kontrolle der Zimmerbelegung mit 75%
    var ZimmerProzentBelegung = function(id, personenAnzahl, bettenImZimmer)
    {
        var restPersonenAnzahl = personenAnzahl % bettenImZimmer;
        var flagMindestPersonenImZimmer = false;

        if(restPersonenAnzahl == 0){
            flagMindestPersonenImZimmer = true;

            return flagMindestPersonenImZimmer;
        }
        // 75% Regel für Mehrbettzimmer
        else{

            var mindestPersonenanzahlZimmer = bettenImZimmer / 4 * 3;
            mindestPersonenanzahlZimmer = Math.floor(mindestPersonenanzahlZimmer);


            if(restPersonenAnzahl < mindestPersonenanzahlZimmer){
                $("#zimmerbelegung_" + id).html(infoZimmerbelegung);
                $("#" + id).val(0);

                return  flagMindestPersonenImZimmer;
            }
            else{
                flagMindestPersonenImZimmer = true;
                return flagMindestPersonenImZimmer;
            }
        }
    }

    var berechnePersonenpreis = function(id, personenAnzahl, preis){
        var zimmerpreis = preis * personenAnzahl;
        $("#zimmerPreis_" + id).html(zimmerpreis.toFixed(2));

        return;
    }

    var preisBerechnungRate = function(id){
        var preisTyp = $("#preistyp_" + id).html();
        var personenAnzahl = $("#" + id).val();
        personenAnzahl = parseInt(personenAnzahl, 10);

        $("#zimmerPreis_" + id).html('');

        // Personenanzahl ist Null oder Nan
        if( (personenAnzahl == 0) || (isNaN(personenAnzahl)) )
            personenAnzahl = 0;

        var preis = $("#preis_" + id).html();
        preis = parseFloat(preis);

        // Preis ist Null oder Nan
        if(isNaN(preis) || (preis == 0))
            preis = 0;

        if(preisTyp == 'zimmerpreis')
            berechneZimmerpreis(id, personenAnzahl, preis);
        else if(preisTyp == 'personenpreis')
            berechnePersonenpreis(id, personenAnzahl, preis);

        return;
    }

    // Ende private

    // Beginn public
    return {
        init: function(){


             // Insert neue Raten
            // if(flagUpdate == 1)
                $("#submitForm").attr('value', submitButtonZusatzprodukte);

            // Update vorhandene Raten
            // if(flagUpdate == 2)
                // $("#submitForm").attr('value', submitButtonWarenkorb);

            // Erstes Anzeigen der gebuchten Ratenpreise
            $(".personenanzahl").each(function(){
                var id = this.id;
                preisBerechnungRate(id);
            });


        },
        anzeigeMindespersonenImZimmer: function(id){

            var flagMindestPersonenImZimmer = false;
            var inputFeld = $("#" + id);

            var personenAnzahl = inputFeld.val();
            personenAnzahl = parseInt(personenAnzahl, 10);

            // löschen Information Zimmerbelegung
            $("#zimmerbelegung_" + id).html('');

            if(personenAnzahl == 0){
                return flagMindestPersonenImZimmer;
            }

            // Bettenanzahl im Zimmer
            var bettenImZimmer = $("#bettenanzahl_" + id).html();
            bettenImZimmer = parseInt(bettenImZimmer, 10);

            // Einzelzimmer Mindesbelegung
            if(bettenImZimmer == 1){
                flagMindestPersonenImZimmer = true;

                return flagMindestPersonenImZimmer;
            }

            // Mindestbelegung Doppelzimmer
            if(bettenImZimmer == 2){

                $restAnzahlPersonen = personenAnzahl % 2;

                if($restAnzahlPersonen == 0){
                    flagMindestPersonenImZimmer = true;
                }
                // Zimmer nicht vollständig belegt
                else{
                    flagMindestPersonenImZimmer = false;
                    $("#zimmerbelegung_" + id).html(infoZimmerbelegungDoppelzimmer);
                    $("#" + id).val(0);
                }

                return flagMindestPersonenImZimmer;
            }

            // Mehrbettzimmer
            flagMindestPersonenImZimmer = ZimmerProzentBelegung(id, personenAnzahl, bettenImZimmer);
            return flagMindestPersonenImZimmer;
        },
        preisUebernachtungBerechnen: function(id){
            preisBerechnungRate(id);

            return;
        },
        _berechneZimmerpreis: function(id, personenAnzahl, preis){

            berechneZimmerpreis(id, personenAnzahl, preis);

            return;
        },
        _berechnePersonenpreis: function(id, personenAnzahl, preis){

            berechnePersonenpreis(id, personenAnzahl, preis);

            return;
        },
        personenanzahlBerechnen: function(){

            momentaneAnzahlPersonen = 0;

            $(".personenanzahl").each(function(){
                var personenanzahl = parseInt(this.value, 10);

                if(isNaN(personenanzahl)){
                    this.value = 0;
                    personenanzahl = 0;
                }
                else{
                    this.value = personenanzahl;
                    momentaneAnzahlPersonen += personenanzahl;
                }
            });

            // Hinweis Personenanzahl
            $("#information").html('');
            if(momentaneAnzahlPersonen < mindestAnzahlGruppe)
                this._hinweisPersonenanzahl(2);

            return;
        },
        _hinweisPersonenanzahl: function(flagHinweis){

            // weniger Personen als im Suchformular
            if(flagHinweis == 1){
                var diffPersonen = personenanzahlSuchformular - momentaneAnzahlPersonen;
                var information = counterAnzeigeZuwenig + " " + diffPersonen;
                $("#information").html(information);
            }
            // mindest Gruppenstärke nicht gegeben
            else if(flagHinweis == 2)
                $("#information").html(counterAnzeigeMindestanzahlGruppe);
        },
        berechnungKapazitaet: function(id){
            var flagKontrolleKapazitaet = false;

             // vorhandene Zimmer
            var zimmeranzahl = $("#roomlimit_" + id).html();
            zimmeranzahl = parseInt(zimmeranzahl, 10);

            if(zimmeranzahl == 0){
                $("#zimmerbelegung_" + id).html(infoKapazitaet);
                $("#" + id).val(0);

                return flagKontrolleKapazitaet;
            }

            // Berechnung Personenanzahl
            var personenAnzahl = $("#" + id).val();
            personenAnzahl = parseInt(personenAnzahl, 10);

             // Bettenanzahl im Zimmer
            var bettenImZimmer = $("#bettenanzahl_" + id).html();
            bettenImZimmer = parseInt(bettenImZimmer, 10);

            if(personenAnzahl > (zimmeranzahl * bettenImZimmer) ){
                $("#zimmerbelegung_" + id).html(infoKapazitaet);
                $("#" + id).val(0);

                return flagKontrolleKapazitaet;
            }

            flagKontrolleKapazitaet = true;

            return flagKontrolleKapazitaet;
        },
        berechnungGesamtpreisAllerUebernachtungen: function(){

            var anzahlUebernachtungen = parseInt(uebernachtungen, 10);
            var PreisAllerUebernachtungen = 0;
            var PreisFuerEinenTag = 0;
            var mittlererPersonenpreis = 0;
            var personenAnzahl = 0;

            $(".personenanzahl").each(function(){
                var id = this.id;

                var personen = $("#" + id).val();
                personen = parseInt(personen, 10);

                if(isNaN(personen))
                    personen = 0;

                personenAnzahl += personen;

                var zimmerpreis = $("#zimmerPreis_" + id).html();
                zimmerpreis = parseFloat(zimmerpreis);

                if(zimmerpreis == '')
                    zimmerpreis = 0;

                PreisFuerEinenTag += zimmerpreis;

                zimmerpreis = zimmerpreis * anzahlUebernachtungen;
                zimmerpreis = parseFloat(zimmerpreis);

                PreisAllerUebernachtungen += zimmerpreis;

            });

            mittlererPersonenpreis = PreisAllerUebernachtungen / personenAnzahl;
            if(isNaN(mittlererPersonenpreis))
                mittlererPersonenpreis = 0;

            $("#gesamtPreisAllerUebernachtungen").html(PreisAllerUebernachtungen.toFixed(2));
            $("#tagesPreis").html(PreisFuerEinenTag.toFixed(2));
            $("#mittlererPersonenPreis").html(mittlererPersonenpreis.toFixed(2));

            return;
        },
        informationRateZuruecksetzen: function()
        {
            $(".personenanzahl").each(function(){
                var id = this.id;

                $("#zimmerbelegung_" + id).html('');

            });
        },
        absendenForm: function()
        {
            var gesamtAnzahlPersonen = 0;
            var fehler = 0;

            $("#formID").attr('action','');

            // Berechnung Personenanzahl
            $(".personenanzahl").each(function(){
                var personenanzahl = parseInt(this.value, 10);

                if(isNaN(personenanzahl))
                    personenanzahl = 0;
                else
                    gesamtAnzahlPersonen += personenanzahl;
            });

            // Kontrolle Gruppenstaerke
            if(gesamtAnzahlPersonen >= mindestAnzahlGruppe){

                $("#personenAnzahlSuchParameter").html(gesamtAnzahlPersonen);
                flagUpdate = parseInt(flagUpdate);

                // Aktionen des Formulares !!!
                if( (!isNaN(flagUpdate)) && (flagUpdate > 0) && (flagUpdate < 3) ){
                    // insert der Raten
                    if(flagUpdate == 1)
                        $("#formID").attr('action','/front/hotelreservation/save/flagUpdate/1/');
                    // Update der Raten
                    if(flagUpdate == 2)
                        $("#formID").attr('action','/front/hotelreservation/save/flagUpdate/2/');
                }
            }
            else{
                $("#formID").attr('action','');
                $("#personenAnzahlSuchParameter").html(mindestAnzahlGruppe);
                fehler++;
            }

            return fehler;
        },
        setGesamtpreis: function(preis){
            gesamtpreisZimmerEinerUebernachtung = preis;

            return;
        },
        buttonAendern: function(fehler)
        {
            if(fehler == 0)
                $("#submitForm").removeClass('submit_passiv').addClass('submit');
            else
                $("#submitForm").removeClass('submit').addClass(' submit_passiv');

            return;
        },
        pruefen: function()
        {
            var that = this;
            var fehler = 0;
            var anzahlPersonenEingaben = 0;

            $(".personenanzahl").each(function(){

                var id = parseInt(this.id, 10);

                var personenAnzahl = $("#" + id).val();
                personenAnzahl = parseInt(personenAnzahl, 10);

                // Kontrolle Personeneingabe
                if(personenAnzahl > 0){
                    anzahlPersonenEingaben++;

                    // zurücksetzen der Raten Information
                    that.informationRateZuruecksetzen();

                    // Berechnung Kapazität
                    var flagKontrolleKapazitaet = that.berechnungKapazitaet(id);

                    // Kontrolle Mindestanzahl Personen im Zimmer
                    if(flagKontrolleKapazitaet)
                        var flagMindestPersonenImZimmer = that.anzeigeMindespersonenImZimmer(id);

                    // Preisberechnung der Rate
                    that.preisUebernachtungBerechnen(id);

                    // Anzeige Gesamt Personenanzahl
                    that.personenanzahlBerechnen();

                    // Berechnung Gesamtpreis
                    that.berechnungGesamtpreisAllerUebernachtungen();

                    // aufsummieren Fehler
                    if(!flagKontrolleKapazitaet || !flagMindestPersonenImZimmer)
                        fehler++;
                }
            });

            // Kontrolle Personenanzahl
            if(fehler == 0){
                var fehlerPersonenAnzahl = this.absendenForm();
                fehler = fehler + fehlerPersonenAnzahl;
            }

            // verändern Button
            this.buttonAendern(fehler);


            if(anzahlPersonenEingaben == 0)
                fehler++;

            return fehler;
        }
    }
    // Ende public
}

// Start der Klasse
$(document).ready(function() {
    var hotelreservationClass = new hotelreservation();
    hotelreservationClass.init();

    // Überprüfung erzwingen
    var fehler = 1;

    $("#pruefen").click(function(){
        fehler = hotelreservationClass.pruefen();
        berechnungRabatt();
    });

    $("#formID").submit(function(){
        fehler = hotelreservationClass.pruefen();
        berechnungRabatt();

        if(fehler == 0)
            return true;
        else
            return false;
    });

});

/**
 * Holt vom Server mit
 * Ajax die Zimmerbeschreibung
 *
 * @param zimmerkategorie
 * @return {Boolean}
 */
function getZimmerbeschreibung(zimmerkategorie){

    $.ajax({
       type: "POST",
       url: "/front/hotelreservation/zimmerbeschreibung",
       data: {
           propertyId: propertyId,
           zimmerrate: zimmerkategorie,
           sprache: language
       },
       success: function(subtemplate){
            $("#zimmerbeschreibung").html(subtemplate);

            // move zur Zimmerbeschreibung
            $('html,body').animate({scrollTop: $("#move").offset().top}, 'slow');

           // Maxbild Kategorie
           anzeigeMaxbildKategorie();
       }
     });

    return false;
}
