function fillTemplate(sprache){
     if(!grid.getSelectionModel().hasSelection()){
        showMsgBox('Hotel auswählen');

        return;
    }

    if(!memoryHotelId){
        showMsgBox('Hotel auswählen');
        return;
    }

    var templateUrl = '/admin/hotels/gettemplate/hotelId/' + memoryHotelId + '/sprache/' + sprache;
    var rootUrl = '/admin/hotels/';

    panel.setTitle('Hotel ID: ' + memoryHotelId);

     panel.load({
        url: templateUrl,
        text: 'lade Ansicht',
        callback: function(){

            if(sprache == 'de'){
                var titel = 'deutsche Beschreibung, Hotel ID: ' + memoryHotelId;
                panel.setTitle('deutsche Beschreibung, Hotel ID: ' + memoryHotelId);
            }
            else{
                var titel = 'englische Beschreibung, Hotel ID: ' + memoryHotelId;
                panel.setTitle('englische Beschreibung, Hotel ID: ' + memoryHotelId);
            }

            var fenster = new Ext.Window({
                width: 750,
                shadow: false,
                buttonAlign: 'right',
                title: titel,
                id: 'editorFenster',
                draggable: true,
                x: 20,
                y: 20,
                resizable: false,
                modal: true,
                border: false,
                closable: showCloseButton
            });

            var stupidcat = Ext.select('.stupidcat');

            var formFenster = new Ext.form.FormPanel({
                labelWidth: 200,
                id: 'formFenster',
                method: 'post',
                fileUpload: true,
                border: false,
                autoHeight: true,
                frame: true
            });

            var formField;

            formField = {
                xtype: 'textfield',
                value: memoryHotelId,
                id: 'hotelId',
                frame: true,
                border: false,
                width: 50,
                readOnly: true,
                cls: 'readonly',
                hideLabel: true
            };

            formFenster.add(formField);

            formField = {
                xtype: 'textfield',
                value: sprache,
                id: 'sprache',
                hidden: true
            };

            formFenster.add(formField);


            stupidcat.each(function(){
                var value = arguments[0].dom.innerHTML;
                var editor = arguments[0].dom.attributes.getNamedItem('editor').value;
                var label = arguments[0].dom.attributes.getNamedItem('label').value;
                var id = arguments[0].id;

                switch(editor){
                    case "textfield":
                        formField = {
                            fieldLabel: label,
                            xtype: 'textfield',
                            value: value,
                            id: id,
                            frame: true,
                            border: false,
                            width: 300,
                            helpText: 'Bitte Slogan eintragen, Feld ist optional'
                        };
                    break;
                    case "htmleditor":
                        formField = {
                            fieldLabel: label,
                            xtype: 'htmleditor',
                            value: value,
                            id: id,
                            width: 700,
                            boxMinWidth: 350,
                            frame: true,
                            border: false,
                            autoWidth: true,
                            allowBlank: false,
                            resizable: false,
                            helpText: 'Bitte Text editieren'
                        };
                    break;
                    case "upload":
                        formField = {
                            fieldLabel: label,
                            xtype: 'textfield',
                            inputType: 'file',
                            value: value,
                            id: id,
                            frame: true,
                            border: false,
                            autoWidth: true,
                            helpText: 'Bitte Bild auswählen, Breite 150px'
                        };
                    break;
                }

                formFenster.add(formField);
            });

            var buttonEintragen = {
                xtype: 'button',
                text: 'eintragen',
                icon: '/buttons/vor.png',
                cls: 'x-btn-text-icon',
                iconAlign: 'right',
                handler: function(){

                    Ext.getCmp('formFenster').getForm().submit({
                        url: '/admin/hotels/setvalue/',
                        params: {
                            hotelId: memoryHotelId,
                            sprache: sprache
                        },
                        success: function(){

                            panel.load({
                                url: templateUrl
                            });

                            fenster.close();
                            if(sprache == 'de')
                                fillTemplate('en');
                        },
                        failure: function(){
                            showMsgBox('Werte wurde nicht geändert');
                        }
                    });

                }
            };

            var buttonVerantwortlicher = {
                 xtype: 'button',
                text: 'Verantwortlicher',
                icon: '/buttons/zurueck.png',
                cls: 'x-btn-text-icon',
                iconAlign: 'left',
                handler: function(){
                    fenster.close();
                    fillPersonaldaten();
                }
            }

            if(sprache == 'de')
                fenster.addButton(buttonVerantwortlicher);

            var cleanButton = {
                xtype: 'button',
                text: 'Sonderzeichen bearbeiten',
                handler: function(){
                    var txt = Ext.getCmp('txt');
                    var text = txt.getValue();
                    text = text.replace(/<(.*?)>/g,'');
                    text = removeMsWordChars(text);
                    txt.setValue(text);
                }
            };

            var deutscheTextButton = {
                xtype: 'button',
                text: 'deutsche Beschreibung',
                icon: '/buttons/zurueck.png',
                cls: 'x-btn-text-icon',
                iconAlign: 'left',
                handler: function(){
                    fenster.close();
                    fillTemplate('de');
                }
            }

            fenster.add(formFenster);

            if(sprache == 'en')
                fenster.addButton(deutscheTextButton);

            fenster.addButton(cleanButton);
            fenster.addButton(buttonEintragen);
            fenster.doLayout();
            fenster.show();

            formFenster.load({
                url: '/admin/hotels/getvalue/',
                params: {
                    sprache: sprache,
                    hotelId: memoryHotelId
                }
            });

        }

     });

}