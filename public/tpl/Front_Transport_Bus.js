$(document).ready(function() {
	
	var optionDatepicker = buildDatepicker();
	
	$("#formTransferData").validationEngine();
	
	$(function() {
		$( "#datepicker" ).datepicker(optionDatepicker);
	});


	$(function() {
		$( "#adresseAbfahrt" ).autocomplete({
            source: "/front/transport/searchadresse/",
			minLength: 2,
			delay: 500
		});

		$( "#adresseZiel" ).autocomplete({
            source: "/front/transport/searchadresse/",
			minLength: 2,
			delay: 500
		});
		
	});

    $("#warenkorb").bind('click',function(){
        var kontrolle = 0;
        $('#Orte').validationEngine('hide');

        var gewaehlteAbfahrtsOrt = $("input:radio:checked[name='abfahrtsOrt']").val();

        if(gewaehlteAbfahrtsOrt == 1){
            if($("#adresseAbfahrt").val().length < 5)
                kontrolle++;
        }
        else if(gewaehlteAbfahrtsOrt == 2){
            if($("#eigeneAdresseAbfahrt").val().length < 5)
                kontrolle++;
        }

        var gewaehlteZielOrt = $("input:radio:checked[name='zielOrt']").val();

        if(gewaehlteZielOrt == 1){
            if($("#adresseZiel").val().length < 5)
                kontrolle++;
        }
        else if(gewaehlteZielOrt == 2){
            if($("#eigeneAdresseZiel").val().length < 5)
                kontrolle++;
        }

        if(kontrolle > 0){
            $('#Orte').validationEngine('showPrompt',"Abfahrts oder Zielort fehlt",'pass');
            return false;
        }

        else
            return true;
    });

    $("#reset").bind('click',function(){
        $('#formTransferData').validationEngine('hide');

        return true;
    });
	
	$("#sucheBus").bind('click', function(){
		
		var dauer = $("#dauer").val();
        dauer = parseInt(dauer);

        var personen = $("#persons").val();
        personen = parseInt(personen);

        var stunde = $("#stunde").val();
        stunde = parseInt(stunde);

        var minute = $("#minute").val();
        minute = parseInt(minute);

        var gewaehltesDatum = $("#gewaehltesDatum").val();

        var kontrolle = 0;

        if(!dauer || dauer < 0){
            $('#dauer').validationEngine('validateField','#dauer');

            kontrolle++;
        }

        if(!personen || personen < 0){
            $('#persons').validationEngine('validateField','#persons');

            kontrolle++;
        }

        if(!gewaehltesDatum){
            $('#gewaehltesDatum').validationEngine('validateField','#gewaehltesDatum');

            kontrolle++;
        }

        if(!stunde || stunde < 0 || stunde > 24){
            $('#stunde').validationEngine('validateField','#stunde');

            kontrolle++;
        }

        if(!minute || minute > 60 || minute < 0){
            $('#minute').validationEngine('validateField','#minute');

            kontrolle++;
        }

        if(kontrolle > 0)
            return;

		$.ajax({
		   type: "POST",
		   url: "/front/transport/findbus/",
		   data: {
				personen: personen,
				dauer: dauer
			},
		   success: function(response){
		     var antwort = eval(response);
		     
             $("#selectedBus").val(antwort[0].progname);
             $("#ProgrammId").val(antwort[0].Fa_ID);
             $("#pricePersons").val(antwort[0].pricePersons);
             $("#sachleistung").val(antwort[0].sachleistung);

		   }
		 });
		
	});

});

$("#sucheBus").bind('click', function(){
    alert('Warenkorb');
});

function clearPrompt(){
	$('#formTransferData').validationEngine('hide');
}

function buildDatepicker(){
	
	if(language == 'de'){
		var optionDatepicker = {
				clearText: 'löschen',
				clearStatus: 'aktuelles Datum löschen',
	            closeText: 'schließen',
	            closeStatus: 'ohne Änderungen schließen',
	            prevText: '&#x3c; zurück',
	            prevStatus: 'letzten Monat zeigen',
	            nextText: 'vor &#x3e;',
	            nextStatus: 'nächsten Monat zeigen',
	            currentText: 'heute',
	            currentStatus: '',
	            monthNames: ['Januar','Februar','März','April','Mai','Juni',
	            'Juli','August','September','Oktober','November','Dezember'],
	            monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
	            'Jul','Aug','Sep','Okt','Nov','Dez'],
	            monthStatus: 'anderen Monat anzeigen',
	            yearStatus: 'anderes Jahr anzeigen',
	            weekHeader: 'Wo',
	            weekStatus: 'Woche des Monats',
	            dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
	            dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
	            dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
	            dayStatus: 'Setze DD als ersten Wochentag',
	            dateStatus: 'Wähle D, M d',
	            dateFormat: 'dd.mm.yy',
	            firstDay: 1,
	            initStatus: 'Wähle ein Datum',
	            minDate: new Date(),
	            onSelect: merkeDatum
		};
	}
	else{
		var optionDatepicker = {
		    dateFormat: 'dd/mm/YYYY',
		    firstDay: 1,
		    onSelect: merkeDatum
		}
	}
    
    return optionDatepicker;
}

function merkeDatum(date, inst){
	var test = date;
    $('#gewaehltesDatum').val(date);
	$("#datum").val(date);

	return;
}