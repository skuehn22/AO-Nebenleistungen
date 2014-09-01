Ext.namespace('hotelkategoriesVorhandene');

hotelkategoriesVorhandene = function(){
    // Beginn private

    var self;

    var condition_hotel_new = '1';
	var condition_hotel_passiv = '2';
	var condition_hotel_aktiv = '3';

    var jsonStoreHotels = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        autoLoad: true,
        url: "/admin/hotelkategories/tabelitemshotels/",
        id: 'jsonStoreHotels',
        fields: ['id','property_name','property_code', 'aktiv']
    });

    var jsonStoreKategories = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/hotelkategories/tabelitemshotelkategories/",
        id: 'jsonStoreKategories',
        baseParams: {
            id: memoryHotelId
        },
        fields: ['id','categorie_code','categorie_name', 'property_name', 'aktiv']
    });

    function renderer_aktiv(val){
		var icon = '';
		if(val == condition_hotel_new)
			icon = "<img src='/buttons/exclamation.png' ext:qtip='neu'>";
		else if(val == condition_hotel_passiv)
			icon = "<img src='/buttons/cancel.png' ext:qtip='passiv'>";
		else if(val == condition_hotel_aktiv)
			icon = "<img src='/buttons/accept.png' ext:qtip='aktiv'>";

		return icon;
	}

    function buttonLoadKategories(){
        var grid = self.hotels;

        if(!grid.getSelectionModel().hasSelection()){
            showMsgBox('Bitte Hotel auswählen');

            return;
        }

        showKategories(grid);

        return;
    }

     function showKategories(grid){
         var row = grid.getSelectionModel().getSelected();

         // speichern der Hotel - ID
         memoryHotelId = row.data.id;

         // leeren CategoryId
         memoryCategoryId = false;

         // laden Store
         jsonStoreKategories.setBaseParam('id', row.data.id);
         jsonStoreKategories.load();
     }
    
    // Beginn public
    return{
        hotels: new Ext.grid.GridPanel({
			autoHeight: true,
            renderTo: 'gridHotel',
	        stripeRows: true,
	        loadMask: true,
	        columnLines: true,
	        viewConfig: {
                forceFit: true,
                scrollOffset: 0
            },
            listeners: {
                rowclick: showKategories
            },
			autoExpandColumn: 'property_name',
	        store: jsonStoreHotels,
	        width: 450,
	        id: 'hotelsGrid',
	        title: 'vorhandene Hotels',
		    columns: [{
		                xtype: 'gridcolumn',
		                dataIndex: 'id',
		                header: 'Id',
		                width: 50,
		                id: 'id'
		            },{
		                xtype: 'gridcolumn',
		                dataIndex: 'property_name',
		                header: 'Name',
		                width: 200,
		                id: 'property_name'
		            },{
		                xtype: 'gridcolumn',
		                dataIndex: 'property_code',
		                header: 'Hotel Code',
		                id: 'property_code',
		                width: 100
		   			},{
		                xtype: 'gridcolumn',
		                dataIndex: 'aktiv',
		                header: 'Hotel aktiv / passiv',
		                id: 'aktiv',
		                width: 100,
		                renderer: renderer_aktiv
		   			}],
		   bbar: [{
	            xtype: 'paging',
	            store: jsonStoreHotels,
	            id: 'pagingHotels',
	            pageSize: 10,
	            displayMsg: "Anzeige: {0} - {1} von {2} ",
	            displayInfo: true
	        }],
           buttons: [{
                text: 'Kategorien des Hotels anzeigen',
                tooltip: 'Anzeigen der vorhandenen Kategorien eines Hotels',
                handler: buttonLoadKategories
           }]
		}),

        gridKategories: new Ext.grid.GridPanel({
			autoHeight: true,
	        stripeRows: true,
	        loadMask: true,
	        columnLines: true,
            // renderTo: 'gridKategories',
            id: 'hotelkategoriesVorhandeneGridKategories',
	        viewConfig: {
                forceFit: true,
                scrollOffset: 0
            },
            listeners: {
                rowclick: function(grid){
                    var row = grid.getSelectionModel().getSelected();
                    memoryCategoryId = row.data.id;
                },
                rowdblclick: hotelkategoriesDatenAdminVorhanden
            },
			autoExpandColumn: 'categorie_name',
	        store: jsonStoreKategories,
	        width: 500,
	        id: 'kategoriesGrid',
	        title: 'vorhandene Kategorien eines Hotels',
		    columns: [{
		                xtype: 'gridcolumn',
		                dataIndex: 'id',
		                header: 'Id der Kategorie',
		                width: 100,
		                id: 'id'
		            },{
		                xtype: 'gridcolumn',
		                dataIndex: 'property_name',
		                header: 'Name des Hotels',
		                width: 100,
		                id: 'property_name'
		            },{
		                xtype: 'gridcolumn',
		                dataIndex: 'categorie_code',
		                header: 'Kategorie Code',
		                width: 100,
		                id: 'categorie_code'
		            },{
		            	xtype: 'gridcolumn',
		                dataIndex: 'categorie_name',
		                header: 'Name der Kategorie',
		                width: 100,
		                id: 'categorie_name'
		   			},{
		            	xtype: 'gridcolumn',
		                dataIndex: 'aktiv',
		                header: 'aktiv / passiv',
		                width: 100,
		                id: 'aktiv',
		                renderer: renderer_aktiv,
                        hidden: true
		   			}],
		   bbar: [{
	            xtype: 'paging',
	            store: jsonStoreKategories,
	            id: 'pagingKategories',
	            pageSize: 10,
	            displayMsg: "Anzeige: {0} - {1} von {2} ",
	            displayInfo: true
	       }]
		}),

        gridKategoriesButtons: [{
                text: 'Daten einer Kategorie anzeigen',
                tooltip: 'Daten einer vorhandenen Kategorie anzeigen',
                handler: function(){
                    if(!memoryCategoryId){
                        showMsgBox('Bitte Kategorie auswählen');

                        return;
                    }

                    hotelkategoriesDatenAdminVorhanden();
                }
            },{
                text: 'deutsch',
                handler: function(){
                    hotelkategoriesBeschreibungAdmin('de');
                },
                tooltip: 'Beschreibung der Kategorie in deutsch'
            },{
                text: 'englisch',
                handler: function(){
                    hotelkategoriesBeschreibungAdmin('en');
                },
                tooltip: 'Beschreibung der Kategorie in englisch'
            },{
                text: 'neue Kategorie anlegen',
                tooltip: 'neue Kategorie anlegen',
                handler: function(){
                    if(!memoryHotelId){
                        showMsgBox('Bitte ein Hotel auswählen !');

                        return;
                    }

                    hotelkategoriesDatenAdminNeu();
                }
            }],

        workAdmin: function(){
            self = this;
        }
    }
}

function hotelkategoriesVorhandeneAdmin(){
    var view = new hotelkategoriesVorhandene();
    view.gridKategories.addButton(view.gridKategoriesButtons);
    view.gridKategories.render('gridKategories');
    view.workAdmin();

    parentView = view;
}