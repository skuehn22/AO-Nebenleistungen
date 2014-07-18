Ext.onReady(function(){

    jsonStoreCities = new Ext.data.JsonStore({
        root: 'data',
        method: 'post',
        url: "/admin/newprogram/getcities/",
        id: 'jsonStoreRegion',
        autoLoad: true,
        fields: ['city','id']
    });

    formNewProgram = new Ext.form.FormPanel({
        labelWidth: 150,
        frame: true,
        bodyStyle: 'padding: 10px',
        autoHeight: true,
        autoWidth: true,
        url: '/admin/newprogram/addnewprogram/',
        method: 'post',
        id: 'formNewProgram',
        buttonAlign: 'right',
        border: false,
        items: [{
            xtype: 'textfield',
            fieldLabel: 'Programmname ( de. ) ',
            width: 150,
            id: 'programDe',
            minLength: 3,
            allowBlank: false
        },{
            xtype: 'textfield',
            fieldLabel: 'Programmname ( en. ) ',
            width: 150,
            id: 'programEn',
            minLength: 3,
            allowBlank: false
        },{
            xtype: 'combo',
            fieldLabel: 'Stadt',
            id: 'cities',
            width: 150,
            allowBlank: false,
            store: jsonStoreCities,
            selectOnFocus: true,
            typeAhead: true,
            mode: 'local',
            displayField: 'city',
            valueField: 'id',
            hiddenName: 'city',
            forceSelection: true,
            triggerAction: 'all'
        }],
        buttons: [{
            align: 'right',
            text: 'speichern',
            listeners: {
                click: saveNewProgram
            }
        }]
    });

});