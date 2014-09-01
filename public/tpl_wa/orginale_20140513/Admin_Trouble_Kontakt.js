var adminTroubleKontakt = function(){


    function loadKontaktGrid()
    {
        Ext.Ajax.request({
           url: '/admin/trouble/get-kontakt/',
           success: function(response){
               var data = Ext.util.JSON.decode(response.responseText);
               var propGrid = Ext.getCmp('tabelleKontakte');
               propGrid.setSource(data);
           },
           params: {
                kundenId: memoryKundenId
           }
        });
    }

    var tabelleKontakte = new Ext.grid.PropertyGrid({
        renderTo: 'kontakt',
        width: 300,
        id: 'tabelleKontakte',
        autoHeight: true,
        source: {
            Anrede: '',
            Vorname: '',
            Name: '',
            Stadt: '',
            Strasse: '',
            Hausnummer: '',
            PLZ: '',
            Mail: '',
            Telefon1: '',
            Telefon2: ''
        },
        viewConfig : {
            forceFit: true,
            scrollOffset: 2
        }
    });



    return{
        init: function()
        {
            // jsonStoreKontakt.setBaseParam('kundenId', memoryKundenId);
            // jsonStoreKontakt.load();
            loadKontaktGrid();
        }
    }
}

Ext.onReady(function(){
    memoryAdminTroubleKontakt = new adminTroubleKontakt();
});
