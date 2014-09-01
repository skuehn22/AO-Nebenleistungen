Ext.namespace('abfahrtsorteZeiten');

abfahrtsorteZeiten = function(){

    var self = null;

    function zeitenEintragen(){
        self.formular.getForm().submit({
            url: '/admin/abfahrtsorte/setneuezeit',
            method: 'post',
            params: {
                programmId: programmId
            },
            success: function(){
                listeStore.load();
            }
        });
    }

    function zeitLoeschen(){
        if(!self.liste.getSelectionModel().hasSelection()){
            showMsgBox('Bitte Zeit markieren');

            return;
        }

        var selected = self.liste.getSelectionModel().getSelected();


        Ext.Ajax.request({
            url: '/admin/abfahrtsorte/deletezeiten',
            method: 'post',
            params: {
                id: selected.data.id
            },
            success: function(){
                listeStore.remove(selected);
            }
        });

        
    }

    var listeStore = new Ext.data.JsonStore({
        url: '/admin/abfahrtsorte/getprogrammzeiten',
        baseParams: {
            programmId: programmId
        },
        method: 'post',
        root: 'data',
        fields: ['ankunft', 'abfahrt','id']
    });

    return{

        formular: new Ext.form.FormPanel({
            padding: 10,
            frame: true,
            items: [{
                xtype: 'timefield',
                id: 'ankunft',
                width: 200,
                fieldLabel: 'Ankunftszeit',
                helpText: 'Die Angabe der Ankunftszeit ist<br>nicht zwingend erforderlich'
            },{
                xtype: 'timefield',
                id: 'abfahrt',
                width: 200,
                fieldLabel: 'Abfahrtszeit *',
                allowBlank: false
            }],
            buttons: [{
                text: 'Zeiten eintragen',
                handler: zeitenEintragen,
                tooltip: 'Eintragen einer neuen Abfahrts und Ankunftszeit'
            }]

        }),

        liste: new Ext.grid.GridPanel({
            emptyText: 'keine Zeiten vorhanden',
            width: 320,
            autoHeight: true,
            border: false,
            stripeRows: true,
            columnLines: true,
            store: listeStore,
            viewConfig: {
                forceFit: true,
                scrollOffset: 0
            },
            bodyStyle: {
                marginLeft: '10px'
            },
            columns: [{
                xtype: 'gridcolumn',
                hidden: true,
                id: 'id',
                dataIndex: 'id'
            },{
                header: 'Ankunftszeit',
                xtype: 'gridcolumn',
                dataIndex: 'ankunft',
                width: 150,
                sortable: false
            },{
                header: 'Abfahrtszeit',
                dataIndex: 'abfahrt',
                width: 150,
                xtype: 'gridcolumn',
                sortable: false
            }],
            buttons: [{
                text: 'Zeit löschen',
                tooltip: 'Löschen einer markierten Ankunfts / Abfahrtszeit',
                handler: function(){
                    zeitLoeschen();
                }
            }]
        }),

        fenster: new Ext.Window({
            title: 'Ankunfts / Abfahrtszeiten, Programm ID: ' + programmId,
            closable: showCloseButton,
            layout: 'hbox',
            width: 700,
            resizable: false,
            autoHeight: true,
            padding: 10,
            buttonAlign: 'right',
            shadow: false,
            modal: true,
            layout: 'hbox',
            x: 20,
            y: 20
        }),

        workAdmin: function(){
            listeStore.load();
            self = this;
        }
    }


}

function abfahrtsorteZeitenAdmin(){
    if(!programmId){
        showMsgBox('Bitte Programm auswählen');

        return;
    }

    var view = new abfahrtsorteZeiten();
    view.fenster.add(view.formular);
    view.fenster.add(view.liste);

    view.fenster.show();
    view.workAdmin();

}
