Ext.namespace('App','App.stadtbeschreibung','App.stadtbeschreibung.formular','App.stadtbeschreibung.stadtStore');

// Basiselemente

App.stadtbeschreibung.stadtStore = new Ext.data.JsonStore({
    url: '/admin/stadtbeschreibung/storestadtbeschreibung/',
    method: 'post',
    autoLoad: true,
     root: 'data',
     fields: ['id', 'stadt']
});

App.stadtbeschreibung.formular = Ext.extend(Ext.Panel,{

    constructor: function(config){

        Ext.apply(this, {
            width: 840,
            height: 670,
            renderTo: 'formular',
            labelAlign: 'top',
            padding: 10,
            tbar: [{
                xtype: 'tbspacer',
                width: 10
            },{
                xtype: 'tbtext',
                text: 'Bitte Stadt auswählen:'
            },{
                xtype: 'tbspacer',
                width: 5
            },{
                xtype: 'combo',
                store: App.stadtbeschreibung.stadtStore,
                mode: 'local',
                id: 'stadtCombo',
                valueField: 'id',
                displayField: 'stadt',
                hiddenName: 'stadtId',
                triggerAction: 'all',
                typeAhead: true,
                selectOnFocus: true
            },{
                xtype: 'tbspacer',
                width: 10
            },{
                xtype: 'tbtext',
                text: 'Bitte Sprache auswählen:'
            },{
                xtype: 'tbspacer',
                width: 5
            },{
                xtype: 'radiogroup',
                id: 'selectSprache',
                items: [
                    {boxLabel: 'deutsch', name: 'sprache_id', inputValue: '1', checked: true},
                    {boxLabel: 'englisch', name: 'sprache_id', inputValue: '2'}
                ]
            },{
               xtype: 'tbspacer',
               width: 10
            },{
                xtype: 'tbseparator'
            },{
                xtype: 'button',
                text: 'Texte anzeigen',
                tooltip: 'nach Stadttexten in der gewählten Sprache suchen',
                handler: function(){
                    var sprache = Ext.getCmp('selectSprache');
                    var spracheWahl;
                    sprache.eachItem(function(wert){
                        if(wert.checked)
                            spracheWahl = wert.inputValue;
                    });

                    var neueStadtId = Ext.getCmp('stadtCombo').getValue();

                    if(!neueStadtId){
                        showMsgBox('Bitte Stadt wählen');

                        return;
                    }

                    var stadtForm = Ext.getCmp('stadtBeschreibungForm').getForm();
                    stadtForm.reset();
                    stadtForm.load({
                        url: '/admin/stadtbeschreibung/view/',
                        method: 'post',
                        params: {
                            city: neueStadtId,
                            sprache: spracheWahl
                        }
                    });
                }
            },{
                xtype: 'tbseparator'
            }],
            items: [{
                xtype: 'form',
                id: 'stadtBeschreibungForm',
                border: false,
                url: '/admin/stadtbeschreibung/edit',
                method: 'post',
                layout: 'column',
                labelWidth: 300,
                items: [{
                    border: false,
                    columnWidth: 0.5,
                    width: 500,
                    layout: 'form',
                    items: [{
                        xtype: 'label',
                        fieldLabel: 'allgemeine Beschreibung der Stadt'
                    },{
                        xtype: 'textarea',
                        width: 400,
                        height: 250,
                        hideLabel: true,
                        id: 'stadtbeschreibung'
                    }]
                },{
                    columnWidth: 0.5,
                    border: false,
                    layout: 'form',
                    items: [{
                        xtype: 'label',
                        fieldLabel: 'Beschreibung der Übernachtungen in einer Stadt'
                    },{
                        xtype: 'textarea',
                        width: 400,
                        height: 250,
                        hideLabel: true,
                        id: 'hotelbeschreibung'
                    },{
                        xtype: 'label',
                        fieldLabel: 'Beschreibung der Programme in einer Stadt'
                    },{
                        xtype: 'textarea',
                        width: 400,
                        height: 250,
                        hideLabel: true,
                        id: 'programmbeschreibung'
                    }]
                }]
            }]
        });

        App.stadtbeschreibung.formular.superclass.constructor.apply(this, arguments);
    }

});

function adminFormular(){

   var panel = new App.stadtbeschreibung.formular({
       title: 'Stadtbeschreibung',
       buttons: [{
           text: 'speichern der Texte',
           tooltip: 'Beschreibungstexte der Stadt in der gewählten Sprache speichern',
           handler: function(){

               var stadt = Ext.getCmp('stadtCombo').getValue();
               if(!stadt){
                   showMsgBox('Bitte Stadt wählen');

                   return;
               }
               
                var sprache = Ext.getCmp('selectSprache');
                var spracheWahl;
                sprache.eachItem(function(wert){
                if(wert.checked)
                    spracheWahl = wert.inputValue;
                });


               Ext.getCmp('stadtBeschreibungForm').getForm().submit({
                   params: {
                       city_id: stadt,
                       sprache_id: spracheWahl
                   },
                   success: function(){
                       showMsgBox('Stadttexte gespeichert');
                   }
               });
           }
       }]
    });

}