function fillTermine(){
    if(!programmId){
        showMsgBox('Bitte Programm auswählen');
        return;
    }

    jsonStoreSperrtage = new Ext.data.JsonStore({
        url: '/admin/datensatz/getsperrtage',
        root: 'data',
        method: 'post',
        idProperty: 'id',
        totalProperty: 'count',
        fields: ['id','datumSperrtag']
    });

    var programmzeiten = [{
           xtype: 'htmleditor',

            name: 'abfahrtszeit',
            hideLabel: true,
            width: 450,
            height: 150
        },{

            xtype: 'tbspacer',
            height: 20
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 1',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit1stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit1minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 2',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit2stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit2minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 3',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit3stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit3minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 4',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit4stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit4minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 5',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit5stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit5minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 6',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit6stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit6minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 7',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit7stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit7minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 8',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit8stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit8minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 9',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit9stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit9minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        },{
            xtype: 'compositefield',
            fieldLabel: 'Programmzeit 10',
            combineErrors: false,
            items: [{
                xtype: 'numberfield',
                name: 'programmzeit10stunde',
                minValue: 0,
                maxValue: 23,
                width: 50
            },{
                xtype: 'numberfield',
                name: 'programmzeit10minute',
                minValue: 0,
                maxValue: 59,
                width: 50
            }]
        }];

    var saison = [{
        xtype: 'fieldset',
        title: 'Saison',
        labelWidth: 120,
        items: [{
            xtype: 'datefield',
            fieldLabel: 'Saisonstart *',
            id: 'valid_from',
            width: 150,
            allowBlank: false,
            helpText: 'Beginn der Saison'
        },{
            xtype: 'datefield',
            fieldLabel: 'Saisonende *',
            id: 'valid_thru',
            width: 150,
            allowBlank: false,
            helpText: 'Ende der Saison'
        },{
            xtype: 'numberfield',
            fieldLabel: 'Buchungsfrist *',
            width: 50,
            allowBlank: false,
            id: 'buchungsfrist',
            helpText: 'min. Buchungsfrist in Tagen'
        },{
            xtype: 'numberfield',
            fieldLabel: 'Stornofrist *',
            width: 50,
            allowBlank: false,
            id: 'stornofrist',
            helpText: 'min. Stornofrist in Tagen'
        }]},{
            xtype: 'fieldset',
            title: 'Dauer des Programmes',
            labelWidth: 100,
            items: [{
                xtype: 'label',
                text: 'vorraussichtliche Dauer des Programmes'
            },{
                xtype: 'timefield',
                hideLabel: true,
                width: 100,
                id: 'minDuration',
                minValue: '0:00',
                increment: 15
            },{
                xtype: 'label',
                text: 'maximale Dauer des Programmes'
            },{
                xtype: 'timefield',
                hideLabel: true,
                width: 100,
                id: 'maxDuration',
                minValue: '0:00',
                increment: 15
            },{
                xtype: 'label',
                text: 'Hinweis zur Dauer in deutsch'
            },{
                xtype: 'textarea',
                hideLabel: true,
                width: 150,
                height: 50,
                id: 'hinweisDeutsch'
            },{
                xtype: 'label',
                text: 'Hinweis zur Dauer in englisch'
            },{
                xtype: 'textarea',
                hideLabel: true,
                width: 150,
                height: 50,
                id: 'hinweisEnglisch'
            }]
    }];

    checkboxSelSperrtage = new Ext.grid.CheckboxSelectionModel();

    termineForm = new Ext.form.FormPanel({
        frame: true,
        width: 1200,
        autoHeight: true,
        url: '/admin/datensatz/setschedules/',
        method: 'post',
        id: 'termineForm',
        layout: 'column',
        labelWidth: 120,
        border: false,
        items: [{
                width: 330,
                layout: 'form',
                border: false,
                padding: 10,
                autoHeight: true,
                items: saison
        },{
                width: 310,
                layout: 'form',
                border: false,
                padding: 10,
                autoHeight: true,
                items: [{
                    xtype: 'fieldset',
                    title: 'Sperrtage',
                    labelWidth: 60,
                    items: [{
                        xtype: 'datefield',
                        fieldLabel: 'Auswahl Sperrtage',
                        width: 150,
                        id: 'selectSperrtag',
                        listeners: {
                            blur: function(){
                                var newRecord = Ext.data.Record.create([{name: 'datumSperrtag'},{name: 'id'}]);
                                var datum = arguments[0].value;
                                if(datum.length < 1)
                                    return;

                                jsonStoreSperrtage.add(new newRecord({datumSperrtag: datum, id: '100'}));
                                jsonStoreSperrtage.sort('datumSperrtag', 'ASC');
                            }
                        }
                },{
                    xtype: 'grid',
                    id: 'sperrtage',
                    width: 230,
                    autoHeight: true,
                    store: jsonStoreSperrtage,
                    sm: checkboxSelSperrtage,
                    columns: [
                        checkboxSelSperrtage
                    ,{
                        width: '50',
                        header: 'ID',
                        dataIndex: 'id',
                        hidden: true
                    },{
                        width: '150',
                        header: 'Sperrtage',
                        dataIndex: 'datumSperrtag'
                    },{
                        width: '75',
                        header: 'Wochentag',
                        dataIndex: 'datumSperrtag',
                        renderer: rendererBerechneWochentag
                    }]
                }]
             }]
        },{
            width: 500,
            layout: 'form',
            border: false,
            padding: 10,
            autoHeight: true,
            items: [{
                xtype: 'fieldset',
                title: 'Abfahtszeit',
                labelWidth: 220,
                items: programmzeiten
            }]
        }]
    });

    var eintragenButton = {
        text: 'eintragen',
        icon: '/buttons/vor.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'right',
        handler: function(){

            var sperrtage = ' ';
            var i = 0;
            jsonStoreSperrtage.each(function(record){
                sperrtage = sperrtage + record.data.datumSperrtag + "#";
                i++;
            });

            Ext.getCmp('termineForm').getForm().submit({
                params: {
                    programmId: programmId,
                    sperrtageFuerProgramm: sperrtage
                },
                success: function(form,action){
                    fensterTermine.close();
                    fillStornofristen();
                },
                failure: function(form,action){
                    showMsgBox('Termine nicht geändert');
                }
            });
        }
    };

    var loeschenSperrtageButton = {
        text: 'Sperrtage löschen',
        listeners: {
            click: loescheSelectedSperrtage
        }
    };

    termineForm.getForm().reset();

    jsonStoreSperrtage.removeAll();
    jsonStoreSperrtage.setBaseParam('programmId', programmId);
    jsonStoreSperrtage.load();

    var fensterTermine = new Ext.Window({
        title: 'Termine und Sperrtage, ID: ' + programmId + ', Programmname: ' + programmName,
        autoWidth: true,
        autoHeight: true,
        modal: true,
        shadow: false,
        border: true,
        padding: 10,
        resizable: false,
        x: 20,
        y: 20,
        closable: true,
        resizable: false
    });

    var sprachenButton = {
        xtype: 'button',
        icon: '/buttons/zurueck.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'left',
        text: 'Öffnungszeiten',
        handler: function(){
            fensterTermine.close();
            fillOeffnungszeiten();
        }
    };

    fensterTermine.add(termineForm);
    fensterTermine.addButton(sprachenButton);
    fensterTermine.addButton(loeschenSperrtageButton);
    fensterTermine.addButton(eintragenButton);
    fensterTermine.doLayout();
    fensterTermine.show();

    termineForm.load({
        url: '/admin/datensatz/findschedules/',
        method: 'post',
        params: {
            programmId: programmId
        }
    });
}