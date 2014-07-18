function fillProdukteEinerRate(){
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
        title: 'Produkte einer Rate - Rate Code: ' + "'" + memoryRateCode + "' Id: " + memoryRatenId,
        autoWidth: true,
        modal: true,
        autoHeight: true,
        shadow: false,
        border: false,
        // border: showCloseButton,
        x: 20,
        y: 20
    });

    var jsonStoreProducts = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/hotelrates1/getproductsfromhotel/",
        baseParams: {
            hotelId: memoryHotelId,
            rateId: memoryRatenId
        },
        id: 'jsonStoreProducts',
        fields: ['checked','id','product_name', 'price', 'vat', 'typ', 'standardProduct'],
        listeners: {
            load: function(store, data, options){
                var select = new Array();
                var i = 0;
                for(var j=0; j < data.length; j++){
                    var control = data[j].data.checked;
                    if(control == true){
                        select[i] = j;
                        i++;
                    }
                }

                checkboxSel.selectRows(select);
            }
        }
    });

    var checkboxSel = new Ext.grid.CheckboxSelectionModel();

    gridProducts = new Ext.grid.GridPanel({
        store: jsonStoreProducts,
        autoHeight: true,
        stripeRows: true,
        width: 550,
        loadMask: true,
        columnLines: true,
        id: 'productsGrid',
        // autoExpandColumn: 'productName',
        sm: checkboxSel,
        viewConfig: {
            forceFit: true,
            scrollOffset: 0
        },
        columns: [
            checkboxSel,
        {
            xtype: 'gridcolumn',
            dataIndex: 'id',
            header: 'ID',
            sortable: true,
            width: 50,
            id: 'id'
        },{
            xtype: 'gridcolumn',
            header: 'Name des Produkts',
            sortable: true,
            width: 150,
            dataIndex: 'product_name',
            id: 'productName',
            renderer: renderer_standardProducts
        },{
            xtype: 'gridcolumn',
            header: 'Preis des Produkts',
            sortable: true,
            width: 100,
            dataIndex: 'price',
            id: 'price',
            renderer: renderer_euro
        },{
            xtype: 'gridcolumn',
            header: 'Mehrwertsteuer',
            sortable: true,
            width: 50,
            dataIndex: 'vat',
            id: 'vat',
            renderer: renderer_prozent
        },{
            xtype: 'gridcolumn',
            header: 'Produktzuordnung',
            sortable: true,
            width: 100,
            dataIndex: 'typ',
            id: 'produktTyp',
            renderer: renderer_typProduct
        }],
        buttons: [{
            text: 'speichern',
            tooltip: 'Produkte und Leistungen einer Rate zuordnen',
            handler: function(){
                setProdukteEinerRate();
            }
        }]
    });


    fenster.add(gridProducts);
    fenster.doLayout();

    fenster.show();
    jsonStoreProducts.load();
}

function setProdukteEinerRate(){
    var records = gridProducts.getSelectionModel().getSelections();
    var data = new Array();
    for(var i = 0; i < records.length; i++){
        data[i] = records[i].data.id;
    }

    var produkte = Ext.util.JSON.encode(data);

    Ext.Ajax.request({
       url: '/admin/hotelrates1/setprodukteeinerrate/',
       method: 'post',
       params: {
           hotelId: memoryHotelId,
           rateId: memoryRatenId,
           produkte: produkte
       },
       success: function(){
           fenster.close();
       }
    });

    return;
}

function renderer_standardProducts(value, column, record){
    var icon = '';

    var standardProduct = record.get('standardProduct');

    if(standardProduct == 2)
        icon = "<img src='/buttons/accept.png' ext:qtip='ist Standard Produkt des Hotels'>";

    return value + ' ' + icon;
}

function renderer_typProduct(typ){
    var textProductTyp = ' ';

    if(typ == 1)
        textProductTyp = 'je Person';
    if(typ == 2)
        textProductTyp = 'je Buchung';
    if(typ == 3)
        textProductTyp = 'je Person / Nacht';
    if(typ == 4)
        textProductTyp = 'allgemein';

    return textProductTyp;
}

function renderer_euro(val){
    // var preis = val.replace(/\./,",");
    var preis = val;
    preis += ' Euro';

    return preis;
}

function renderer_prozent(val){

    return val + " %";
}
