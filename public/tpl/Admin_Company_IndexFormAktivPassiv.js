FormAktivPassiv = Ext.extend(Ext.form.FormPanel, {
    autoHeight: true,
    autoWidth: true,
    height: 250,
    width: 400,
    padding: 10,
    border: false,
    frame: true,
    method: 'post',
    buttonAlign: 'right',
    id: 'formAktivPassiv',

    initComponent: function() {
        Ext.applyIf(this, {
            items: [{
                    xtype: 'textfield',
                    id: 'companyId',
                    width: 50,
                    fieldLabel: 'Kunden Id',
                    readOnly: true,
                    cls: 'readonly'
                },{
                    xtype: 'textfield',
                    id: 'companyName',
                    width: 300,
                    fieldLabel: 'Firmenname',
                    readOnly: true,
                    cls: 'readonly'
                },{
                    xtype: 'combo',
                    width: 200,
                    mode: 'local',
                    store: [[3,'aktiv'],[2,'passiv'],[4,'vormerken löschen']],
                    displayField: 'displayAktiv',
                    valueField: 'valueAktiv',
                    hiddenName: 'aktiv',
                    forceSelection: true,
                    triggerAction: 'all',
                    allowBlank: false,

                    fieldLabel: 'aktiv / passiv'
            }],
            buttons: [{
                        xtype: 'button',
                        id: 'buttonAktivPassiv',
                        text: 'speichern',
                        handler: saveAktivCompany
                    }]
        });

        FormAktivPassiv.superclass.initComponent.call(this);
    }
});
