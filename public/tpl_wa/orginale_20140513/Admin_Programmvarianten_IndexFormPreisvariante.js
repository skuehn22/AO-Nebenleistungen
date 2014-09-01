/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 12.12.11
 * Time: 12:39
 * To change this template use File | Settings | File Templates.
 */

Ext.onReady(function(){

    Ext.namespace('MyApps.Programmvarianten');

    formPreisvarianten = new Ext.form.FormPanel({
        id: 'formPreisvariante',
        autoHeight: true,
        width: 400,
        frame: true,
        padding: 10,
        renderTo: 'divFormPreisvarianten',
        method: 'post',
        url: '/admin/programmvarianten/formprogrammvarianten/',
        title: 'neue Preisvariante',
        labelWidth: 120,
        items: [{
                    xtype: 'textfield',
                    id: 'id',
                    width: 50,
                    hidden: true
                },{
                    xtype: 'textfield',
                    id: 'bezeichnung',
                    width: 200,
                    fieldLabel: 'Bezeichnung *',
                    allowBlank:false,
                    helpText: 'Bezeichnung Programmvariante'
                },{
                    xtype: 'textfield',
                    id: 'bezug',
                    width: 200,
                    fieldLabel: 'Bezugsgröße *',
                    allowBlank:false,
                    helpText: 'Bezugsgröße ( Stunden, Mengen ...)'
                },{
                    xtype: 'compositefield',
                    id: 'kombination',
                    helpText: 'Vergleichsparameter und Anzahl',
                    fieldLabel: 'Verrechnung *',
                    items: [
                        {
                            xtype: 'combo',
                            displayField: 'ansatz',
                            valueField: 'ansatz',
                            hiddenName: 'ansatz',
                            forceSelection: true,
                            triggerAction: 'all',
                            allowBlank: false,
                            helpText: 'Vergleichsoperator auswählen',
                            store: [[1,'<'],[2,'='],[3,'>']],
                            width: 50
                        },
                        {
                            xtype: 'numberfield',
                            id: 'anzahl',
                            width: 100,
                            helpText: 'Anzahl der Bezugsgröße'
                    }]
                },{
                    xtype: 'combo',
                    width: 200,
                    mode: 'local',
                    helpText: 'Preisbildung auswählen',
                    forceSelection: true,
                    triggerAction: 'all',
                    selectOnFocus: true,
                    allowBlank: false,
                    typeAhead: true,
                    displayField: 'preistyp',
                    valueField: 'preistyp',
                    hiddenName: 'preistyp',
                    fieldLabel: 'Verrechnung *',
                    store: [[2,'Zuschlag'],[3,'Faktor']]
                },{
                    xtype: 'textarea',
                    width: 200,
                    height: 50,
                    fieldLabel: 'Hinweistext deutsch *',
                    id: 'deutsch',
                    allowBlank: false
                },{
                    xtype: 'textarea',
                    width: 200,
                    height: 50,
                    fieldLabel: 'Hinweistext englisch *',
                    id: 'englisch',
                    allowBlank: false
        }],
        buttons: [{
            text: 'neue Preisvariante speichern',
            id: 'preisvarianteSpeichern',
            listeners: {
                click: eintragenPreisvariante
            }
        }]
    });

});