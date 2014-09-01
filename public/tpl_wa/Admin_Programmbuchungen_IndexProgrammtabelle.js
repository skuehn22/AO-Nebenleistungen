/**
 * Created with JetBrains PhpStorm.
 * User: PC Nutzer
 * Date: 17.07.12
 * Time: 11:40
 * To change this template use File | Settings | File Templates.
 */

var Admin_Programmbuchungen_IndexProgrammtabelle = function(){
    // begin private

    var gebuchteProgrammTabelle = null;

    var gridProgrammeJsonStore = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/programmbuchungen/getprogrammbuchungen/",
        id: 'storeGridProgrammbuchubgen',
        fields: ['id','buchungsnummer_id','firstname','lastname','datum','progname','status','superuser_id']
    });

    // sucht nach Buchungsnummer und Name
    function sucheBuchungsnummerName(){
        var buchungsnummer = Ext.get('sucheBuchungsnummer').getValue();
        var name = Ext.get('sucheName').getValue();
        var superuser = Ext.getCmp('sucheSuperuser').checked;

        if(buchungsnummer.length < 1 && name.length < 1 && superuser == false){
            showMsgBox('Bitte Suchwerte eingeben');

            return;
        }

        gridProgrammeJsonStore.setBaseParam('name', name);
        gridProgrammeJsonStore.setBaseParam('buchungsnummer', buchungsnummer);
        gridProgrammeJsonStore.setBaseParam('superuser', superuser);
        gridProgrammeJsonStore.load();
    }

    function rendererProgrammBuchungsStatus(){
        var status = arguments[0];

        if(status == 2)
            return "<img src='/buttons/exclamation.png' ext:qtip='Kunde hat angefragt'>";
        if(status == 3)
            return "<img src='/buttons/comment_new.png' ext:qtip='Programmanbieter hat zugesagt'>";
        if(status == 4)
            return "<img src='/buttons/icon_favourites.png' ext:qtip='Kunde wurde informiert'>";
    }

    function buildGebuchteProgrammeTabelle(){

       if(typeof programmGrid == 'object')
          return;

       gebuchteProgrammTabelle = new Ext.grid.GridPanel({
            id: 'adminProgrammbuchungenIndexProgrammtabelle',
            autoHeight: true,
            width: 750,
            border: false,
            collapseFirst: false,
            title: 'gebuchte Programme',
            enableColumnHide: false,
            enableColumnMove: false,
            enableColumnResize: false,
            store: gridProgrammeJsonStore,
            renderTo: 'gridProgrammbuchung',
            autoExpandColumn: 'grid_programmanme',
            columnLines: true,
            stripeRows: true,
            viewConfig: {
               forceFit: true,
               scrollOffset: 0
            },
            columns: [{
                dataIndex: 'id',
                editable: false,
                groupable: false,
                hidden: true,
                header: 'ID Programmbuchung',
                resizable: false,
                sortable: true,
                width: 10
            },{
                dataIndex: 'buchungsnummer_id',
                editable: false,
                groupable: false,
                header: 'Buchungsnummer',
                hideable: false,
                resizable: false,
                sortable: true,
                width: 75
            },{
                dataIndex: 'lastname',
                editable: false,
                groupable: false,
                header: 'Name',
                hideable: false,
                resizable: false,
                sortable: true,
                width: 120
            },{
                dataIndex: 'firstname',
                editable: false,
                groupable: false,
                header: 'Vorname',
                hideable: false,
                resizable: false,
                sortable: true,
                width: 120
            },{
                dataIndex: 'datum',
                editable: false,
                groupable: false,
                header: 'Buchungsdatum',
                hideable: false,
                name: 'grid_datum',
                resizable: false,
                sortable: true,
                width: 120
            },{
                dataIndex: 'progname',
                editable: false,
                groupable: false,
                header: 'Programmname',
                hideable: false,
                resizable: false,
                sortable: true,
                width: 120
            },{
                dataIndex: 'status',
                editable: false,
                groupable: false,
                header: 'Status',
                hideable: false,
                resizable: false,
                sortable: true,
                renderer: rendererProgrammBuchungsStatus,
                width: 55
            },{
                dataIndex: 'superuser_id',
                editable: false,
                groupable: false,
                header: 'Superuser',
                hideable: false,
                resizable: false,
                sortable: true,
                width: 140
            }],
           bbar: {
               xtype: 'paging',
               displayInfo: true,
               store: gridProgrammeJsonStore,
               pageSize: 10
           },
           tbar:[{
                    xtype: 'tbspacer',
                    width: 20
                },{
                    text: 'Superuser:'
                },{
                    xtype: 'checkbox',
                    id: 'sucheSuperuser',
                    checked: false
                },{
                    xtype: 'tbseparator'
                },{
					text: 'Buchungsnummer :'
		        },{
					xtype: 'textfield',
					width: 100,
					id: 'sucheBuchungsnummer',
                    maskRe: /[0-9]/
			    },{
                    xtype: 'tbseparator'
                },{
                    text: 'Name :'
                },{
                    xtype: 'textfield',
				    width: 100,
					id: 'sucheName'
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    text: 'suchen',
                    tooltip: 'Suche nach Buchungen',
                    icon: '/buttons/arrow_right.png',
                    handler: function(){
                        sucheBuchungsnummerName();
                    }
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    text: 'alles anzeigen',
                    tooltip: 'zeigt alle Buchungen an',
                    icon: '/buttons/arrow_right.png',
                    handler: function(){
                        var buchungsnummer = Ext.getCmp('sucheBuchungsnummer').setValue('');
                        var name = Ext.getCmp('sucheName').setValue('');

                        gridProgrammeJsonStore.setBaseParam('name','');
                        gridProgrammeJsonStore.setBaseParam('buchungsnummer', '');
                        gridProgrammeJsonStore.setBaseParam('superuser', '');
                        gridProgrammeJsonStore.load();
                    }
           },{
                    xtype: 'tbseparator'
           }]
        });

        return;
    }



    // end private
    // begin public
    return{
        storeLoad: function(){
            gridProgrammeJsonStore.load();
        },
        tableShow: function(){
            buildGebuchteProgrammeTabelle();
            gebuchteProgrammTabelle.show();

            Ext.getCmp('sucheBuchungsnummer').getEl().on('dblclick',function(){
                sucheBuchungsnummerName();
            });
            
            Ext.getCmp('sucheName').getEl().on('dblclick',function(){
                sucheBuchungsnummerName();
            });
        },
        getStore: function(){
            return gridProgrammeJsonStore;
        }
    }
    // end public
};