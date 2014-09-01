function fillOeffnungszeiten(){
     if(!programmId){
        showMsgBox('Bitte Programm auswählen');
         
        return;
    }

    var myWindow = new Ext.Window({
        title: 'Öffnungszeiten, ID: ' + programmId + ', Programmname: ' + programmName,
        width: 370,
        autoHeight: true,
        modal: true,
        shadow: false,
        border: true,
        resizable: false,
        padding: 10,
        x: 20,
        y: 20,
        closable: showCloseButton,
        buttons: [{
                text: 'Programmsprachen',
                icon: '/buttons/zurueck.png',
                cls: 'x-btn-text-icon',
                iconAlign: 'left',
                handler: function(){
                    myWindow.close();
                    fillSprache();
                }
            },{
                xtype: 'tbspacer'
            },{
                text: 'eintragen',
                icon: '/buttons/vor.png',
                cls: 'x-btn-text-icon',
                iconAlign: 'right',
                handler: function(){

                    Ext.getCmp('formOeffnungszeiten').getForm().submit({
                        url: '/admin/datensatz/setzeiten',
                        method: 'post',
                        params: {
                            programmId: programmId
                        },
                        success: function(){
                            myWindow.close();
                            fillTermine();
                        }
                    });
                }
        }]
    });
    
    var form = new Ext.form.FormPanel({
        id: 'formOeffnungszeiten',
        border: false,
        padding: 10,
        items: [{
            xtype: 'label',
            text: 'gesamter Tag von 00:00 bis 23:59 Uhr !'
        },{
            xtype: 'compositefield',
            labelWidth: 100,
            fieldLabel: 'Montag',
            items: [{
                xtype: 'checkbox',
                id: 'montag',
                value: 1
            },{
                xtype: 'timefield',
                id: 'startMontag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            },{
                xtype: 'timefield',
                id: 'endeMontag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            }]
        },{
            xtype: 'compositefield',
            labelWidth: 100,
            fieldLabel: 'Dienstag',
            items: [{
                xtype: 'checkbox',
                id: 'dienstag',
                value: 2
            },{
                xtype: 'timefield',
                id: 'startDienstag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            },{
                xtype: 'timefield',
                id: 'endeDienstag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            }]
        },{
            xtype: 'compositefield',
            labelWidth: 100,
            fieldLabel: 'Mittwoch',
            items: [{
                xtype: 'checkbox',
                id: 'mittwoch',
                value: 3
            },{
                xtype: 'timefield',
                id: 'startMittwoch',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            },{
                xtype: 'timefield',
                id: 'endeMittwoch',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            }]
        },{
            xtype: 'compositefield',
            labelWidth: 100,
            fieldLabel: 'Donnerstag',
            items: [{
                xtype: 'checkbox',
                id: 'donnerstag',
                value: 4
            },{
                xtype: 'timefield',
                id: 'startDonnerstag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            },{
                xtype: 'timefield',
                id: 'endeDonnerstag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            }]
        },{
            xtype: 'compositefield',
            labelWidth: 100,
            fieldLabel: 'Freitag',
            items: [{
                xtype: 'checkbox',
                id: 'freitag',
                value: 5
            },{
                xtype: 'timefield',
                id: 'startFreitag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            },{
                xtype: 'timefield',
                id: 'endeFreitag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            }]
        },{
            xtype: 'compositefield',
            labelWidth: 100,
            fieldLabel: 'Sonnabend',
            items: [{
                xtype: 'checkbox',
                id: 'sonnabend',
                value: 6
            },{
                xtype: 'timefield',
                id: 'startSonnabend',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            },{
                xtype: 'timefield',
                id: 'endeSonnabend',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            }]
        },{
            xtype: 'compositefield',
            labelWidth: 100,
            fieldLabel: 'Sonntag',
            items: [{
                xtype: 'checkbox',
                id: 'sonntag',
                value: 7
            },{
                xtype: 'timefield',
                id: 'startSonntag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            },{
                xtype: 'timefield',
                id: 'endeSonntag',
                value: '00:00',
                maxValue: '23:59',
                width: 75
            }]
        }]
    });

    myWindow.add(form);
    myWindow.show();

    Ext.getCmp('formOeffnungszeiten').getForm().load({
        url: '/admin/datensatz/getzeiten/',
        method: 'post',
        params: {
            programmId: programmId
        }
    });


}
