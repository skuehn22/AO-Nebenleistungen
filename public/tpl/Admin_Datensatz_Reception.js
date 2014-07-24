var adminDatensatzIndexReception = function(){
    // Beginn private
    var programmId;

    var formular = new Ext.form.FormPanel({
        url: "/admin/reception/read/",
        method: 'post',
        autoHeight: true,
        width: 580,
        padding: 10,
        items: [{
            xtype: 'textarea',
            width: 400,
            height: 400,
            fieldLabel: 'Zusatzinformation Rezeption',
            name: 'informationRezeption',
            id: 'informationRezeption'
        },{
            fieldLabel: 'Bild2',
            xtype: 'textfield',
            inputType: 'file',
            frame: true,
            border: false,
            autoWidth: true,
            name: 'miniBild2',
            helpText: 'Bitte Bild 150px * 100px auswählen'
        }],
        buttons: [{
            text: 'eintragen',
            cls: 'x-btn-text-icon',
            handler: function(){
                formular.getForm().submit({
                    url: "/admin/reception/update/",
                    method: 'post',
                    params: {
                        programmId: programmId,
                        miniBild2: "shskajfhskjfhskjfh"
                    },
                    success: function(){
                        Ext.getCmp('adminDatensatzIndexReceptionFenster').close();
                    }
                });
            }
        }]
    });

    var fenster = new Ext.Window({
        shadow: false,
        width: 600,
        id: 'adminDatensatzIndexReceptionFenster',
        autoHeight: true,
        border: false,
        modal: true,
        closable: true,
        x: 20,
        y: 20
    });

    // Ende private

    // Beginn public
    return{
        start: function(){
            fenster.title = 'Zusatzangaben Rezeption, Programm - ID: ' + programmId;
            fenster.add(formular);
            fenster.show();

            this.formularLaden();
        },
        setProgrammId: function(idProgramm){
            programmId = idProgramm;
        },
        formularLaden: function(){
            formular.getForm().load({
                params: {
                    programmdetailsId: programmId
                }
            });

            return;
        }
    }
    // Ende public
}




