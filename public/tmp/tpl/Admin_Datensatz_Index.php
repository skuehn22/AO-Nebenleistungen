<?php if(!class_exists('raintpl')){exit;}?><!-- extensions -->
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexPreisvarianten.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexFormEditor.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexFormSprache.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexFormTermine.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexDiverses.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexOeffnungszeiten.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexZusatzinformation.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexStornofristen.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexKommentar.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_Reception.js"></script>
<script type="text/javascript" src="/tpl/Admin_Datensatz_IndexKapazitaet.js"></script>

<script type="text/javascript" src="/extjs/ux/form/cleanHtml.js"></script>

<script type="text/javascript">

    // Button für Administrator
    var showCloseButton = <?php echo $showCloseButton;?>;

    // Steuerung der Child Views
    var programmId = null;
    var programmName = null;
    var cityId = null;

    var vorauswahlCompany = '<?php echo $vorauswahlCompany;?>';

	var grid;
	var gridStore;
	var cityStore;
	var panel;
    
    var moles;
    var dummyBild;

    var sprache;
    var preiseForm;
    var termineForm;
    var storePreiseMwst;
    var diversesForm;
    var countryStore;
    var bundeslandStore;
    var jsonStoreSperrtage;
    var checkboxSelSperrtage;
    var gridPreisVarianten;
    var jsonStoreGridPreisVarianten;
    var spracheFenster;

    var test;

	Ext.onReady(function(){

		cityStore = new Ext.data.JsonStore({
	        root: 'data',
	        method: 'post',
	        url: "/admin/datensatz/getcities/",
	        id: 'cities',
	        fields: ['id','city'],
	        autoLoad: true
		}); 

		gridStore = new Ext.data.JsonStore({
	        totalProperty: 'anzahl',
	        root: 'data',
	        method: 'post',
	        url: "/admin/datensatz/tabelitems/",
	        id: 'jsonStore',
	        fields: ['id','progname','AO_City','company','cityId','aktiv','buchungstyp','oeffnungszeitentyp','sichtbarAdmin','kooperationHob','kooperationAustria','kooperationAo']
	    });

        countryStore = new Ext.data.JsonStore({
            root: 'data',
            method: 'post',
            url: '/admin/datensatz/findcountries/',
            id: 'countryStore',
            fields: ['id','de'],
            autoLoad: true
        });
        
        bundeslandStore = new Ext.data.JsonStore({
            root: 'data',
            method: 'post',
            url: '/admin/datensatz/findbundesland/',
            id: 'bundeslandStore',
            fields: ['id','bundesland'],
            autoLoad: true
        });

        // Funktion suche Programm
        function sucheProgramm(){
            var progSearch = Ext.getCmp('progSearch').getValue();
            var city = Ext.getCmp('cityCombo').getValue();

            gridStore.setBaseParam('progsearch', progSearch);
            gridStore.setBaseParam('city', city);

            var companySearch = Ext.getCmp('companySearch').getValue();
            gridStore.setBaseParam('company', companySearch);

            var idSearch = Ext.getCmp('idSearch').getValue();
            gridStore.setBaseParam('idSearch', idSearch);

            gridStore.load();
        }

        // Toolbar Top Tabelle
        var tbar = new Ext.Toolbar({
            items  : [{
                text: 'Programm ID :'
            },{
                xtype: 'textfield',
                width: 50,
                name: 'idSearch',
                id: 'idSearch',
                enableKeyEvents: true,
                listeners: {
                    'keypress': function(field,event){
                        if (event.getKey() == event.ENTER){
                            sucheProgramm();
                        }
                    }
                }
            },{
                xtype: 'tbseparator'
            },{
                text: 'Programmbezeichnung :'
            },{
                xtype: 'textfield',
                width: 150,
                name: 'progSearch',
                id: 'progSearch',
                enableKeyEvents: true,
                listeners: {
                    'keypress': function(field,event){
                        if (event.getKey() == event.ENTER){
                            sucheProgramm();
                        }
                    }
                }
            },{
                xtype: 'tbseparator'
            },{
                text: 'Stadt :'
            },{
                xtype: 'combo',
                width: 150,
                id: 'cityCombo',
                forceSelection: true,
                triggerAction: 'all',
                displayField: 'city',
                valueField: 'id',
                selectOnFocus: true,
                hiddenName: 'city',
                mode: 'local',
                store: cityStore,
                typeAhead: true
            },{
                xtype: 'tbseparator'
            },{
                text: 'Firma:'
            },{
                xtype: 'textfield',
                width: 150,
                name: 'companySearch',
                id: 'companySearch',
                enableKeyEvents: true,
                listeners: {
                    'keypress': function(field,event){
                        if (event.getKey() == event.ENTER){
                            sucheProgramm();
                        }
                    }
                }
            },{
                xtype: 'tbseparator'
            },{
                xtype: 'button',
                text: 'suche',
                icon: '/buttons/arrow_right.png',
                handler: sucheProgramm
            },{
                xtype: 'tbseparator'
            }]
        });

        // Breich Tabelle der vorhandenen Programme
		grid = new Ext.grid.GridPanel({
			autoHeight: true,
	        stripeRows: true,
	        loadMask: true,
	        columnLines: true,
			autoExpandColumn: 'progname',
	        store: gridStore,
	        width: 1000,
	        id: 'myGrid',
	        title: 'vorhandene Programme',
            selModel: new Ext.grid.CellSelectionModel({
                listeners: {
                    cellselect: function(){
                        programmId = arguments[0].selection.record.data.id;
                        programmName = arguments[0].selection.record.data.progname;

                        var varianteSpalteUmschaltung = arguments[2];
                        var zustandSpalte = 0;

                        switch(varianteSpalteUmschaltung){
                            case 5:
                                umschaltenAktivPassiv('aktiv');
                                zustandSpalte = arguments[0].selection.record.get('aktiv');

                                if(zustandSpalte == 1)
                                    arguments[0].selection.record.set('aktiv',2);
                                else
                                    arguments[0].selection.record.set('aktiv',1);
                                break;
                            case 6:
                                umschaltenBuchungsmodus();
                                zustandSpalte = arguments[0].selection.record.get('buchungstyp');

                                if(zustandSpalte == 1)
                                    arguments[0].selection.record.set('buchungstyp',2);
                                else
                                    arguments[0].selection.record.set('buchungstyp',1);
                                break;
                            case 7:
                                umschaltenAktivPassiv('sichtbarHob');
                                zustandSpalte = arguments[0].selection.record.get('kooperationHob');

                                if(zustandSpalte == 1)
                                    arguments[0].selection.record.set('kooperationHob',2);
                                else
                                    arguments[0].selection.record.set('kooperationHob',1);
                                break;
                            case 8:
                                umschaltenAktivPassiv('sichtbarAustria');
                                zustandSpalte = arguments[0].selection.record.get('kooperationAustria');

                                if(zustandSpalte == 1)
                                    arguments[0].selection.record.set('kooperationAustria',2);
                                else
                                    arguments[0].selection.record.set('kooperationAustria',1);
                                break;
                            case 9:
                                umschaltenAktivPassiv('sichtbarAo');
                                zustandSpalte = arguments[0].selection.record.get('kooperationAo');

                                if(zustandSpalte == 1)
                                    arguments[0].selection.record.set('kooperationAo',2);
                                else
                                    arguments[0].selection.record.set('kooperationAo',1);
                                break;
                        }

                    }
                }
            }),
            viewConfig: {
                forceFit: true,
                scrollOffset: 0
            },
		    columns: [{
		                xtype: 'numbercolumn',
		                dataIndex: 'id',
		                header: 'Programm ID',
		                sortable: true,
		                width: 100,
		                format: '0',
		                id: 'id'
		            },{
		                xtype: 'gridcolumn',
		                header: 'Programmname',
		                sortable: true,
		                dataIndex: 'progname',
			            id: 'progname'
		            },{
		                xtype: 'gridcolumn',
		                header: 'Stadt',
		                sortable: true,
		                width: 150,
		                dataIndex: 'AO_City',
			            id: 'AO_City'
		     		},{
		                xtype: 'gridcolumn',
		                header: 'Firma',
		                sortable: true,
		                width: 250,
		                dataIndex: 'company',
			            id: 'company'
		     		},{
		                xtype: 'gridcolumn',
		                header: 'cityId',
		                hidden: true,
		                width: 50,
		                dataIndex: 'cityId',
			            id: 'cityId'
		     		},{
						xtype: 'gridcolumn',
						header: 'aktiv / Passiv',
                        tooltip: 'Programm aktiv oder passiv schalten',
						width: 50,
						dataIndex: 'aktiv',
						id: 'aktiv',
						renderer: rendererAktivProgramm
			     	},{
                        xtype: 'gridcolumn',
                        header: 'Offline / Online',
                        tooltip: 'Buchungstyp Programm Offline oder Online',
                        width: 60,
                        dataIndex: 'buchungstyp',
                        id: 'buchungstyp',
                        renderer: rendererBuchungstyp
                    },{
                        xtype: 'gridcolumn',
                        header: 'HOB',
                        tooltip: 'zugehörig Kooperation HOB',
                        width: 50,
                        dataIndex: 'kooperationHob',
                        id: 'sichtbarHob',
                        renderer: rendererSichtbarHob
                    },{
                        xtype: 'gridcolumn',
                        header: 'Austria',
                        tooltip: 'zugehörig Kooperation Austria',
                        width: 50,
                        dataIndex: 'kooperationAustria',
                        id: 'sichtbarAustria',
                        renderer: rendererSichtbarAustria
                    },{
                        xtype: 'gridcolumn',
                        header: 'AO',
                        tooltip: 'zugehörig Kooperation AO',
                        width: 50,
                        dataIndex: 'kooperationAo',
                        id: 'sichtbarAo',
                        renderer: rendererSichtbarAo
            }],
		     bbar: [{
		            xtype: 'paging',
		            store: gridStore,
		            id: 'paging',
		            pageSize: 20,
		            displayMsg: "Anzeige: {0} - {1} von {2} ",
		            displayInfo: true
		     }],
		     tbar: tbar
		});

        // Bereich Button
        var buttonPanel = new Ext.Panel({
            layout: 'table',
            defaultType: 'button',
            baseCls: 'x-plain',
            cls: 'btn-panel',
            renderTo: 'buttonsBereich',
            menu: undefined,
            split: false,
            layoutConfig: {
                columns: 5
            },
            defaults: {
                style: 'margin: 3px;',
                width: 150
            },
            items: [{
                    text: 'Zusatzinformation',
                    handler: function(){
                        fillZusatzinformation();
                    }
                },{
                    text: 'Diverses',
                    handler: function(){
                        fillDiverses();
                    }
                },{
                    text: 'deutsch',
                    handler: function(){
                        fillTemplate(1);
                    }
                },{
                    text: 'englisch',
                    handler: function(){
                        fillTemplate(2)
                    }
                },{
                    text: 'Programmsprachen',
                    handler: function(){
                        fillSprache();
                    }
                },{
                    text: 'Öffnungszeiten Wochentage',
                    handler: function(){
                        fillOeffnungszeiten();
                    }
                },{
                    text: 'Termine',
                    handler: function(){
                        fillTermine();
                    }
                },{
                    text: 'Stornofristen',
                    handler: function(){
                        fillStornofristen();
                    }
                },{
                    text: 'Preisvarianten',
                    handler: function(){
                        fillPreise();
                    }
                },{
                    text: 'Kommentar',
                    handler: function(){
                        fillKommentar();
                    }
                },{
                    text: 'Kapazität',
                    handler: function(){
                        fillKapazitaet();
                    }
                },{
                    text: 'Zusatzinformation Reception',
                    handler: function(){
                        eingabeZusatzinformationReception();
                    }
                }]
        });

        // Umschalten Buchungsmodus
        function umschaltenBuchungsmodus()
        {
            if(!programmId){
                showMsgBox('Bitte Programm auswählen !');

                return;
            }

            Ext.Ajax.request({
               url: '/admin/datensatz/wechsel-buchungstyp/',
               success: function(){
                   // gridStore.load();
               },
               params: {
                    programmId: programmId
               }
            });
        }

        // Eingabeformular Reception
        function eingabeZusatzinformationReception()
        {
            if(!programmId){
                showMsgBox('Bitte Programm auswählen !');

                return;
            }

            var reception = new adminDatensatzIndexReception();
            reception.setProgrammId(programmId);
            reception.start();

        }

        // Umschalten HOB Aktiv / Passiv
        function umschaltenAktivPassiv(spalte)
        {
            if(!programmId){
                showMsgBox('Bitte Programm auswählen !');

                return;
            }

            Ext.Ajax.request({
               url: '/admin/datensatz/wechsel-aktiv/',
               success: function(){
                    // gridStore.load();
               },
               params: {
                   programmId: programmId,
                   spalte: spalte
               }
            });
        }

        // Speicherung der Steuerungsvariablen der Child Views
        // wenn ein Programm in der Tabelle markiert wurde

		grid.getSelectionModel().on('rowselect', function(sm, rowIndex, record){
			programmId = record.data.id;
            programmName = record.data.progname
			cityId = record.data.cityId;
		});

		grid.render('grid');
		gridStore.load();

        // Vorauswahl der Suchparameter Firma
        if(vorauswahlCompany){
            Ext.getCmp('companySearch').setValue(vorauswahlCompany);
        }
        
	});

	function rendererAktivProgramm(val){
		if(val == '2')
			return "<img src='/buttons/accept.png' ext:qtip='Programm ist aktiv geschaltet'>";
		else if(val == 1)
			return "<img src='/buttons/cancel.png' ext:qtip='Programm ist passiv geschaltet'>";
        else if(val == '3')
            return "<img src='/buttons/weather_lightning.png' ext:qtip='Programm zum löschen vorgemerkt'>";
	}

    function rendererBuchungstyp(val){
        if(val == '1')
            return "<img src='/buttons/cancel.png' ext:qtip='Modus Offline Buchung'>";
        else if(val == '2')
            return "<img src='/buttons/accept.png' ext:qtip='Modus Online Buchung'>";
    }

    function rendererOeffnungszeitentyp(val)
    {
        // keine Startzeiten und Öffnungszeiten, Datum
        if(val == '1')
            return "<img src='/buttons/cancel.png' ext:qtip='keine Startzeiten und Öffnungszeiten, Datum'>";
        // Öffnungszeiten und Datum, keine Startzeiten
        if(val == '2')
            return "<img src='/buttons/accept.png' ext:qtip='Öffnungszeiten und Datum, keine Startzeiten'>";
        // Startzeiten und Datum, keine Öffnungszeiten
        if(val == '3')
            return "<img src='/buttons/accept.png' ext:qtip='Startzeiten und Datum, keine Öffnungszeiten'>";
        // kein Datum und Öffnungszeiten und Starttermine
        if(val == '4')
            return "<img src='/buttons/clock_stop.png' ext:qtip='kein Datum und Öffnungszeiten und Starttermine'>";

    }

    function rendererSichtbarAdmin(val)
    {
        // nicht sichtbar für Rolle 'Admin'
        if(val == '1')
            return "<img src='/buttons/cancel.png' ext:qtip='nicht sichtbar für Admin'>";
        // sichtbar für Rolle Admin
        else if(val == '2')
            return "<img src='/buttons/accept.png' ext:qtip='sichtbar für Admin'>";
    }

    function rendererSichtbarHob(val)
    {
        // zugehörig zur Kooperation Austria
        if(val == '2')
            return "<img src='/buttons/accept.png' ext:qtip='zugehörig Kooperation HOB'>";
        else
            return "<img src='/buttons/cancel.png' ext:qtip='gehört nicht zur Kooperation HOB'>";
    }

    function rendererSichtbarAustria(val)
    {
        // zugehörig zur Kooperation Austria
        if(val == '2')
            return "<img src='/buttons/accept.png' ext:qtip='zugehörig Kooperation Austria'>";
        else
            return "<img src='/buttons/cancel.png' ext:qtip='gehört nicht zur Kooperation Austria'>";
    }

    function rendererSichtbarAo(val)
    {
        // zugehörig zur Kooperation Austria
        if(val == '2')
            return "<img src='/buttons/accept.png' ext:qtip='zugehörig Kooperation AO'>";
        else
            return "<img src='/buttons/cancel.png' ext:qtip='gehört nicht zur Kooperation AO'>";
    }

	function rendererFlag(val){
		return "<img src='/flags/" + val + ".png'>"
	}

    function rendererBerechneWochentag(val){

        var dd = val.substr(0,2);
        var mm = val.substr(3,2);
        var yyyy = val.substr(6,4);
        var wochentag = objectDatumsBerechnung.ermittleWochentag(dd,mm,yyyy);

        return wochentag;
    }

    function loescheSelectedSperrtage(){

        var selectedRecords = [];
        selectedRecords = checkboxSelSperrtage.getSelections();
        jsonStoreSperrtage.remove(selectedRecords);
    }

</script>


<div class='span-20 blockDescription' id='info'>
        <h3 style="color: blue">Bearbeiten der Daten eines Programmes</h3>
        Überarbeitung der Daten eines Programmdatensatzes.
</div>
<div class="span-32">&nbsp;</div>
<div class='span-30' id='grid'></div>
<div class='span-30'>&nbsp;</div>
<div class='span-30' id='buttonsBereich'></div>