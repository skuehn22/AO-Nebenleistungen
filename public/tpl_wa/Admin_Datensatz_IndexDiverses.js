function fillDiverses(){

    if(!programmId){
        showMsgBox('Bitte Programm auswählen');
        return;
    }

     var diversesForm = new Ext.form.FormPanel({
        padding: 10,
        border: false,
        frame: true,
        id: 'diverseFormElements',
        url: '/admin/datensatz/setdiverses/',
        labelWidth: 180,
        items: [{
            layout: 'column',
            border: false,
            items: [{
                columnWidth:0.5,
                layout: 'form',
                border: false,
                items: [{
                    fieldLabel: 'aktiv / passiv / zum löschen vormerken *',
                    labelWidth: 400,
                    xtype: 'combo',
                    id: 'aktivPassivComboBox',
                    allowBlank: false,
                    forceSelection: true,
                    triggerAction: 'all',
                    mode: 'local',
                    name: 'aktiv',
                    store: [[2,'aktiv'],[1,'passiv'],[3,'löschen vormerken']],
                    selectOnFocus: true,
                    hiddenName: 'aktiv',
                    lazyRender:true,
                    typeAhead: false
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'Bestätigungstext 1 (deutsch)',
                    width: 270,
                    name: 'confirm_1_de'
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'Bestätigungstext 2 (deutsch)',
                    width: 270,
                    name: 'confirm_2_de'
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'Bestätigungstext 3 (deutsch)',
                    width: 270,
                    name: 'confirm_3_de'
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'an Programmanbieter 1 (deutsch)',
                    width: 270,
                    name: 'an_prog_1_de'
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'Buchungstext (deutsch)',
                    width: 270,
                    name: 'buchungstext_de'
                }]
            },{
                columnWidth:0.5,
                layout: 'form',
                border: false,
                items: [{
                    fieldLabel: 'Ort *',
                    xtype: 'combo',
                    width: 150,
                    allowBlank: false,
                    forceSelection: true,
                    selectOnFocus: true,
                    typeAhead: true,
                    triggerAction: 'all',
                    displayField: 'city',
                    valueField: 'id',
                    hiddenName: 'AO_City',
                    mode: 'local',
                    store: cityStore
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'Bestätigungstext 1 (englisch)',
                    width: 270,
                    name: 'confirm_1_en'
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'Bestätigungstext 2 (englisch)',
                    width: 270,
                    name: 'confirm_2_en'
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'Bestätigungstext 3 (englisch)',
                    width: 270,
                    name: 'confirm_3_en'
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'an Programmanbieter 1 (englisch)',
                    width: 270,
                    name: 'an_prog_1_en'
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'Buchungstext (englisch)',
                    width: 270,
                    name: 'buchungstext_en'
                }]
            }]
        }]
    });

    var buttonEintragen =  {
        text: 'eintragen',
        icon: '/buttons/vor.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'right',
        handler: function(){
            diversesForm.getForm().submit({
                params: {
                    programmId: programmId
                },
                success: function(form,action){
                    Ext.getCmp('myGrid').store.load();
                    fensterDiverses.close();
                    fillTemplate(1, programmId);
                },
                failure: function(form,action){
                    showMsgBox('Bitte Eingaben überprüfen !');
                }
            });
        }
    };

    var buttonPreise = {
        xtype: 'button',
        text: 'Zusatzinformation',
        icon: '/buttons/zurueck.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'left',
        handler: function(){
            fensterDiverses.close();
            fillZusatzinformation();
        }
    };

    var fensterDiverses = new Ext.Window({
        title: 'diverse Angaben, Id: ' + programmId + ', Programmname: ' + programmName,
        modal: true,
        width: 1000,
        autoHeight: true,
        shadow: false,
        border: false,
        padding: 10,
        border: showCloseButton,
        x: 20,
        y: 20,
        closable: showCloseButton
    });

    fensterDiverses.addButton(buttonPreise);
    fensterDiverses.addButton(buttonEintragen);
    fensterDiverses.add(diversesForm);
    fensterDiverses.doLayout();
    fensterDiverses.show();

    // Ext.getCmp('aktivPassivComboBox').setValue(1);

    diversesForm.load({
        url: '/admin/datensatz/finddiverses/',
        method: 'post',
        params: {
            programmId: programmId
        }
    });

}
