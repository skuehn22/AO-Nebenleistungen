/**
 * Verwaltung der Zahlungsziele eines Programmes1
 *
 */

var adminDatensatzIndexStornofristen = function(){
    // Beginn Private

    var ProgrammId = null;
    var ProgrammName = null;
    var Formular = null;
    var Fenster = null;

    var listeTageStornofristen = [
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
        [999, 'kein Storno']
    ];

    // Ende Private
    // Beginn Public
    return {
        start: function(){
            ProgrammId = this.programmId;
            ProgrammName = this.programmName;
            Formular = this.formular;
            Fenster = this.fenster;

            this.fenster.title = 'Stornofristen, ID: ' + ProgrammId + ", Programmname: " + ProgrammName;
            this.fenster.add(this.formular);
            this.fenster.show();
            this.formularLaden();
        },

        programmId: null,

        programmName: null,

        formularLaden: function(){
            Formular.getForm().load({
                params: {
                    programmdetailsId: ProgrammId
                }
            });

            return;
        },

        formular: new Ext.form.FormPanel({
            url: "/admin/datensatz-stornofristen/view/",
            method: 'post',
            autoHeight: true,
            width: 450,
            padding: 10,
            items: [{
                xtype: 'displayfield',
                value: 'Eingabe der Stornofristen in % und Werktagen.<br> 999 Tage = SOFORT 100 %  <br><br> Standard:<br> 4 Tage = Storno 0%,<br> 3 Tage = Storno 100%',
                style: {
                    marginBottom: '10px'
                }
                },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'numberfield',
                    name: 'tage1',
                    fieldLabel: '1. Stornofrist* (bis x Werktage)',
                    allowBlank: true,
                    width: 50
                },{
                    xtype: 'textfield',
                    name: 'prozente1',
                    width: 50,
                    id: 'adminDatensatzIndexStornofristenProzente1',
                    allowBlank: true,
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'numberfield',
                    name: 'tage2',
                    fieldLabel: '2. Stornofrist (bis y Werktage) ',
                    allowBlank: true,
                    width: 50
                },{
                    xtype: 'textfield',
                    name: 'prozente2',
                    width: 50,
                    id: 'adminDatensatzIndexStornofristenProzente2',
                    allowBlank: true,
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'numberfield',
                    name: 'tage3',
                    fieldLabel: '3. Stornofrist (bis y Werktage) ',
                    allowBlank: true,
                    width: 50
                },{
                    xtype: 'textfield',
                    name: 'prozente3',
                    width: 50,
                    id: 'adminDatensatzIndexStornofristenProzente3',
                    allowBlank: true,
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'numberfield',
                    name: 'tage4',
                    fieldLabel: '4. Stornofrist (bis y Werktage) ',
                    allowBlank: true,
                    width: 50
                },{
                    xtype: 'textfield',
                    name: 'prozente4',
                    width: 50,
                    id: 'adminDatensatzIndexStornofristenProzente4',
                    allowBlank: true,
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'numberfield',
                    name: 'tage5',
                    fieldLabel: '5. Stornofrist (bis y Werktage) ',
                    allowBlank: true,
                    width: 50
                },{
                    xtype: 'textfield',
                    name: 'prozente5',
                    width: 50,
                    id: 'adminDatensatzIndexStornofristenProzente5',
                    allowBlank: true,
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'numberfield',
                    name: 'tage6',
                    fieldLabel: '6. Stornofrist (bis y Werktage) ',
                    allowBlank: true,
                    width: 50
                },{
                    xtype: 'textfield',
                    name: 'prozente6',
                    width: 50,
                    id: 'adminDatensatzIndexStornofristenProzente6',
                    allowBlank: true,
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
            },{
                xtype: 'compositefield',
                items: [{
                    xtype: 'numberfield',
                    name: 'tage7',
                    fieldLabel: '7. Stornofrist (ab z Werktage)',
                    allowBlank: true,
                    width: 50
                },{
                    xtype: 'textfield',
                    name: 'prozente7',
                    width: 50,
                    id: 'adminDatensatzIndexStornofristenProzente7',
                    allowBlank: true,
                    maskRe: /[0-9]/
                },{
                    xtype: 'label',
                    text: ' %'
                }]
        },{
            }],
            buttons: [{
                text: 'Termine',
                icon: '/buttons/zurueck.png',
                cls: 'x-btn-text-icon',
                iconAlign: 'left',
                handler: function(){
                    Fenster.close();
                    fillTermine();
                }
            },{
                text: 'eintragen',
                icon: '/buttons/vor.png',
                cls: 'x-btn-text-icon',
                iconAlign: 'right',
                handler: function(){
                    Formular.getForm().submit({
                        url: "/admin/datensatz-stornofristen/edit/",
                        method: 'post',
                        params: {
                            programmId: ProgrammId
                        },
                        success: function(){
                            Ext.getCmp('adminDatensatzIndexStornofristenFenster').close();
                            fillPreise();
                        }
                    });
                }
            }]
        }),

        fenster: new Ext.Window({
            shadow: false,
            width: 500,
            id: 'adminDatensatzIndexStornofristenFenster',
            autoHeight: true,
            border: false,
            modal: true,
            resizable: false,
            closable: true,
            resizable: false,
            padding: 10,
            x: 20,
            y: 20
        })
    }
    // Ende Public
}

function fillStornofristen(){
    if(!programmId){
        showMsgBox('Bitte ein Programm auswählen');

        return;
    }

    var stornofristen = new adminDatensatzIndexStornofristen();
    stornofristen.programmId = programmId;
    stornofristen.programmName = programmName;
    stornofristen.start();
}
