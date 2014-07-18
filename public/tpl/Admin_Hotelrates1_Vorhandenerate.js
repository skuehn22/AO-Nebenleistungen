function fillVorhandenerate(){

    if(!memoryHotelId){
        showMsgBox('Bitte Hotel auswählen');

        return;
    }

    if(!memoryHotelCode){
        showMsgBox('Bitte Hotel auswählen');

        return;
    }

    if(!memoryRatenId){
        showMsgBox('Bitte Rate auswählen');

        return;
    }

    if(!memoryRateCode){
        showMsgBox('Bitte Rate auswählen');

        return;
    }

    fenster = new Ext.Window({
        title: 'Rate bearbeiten: '+ memoryRatenName + " Id: " + memoryRatenId,
        autoWidth: true,
        modal: true,
        autoHeight: true,
        shadow: false,
        border: false,
        shadow: false,
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
            hotelId: memoryHotelId,
            ratenId: memoryRatenId
        },
        fields: ['id','categorie_name'],
        autoLoad: true
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
                xtype: 'displayfield',
                fieldLabel: 'Raten Code',
                value: memoryRateCode,
                id: 'rateCodeAnsicht',
                helpText: 'Raten Code kann im Hotel nur einmal vergeben werden'
            },{
                xtype: 'textfield',
                fieldLabel: 'Raten Name *',
                width: 120,
                id: 'rateName',
                allowBlank: false,
                helpText: 'Raten Name kann im Hotel nur einmal vergeben werden'
            },{
                xtype: 'textfield',
                width: 120,
                id: 'rateCode',
                hidden: true,
                value: memoryRateCode
            },{
                xtype: 'combo',
                fieldLabel: 'Kategorie Name *',
                width: 200,
                id: 'kategorieName',
                allowBlank: false,
                forceSelection: true,
                triggerAction: 'all',
                displayField: 'categorie_name',
                valueField: 'id',
                hiddenName: 'categoryId',
                store: categoryStore,
                allowBlank: false,
                helpText: 'vorhandene Kategorien des Hotels'
            },{
                xtype: 'combo',
                fieldLabel: 'Aktiv / Passiv *',
                width: 100,
                allowBlank: false,
                id: 'rateAktiv',
                forceSelection: true,
                triggerAction: 'all',
                displayField: 'aktiv',
                valueField: 'id',
                hiddenName: 'aktivschaltung',
                store: [['3','aktiv'],['2','passiv']],
                helpText: 'Aktiv / Passivschaltung'
        }],
        buttons: [{
            text: 'speichern',
            handler: function(){
                formKategorieHotel.getForm().submit({
                    params: {
                        hotelId: memoryHotelId,
                        ratenId: memoryRatenId,
                        hotelCode: memoryHotelCode
                    },
                    success: function(){
                        fenster.close();
                        jsonStoreKategories.reload();
                    },
                    failure: function(){
                        showMsgBox('Bitte Eingabe überprüfen');
                    }
                });
            },
            tooltip: 'neue Rate speichern'
        }]
    });



    fenster.add(formKategorieHotel);
    fenster.doLayout();

    fenster.show();

    formKategorieHotel.getForm().load({
        url: '/admin/Hotelrates1/getratenstammwerte/',
        params: {
            hotelId: memoryHotelId,
            ratenId: memoryRatenId
        },
        success: function(form, formAction){
            Ext.getCmp('kategorieName').setValue(formAction.result.data.kategorieId);
            Ext.getCmp('rateAktiv').setValue(formAction.result.data.aktiv);
        }
    });

}
