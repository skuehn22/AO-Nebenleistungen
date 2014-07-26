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
            height: 200,
            fieldLabel: 'Zusatzinformation Rezeption',
            name: 'informationRezeption',
            id: 'informationRezeption'
        },{
            xtype: 'textarea',
            width: 400,
            height: 55,
            fieldLabel: 'Zusatzdokumente',
            name: 'zusatzdokumente',
            id: 'zusatzdokumente'
        }],
        buttons: [{
            text: 'eintragen',
            cls: 'x-btn-text-icon',
            handler: function(){
                var test = 123;

                formular.getForm().submit({
                    url: "/admin/reception/update/",
                    method: 'post',
                    params: {
                        programmId: programmId
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
    // Kontrolle ob Bild vorhanden ist
    checkImage();
    // Ende public
}




