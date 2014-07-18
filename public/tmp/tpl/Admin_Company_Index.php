<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript">

    var storeGridCompany;
    var jsonStoreRegion;
    var jsonStoreCity;
    var storeAktiv;

    var gridCompany;
    var formCompany;
    var FormAktivPassiv;

    var memoryCompanyId = null;
    var memoryCompanyName = null;

    var condition_new = '1';
	var condition_passiv = '2';
	var condition_aktiv = '3';
    var condition_loeschen = '4';
    var condition_country_germany = 52;

    function firmaAktivPassiv(){
        if(!_checkIsCompanySelected())
            return;

        childWindow.removeAll();
        var form = new FormAktivPassiv();
        childWindow.add(form);

        childWindow.setTitle('Schaltung der Firma');
        childWindow.doLayout();
        childWindow.show();

        _holenDatenFirmaAktiv();
    }

    function _holenDatenFirmaAktiv(){
        Ext.getCmp('formAktivPassiv').load({
            url: '/admin/company/getactivdatacompany/',
            params: {
                companyId: memoryCompanyId
            }
        });
    }

    function saveAktivCompany(){
        Ext.getCmp('formAktivPassiv').getForm().submit({
            url: '/admin/company/setactivdatacompany/',
            success: function(){
                storeGridCompany.reload();
                childWindow.hide();
            },
            failure: function(){
                showMsgBox('Veränderung nicht eingetragen');
            }
        });
    }

    function findPersonaldataFromCompany(){

        Ext.getCmp('formCompanyId').getForm().load({
            url: '/admin/company/getpersonaldata/',
            method: 'post',
            params: {
                companyId: memoryCompanyId
            },
            failure: function(){
                showMsgBox('Fehler beim ändern der Daten');
            }
        });
    }

    function renderer_aktiv(val){
		var icon = '';

		if(val == condition_new)
			icon = "<img src='/buttons/exclamation.png' ext:qtip='Firma wurde neu angelegt'>";
		else if(val == condition_passiv)
			icon = "<img src='/buttons/cancel.png' ext:qtip='Firma wurde abgeschaltet'>";
		else if(val == condition_aktiv)
			icon = "<img src='/buttons/accept.png'ext:qtip='Firma ist aktiv'>";
        else if(val == condition_loeschen)
            icon = "<img src='/buttons/weather_lightning.png'ext:qtip='Firma zum löschen vorgesehen'>";

		return icon;
	}

    function _checkIsCompanySelected(){
        var selectionModel = gridCompany.getSelectionModel();

        if(!selectionModel.hasSelection()){
            showMsgBox('Bitte Firma auswählen');

            return false;
        }

        memoryCompanyId =   selectionModel.getSelected().data.id;

        return true;
    }

    function getVorhandenerProgrammanbieter(){

        if(!_checkIsCompanySelected())
            return;

        childWindow.removeAll();
        var form = new formCompany();
        form.getForm().reset();

        childWindow.add(form);
        childWindow.setTitle('Daten Programmanbieter');

        formResponsibleFromCompany = form;
        findPersonaldataFromCompany();

        childWindow.doLayout();
        childWindow.show();

        Ext.getCmp('formButtonAendern').show();
        Ext.getCmp('formButtonNeu').hide();

        Ext.getCmp('countryCombo').setValue(condition_country_germany);

        // laden City Store
        jsonStoreCity.load();
    }

    function newProgramProvider(){

        childWindow.removeAll();
        var form = new formCompany();
        form.getForm().reset();

        childWindow.add(form);

        childWindow.setTitle('neuer Programmanbieter');
        childWindow.doLayout();
        childWindow.show();

        Ext.getCmp('formButtonAendern').hide();
        Ext.getCmp('formButtonNeu').show();

        // Vorbelegung Comboboxen
        var newsletterComboBox = Ext.getCmp('newsletterCombo');
        newsletterComboBox.setValue(1);

        var aktivComboBox = Ext.getCmp('aktivCombo');
        aktivComboBox.setValue(3);

        // laden City Store
        jsonStoreCity.load();

    }

    function saveNewProgramProvider(){
        
            Ext.getCmp('formCompanyId').getForm().submit({
                url: '/admin/company/newcompany/',
                success: function(){
                    storeGridCompany.reload();
                    Ext.getCmp('formCompanyId').getForm().reset();
                    showMsgBox('Personendaten wurden eintragen');
                },
                failure: function(){
                    showMsgBox('Bitte überprüfen sie die Daten');
                }
            });

    }

    function changeDataProgramProvider(){

        Ext.getCmp('formCompanyId').getForm().submit({
            url: '/admin/company/updateadmincompany/',
            params: {
                companyId: memoryCompanyId
            },
            success: function(){
                storeGridCompany.load();
                showMsgBox('Personendaten wurden geändert');
                formResponsibleFromCompany.getForm().reset();
                childWindow.hide();
            },
            failure: function(){
                showMsgBox('Bitte überprüfen sie die Daten');
            }
        });

    }

    function fillBestaetigungstexte(){
        if(!memoryCompanyId){
            showMsgBox('Bitte Firma auswählen');

            return;
        }


        var bestaetigungsTexte = new adminCompanyIndexBestaetigungstexte();
        bestaetigungsTexte.start();
    }

</script>
<script type="text/javascript" src="/tpl/Admin_Company_IndexGridVorhandeneFirmen.js"></script>
<script type="text/javascript" src="/tpl/Admin_Company_IndexFormCompany.js"></script>
<script type="text/javascript" src="/tpl/Admin_Company_IndexFormAktivPassiv.js"></script>
<script type="text/javascript" src="/tpl/Admin_Company_IndexBestaetigungstexte.js"></script>
<script type="text/javascript" src="/tpl/Admin_Company_IndexButtons.js"></script>

<div class='span-32' id='info'>
    <div class="blockDescription span-15">
    <h3 style="color: blue">Bearbeiten der Zuordnung von Programmen</h3>
    <br>
    Anlegen oder editieren der Daten eines Programmanbieters.<br>
    </div>
</div>
<div class="span-32">&nbsp;</div>
<div class='span-13' id='panelGridCompany'></div>
<div class="span-32">&nbsp;</div>
<div class="span-22" id='buttons'></div>