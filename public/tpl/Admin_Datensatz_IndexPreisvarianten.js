/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 01.10.2012
 * Time: 11:53
 */


var adminDatensatzIndexPreisvarianten = function (){
    // Beginn Private

    var AnzeigeFenster = null;

    var ProgrammId = null;
    var ProgrammName = null;
    var tabelle = null;
    var formular = null;
    var besonderheitenPreisvarianteForm = null;

    var preiseBeschreibung = null;
    var preisvarianteId = null;
    var preisvarianteName = null;

    var jsonStoreGridPreisVarianten = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: '/admin/datensatz/getpreisvarianten/',
        fields: ['id', 'preisvariante_de', 'preisvariante_en', 'verkaufspreis', 'einkaufspreis', 'mwst']
    });

    var jsonStoreVariantenDurchlaeufer = new Ext.data.JsonStore({
        method: 'post',
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: '/admin/preisposten/index/',
        fields: ['durchlaeuferId', 'durchlaeufer'],
        id: 'jsonStoreVariantenDurchlaeufer'
    });

    var wandlePreis = function(value){
        value = value.replace('.',',');
        value += ' €';

        return value;
    }

    var loeschenPreisvariante = function(){
         if(!tabelle.getSelectionModel().hasSelection()){
            showMsgBox('Bitte Preisvariante auswählen');

            return;
        }

        var id = tabelle.getSelectionModel().getSelected().get('id');
        Ext.MessageBox.confirm('löschen','Wollen sie die Preisvariante ' + id + ' wirklich löschen',
            function(btn){
                if(btn == 'yes'){
                    Ext.Ajax.request({
                        url: '/admin/datensatz/loeschenpreisvariante/',
                        method: 'post',
                        params: {
                            preisvarianteId: id
                        },
                        success: function(){
                            // neuladen Tabelle
                            jsonStoreGridPreisVarianten.load();

                            // Formular Preisvarianten leeren
                            var form = formular.getForm();
                            form.url = '/admin/datensatz/speichernpreisvariante/';
                            form.reset();

                            // Veränderung Button
                            var button = formular.getFooterToolbar().items.items[0];
                            button.setText('speichern');

                            // Formular Bestätigungstexte leeren
                            Ext.getCmp('adminDatensatzPreisvariantePreisbeschreibung').getForm().reset();


                        }
                    });
                }

                return;
            }
        );


        return;
    }

    var neuePreisvariante = function(){

        var form = formular.getForm();
        form.url = '/admin/datensatz/speichernpreisvariante/';
        form.reset();

        var button = formular.getFooterToolbar().items.items[0];
        button.setText('speichern');

        return;
    }



    var einlesenBeschreibungEinerPreisvariante = function(){

        var row = ermittelnMarkiertePreisvariante();
        if(!row)
            return;

        preiseBeschreibung.getForm().load({
            params: {
                programmId: ProgrammId,
                preisvarianteId: row.data.id
            }
        });

        return;
    }

    var ermittelnMarkiertePreisvariante = function(){

        var selection = tabelle.getSelectionModel();

        if(!selection.hasSelection()){
            showMsgBox('Bitte Preisvariante auswählen!');

        }
        else{
            var row = selection.getSelected();

            return row;
        }
    }

    var einlesenFormular = function(){

        // Formular Bestätigungstexte
        einlesenBeschreibungEinerPreisvariante();


        // Formular Preisvarianten
        if(!tabelle.getSelectionModel().hasSelection()){
            showMsgBox('Bitte Preisvariante auswählen');

            return;
        }

        var button = formular.getFooterToolbar().items.items[0];
        button.setText('bearbeiten');

        formular.getForm().url = '/admin/datensatz/bearbeitenpreisvariante/';

        var record = tabelle.getSelectionModel().getSelected();
        record.data.einkaufspreis = record.data.einkaufspreis.replace('.',',');
        record.data.verkaufspreis = record.data.verkaufspreis.replace('.',',');

        formular.getForm().loadRecord(record);

        return;
    }

    var speichernFormulardaten = function(){

        var form = formular.getForm();
        form.submit({
            params: {
                FaId: ProgrammId
            },
            success: function(){

                var form = formular.getForm();
                form.url = '/admin/datensatz/speichernpreisvariante/';

                var button = formular.getFooterToolbar().items.items[0];
                button.setText('speichern');

                showMsgBox('gespeichert');

                jsonStoreGridPreisVarianten.reload();

                return;
            }
        });

        return;
    }



    // Ende Private
    // Beginn Public
    return {

        formularPreisvariante: new Ext.form.FormPanel({
            autoHeight: true,
            width: 500,
            title: 'Preisvarianten eines Programmes',
            method: 'post',
            url: '/admin/datensatz/speichernpreisvariante/',
            labelWidth: 100,
            padding: 10,
            items: [{
                xtype: 'textfield',
                width: 50,
                name: 'id',
                fieldLabel: 'ID',
                readOnly: true,
                disable: true
            },{
                xtype: 'textfield',
                width: 200,
                name: 'preisvariante_de',
                fieldLabel: 'deutsch *',
                allowBlank: false,
                blankText: 'Preisvariante min. 5 Zeichen',
                minLength: 5
            },{
                xtype: 'textfield',
                width: 200,
                name: 'preisvariante_en',
                fieldLabel: 'englisch *',
                blankText: 'Preisvariante min. 5 Zeichen',
                minLength: 5
            },{
                xtype: 'textfield',
                width: 75,
                name: 'einkaufspreis',
                fieldLabel: 'EK Brutto *',
                maskRe: /^[0-9\,]$/,
                allowBlank: false
            },{
                xtype: 'textfield',
                width: 75,
                name: 'verkaufspreis',
                fieldLabel: 'VK Brutto *',
                maskRe: /^[0-9\,]$/,
                allowBlank: false
            }],
            buttons: [{
            text: 'speichern',
            handler: function(){
                speichernFormulardaten();
            }
            }]
        }),

        tabellePreisVarianten: new Ext.grid.GridPanel({
            autoHeight: true,
            width: 450,
            stripeRows: true,
            columnLines: true,
            autoExpandColumn: 'variantenname',
            store: jsonStoreGridPreisVarianten,
            title: 'Preisvarianten',
            listeners: {
                rowdblclick: einlesenFormular
            },
            viewConfig: {
                forceFit: true,
                scrollOffset: 0
            },
            columns: [{
                xtype: 'gridcolumn',
                dataIndex: 'id',
                header: 'Id',
                id: 'id',
                sortable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'preisvariante_de',
                header: 'Preisvariante',
                sortable: true,
                width: 200
            },{
                xtype: 'gridcolumn',
                dataIndex: 'verkaufspreis',
                header: 'Verkaufspreis',
                sortable: false,
                width: 150,
                renderer: wandlePreis
            }],
            bbar: {
                xtype: 'paging',
                displayInfo: true,
                store: jsonStoreGridPreisVarianten,
                pageSize: 5
            },
            buttons: [{
                text: 'Bestätigungstext anzeigen',
                handler: function(){
                    einlesenBeschreibungEinerPreisvariante();
                }
            },{
                text: 'neue Preisvariante',
                handler: function(){
                    neuePreisvariante();
                }
            },{
                text: 'bearbeiten',
                handler: function(){
                    einlesenFormular();
                }
            },{
                text: 'löschen',
                handler: function(){
                    loeschenPreisvariante();
                }
            }]
        }),

        besonderheitenPreisvariante: new Ext.form.FormPanel({
            title: 'Besonderheiten',
            autoHeight: true,
            width: 450,
            method: 'post',
            url: '/admin/datensatz/besonderheiten-preisvariante/',
            labelWidth: 170,
            padding: 10,
            items: [{
                xtype: 'textfield',
                width: 75,
                name: 'werbepreis',
                fieldLabel: 'Werbepreis Brutto *',
                maskRe: /^[0-9\,]$/
            },{
                id: 'comboDurchlaeufer',
                fieldLabel: 'Durchläufer *',
                width:          200,
                xtype:          'combo',
                triggerAction:  'all',
                forceSelection: true,
                mode: 'local',
                editable: false,
                displayField:   'durchlaeufer',
                valueField:     'durchlaeuferId',
                hiddenName: 'durchlaeuferId',
                hiddenValue: 'durchlaeufer',
                store: jsonStoreVariantenDurchlaeufer
            },{
                fieldLabel: 'MwSt EK *',
                xtype: 'combo',
                width: 75,
                triggerAction: 'all',
                selectOnFocus: true,
                typeAhead: true,
                mode: 'local',
                hiddenName: 'mwst_ek',
                name: 'mwst_ek',
                value: '0.19',
                store: [[0.19,'19%'],[0.07,'7%'],[0, '0%'],[0.1,'10%']]
            },{
                fieldLabel: 'Mwst VK *',
                xtype: 'combo',
                width: 75,
                value: '0.19',
                triggerAction: 'all',
                selectOnFocus: true,
                typeAhead: true,
                mode: 'local',
                hiddenName: 'mwst',
                name: 'mwst',
                store: [[0.19,'19%'],[0.07,'7%'],[0, '0%']]
            },{
                xtype: 'checkbox',
                fieldLabel: 'Buchungspauschale',
                name: 'buchungspauschale',
                value: '2'
            }],
            buttons: [{
                text: 'speichern',
                handler: function(){
                    besonderheitenPreisvarianteForm.getForm().submit({
                        params: {
                            programmId: ProgrammId
                        }
                    });

                    return;
                }
            }]
        }),

        besonderheitenLaden: function(){
            this.besonderheitenPreisvariante.load({
                url: '/admin/datensatz/getbesonderheiten/',
                method: 'post',
                params: {
                    programmId: ProgrammId
                }
            });

            return;
        },

        preiseBeschreibung: new Ext.form.FormPanel({
            title: 'Bestätigungstexte einer Preisvariante',
            method: 'post',
            url: '/admin/datensatz/preisbeschreibung-View',
            id: 'adminDatensatzPreisvariantePreisbeschreibung',
            padding: 10,
            items: [{
                xtype: 'textarea',
                width: 250,
                height: 100,
                fieldLabel: 'Bestätigungstext deutsch',
                name: 'confirm_1_de'
            },{
                xtype: 'textarea',
                width: 250,
                height: 100,
                fieldLabel: 'Bestätigungstext englisch',
                name: 'confirm_1_en'
            }],
            buttons: [{
                text: 'speichern',
                handler: function(){

                    var row = ermittelnMarkiertePreisvariante();
                    if(!row)
                        return;

                    preiseBeschreibung.getForm().submit({
                        method: 'post',
                        url: '/admin/datensatz/preisbeschreibung-Edit',
                        params: {
                            programmId: ProgrammId,
                            preisvarianteId: row.data.id
                        }
                    });
                }
            }]
        }),



        fenster: new Ext.Window({
            shadow: false,
            width: 530,
            autoHeight: true,
            layout: 'table',
            verticalAlign: 'middle',
            draggable: false,
            layoutConfig: {
                columns: 1
            },
            border: false,
            modal: true,
            resizable: false,
            padding: 10,
            x: 20,
            y: 20
        }),

        start: function(programmId, programmName){

            ProgrammId = programmId;
            ProgrammName = programmName;

            jsonStoreGridPreisVarianten.setBaseParam('programmId', programmId);
            jsonStoreGridPreisVarianten.load();
            jsonStoreVariantenDurchlaeufer.load();

            this.fenster.title = 'Preisvarianten, Programm ID: ' + programmId + ', Programmname: ' + '"' + programmName + '"';

            tabelle = this.tabellePreisVarianten;
            this.fenster.add(this.tabellePreisVarianten);

            besonderheitenPreisvarianteForm = this.besonderheitenPreisvariante;
            this.fenster.add(besonderheitenPreisvarianteForm);

            formular = this.formularPreisvariante;
            this.fenster.add(this.formularPreisvariante);

            preiseBeschreibung = this.preiseBeschreibung;
            this.fenster.add(this.preiseBeschreibung);

            this.besonderheitenLaden();

            this.fenster.show();

            AnzeigeFenster = this.fenster;

            return;
        }
    }
    // Ende Public
}

/**
 *
 */
function fillPreise(){
    if(!programmId){
        showMsgBox('Bitte ein Programm auswählen');

        return;
    }

    var preisvarianten = new adminDatensatzIndexPreisvarianten();
    preisvarianten.start(programmId, programmName);

}