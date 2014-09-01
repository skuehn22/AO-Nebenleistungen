function fillRateneingabe(){

    var tageAnzahl = [[0],
    [1],
    [2],
    [3],
    [4],
    [5],
    [6],
    [7],
    [8],
    [9],
    [10]];

    var tageStore = new Ext.data.SimpleStore({
        fields:['tage'],
        data: tageAnzahl
    });

    var tageMinimal = [[1],
    [2],
    [3],
    [4],
    [5],
    [6],
    [7],
    [8],
    [9],
    [10],
    [11],
    [12]];

    var tageMinimalStore = new Ext.data.SimpleStore({
        fields:['minimal'],
        data: tageMinimal
    });

    var storeHotels = new Ext.data.JsonStore({
        url: '/admin/Ratenverfuegbarkeit/hotellist/',
        root: 'data',
        fields: ['id','hotelname']
    });

    var storeRaten = new Ext.data.JsonStore({
        url: '/admin/Ratenverfuegbarkeit/getratenhotel/',
        root: 'data',
        fields: ['id', 'ratenname']
    });

    var formRateneingabe = new Ext.form.FormPanel({
        id: 'ratenverfuegbarkeit',
        width: 700,
        autoHeight: true,
        padding: 10,
        renderTo: 'formRateneingabe',
        title: 'manuelle Eingabe der der Raten',
        labelWidth: 300,
        url: '/admin/Ratenverfuegbarkeit/saverate/',
        method: 'post',
        items: [{
                xtype: 'combo',
                id: 'hotels',
                width: 150,
                fieldLabel: 'Hotels',
                store: storeHotels,
                displayField: 'hotelname',
                valueField: 'id',
                typeAhead: true,
                editable: false,
                mode: 'remote',
                allowBlank: false,
                forceSelection: true,
                triggerAction: 'all',
                emptyText: 'Hotel wählen',
                selectOnFocus: true,
                listeners: {
                    select: function(cmb, rec, idx) {

                        Ext.getCmp('uebersicht').setDisabled(false);

                        memoryHotelId = this.getValue();

                        var raten=Ext.getCmp('raten');
                        raten.clearValue();
                        raten.store.load({
                            params: {
                                'hotelId': memoryHotelId
                            }
                        });
                        raten.enable();
                    }
                }
            },{
                xtype: 'combo',
                id: 'raten',
                width: 150,
                allowBlank: false,
                fieldLabel: 'Raten des Hotels *',
                mode: 'local',
                store: storeRaten,
                displayField: 'ratenname',
                valueField: 'id',
                typeAhead: true,
                editable: false,
                disabled: true,
                allowBlank: false,
                forceSelection: true,
                triggerAction: 'all',
                emptyText: 'Rate wählen',
                selectOnFocus: true,
                helpText: 'Rate des Hotels auswählen',
                listeners: {
                    select: function(){
                        memoryRatenId = this.getValue();
                    }
                }
            },{
                xtype: 'datefield',
                id: 'von',
                allowBlank: false,
                fieldLabel: 'von *',
                helpText: 'Datum von'
            },{
                xtype: 'datefield',
                id: 'bis',
                allowBlank: false,
                fieldLabel: 'bis *',
                helpText: 'Datum bis einschließlich betreffender Tag'
            },{
                xtype: 'numberfield',
                id: 'anzahl',
                width: 50,
                fieldLabel: 'Anzahl *',
                allowDecimals: false,
                allowNegative: false,
                helpText: 'Anzahl der Raten'
            },{
                xtype: 'numberfield',
                id: 'preis',
                width: 50,
                fieldLabel: 'Preis *',
                allowNegative: false,
                decimalSeparator: ',',
                helpText: 'Preis in €'
            },{
                xtype: 'combo',
                id: 'preistypen',
                width: 150,
                allowBlank: false,
                fieldLabel: 'Preistypen *',
                mode: 'local',
                store: ['Zimmerpreis','Personenpreis'],
                typeAhead: true,
                editable: false,
                allowBlank: false,
                forceSelection: true,
                triggerAction: 'all',
                emptyText: 'Preistyp wählen',
                selectOnFocus: true,
                helpText: 'Personenpreis oder Zimmerpreis auswählen'
            },{
                xtype: 'checkboxgroup',
                id: 'anreise',
                width: 400,
                helpText: 'mögliche Anreisetage in der Woche',
                fieldLabel: 'Anreisetage',
                items: [{
                        xtype: 'checkbox',
                        id: 'montagAn',
                        value: 1,
                        boxLabel: 'Mo.'
                    },{
                        xtype: 'checkbox',
                        id: 'dienstagAn',
                        value: 1,
                        boxLabel: 'Die.'
                    },{
                        xtype: 'checkbox',
                        id: 'mittwochAn',
                        value: 1,
                        boxLabel: 'Mi.'
                    },{
                        xtype: 'checkbox',
                        id: 'donnerstagAn',
                        value: 1,
                        boxLabel: 'Do.'
                    },{
                        xtype: 'checkbox',
                        id: 'freitagAn',
                        value: 1,
                        boxLabel: 'Fr.'
                    },
                    {
                        xtype: 'checkbox',
                        id: 'sonnabendAn',
                        value: 1,
                        boxLabel: 'Sa.'
                    },{
                        xtype: 'checkbox',
                        id: 'sonntagAn',
                        value: 1,
                        boxLabel: 'So.'
                    }]
            },{
                xtype: 'checkboxgroup',
                id: 'abreise',
                width: 400,
                fieldLabel: 'Abreisetage',
                helpText: 'mögliche Abreisetage in der Woche',
                items: [{
                        xtype: 'checkbox',
                        id: 'montagAb',
                        value: 1,
                        boxLabel: 'Mo.'
                    },{
                        xtype: 'checkbox',
                        id: 'dienstagAb',
                        value: 1,
                        boxLabel: 'Die.'
                    },{
                        xtype: 'checkbox',
                        id: 'mittwochAb',
                        value: 1,
                        boxLabel: 'Mi.'
                    },{
                        xtype: 'checkbox',
                        id: 'donnerstagAb',
                        value: 1,
                        boxLabel: 'Do.'
                    },{
                        xtype: 'checkbox',
                        id: 'freitagAb',
                        value: 1,
                        boxLabel: 'Fr.'
                    },
                    {
                        xtype: 'checkbox',
                        id: 'sonnabendAb',
                        value: 1,
                        boxLabel: 'Sa.'
                    },{
                        xtype: 'checkbox',
                        id: 'sonntagAb',
                        value: 1,
                        boxLabel: 'So.'
                    }]
            },{
                xtype: 'combo',
                id: 'fruehestens',
                width: 100,
                fieldLabel: 'frühestens buchbar *',
                store: tageStore,
                displayField: 'tage',
                valueField: 'tage',
                editable: false,
                mode: 'local',
                forceSelection: true,
                triggerAction: 'all',
                emptyText: 'Anzahl Tage auswählen',
                selectOnFocus: true,
                helpText: 'frühestens buchbar vor Anreise',
                allowBlank: false
            },{
                xtype: 'combo',
                id: 'spaetestens',
                width: 100,
                fieldLabel: 'spätestens buchbar *',
                helpText: 'spätestens buchbar vor Anreise',
                store: tageStore,
                displayField: 'tage',
                valueField: 'tage',
                editable: false,
                mode: 'local',
                forceSelection: true,
                triggerAction: 'all',
                emptyText: 'Anzahl Tage auswählen',
                selectOnFocus: true,
                allowBlank: false
            },{
                xtype: 'combo',
                id: 'minimal',
                width: 100,
                fieldLabel: 'min. Anzahl Übernachtungen *',
                helpText: 'min. Anzahl der Übernachtungen des Kunden',
                store: tageMinimalStore,
                displayField: 'minimal',
                valueField: 'minimal',
                editable: false,
                mode: 'local',
                forceSelection: true,
                triggerAction: 'all',
                emptyText: 'Anzahl Übernachtungen auswählen',
                selectOnFocus: true,
                allowBlank: false
        }],
        bbar: {
            xtype: 'toolbar',
            anchor: '100%',
            items: [{
                    xtype: 'tbspacer',
                    id: 'spacer',
                    width: 10
                },{
                    xtype: 'tbseparator',
                    id: 'separator1'
                },
                {
                    xtype: 'button',
                    text: 'speichern',
                    handler: function(){
                        sendenRatenParameter();
                    }
                },{
                    xtype: 'tbseparator',
                    id: 'separator2',
                    height: 17,
                    width: 10
                },{
                    xtype: 'button',
                    id: 'uebersicht',
                    text: 'zur Übersicht',
                    disabled: true,
                    handler: function(){
                        zurUebersicht();
                    }
                },{
                    xtype: 'tbseparator',
                    id: 'separator3'
                }]
        }
    });

}

function sendenRatenParameter(){

    var form = Ext.getCmp('ratenverfuegbarkeit');
    form.getForm().submit({
        params: {
            hotelId: memoryHotelId,
            ratenId: memoryRatenId
        },
        success: function(){
            showMsgBox('Rate wurde gespeichert !');
        }
    });
}

function zurUebersicht(){
     window.location.href = '/admin/availablerates/index/';
}
