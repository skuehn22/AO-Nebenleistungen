var adminNotschalterFormular = function(){

    function speichern(){
        formular.getForm().submit({
            success: function(){
                showMsgBox('Schalter eingetragen');
            }
        });

        return;
    }

    function ladenFormular(){
        formular.getForm().load({
            url: '/admin/notschalter/view'
        });

        return;
    }

    var formular = new Ext.FormPanel({
        labelWidth: 250,
        url:'/admin/notschalter/edit',
        title: 'Notschalter des System',
        method: 'post',
        padding: 10,
        width: 300,
        defaultType: 'checkbox',
        items: [{
                fieldLabel: 'Buchungen Programme abschalten',
                name: 'programmbuchung'
            },{
                fieldLabel: 'Buchung Übernachtungen abschalten',
                name: 'hotelbuchung'
            }],
        fbar: [{
            xtype: 'tbspacer',
            width: 10
        },{
            xtype: 'button',
            text: 'speichern',
            handler: function(){
                speichern();
            }
        },{
            xtype: 'tbspacer',
            width: 10
        }]
    });



    return{
        init: function(){
            formular.render('formularNotschalter');
            ladenFormular();
        }
    }
}