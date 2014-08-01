function fillDiverses(){

    if(!programmId){
        showMsgBox('Bitte Programm auswählen');

        return;
    }

     var diversesForm = new Ext.form.FormPanel({
        padding: 10,
        border: false,
        frame: true,
        id: 'diverseFormElements',
        url: '/admin/datensatz/setdiverses/',
        labelWidth: 180,
        items: [{
            layout: 'column',
            border: false,
            items: [{
                columnWidth:1,
                layout: 'form',
                border: false,
                items: [{
                    fieldLabel: 'Ort *',
                    xtype: 'combo',
                    width: 150,
                    allowBlank: false,
                    forceSelection: true,
                    selectOnFocus: true,
                    typeAhead: true,
                    triggerAction: 'all',
                    displayField: 'city',
                    valueField: 'id',
                    hiddenName: 'AO_City',
                    mode: 'local',
                    store: cityStore
                },{
                columnWidth:1,
                layout: 'form',
                border: false,
                items: [{
                    xtype: 'displayfield',
                    value: ' ',
                    height: 27
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'an Programmanbieter 1:',
                    width: 270,
                    name: 'an_prog_1_de'
                },{
                    xtype: 'textarea',
                    height: 150,
                    fieldLabel: 'Zusatzinformation an Reception:',
                    width: 270,
                    name: 'buchungstext_de'
                }]
            }]
            }]
        }]
    });

    var buttonEintragen =  {
        text: 'eintragen',
        icon: '/buttons/vor.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'right',
        handler: function(){
            diversesForm.getForm().submit({
                params: {
                    programmId: programmId
                },
                success: function(form,action){
                    Ext.getCmp('myGrid').store.load();
                    fensterDiverses.close();
                    fillTemplate(1, programmId);
                },
                failure: function(form,action){
                    showMsgBox('Bitte Eingaben überprüfen !');
                }
            });
        }
    };

    var buttonPreise = {
        xtype: 'button',
        text: 'Zusatzinformation',
        icon: '/buttons/zurueck.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'left',
        handler: function(){
            fensterDiverses.close();
            fillZusatzinformation();
        }
    };

    var fensterDiverses = new Ext.Window({
        title: 'diverse Angaben, Id: ' + programmId + ', Programmname: ' + programmName,
        modal: true,
        width: 700,
        autoHeight: true,
        shadow: false,
        border: false,
        padding: 10,
        border: showCloseButton,
        x: 20,
        y: 20,
        closable: true,
        resizable: false
    });

    fensterDiverses.addButton(buttonPreise);
    fensterDiverses.addButton(buttonEintragen);
    fensterDiverses.add(diversesForm);
    fensterDiverses.doLayout();
    fensterDiverses.show();

    // Ext.getCmp('aktivPassivComboBox').setValue(1);

    diversesForm.load({
        url: '/admin/datensatz/finddiverses/',
        method: 'post',
        params: {
            programmId: programmId
        }
    });

}
