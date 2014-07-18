$(document).ready(function() {
	
	var optionDatepicker = buildDatepicker();
	
	$("#formTransferData").validationEngine();
	$("#adresseManuell").hide();
	
	$(function() {
		$( "#datepicker" ).datepicker(optionDatepicker);
	});
	
	$(".adresseStatus").bind('change', function(){
		$("#formTransferData").validationEngine('hideAll');
		
		if(this.value == 1){
			$("#adresseVorschlag").hide();
			$("#adresseManuell").show();
			$("#selectedBus").val('');
			$("#pricePersons").val('');
		}
		else{
			$("#adresseVorschlag").show();
			$("#adresseManuell").hide();
			$("#selectedBus").val('');
			$("#pricePersons").val('');
		}
	});
	
	$("#plzZiel").bind('change', function(){
		$("#selectedBus").val('');
		$("#pricePersons").val('');
		$("#adresseZiel").val('');
	});
	
	$("#plzAbfahrt").bind('change', function(){
		$("#selectedBus").val('');
		$("#pricePersons").val('');
		$("#adresseAbfahrt").val('');
	});
	
	$(function() {
		$( "#plzAbfahrt" ).autocomplete({
			source: "/front/transport/searchplz/",
			minLength: 2,
			delay: 500
		});
		
		$( "#adresseAbfahrt" ).autocomplete({
			source: "/front/transport/searchadresse/",
			minLength: 4,
			delay: 500
		});
		
		$( "#plzZiel" ).autocomplete({
			source: "/front/transport/searchplz/",
			minLength: 2,
			delay: 500
		});
		
		$( "#adresseZiel" ).autocomplete({
			source: "/front/transport/searchadresse/",
			minLength: 4,
			delay: 500
		});
		
	});
	
	$("#sucheBus").bind('click', function(){
		var plzZiel = $("#plzZiel").val();
		if(plzZiel.length < 5){
			jQuery('#plzZiel').validationEngine('showPrompt', fieldPlz, 'error', true);
			jQuery('#plzZiel').val('');
			jQuery('#adresseZiel').val('');
			
			return;
		}
		
		var plzAbfahrt = '';
		if ($("#adresseStatus1:checked").val() == "1")
			plzAbfahrt = $("#plzAbfahrt").val();
		else
			plzAbfahrt = $("#vorschlag").val();
		
		if(plzAbfahrt.length < 5){
			if(status == 1){
				$('#plzAbfahrt').validationEngine('showPrompt', fieldPlz, 'error', true);
				$('#plzAbfahrt').val('');
				$('#adresseAbfahrt').val('');
			}
			
			return;
		}
		
		$.ajax({
		   type: "POST",
		   url: "/front/transport/findtransferbus/",
		   data: {
				personen: $("#persons").val(),
				plzAbfahrt: plzAbfahrt,
				plzZiel: plzZiel
			},
		   success: function(response){
		     var antwort = eval(response);
		     
		     if(antwort[0].error){
		    	 $('#adresseManuell').validationEngine('showPrompt', systemError, 'error', true);
			     setBlankAll();
		     }
		     else{
			     $("#selectedBus").val(antwort[0].progname);
			     $("#ProgrammId").val(antwort[0].Fa_ID);
			     $("#pricePersons").val(antwort[0].pricePersons);
			     $("#sachleistung").val(antwort[0].sachleistung);
		     }
		   }
		 });
		
	});


	
});

function clearPrompt(){
	$('#formTransferData').validationEngine('hide');
}

function setBlankAll(){
	$("#selectedBus").val('');
    $("#ProgrammId").val('');
    $("#pricePersons").val('');
    $("#sachleistung").val('');
    $('#plzAbfahrt').val('');
    $('#plzZiel').val('');
    $('#adresseAbfahrt').val('');
    $('#adresseZiel').val('');
	
	return;
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
	$("#datum").val(date);
	
	return;
}