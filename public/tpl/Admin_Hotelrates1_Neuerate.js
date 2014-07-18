function fillNeueRate(){

    if(!memoryHotelId){
        showMsgBox('Bitte Hotel auswählen');

        return;
    }

    fenster = new Ext.Window({
        title: 'neue Rate anlegen',
        autoWidth: true,
        modal: true,
        autoHeight: true,
        shadow: false,
        border: false,
        // border: showCloseButton,
        x: 270,
        y: 160
    });

     var categoryStore = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/hotelrates1/getcategoriesfromhotel/",
        id: 'jsonStoreCategories',
        baseParams: {
            hotelId: memoryHotelId
        },
        fields: ['id','categorie_name'],
        autoLoad: false
    });


    formKategorieHotel = new Ext.form.FormPanel({
        autoHeight: true,
        frame: true,
        id: 'formStammdatenKategorie',
        url: '/admin/hotelrates1/newhotelrate/',
        method: 'post',
        labelWidth: 150,
        border: false,
        width: 400,
        items: [{
                    xtype: 'textfield',
                    fieldLabel: 'Raten Name',
                    width: 120,
                    id: 'rateName',
                    allowBlank: false,
                    helpText: 'Raten Name kann im Hotel nur einmal vergeben werden'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Raten Code',
                    width: 120,
                    id: 'rateCode',
                    allowBlank: false,
                    helpText: 'Raten Code kann im Hotel nur einmal vergeben werden'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Kategorie Name',
                    width: 200,
                    id: 'kategorieName',
                    allowBlank: false,
                    forceSelection: true,
                    triggerAction: 'all',
                    displayField: 'categorie_name',
                    valueField: 'id',
                    hiddenName: 'categorie_id',
                    store: categoryStore,
                    helpText: 'vorhandene Zimmertypen des Hotels'
        }],
        buttons: [{
            text: 'speichern',
            handler: neueRate,
            tooltip: 'neue Rate speichern'
        }]
    });


    fenster.add(formKategorieHotel);
    fenster.doLayout();

    fenster.show();
}

function neueRate(){

    formKategorieHotel.getForm().submit({
        params: {
            hotelId: memoryHotelId,
            hotelCode: memoryHotelCode
        },
        success: function(form, action){

            var messages = Ext.util.JSON.decode(action.response.responseText);
            showMsgBox('neue Rate angelegt');

            jsonStoreKategories.load({
                params: {
                    idProperties: memoryHotelId
                }
            });

            formKategorieHotel.getForm().reset();

            fenster.close();
        },
        failure: function(){
            showMsgBox('Bitte Eingaben überprüfen');
        }
    });
}
