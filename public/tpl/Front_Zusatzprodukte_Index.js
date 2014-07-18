var berechnung = function(){

    var gesamtPreisProdukt = 0;
    var totalPreis = 0;

    function setzePreisTotal(preis){
        var gemerktePreis = parseFloat(totalPreis);
        var berechneTotalPreis = gemerktePreis + preis;

        totalPreis = berechneTotalPreis;

        berechneTotalPreis = number_format(berechneTotalPreis);
        $("#total").html(berechneTotalPreis);
    }

    function number_format(number){

        var decimals = 2;
        var dec_point = '.';
        var thousands_sep = '';

        if(language == 'de')
            dec_point = ',';


        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);            return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');    }
        return s.join(dec);
    }

    return{ 

        berechnungPreisZusatzprodukt: function(){

            totalPreis = 0;

            $(".zusatzprodukt").each(function(){
                var anzahl = 0;
                anzahl = this.value;
                anzahl = parseInt(anzahl,10);

                // wenn keine ordnungsgemäße Anzahl
                if(!isNaN(anzahl)){
                    var id = this.id;

                    // gibt es einen Radiobutton ?
                    // befüllen der 'hidden' input der verpflegungstypen
                    if($("#verpflegung" + id).attr('checked') == true || $("#verpflegung" + id).attr('checked') == false){

                        if($("#verpflegung" + id).attr('checked') == false){
                            $('input[name=' + id + ']').val(0);
                            anzahl = 0;
                        }
                        else{
                            $('input[name=' + id + ']').val(anzahl);
                        }
                    }


                    var preis = $("#preis" + id).val();
                    preis = parseFloat(preis);

                    var gesamtPreisProdukt = preis * anzahl;
                    gesamtPreisProdukt = gesamtPreisProdukt.toFixed(2);

                    // Berechnung Totalpreis
                    var gesamtPreisProdukt = parseFloat(gesamtPreisProdukt);
                    setzePreisTotal(gesamtPreisProdukt); // anzeige Gesamtpreis

                    // Formatierung Preis
                    gesamtPreisProdukt = number_format(gesamtPreisProdukt);
                    $("#gesamtPreisProdukt" + id).html(gesamtPreisProdukt);
                }
            });
            
            return;
        }
    }
}();


$(document).ready(function() {

    // Produktbeschreibung
    $(".produktbeschreibung").mouseover(function(){
        var produktbeschreibung = $("#" + this.id).attr('description');
        $('#' + this.id).validationEngine('showPrompt', produktbeschreibung, 'pass', true);
    });

    $(".produktbeschreibung").mouseout(function(){
        $('.produktbeschreibung').validationEngine('hideAll');
    });

    // Option Verpflegung
    // Verpflegungstypen
    $(".verpflegung").click(function(){
        berechnung.berechnungPreisZusatzprodukt();
    });

    // Berechnung Preise der Zusatzprodukte
    $(".zusatzprodukt").keyup(function(){
        var anzahl = this.value;
        anzahl = parseInt(anzahl,10);

        if(isNaN(anzahl))
            anzahl = 0;

        if(anzahl > parseInt(memoryPersonenanzahl))
            anzahl = 0;

        this.value = anzahl; // setzt Anzeige der Anzahl

        berechnung.berechnungPreisZusatzprodukt();
    });

    // abschalten Radio Button
    $("input[type='radio']").dblclick(function() {
       $("input[type='radio']").each(function(){
          $(this).attr('checked',false);
           berechnung.berechnungPreisZusatzprodukt();
       });
    });

    // Validierung Formular
    $("#form").validationEngine();
    
});