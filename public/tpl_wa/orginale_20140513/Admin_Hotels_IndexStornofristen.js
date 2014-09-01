/**
 * Verwaltung der Zahlungsziele eines Programmes1
 *
 */

var adminHotelIndexStornofristen = function(){
    // Beginn Private

    var HotelId = null;
    var Formular = null;

    var listeTageStornofristen = [
        [0, 'kein Eintrag'],
        [1, '1 Tag'],
        [2, '2 Tage'],
        [3, '3 Tage'],
        [4, '4 Tage'],
        [5, '5 Tage'],
        [6, '6 Tage'],
        [7, 'ab 7 Tage vor Anreise'],
        [8, 'bis 8 Tage vor Anreise'],
        [14, 'bis 2 Wochen vor Anreise'],
        [21, '3 Wochen'],
        [28, 'bis 4 Wochen vor Anreise'],
        [999, 'kein Storno']
    ];

    // Ende Private
    // Beginn Public
    return {
        start: function(){
            HotelId = memoryHotelId;
            Formular = this.formular;

            this.fenster.title = 'Stornofristen' + '"' + memoryHotelName + '"'
            this.fenster.add(this.formular);
            this.fenster.show();
            this.formularLaden();
        },

        formularLaden: function(){
            Formular.getForm().load({
                params: {
                    HotelId: HotelId
                }
            });

            Ext.getCmp('adminHotelIndexStornofristenTage1').setValue(28);
            Ext.getCmp('adminHotelIndexStornofristenProzente1').setValue(0);

            Ext.getCmp('adminHotelIndexStornofristenTage2').setValue(14);
            Ext.getCmp('adminHotelIndexStornofristenProzente2').setValue(50);

            Ext.getCmp('adminHotelIndexStornofristenTage3').setValue(8);
            Ext.getCmp('adminHotelIndexStornofristenProzente3').setValue(75);

            Ext.getCmp('adminHotelIndexStornofristenTage4').setValue(7);
            Ext.getCmp('adminHotelIndexStornofristenProzente4').setValue(90);

            return;
        },

        formular: new Ext.form.FormPanel({
            url: '/admin/hotels/holestornofristen/',
            method: 'post',
            autoHeight: true,
            width: 450,
            padding: 10,
            items: [{
                xtype: 'displayfield',
                id: 'adminHotelIndexStornofristenInfo',
                value: 'Eingabe der Stornofristen in %. Stornogebühren werden fällig entsprechend der Tage vor dem Datum der Anreise.',
                style: {
                    marginBottom: '10px'
                }
                },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'combo',
                    store: listeTageStornofristen,
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    valueField: 'tage1',
                    hiddenName: 'tage1',
                    id: 'adminHotelIndexStornofristenTage1',
                    typeAhead: true,
                    fieldLabel: '1. Stornofrist',
                    listeners: {
                        change: function(combo, newValue, oldValue){
                            if(newValue == 999){
                                Ext.getCmp('adminHotelIndexStornofristenProzente1').setValue(100);
                            }
                            if(newValue == 0){
                                Ext.getCmp('adminHotelIndexStornofristenProzente1').setValue(0);
                            }
                        }
                    },
                    width: 200
                },{
                    xtype: 'textfield',
                    name: 'prozente1',
                    width: 50,
                    id: 'adminHotelIndexStornofristenProzente1',
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
                },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'combo',
                    store: listeTageStornofristen,
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    valueField: 'tage2',
                    hiddenName: 'tage2',
                    id: 'adminHotelIndexStornofristenTage2',
                    typeAhead: true,
                    fieldLabel: '2. Stornofrist',
                    listeners: {
                        change: function(combo, newValue, oldValue){
                            if(newValue == 999){
                                Ext.getCmp('adminHotelIndexStornofristenProzente2').setValue(100);
                            }
                            if(newValue == 0){
                                Ext.getCmp('adminHotelIndexStornofristenProzente2').setValue(0);
                            }
                        }
                    },
                    width: 200
                },{
                    xtype: 'textfield',
                    name: 'prozente2',
                    width: 50,
                    id: 'adminHotelIndexStornofristenProzente2',
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'combo',
                    store: listeTageStornofristen,
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    valueField: 'tage3',
                    hiddenName: 'tage3',
                    id: 'adminHotelIndexStornofristenTage3',
                    typeAhead: true,
                    fieldLabel: '3. Stornofrist',
                    listeners: {
                        change: function(combo, newValue, oldValue){
                            if(newValue == 999){
                                Ext.getCmp('adminHotelIndexStornofristenProzente3').setValue(100);
                            }
                            if(newValue == 0){
                                Ext.getCmp('adminHotelIndexStornofristenProzente3').setValue(0);
                            }
                        }
                    },
                    width: 200
                },{
                    xtype: 'textfield',
                    name: 'prozente3',
                    width: 50,
                    id: 'adminHotelIndexStornofristenProzente3',
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'combo',
                    store: listeTageStornofristen,
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    valueField: 'tage4',
                    hiddenName: 'tage4',
                    id: 'adminHotelIndexStornofristenTage4',
                    typeAhead: true,
                    fieldLabel: '4. Stornofrist',
                    listeners: {
                        change: function(combo, newValue, oldValue){
                            if(newValue == 999){
                                Ext.getCmp('adminHotelIndexStornofristenProzente4').setValue(100);
                            }
                            if(newValue == 0){
                                Ext.getCmp('adminHotelIndexStornofristenProzente4').setValue(0);
                            }
                        }
                    },
                    width: 200
                },{
                    xtype: 'textfield',
                    name: 'prozente4',
                    width: 50,
                    id: 'adminHotelIndexStornofristenProzente4',
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            }],
            buttons: [{
                text: 'speichern',
                handler: function(){
                    Formular.getForm().submit({
                        url: '/admin/hotels/speicherestornofristen/',
                        method: 'post',
                        params: {
                            HotelId: HotelId
                        },
                        success: function(){
                            Ext.getCmp('adminHotelIndexStornofristenFenster').close();
                        }
                    });
                }
            }]
        }),

        fenster: new Ext.Window({
            shadow: false,
            width: 500,
            autoHeight: true,
            id: 'adminHotelIndexStornofristenFenster',
            border: false,
            modal: true,
            padding: 10,
            closable: showCloseButton,
            x: 20,
            y: 20
        })
    }
    // Ende Public
}

function fillStornofristenHotel(){
    if(!memoryHotelId){
        showMsgBox('Bitte ein Hotel auswählen');

        return;
    }

    var stornofristen = new adminHotelIndexStornofristen();
    stornofristen.start(memoryHotelId);
}
