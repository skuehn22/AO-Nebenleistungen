Ext.namespace('hotelkategoriesDaten');

hotelkategoriesDaten = function(){
    // Beginn private
    var self;

    function newCategory(){

		self.kategorieDaten.getForm().submit({
			params: {
                hotelId: memoryHotelId
			},
			success: function(form, action){
				EM.fireEvent('hotelkategoriesVorhandeneKategorieGridLoad');
                self.fenster.close();
			}
		});
	}

    // Beginn public
    return{
        kategorieDaten: new Ext.form.FormPanel({
		    autoHeight: true,
            frame: true,
	        id: 'formStammdatenKategorie',
	        url: '/admin/hotelkategories/newhotelkategory/',
	        method: 'post',
            labelWidth: 200,
            width: 450,
		    items: [{
		                xtype: 'textfield',
		                fieldLabel: 'Kategorie Code',
		                width: 120,
		                id: 'categorie_code',
                        allowBlank: false,
                        helpText: 'Kategorie-Code kann im Hotel nur einmal vergeben werden'
		            },{
		                xtype: 'textfield',
		                fieldLabel: 'Name der Kategorie (de)',
		                width: 200,
		                id: 'categorie_name',
                        allowBlank: false,
                        helpText: 'Bitte deutschen Kategoriename eingeben'
		            },{
                        xtype: 'textfield',
                        fieldLabel: 'Name der Kategorie (en)',
                        width: 200,
                        id: 'categorie_name_en',
                        allowBlank: false,
                        helpText: 'Bitte englischen Kategoriename eingeben'
                    },{
		                xtype: 'numberfield',
		                fieldLabel: 'Personen',
		                width: 50,
		                id: 'standard_persons',
                        allowBlank: false,
                        allowNegative: false,
                        allowDecimals: false,
                        helpText: 'Bitte Anzahl der Betten ohne Zustellbetten eingeben'
		            },{
		                xtype: 'numberfield',
		                fieldLabel: 'min. Personen',
		                width: 50,
		                id: 'min_persons',
                        allowBlank: false,
                        allowNegative: false,
                        allowDecimals: false,
                        helpText: 'Bitte min. Anzahl der Personen je Zimmer eingeben'
		            },{
		                xtype: 'numberfield',
		                fieldLabel: 'max. Personen',
		                width: 50,
		                id: 'max_persons',
                        allowBlank: false,
                        allowDecimals: false,
                        allowNegative: false,
                        helpText: 'Anzahl der Betten mit Aufbettung'
		            },{
                        xtype: 'combo',
                        fieldLabel: 'Zimmer shared',
                        helpText: 'Fremdpersonen im Zimmer',
                        store: [[1,'nein'],[2,'ja']],
                        allowBlank: false,
                        forceSelection: true,
                        triggerAction: 'all',
                        valueField: 'geteilt',
                        hiddenName: 'shared',
                        displayField: 'myVat',
                        width: 75
                    }],
		   	buttons: [{
				text: 'speichern',
				handler: function(){
		   			newCategory();
		   		}
			}]
		}),

        kategorieDataLoad: function(){
            this.kategorieDaten.getForm().load({
                url: '/admin/hotelkategories/getcategorydata/',
                params: {
                    hotelId: memoryHotelId,
                    categoryId: memoryCategoryId
                }
            });
        },

        fenster: new Ext.Window({
            title: 'Stammdaten Kategorie, Hotel ID: ' + memoryHotelId,
            closable: true,
            autoHeight: true,
            resizable: false,
            width: 500,
            padding: 10,
            buttonAlign: 'right',
            shadow: false,
            modal: true,
            layout: 'hbox',
            x: 20,
            y: 20
        }),

        neueDaten: function(){
            this.kategorieDaten.getForm().reset();
        },

        workAdmin: function(){

            self = this;
        }
    }
}

function hotelkategoriesDatenAdminNeu(){
    if(!memoryHotelId){
        showMsgBox('Bitte Hotel auswählen');

        return;
    }

    var view = new hotelkategoriesDaten();

    view.workAdmin();
    view.fenster.add(view.kategorieDaten);
    view.fenster.show();
    view.neueDaten();
}

function hotelkategoriesDatenAdminVorhanden(){
    if(!memoryCategoryId){
        showMsgBox('Bitte Kategorie auswählen');

        return;
    }

    var view = new hotelkategoriesDaten();

    view.workAdmin();
    view.fenster.add(view.kategorieDaten);
    view.fenster.show();
    view.kategorieDataLoad();
}


