Ext.onReady(function(){

    storePreiseMwst = new Ext.data.SimpleStore({
        fields: ['mwstId','mwst'],
        data: [['A','19%'],['B','7%'],['C','0%']]
    });

    preiseForm = new Ext.form.FormPanel({
		    title: 'Grundpreis',
		    width: 300,
            frame: true,
		    height: 200,
		    padding: 10,
            labelWidth: 120,
		    url: '/admin/datensatz/setprices/',
		    method: 'post',
		    id: 'preiseForm',
		    items: [{
	                xtype: 'numberfield',
	                fieldLabel: 'Netto Einkaufspreis Euro *',
	                id: 'ek',
	                width: 220,
		            decimalPrecision: 2,
                    width: 100,
		            allowBlank: false
	            },{
	                xtype: 'numberfield',
	                fieldLabel: 'Netto Verkaufspreis Euro *',
	                id: 'vk',
	                width: 220,
	                decimalPrecision: 2,
                    width: 100,
	                allowBlank: false
	            },{
	            	xtype: 'combo',
	                fieldLabel: 'Mehrwertsteuer',
	                width: 100,
	                allowBlank: false,
	                forceSelection: true,
					triggerAction: 'all',
					displayField: 'mwst',
					valueField: 'mwstId',
					hiddenName: 'mwst_satz',
					store: storePreiseMwst,
					mode: 'local',
					lazyRender:true,
					typeAhead: true
	            },{
                    xtype: 'radiogroup',
                    fieldLabel: 'Preistyp',
                    items: [
                        {boxLabel: 'Personenpreis', name: 'gruppenpreis', inputValue: 1},
                        {boxLabel: 'Gruppenpreis', name: 'gruppenpreis', inputValue: 2}
                    ]

            }],
	            buttons: [{
					text: 'eintragen',
					handler: function(){
	            		Ext.getCmp('preiseForm').getForm().submit({
		            		params: {
								programmId: programmId
		            		},
							success: function(form,action){
								showMsgBox('Preise geändert');
							},
							failure: function(form,action){
								showMsgBox('Fehler');
							}
						});
					}
		        }]
    });

});
