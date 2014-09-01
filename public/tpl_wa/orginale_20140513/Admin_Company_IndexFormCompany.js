Ext.onReady(function(){

     jsonStoreCity = new Ext.data.JsonStore({
        root: 'data',
        method: 'post',
        url: "/admin/hotels/getcountrycities/",
        id: 'jsonStoreRegion',
        fields: ['city','cityId']
    });

    storeAktiv = new Ext.data.SimpleStore({
        fields: ['id', 'aktiv'],
        data: [
                ['1', 'neu'],
                ['2', 'passiv'],
                ['3', 'aktiv']
            ]
    });


    formCompany = Ext.extend(Ext.form.FormPanel, {
        width: 800,
        autoHeight: true,
        layout: 'column',
        frame: true,
        id: 'formCompanyId',
        initComponent: function() {
           Ext.applyIf(this, {
             items: [{
                layout: 'form',
                labelWidth: 120,
                columnWidth: 0.5,
                border: false,
                height: 330,
                items: [{
                    xtype: 'textfield',
                    width: 50,
                    id: 'kundeId',
                    cls: 'readonly',
                    readOnly: true,
                    hideLabel: true
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Firmenname *',
                    width: 250,
                    allowBlank: false,
                    id: 'company_name',
                    helpText: 'Firma',
                    minLength: 3
                },{
                    xtype: 'combo',
                    fieldLabel: 'Titel',
                    width: 150,
                    id: 'zusatz',
                    mode: 'local',
                    store: ['Prof.','Dr.'],
                    selectOnFocus: true,
                    typeAhead: true,
                    triggerAction: 'all',
                    forceSelection: true
                },{
                    xtype: 'combo',
                    fieldLabel: 'Anrede',
                    width: 150,
                    id: 'title',
                    mode: 'local',
                    store: ['Frau','Herr'],
                    forceSelection: true,
                    triggerAction: 'all',
                    selectOnFocus: true,
                    typeAhead: true,
                    // allowBlank: false,
                    helpText: 'Bitte Anrede auswählen'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Vorname',
                    width: 250,
                    // allowBlank: false,
                    id: 'firstname',
                    minLength: 3,
                    helpText: 'Bitte Vorname eintragen'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Familienname',
                    width: 250,
                    // allowBlank: false,
                    id: 'lastname',
                    minLength: 3,
                    helpText: 'Bitte Familienname eintragen'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Land',
                    width: 150,
                    id: 'countryCombo',
                    mode: 'local',
                    helpText: 'Bitte Land auswählen',
                    store: new Ext.data.ArrayStore({
                        fields: ['id', 'country'],
                        data: [['52','Deutschland'],['174','Österreich'],['198','Schweiz'],['232','Tschechien']]
                    }),
                    displayField: 'country',
                    valueField: 'id',
                    hiddenName: 'country',
                    forceSelection: true,
                    triggerAction: 'all',
                    selectOnFocus: true
                },{
                    xtype: 'textfield',
                    fieldLabel: 'PLZ *',
                    width: 50,
                    maxLength: 6,
                    minLength: 4,
                    allowBlank: false,
                    id: 'zip',
                    maskRe: /^[0-9]$/,
                    helpText: 'Bitte PLZ eingeben',
                    listeners: {
                        blur: function(field){

                            if(!this.isValid()){
                                showMsgBox('Bitte PLZ überprüfen !');
                                return;
                            }

                            Ext.Ajax.request({
                                url : '/admin/company/findregion/',
                                method : 'POST',
                                params : {
                                    plz : field.getValue()
                                },
                                success : function(response, action) {
                                    var json =  Ext.decode(response.responseText);
                                    Ext.getCmp('region').setValue(json.region);
                                },
                                failure : function(response, options) {

                                }
                            });

                        }
                    }
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Bundesland',
                    width: 100,
                    id: 'region',
                    readOnly: true,
                    cls: 'readOnly'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Stadt *',
                    width: 150,
                    allowBlank: false,
                    store: jsonStoreCity,
                    displayField: 'city',
                    valueField: 'city',
                    hiddenName: 'city',
                    helpText: 'Bitte Stadt auswählen',
                    // forceSelection: true,
                    triggerAction: 'all',
                    mode: 'local',
                    selectOnFocus: true,
                    typeAhead: true
                },{
                    xtype: 'textarea',
                    id: 'zusatzinformation',
                    width: 250,
                    height: 50,
                    fieldLabel: 'Zusatzinformation'
                }]
        },{
            layout: 'form',
            // frame: true,
            columnWidth: 0.5,
            border: false,
            autoHeight: true,
            labelWidth: 120,
            items: [{
                    xtype: 'textfield',
                    fieldLabel: 'Strasse + Nr. *',
                    helpText: 'Bitte Straße / Hausnummer eingeben',
                    width: 250,
                    allowBlank: false,
                    id: 'street',
                    name: 'street'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Mailadresse *',
                    width: 250,
                    allowBlank: false,
                    id: 'email',
                    name: 'email',
                    helpText: 'Bitte Mailadresse eingeben',
                    vtype: 'email'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Passwort',
                    width: 200,
                    emptyText: 'nur neues Passwort',
                    id: 'password',
                    name: 'password',
                    // value: 'administrator',
                    helpText: 'Bitte nur Passwort eingeben wenn neues Passwort benötigt wird!',
                    minLength: 8
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Telefon *',
                    width: 250,
                    allowBlank: false,
                    helpText: 'Bitte Telefonnummer eingeben',
                    id: 'phonenumber'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Telefon ',
                    helpText: 'Bitte Telefonnummer eingeben',
                    width: 250,
                    id: 'phonenumber1'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Telefon ',
                    helpText: 'Bitte Telefonnummer eingeben',
                    width: 250,
                    id: 'phonenumber2'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Mobile',
                    helpText: 'Bitte Handynummer eingeben',
                    width: 250,
                    id: 'mobile'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Newsletter *',
                    helpText: 'Bitte Newsletter bestätigen',
                    width: 150,
                    id: 'newsletterCombo',
                    mode: 'local',
                    store: new Ext.data.ArrayStore({
                        fields: ['id', 'newsletter'],
                        data: [[2,'ja bitte'],[1,'nein danke']]
                    }),
                    displayField: 'newsletter',
                    valueField: 'id',
                    hiddenName: 'newsletter',
                    forceSelection: true,
                    triggerAction: 'all',
                    allowBlank: false
                },{
                    xtype: 'combo',
                    fieldLabel: 'aktiv *',
                    width: 150,
                    id: 'aktivCombo',
                    mode: 'local',
                     store: new Ext.data.ArrayStore({
                        fields: ['id', 'aktiv'],
                        data: [['3','aktiv'],['2','passiv']]
                    }),
                    displayField: 'aktiv',
                    valueField: 'id',
                    hiddenName: 'aktiv',
                    forceSelection: true,
                    triggerAction: 'all',
                    helpText: 'Aktiv / Passiv Schaltung',
                    allowBlank: false
                }]
        }],
        buttons: [{
            id: 'formButtonAendern',
            text: 'Programmanbieter speichern',
            handler: changeDataProgramProvider
        },{
            id: 'formButtonNeu',
            text: 'neuer Programmanbieter',
            handler: saveNewProgramProvider
        }]
             });

            formCompany.superclass.initComponent.call(this);
        }

    });

});