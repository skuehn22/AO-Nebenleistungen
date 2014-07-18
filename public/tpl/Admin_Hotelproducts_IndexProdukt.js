/**
 * Darstellung des produktes eines
 * Hotels.
 *
 * Created with JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 16.10.12
 * Time: 11:40
 * To change this template use File | Settings | File Templates.
 */

var AdminHotelproductsIndexProdukt = function(){
    // begin private

    var ProductId = null;
    var ProduktName = '';

    var FormularProdukt = null;
    var ProduktFenster = null;

    // end private
    // begin public
    return {
        start: function(){
            this.formularProdukt.getForm().reset();

            if(this.findeProdukt())
                return;

            this.fenster.setTitle('Produkt: ' + ProduktName + " ID: " + ProductId);

            FormularProdukt = this.formularProdukt;
            this.fenster.add(this.formularProdukt);
            this.fenster.show();

            ProduktFenster = this.fenster;

            this.ladeFormular();
            this.findeBild();
        },

        fenster: new Ext.Window({
            title: '',
            modal: true,
            autoWidth: true,
            autoHeight: true,
            shadow: false,
            border: false,
            padding: 10,
            x: 20,
            y: 20
        }),

        findeProdukt: function(){
            var selectedRowGridProductsFromHotel = gridProducts.getSelectionModel();

            if(!selectedRowGridProductsFromHotel.hasSelection()){
                showMsgBox('Bitte Produkt auswählen');

                return true;
            }

            var recordProduct = selectedRowGridProductsFromHotel.getSelected();
            ProductId = recordProduct.data.id;

            ProduktName = recordProduct.data.product_name;

            return false;
        },

        findeBild: function(){
            var momentaneZeit = new Date();
            var sekunden = momentaneZeit.getSeconds();

            var productPic = document.getElementById('productPic');
            productPic.setAttribute('src',"/images/product/" + ProductId + ".jpg?time=" + sekunden);

            return;
        },

        ladeFormular: function(){
            this.formularProdukt.getForm().load({
                url: '/admin/hotelproducts/getproductdata/',
                method: 'post',
                params: {
                    productId: ProductId
                }
            });

            return;
        },

        formularProdukt: new Ext.form.FormPanel({
            frame: true,
            fileUpload: true,
            id: 'formProductPanel',
            width: 900,
            autoHeight: true,
            bodyStyle: 'padding: 10px',
            labelWidth: 150,
            method: 'post',
            url: '/admin/hotelproducts/setproductsproperties/',
            items: [{
                layout: 'column',
                items: [{
                    layout: 'form',
                    columnWidth: 0.5,
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: 'Produktname de *',
                        width: 200,
                        name: 'product_name',
                        helpText: 'Produktname deutsch',
                        allowBlank: false
                    },{
                        xtype: 'textfield',
                        fieldLabel: 'Produktname en *',
                        width: 200,
                        name: 'product_name_en',
                        helpText: 'Produktname englisch',
                        allowBlank: false
                    },{
                        xtype: 'numberfield',
                        allowNegativ: false,
                        width: 100,
                        name: 'price',
                        fieldLabel: 'Verkaufspreis Brutto *',
                        helpText: 'Brutto Verkaufspreis bitte mit Punkt als Trennzeichen',
                        autoStripChars: true,
                        decimalSeparator: '.',
                        allowBlank: false
                    },{
                        xtype: 'combo',
                        fieldLabel: 'Mwst. *',
                        store: [[7,'7%'],[19,'19%'],[10,'10%'],[20,'20%']],
                        allowBlank: false,
                        forceSelection: true,
                        triggerAction: 'all',
                        valueField: 'vatValue',
                        hiddenName: 'vat',
                        displayField: 'myVat',
                        width: 75,
                        helpText: 'Mehrwertsteuer',
                        allowBlank: false
                    },{
                        xtype: 'combo',
                        fieldLabel: 'aktiv *',
                        width: 100,
                        mode: 'local',
                        store: [[2,'passiv'],[3,'aktiv']],
                        forceSelection: true,
                        triggerAction: 'all',
                        valueField: 'id',
                        hiddenName: 'aktiv',
                        displayField: 'aktivStatus',
                        helpText: 'Status des Produktes',
                        allowBlank: false
                    },{
                        xtype: 'combo',
                        width: 150,
                        fieldLabel: 'Produktzuordnung',
                        helpText: 'Art der Zuordnung der Produkte',
                        mode: 'local',
                        store: [[1,'je Person'],[2,'je Zimmer'],[3,'je Person / Nacht'],[4,'Anzahl'],[5,'Stück / Nacht'],[6,'Stück / für ein Datum']],
                        forceSelection: true,
                        triggerAction: 'all',
                        valueField: 'typProdukt',
                        hiddenName: 'typ',
                        displayField: 'typAnzeige',
                        helpText: 'Verrechnung des Produktes',
                        allowBlank: false
                    },{
                        xtype: 'radiogroup',
                        fieldLabel: 'Datumszuordnung',
                        itemCls: 'x-check-group-alt',
                        columns: 1,
                        items: [
                            {boxLabel: 'keine Datumszuordnung', name: 'datumszuordnung', inputValue: 1},
                            {boxLabel: 'erstmalig am Anreisetag', name: 'datumszuordnung', inputValue: 2},
                            {boxLabel: 'letztmalig am Abreisetag', name: 'datumszuordnung', inputValue: 3}
                        ]
                    },{
                        xtype: 'checkbox',
                        name: 'verpflegung',
                        helpText: 'Produkt ist ein Verpflegungstyp',
                        fieldLabel: 'Verpflegung ?'
                    },{
                        xtype: 'combo',
                        width: 150,
                        fieldLabel: 'Produkt Code',
                        helpText: 'Der Produkt Code darf im Hotel nur einmal vorkommen',
                        mode: 'local',
                        store: [['BR','Frühstück'],['LU','Mittagessen'],['LP','Lunchpaket'],['DI','Abendessen'],['HP','Halbpension'],['VB','Vollverpflegung'],['AI','all inklusive'],['SON','sonstiges']],
                        triggerAction: 'all',
                        typeAhead: true,
                        valueField: 'productCode',
                        hiddenName: 'productCode',
                        displayField: 'productCode',
                        minLength: 2,
                        helpText: 'Bitte Code des Produktes eingeben. Darf im Hotel nur einmal vorkommen. Min. 2 Zeichen.',
                        allowBlank: false
                    }]
                },{
                    layout: 'form',
                    columnWidth: 0.5,
                    items: [{
                        xtype: 'combo',
                        fieldLabel: 'Grundleistung',
                        name: 'standardProduct',
                        store: [[1, 'keine touristische Grundleistung'],[2, 'touristische Grundleistung']],
                        width: 250,
                        typeAhead: true,
                        triggerAction: 'all',
                        helpText: 'Ist das Produkt eine touristische Grundleistung ?',
                        hiddenName: 'standardProduct'
                    },{
                        xtype: 'textfield',
                        name: 'productImage',
                        inputType: 'file',
                        fieldLabel: 'Bild',
                        helpText: 'Bild des Produktes'
                    },{
                        xtype: 'box',
                        autoEl: {
                            tag: 'div',
                            html: '<img id="productPic" src="/images/product/standard.jpg">'
                        }
                    },{
                        xtype: 'textarea',
                        fieldLabel: 'deutsch *',
                        width: 250,
                        name: 'ger',
                        helpText: 'Produktbeschreibung in deutsch',
                        allowBlank: false
                    },{
                        xtype: 'textarea',
                        fieldLabel: 'englisch *',
                        width: 250,
                        name: 'eng',
                        helpText: 'Produktbeschreibung in englisch',
                        allowBlank: false
                    }]
                }]
            }],
            fbar: {
                xtype: 'toolbar',
                items: [{
                        xtype: 'button',
                        text: 'ändern',
                        handler: function(){
                            FormularProdukt.getForm().submit({
                                params: {
                                    productId: ProductId
                                },
                                success: function(form,action){
                                    storeGridProducts.load();

                                    ProduktFenster.close();
                                },
                                failure: function(form,action){
                                    var message = 'Produkt wurde nicht gespeichert';

                                    if(action.failureType == 'server'){
                                        var response = action.response.responseText;
                                        var messageItems = Ext.util.JSON.decode(response);
                                        message = messageItems.message;
                                    }

                                    Ext.Msg.alert(message);
                                }
                            });
                        }
                }]
            }
        })
    }
    // end public
}

function fillAdminHotelproductsIndexProdukt(){
    var product = new AdminHotelproductsIndexProdukt();
    product.start();
}