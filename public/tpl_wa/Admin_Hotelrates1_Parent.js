function fillParent(){

    var jsonStoreHotels = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/hotelrates1/gethotels/",
        id: 'jsonStoreHotels',
        fields: ['id','property_name','property_code', 'aktiv']
    });

    gridHotels = new Ext.grid.GridPanel({
        autoHeight: true,
        stripeRows: true,
        loadMask: true,
        renderTo: 'gridHotel',
        columnLines: true,
        viewConfig: {
            forceFit: true
        },
        autoExpandColumn: 'property_name',
        store: jsonStoreHotels,
        width: 450,
        id: 'hotelsGrid',
        title: 'vorhandene Hotels',
        viewConfig: {
           scrollOffset: 0
        },
        listeners: {
            rowdblclick: anzeigenRatenUndKategorienEinesHotels
        },
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
            text: 'Raten eines Hotels',
            tooltip: 'anzeigen der Kategorien eines Hotels',
            handler: anzeigenRatenUndKategorienEinesHotels
        }]
    });

    jsonStoreKategories = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        baseParams: {
            hotelId: memoryHotelId
        },
        url: "/admin/hotelrates1/gethotelkategorienundraten/",
        id: 'jsonStoreKategories',
        fields: ['id','kategorieName','kategorieId','kategorieCode', 'ratenName', 'ratenCode','aktiv']
    });

    var gridKategories = new Ext.grid.GridPanel({
        autoHeight: true,
        stripeRows: true,
        loadMask: true,
        renderTo: 'gridKategories',
        columnLines: true,
        viewConfig: {
           scrollOffset: 0
        },
        listeners: {
            rowclick: function(){
                var record = gridKategories.getSelectionModel().getSelected();
                memoryRatenId = record.data.id;
                memoryRateCode = record.data.ratenCode;
                memoryRatenName = record.data.ratenName;
            },
            rowdblclick: function(){
                fillVorhandenerate();
            }
        },
        autoExpandColumn: 'ratenName',
        store: jsonStoreKategories,
        width: 650,
        id: 'kategoriesGrid',
        title: 'vorhandene Raten eines Hotels',
        columns: [{
                    xtype: 'gridcolumn',
                    dataIndex: 'id',
                    header: 'Id',
                    width: 50
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'ratenName',
                    header: 'Raten Name',
                    width: 100,
                    id: 'ratenName'
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'ratenCode',
                    header: 'Raten Code',
                    width: 100,
                    id: 'ratenCode'
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'kategorieId',
                    header: 'Kategorie Id',
                    width: 100,
                    id: 'kategorieId'
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'kategorieName',
                    header: 'Kategorie Name',
                    width: 100,
                    id: 'kategorieName'
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'kategorieCode',
                    header: 'Kategorie Code',
                    width: 100,
                    id: 'kategorieCode'
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'aktiv',
                    header: 'aktiv / passiv',
                    width: 75,
                    id: 'aktiv',
                    renderer: renderer_aktiv
                }],
       bbar: [{
            xtype: 'paging',
            store: jsonStoreKategories,
            id: 'pagingKategories',
            pageSize: 10,
            displayMsg: "Anzeige: {0} - {1} von {2} ",
            displayInfo: true
       }],
       buttons: [{
           text: 'neue Rate anlegen',
           handler: function(){
               fillNeueRate();
           }
       },{
           text: 'vorhandene Rate bearbeiten',
           handler: function(){
               fillVorhandenerate();
           }
       },{
           text: 'Produkte einer Rate',
           handler: function(){
               fillProdukteEinerRate();
           }
       },{
           text: 'Bildzuordnung ändern',
           handler: function(){
               fillBildAendern();
           }
       }]
    });

    jsonStoreHotels.load();
}

function anzeigenRatenUndKategorienEinesHotels(){
   var record = gridHotels.getSelectionModel().getSelected();

   if(!gridHotels.getSelectionModel().hasSelection()){
       showMsgBox('Bitte Hotel auswählen');

       return;
   }

   // speichern der Hotel - ID
   memoryHotelId = record.data.id;
   memoryHotelCode = record.data.property_code;

   Ext.getCmp('kategoriesGrid').store.setBaseParam('hotelId', record.data.id);

   jsonStoreKategories.load({
       params: {
           idProperties: record.data.id
       }
   });
}

function renderer_aktiv(val){
    var icon = "<img src='/buttons/accept.png' ext:qtip='aktiv'>";

    if(val == condition_hotel_new)
        icon = "<img src='/buttons/exclamation.png' ext:qtip='neu'>";
    else if(val == condition_hotel_passiv)
        icon = "<img src='/buttons/cancel.png' ext:qtip='passiv'>";
    else if(val == condition_hotel_aktiv)
        icon = "<img src='/buttons/accept.png' ext:qtip='aktiv'>";

    return icon;
}
