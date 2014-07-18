Ext.namespace('abfahrtsorteTreffpunkte');

abfahrtsorteTreffpunkte = function(){

    var self = null;

    function vorhandenerTreffpunkt(){
        self.Form.getForm().load({
            url: '/admin/abfahrtsorte/vorhandenertreffpunkt',
            params: {
                programmId: programmId
            }
        });
    }

    function saveTreffpunkt(){
        self.Form.getForm().submit({
            params: {
                programmId: programmId
            },
            success: function(){
                showMsgBox('gespeichert');
            }
        });
    }

    var TreffpunktStore = new Ext.data.JsonStore({
        url: '/admin/abfahrtsorte/gettreffpunktstart',
        method: 'post',
        root: 'data',
        totalProperty: 'anzahl',
        fields: ['id','treffpunkt']
    });

    var storeVorhandeneTreffpunkte = new Ext.data.JsonStore({
        url: '/admin/abfahrtsorte/gettreffpunktstart',
        method: 'post',
        root: 'data',
        totalProperty: 'anzahl',
        fields: ['id','treffpunkt']
    });

    return{
        fenster: new Ext.Window({
            closable: showCloseButton,
            autoHeight: true,
            resizable: false,
            width: 640,
            autoHeight: true,
            padding: 10,
            title: 'Abfahrtsort , Programm ID: ' + programmId,
            buttonAlign: 'right',
            shadow: false,
            modal: true,
            layout: 'hbox',
            x: 20,
            y: 20
        }),

        Form: new Ext.form.FormPanel({
            xtype: 'form',
            clientValidation: true,
            id: 'Form',
            autoHeight: true,
            width: 600,
            url: '/admin/abfahrtsorte/settreffpunkt',
            border: false,
            padding: 10,
            labelWidth: 150,
            frame: true,
            params: {
                programmId: this.programmId
            },
            items: [{
                xtype: 'textarea',
                id: 'FormInformationDeutsch',
                height: 70,
                width: 200,
                fieldLabel: 'Hinweis deutsch *',
                helpText: 'Beschreibung des Treffpunktes in deutsch',
                allowBlank: false
            },{
                xtype: 'textarea',
                id: 'FormInformationEnglisch',
                height: 70,
                width: 200,
                fieldLabel: 'Hinweis englisch *',
                helpText: 'Beschreibung des Treffpunktes in englisch',
                allowBlank: false
            },{
                xtype: 'combo',
                width: 400,
                allowBlank: false,
                fieldLabel: 'Treffpunkt *',
                store: TreffpunktStore,
                id: 'Treffpunkt',
                displayField: 'treffpunkt',
                valueField: 'id',
                hiddenName: 'FormTreffpunkt',
                editable: true,
                mode: 'remote',
                typeAhead: false,
                forceSelection: true,
                triggerAction: 'all',
                emptyText: 'Bitte den Treffpunkt wählen',
                loadingText: 'Bitte warten ...',
                minChars: 3,
                pageSize: 10,
                hideTrigger: true,
                selectOnFocus: true,
                helpText: 'Bitte den Start Treffpunkt wählen. <br> Suche beginnt nach 3 Zeichen'
            },{
                xtype: 'datefield',
                id: 'FormStartDatum',
                width: 200,
                allowBlank: false,
                fieldLabel: 'Startdatum *',
                helpText: 'Beginn des Programmes'
            },{
                xtype: 'datefield',
                id: 'FormEndDatum',
                width: 200,
                allowBlank: false,
                fieldLabel: 'Enddatum *',
                helpText: 'Ende des Programmes'
            }],
            buttons: [{
                text: 'Treffpunkt speichern',
                handler: saveTreffpunkt,
                tooltip: 'Speichern eines Treffpunktes für das gewählte Programm.<br>Bereits getätigte Angaben werden überschrieben.'
            }]
        }),

//        vorhandeneAbfahrtsorte: new Ext.grid.GridPanel({
//            title: 'vorhandene Treffpunkte Ankunft / Abfahrt'
//        }),

        workAdmin: function(){
            
            self = this;

            vorhandenerTreffpunkt();
        }
    }
}

function abfahrtsorteTreffpunkteAdmin(){
    if(!programmId){
        showMsgBox('Bitte Programm auswählen');

        return;
    }

    var view = abfahrtsorteTreffpunkte();
    view.fenster.add(view.Form);
    view.fenster.show();
    view.workAdmin();
}
