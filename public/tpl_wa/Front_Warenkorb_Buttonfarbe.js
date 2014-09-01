var buttonfarbe = function()
{
    // Standard / nur Programme liegen im Warenkorb
    var variante1 = {
        warenkorb_leeren: 'submit',
        warenkorb_buchen: 'submit_weiter',
        warenkorb_vormerken: 'submit_weiter',
        programmbuchung_neu: 'submit_weiter',
        programmbuchung_stornieren: 'submit',
        programmbuchung_bearbeiten: 'submit',
        hotelbuchung_bearbeiten: 'submit',
        hotelbuchung_loeschen: 'submit',
        hotelbuchung_neu: 'submit',
        produktbuchung_bearbeiten: 'submit',
        produktbuchung_loeschen: 'submit',
        produktbuchung_neu: 'submit'
    };

    // nur Programme im Warenkorb, Warenkorb war eine Vormerkung
    var variante2 = {
        warenkorb_leeren: 'submit',
        warenkorb_buchen: 'submit_weiter',
        warenkorb_vormerken: 'submit',
        programmbuchung_neu: 'submit_weiter',
        programmbuchung_stornieren: 'submit',
        programmbuchung_bearbeiten: 'submit',
        hotelbuchung_bearbeiten: 'submit',
        hotelbuchung_loeschen: 'submit',
        hotelbuchung_neu: 'submit',
        produktbuchung_bearbeiten: 'submit',
        produktbuchung_loeschen: 'submit',
        produktbuchung_neu: 'submit'
    };

    // nur Übernachtungen im Warenkorb
    var variante3 = {
       warenkorb_leeren: 'submit',
       warenkorb_buchen: 'submit_weiter',
       warenkorb_vormerken: 'submit_weiter',
       programmbuchung_neu: 'submit_weiter',
       programmbuchung_stornieren: 'submit',
       programmbuchung_bearbeiten: 'submit',
       hotelbuchung_bearbeiten: 'submit',
       hotelbuchung_loeschen: 'submit',
       hotelbuchung_neu: 'submit',
       produktbuchung_bearbeiten: 'submit',
       produktbuchung_loeschen: 'submit',
       produktbuchung_neu: 'submit'
   };

    // nur Übernachtungen im Warenkorb, Warenkorb war eine Vormerkung
    var variante4 = {
        warenkorb_leeren: 'submit',
        warenkorb_buchen: 'submit_weiter',
        warenkorb_vormerken: 'submit',
        programmbuchung_neu: 'submit_weiter',
        programmbuchung_stornieren: 'submit',
        programmbuchung_bearbeiten: 'submit',
        hotelbuchung_bearbeiten: 'submit',
        hotelbuchung_loeschen: 'submit',
        hotelbuchung_neu: 'submit',
        produktbuchung_bearbeiten: 'submit',
        produktbuchung_loeschen: 'submit',
        produktbuchung_neu: 'submit'
    };

    // eine Bestandsbuchung wurde erneut in den Warenkorb gelegt
    var variante5 = {
        warenkorb_leeren: 'submit',
        warenkorb_buchen: 'submit_weiter',
        warenkorb_vormerken: 'submit_weiter',
        programmbuchung_neu: 'submit_weiter',
        programmbuchung_stornieren: 'submit',
        programmbuchung_bearbeiten: 'submit',
        hotelbuchung_bearbeiten: 'submit',
        hotelbuchung_loeschen: 'submit',
        hotelbuchung_neu: 'submit',
        produktbuchung_bearbeiten: 'submit',
        produktbuchung_loeschen: 'submit',
        produktbuchung_neu: 'submit'
    };

    function zuordnenClassZuButton(obj)
    {
        for(var className in obj){
            $('.' + className).addClass(obj[className]);
        }
    }

    return{
        init: function(){

            if(buttonFarbVariante == 1)
                zuordnenClassZuButton(variante1);

            if(buttonFarbVariante == 2)
                zuordnenClassZuButton(variante2);

            if(buttonFarbVariante == 3)
                zuordnenClassZuButton(variante3);

            if(buttonFarbVariante == 4)
                zuordnenClassZuButton(variante4);

            if(buttonFarbVariante == 5)
                zuordnenClassZuButton(variante5);
        }
    }
}

$(document).ready(function() {
    var darstellungButtonFarbe = new buttonfarbe();
    darstellungButtonFarbe.init();
});

