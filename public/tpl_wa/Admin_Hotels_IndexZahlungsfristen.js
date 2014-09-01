/**
 * Verwaltung der Zahlungsziele eines Hotels
 *
 */

var adminHotelIndexZahlungsfristen = function(){
    // Beginn Private

    var HotelId = null;
    var Formular = null;

    var listeTageZahlungsziel = [
        [0, 'Bezahlung im Hotel'],
        [1, '1 Tag'],
        [2, '2 Tage'],
        [3, '3 Tage'],
        [4, '4 Tage'],
        [5, '5 Tage'],
        [6, '6 Tage'],
        [7, '1 Woche'],
        [14, '2 Wochen'],
        [21, '3 Wochen'],
        [28, '4 Wochen'],
        [999, 'sofort']
    ];

    // Ende Private
    // Beginn Public
    return {
        start: function(iDdesHotels){

            HotelId = iDdesHotels;
            Formular = this.formular;

            this.fenster.title = 'Zahlungsziele eines Hotels ' + '"' + memoryHotelName + '"';
            this.fenster.add(this.formular);

            this.fenster.show();
            this.formularLaden();
        },

        formularLaden: function(){
            Formular.getForm().load({
                params: {
                    selectHotelId: HotelId
                }
            });

            return;
        },

        formular: new Ext.form.FormPanel({
            url: '/admin/hotels/holezahlungsziele/',
            method: 'post',
            autoHeight: true,
            width: 450,
            padding: 10,
            items: [{
                xtype: 'displayfield',
                value: 'Eingabe der Zahlungen in %. Zahlungen werden fällig entsprechend der Tage vor Anreise im Hotel.',
                style: {
                    marginBottom: '10px'
                }
                },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'combo',
                    store: listeTageZahlungsziel,
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    valueField: 'tage1',
                    hiddenName: 'tage1',
                    typeAhead: true,
                    fieldLabel: '1. Zahlungsziel',
                    width: 150
                },{
                    xtype: 'textfield',
                    name: 'prozente1',
                    width: 50,
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
                },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'combo',
                    store: listeTageZahlungsziel,
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    valueField: 'tage2',
                    hiddenName: 'tage2',
                    typeAhead: true,
                    fieldLabel: '2. Zahlungsziel',
                    width: 150
                },{
                    xtype: 'textfield',
                    name: 'prozente2',
                    width: 50,
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'combo',
                    store: listeTageZahlungsziel,
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    valueField: 'tage3',
                    hiddenName: 'tage3',
                    typeAhead: true,
                    fieldLabel: '3. Zahlungsziel',
                    width: 150
                },{
                    xtype: 'textfield',
                    name: 'prozente3',
                    width: 50,
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
                        url: '/admin/hotels/speicherezahlungsziele/',
                        method: 'post',
                        params: {
                            selectHotelId: HotelId
                        },
                        success: function(){
                            Ext.getCmp('adminHotelIndexZahlungsfristenFenster').close();
                        }
                    });
                }
            }]
        }),

        fenster: new Ext.Window({
            shadow: false,
            width: 500,
            autoHeight: true,
            id: 'adminHotelIndexZahlungsfristenFenster',
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

function fillZahlungsfristenHotel(){
    if(!memoryHotelId){
        showMsgBox('Bitte ein Hotel auswählen');

        return;
    }

    var zahlungsziele = new adminHotelIndexZahlungsfristen();
    zahlungsziele.start(memoryHotelId);
}
