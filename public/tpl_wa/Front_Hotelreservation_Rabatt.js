/**
 * Ermittlung des Gruppenrabatt
 */
function berechnungRabatt()
{
   var gesamtanzahlPersonen = 0;
   var raten = '';

   var ratenArray = new Array();
   $(".personenanzahl").each(function(index){
       var anzahl = arguments[1].value;
       anzahl = parseInt(anzahl);

       gesamtanzahlPersonen += anzahl;

       var rate = arguments[1].id;
       rate = parseInt(rate);
       raten += rate + "&" + anzahl + "#";
   });

   if(gesamtanzahlPersonen > 0){

        $.ajax({
            url: "/front/hotelreservation-gruppenrabatt/",
            dataType: 'json',
            data: {
                raten: raten
            },
            type: 'POST',
            success: function()
            {
                if(arguments[0].hotelRabattPreis){

                    // Berechnung und Darstellung des Rabatt
                    var rabatt = parseFloat(arguments[0].hotelRabattPreis);
                    rabatt = rabatt.toFixed(2);
                    $("#gruppenRabatt").html(rabatt);
                    var gesamtpreis = $("#gesamtPreisAllerUebernachtungen").html();
                    gesamtpreis = parseFloat(gesamtpreis);
                    gesamtpreis = gesamtpreis - rabatt;
                    gesamtpreis = gesamtpreis.toFixed(2);
                    $("#gesamtPreisAllerUebernachtungen").html(gesamtpreis);

                    // Darstellung der Freiplätze
                    var informationFreiplaetze = '';
                    for(var i = 0; i < arguments[0].freiplatzRate.length; i++){
                        informationFreiplaetze = informationFreiplaetze + arguments[0].freiplatzRate[i].anzahl + " " + personenIm + " " + arguments[0].freiplatzRate[i].ratenName + "<br>";
                    }

                    $("#freiPlaetze").html(informationFreiplaetze);
                }
                else
                    $("#freiPlaetze").html(' ');

            }
        });
   }
}