/**
 * Darstellung der Personendaten in einer Tabelle.
 * Suche nach Personen über ein Suchformular.
 *
 *
 *
 * User: Stephan.Krauss
 * Date: 15.01.13
 * Time: 10:22
 * To change this template use File | Settings | File Templates.
 */
var AdminPersonendatenIndex = function(){

    //*** Beginn Private

    var definierteRollenRenderer = new Array('Nobody','User','Erstkunde','Kunde','Superuser','Anbieter','Konzernadministrator','Redakteur extern','Redakteur intern','Buchhaltung','Administrator');

    var rolleAendern = function(){
        if(!gridPersonendaten.getSelectionModel().hasSelection()){
            showMsgBox('Bitte einen Benutzer auswählen !');

            return;
        }

        var datenBenutzer = gridPersonendaten.getSelectionModel().getSelected();

        var form = formularPersonendaten.getForm();
        form.reset();

        form.findField('id').setValue(datenBenutzer.data.id);
        form.findField('idKunde').setValue(datenBenutzer.data.id);
        form.findField('company').setValue(datenBenutzer.data.company);
        form.findField('firstname').setValue(datenBenutzer.data.firstname);
        form.findField('lastname').setValue(datenBenutzer.data.lastname);
        var rolleId = datenBenutzer.data.status;
        form.findField('rolle').setValue(rolleId);
    }

    var speichernPersonendaten = function(){
        var form = formularPersonendaten.getForm();

        var idKunde = form.findField('idKunde').getValue();
        if(idKunde == ""){
            showMsgBox('Bitte Benutzer auswählen');

            return;
        }

        form.submit({
            success: function(){
                form.reset();
                jsonStoreGridPersonendaten.load();
            }
        });
    }

    // Definitionen der Rollen
    var rolleRenderer = function(rolleId){
        var rolle = definierteRollenRenderer[rolleId];

        return rolle;
    }

    var bereichRenderer = function(bereichId){
        var bereich = '';

        if(bereichId == 1)
            bereich = 'Programme';
        else if(bereichId == 6)
            bereich = 'Übernachtung';

        return bereich;
    }

    var jsonStoreGridPersonendaten = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: '/admin/personendaten/show',
        id: 'gridPersonendaten',
        fields: ['id', 'company', 'firstname', 'lastname', 'email', 'status', 'anbieter']
    });

    var gridPersonendaten = new Ext.grid.GridPanel({
        renderTo: 'tabellePersonendaten',
        id: 'gridPersonendaten',
        autoHeight: true,
        autoWidth: true,
        stripeRows: true,
        columnLines: true,
        autoExpandColumn: 'company',
        store: jsonStoreGridPersonendaten,
        title: 'Personendaten',
        listeners:{
            rowdblclick: function(){
                rolleAendern();
            }
        },
        viewConfig: {
            forceFit: true,
            scrollOffset: 0
        },
        style: {
            marginLeft: '10px'
        },
        columns: [{
            xtype: 'gridcolumn',
            dataIndex: 'id',
            header: 'Id',
            sortable: true,
            width: 50
        },{
            xtype: 'gridcolumn',
            dataIndex: 'company',
            header: 'Firma',
            sortable: true,
            width: 150
        },{
            xtype: 'gridcolumn',
            dataIndex: 'firstname',
            header: 'Vorname',
            sortable: true,
            width: 100
        },{
            xtype: 'gridcolumn',
            dataIndex: 'lastname',
            header: 'Name',
            sortable: true,
            width: 100
        },{
            xtype: 'gridcolumn',
            dataIndex: 'email',
            header: 'E - Mail',
            sortable: true,
            width: 150
        },{
            xtype: 'gridcolumn',
            dataIndex: 'status',
            header: 'Rolle',
            sortable: true,
            renderer: rolleRenderer,
            width: 75
        },{
            xtype: 'gridcolumn',
            dataIndex: 'anbieter',
            header: 'Bereich',
            sortable: true,
            renderer: bereichRenderer,
            width: 75
        }],
        tbar: [{
            xtype: 'tbseparator'
        },{
            text: 'Name: '
        },{
            xtype: 'textfield',
            width: 150,
            name: 'sucheName',
            id: 'sucheName'
        },{
            xtype: 'tbseparator'
        },{
            text: 'Mail: '
        },{
            xtype: 'textfield',
            width: 150,
            name: 'sucheMail',
            id: 'sucheMail'
        },{
            xtype: 'tbseparator'
        },{
            xtype: 'button',
            width: 75,
            text: 'suchen',
            handler: function(){
                var name = Ext.getCmp('sucheName').getValue();
                var mail = Ext.getCmp('sucheMail').getValue();

                jsonStoreGridPersonendaten.setBaseParam('lastname', name);
                jsonStoreGridPersonendaten.setBaseParam('email', mail);
                jsonStoreGridPersonendaten.load();
            }
        },{
            xtype: 'tbseparator'
        }],
        bbar: [{
            xtype: 'paging',
            store: jsonStoreGridPersonendaten,
            pageSize: 10,
            displayMsg: "Anzeige: {0} - {1} von {2} ",
            displayInfo: true
        }],
        fbar: [{
            xtype: 'button',
            text: 'Rolle ändern',
            handler: function(){
                rolleAendern();
            }
        }]
    });

    var formularPersonendaten = new Ext.form.FormPanel({
        autoHeight: true,
        width: 300,
        padding: 10,
        url: '/admin/personendaten/rolle-aendern',
        method: 'post',
        renderTo: 'formularPersonendaten',
        title: 'Personendaten',
        items: [{
            xtype: 'hidden',
            name: 'idKunde'
        },{
            xtype: 'displayfield',
            fieldLabel: 'Kunden ID',
            name: 'id'
        },{
            xtype: 'displayfield',
            name: 'company',
            fieldLabel: 'Firma'
        },{
            xtype: 'displayfield',
            name: 'firstname',
            fieldLabel: 'Vorname'
        },{
            xtype: 'displayfield',
            name: 'lastname',
            fieldLabel: 'Name'
        },{
            xtype: 'combo',
            name: 'rolle',
            fieldLabel: 'Rolle',
            mode: 'local',
            width: 150,
            forceSelection: true,
            triggerAction: 'all',
            store: [[0,'Nobody'],[1,'User'],[2,'Erstkunde'],[3,'Kunde'],[4,'Superuser'],[5,'Anbieter'],[6,'Konzernadministrator'],[7,'Redakteur extern'],[8,'Redakteur intern'],[9,'Buchhaltung'],[10,'Administrator']],
            displayField: 'rolle',
            valueField: 'rolle',
            hiddenName:'rolle',
            allowBlank: false,
            typeAhead: true
        }],
        fbar: [{
            xtype: 'button',
            text: 'speichern',
            handler: function(){
                speichernPersonendaten();
            }
        }]
    });

    // Ende Private

    // Beginn Public
    return{
        init: function(){
            this.ladenTabelle();
        },
        ladenTabelle: function(){
            jsonStoreGridPersonendaten.load();
        }





    }
    // Ende Public
}


Ext.onReady(function(){

    var personendaten = new AdminPersonendatenIndex();
    personendaten.init();

});


