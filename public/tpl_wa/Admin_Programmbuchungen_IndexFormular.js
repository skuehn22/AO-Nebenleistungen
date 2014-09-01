/**
 * Created with JetBrains PhpStorm.
 * User: PC Nutzer
 * Date: 20.07.12
 * Time: 10:59
 * To change this template use File | Settings | File Templates.
 */

MyFormUi = Ext.extend(Ext.form.FormPanel, {
    height: 559,
    width: 328,
    padding: 10,
    title: 'Programmsuche',

    initComponent: function() {
        Ext.applyIf(this, {
            items: [
                {
                    xtype: 'textfield',
                    id: 'suche_name',
                    width: 200,
                    name: 'suche_name',
                    fieldLabel: 'Name'
                },
                {
                    xtype: 'textfield',
                    id: 'suche_vorname',
                    width: 200,
                    name: 'suche_vorname',
                    fieldLabel: 'Vorname'
                },
                {
                    xtype: 'textfield',
                    id: 'suche_kundennummer',
                    width: 200,
                    name: 'suche_kundennummer',
                    fieldLabel: 'Kundennummer'
                },
                {
                    xtype: 'textfield',
                    id: 'suche_rechnungsnummer',
                    width: 200,
                    name: 'suche_rechnungsnummer',
                    fieldLabel: 'Rechnungsnummer'
                },
                {
                    xtype: 'datefield',
                    id: 'suche_datum',
                    width: 100,
                    name: 'suche_datum',
                    fieldLabel: 'Ort'
                },
                {
                    xtype: 'textfield',
                    id: 'suche_programmname',
                    width: 200,
                    name: 'suche_programmname',
                    fieldLabel: 'Ort'
                },
                {
                    xtype: 'textfield',
                    id: 'suche_ort',
                    width: 200,
                    name: 'suche_ort',
                    fieldLabel: 'Ort'
                }
            ]
        });

        MyFormUi.superclass.initComponent.call(this);
    }
});
