Ext.onReady(function(){
    formKonzernverantwortlicher = new Ext.form.FormPanel({
        title: 'Personendaten Konzernverantwortlicher',
        width: 900,
        autoHeight: true,
        frame: true,
        border: false,
        padding: 10,
        layout: 'column',
        renderTo: 'formKonzernverantwortlicher',
        items: [{
                layout: 'form',
                labelWidth: 120,
                columnWidth: 0.5,
                // frame: true,
                border: false,
                items: [{
                        xtype: 'combo',
                        fieldLabel: 'Titel',
                        width: 150,
                        id: 'zusatz',
                        mode: 'local',
                        store: ['Prof.','Dr.'],
                        forceSelection: true,
                        triggerAction: 'all'
                    },{
                    xtype: 'combo',
                    fieldLabel: 'Anrede',
                    width: 150,
                    id: 'title',
                    mode: 'local',
                    store: ['Frau','Herr'],
                    forceSelection: true,
                    triggerAction: 'all',
                    allowBlank: false,
                    helpText: 'Bitte Anrede auswählen'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Vorname *',
                    width: 250,
                    allowBlank: false,
                    id: 'firstname',
                    minLength: 3,
                    helpText: 'Bitte Vorname eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Familienname *',
                    width: 250,
                    allowBlank: false,
                    id: 'lastname',
                    minLength: 3,
                    helpText: 'Bitte Familienname eingeben'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Land *',
                    width: 150,
                    id: 'country',
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    store: [[52,'Deutschland'],[174,'Österreich'],[198,'Schweiz']],
                    allowBlank: false,
                    helpText: 'Bitte Land auswählen'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Bundesland *',
                    width: 150,
                    store: jsonStoreRegion,
                    displayField: 'region',
                    valueField: 'region',
                    hiddenName: 'region',
                    allowBlank: false,
                    forceSelection: true,
                    triggerAction: 'all',
                    helpText: 'Bitte Bundesland ( Deutschland ) auswählen'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'PLZ *',
                    width: 50,
                    maxLength: 5,
                    minLength: 4,
                    allowBlank: false,
                    id: 'zip',
                    maskRe: /^[0-9]$/,
                    helpText: 'Bitte PLZ eingeben'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Stadt *',
                    width: 150,
                    allowBlank: false,
                    store: jsonStoreCity,
                    displayField: 'city',
                    valueField: 'city',
                    hiddenName: 'city',
                    forceSelection: true,
                    triggerAction: 'all',
                    helpText: 'Bitte Stadt auswählen'
                }]
        },{
            layout: 'form',
            columnWidth: 0.5,
            labelWidth: 120,
            border: false,
            // frame: true,
            items: [{
                    xtype: 'textfield',
                    width: 50,
                    id: 'id',
                    hidden: true
                },{
                    xtype: 'compositefield',
                    fieldLabel: 'Strasse / Nr.',
                    helpText: 'Bitte Straße / Hausnummer eingeben',
                    items: [{
                        xtype: 'textfield',
                        width: 200,
                        allowBlank: false,
                        id: 'street'
                    },{
                        xtype: 'textfield',
                        width: 50,
                        allowBlank: false,
                        id: 'housenumber'
                    }]
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Mailadresse *',
                    width: 250,
                    allowBlank: false,
                    id: 'email',
                    vtype: 'email',
                    helpText: 'Bitte E-Mailadresse eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Passwort *',
                    width: 200,
                    allowBlank: false,
                    id: 'password1',
                    inputType: 'password',
                    minLength: 8,
                    helpText: 'Bitte Passwort mit min. 8 Zeichen eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Passwort wiederholen*',
                    width: 200,
                    allowBlank: false,
                    id: 'password2',
                    inputType: 'password',
                    minLength: 8,
                    helpText: 'Bitte Passwort wiederholen'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Telefon *',
                    width: 250,
                    allowBlank: false,
                    id: 'phonenumber',
                    helpText: 'Bitte Telefonnummer eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Handy',
                    width: 250,
                    id: 'mobile',
                    helpText: 'Bitte Handynummer eingeben'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Newsletter *',
                    width: 150,
                    displayField: 'newsletter',
                    valueField: 'newsletter',
                    hiddenName: 'newsletter',
                    mode: 'local',
                    store: [[2,'aktiv'],[1,'passiv']],
                    forceSelection: true,
                    triggerAction: 'all',
                    helpText: 'Bitte auswählen, ob ein Newsletter zugesandt wird'
                }
            ]
        }],
        buttons: [{
            text: 'Konzernverantwortlichen speichern',
            listeners: {
                click: neuenKonzernverantwortlichenAnlegen
            }
        }]
    });

    Ext.getCmp('password2').on('blur', function(password2){
        var password1 = Ext.getCmp('password1');

        if(password1.getValue() != password2.getValue()){
            showMsgBox('Passwörter stimmen nicht überein');
            password2.setValue('');
            password1.setValue('');
        }

        return;
    });

});
