function checkImage(){
    Ext.Ajax.request({
        url: '/admin/datensatz/kontrolle-bild-vorhanden/',
        method: 'post',
        params: {
            programId: programmId,
            bildTyp: 'midi'
        },
        success: function(response){
            // Milisekunden wegen Browsercache
            var zeit = new Date();
            var ms = zeit.getMilliseconds();

            var antwort = Ext.util.JSON.decode(response.responseText);
            if(antwort.bildId == '0')
                var src = "/img/leer.gif";
            else
                var src = '/images/program/midi/' + antwort.bildId + ".jpg?" + ms;

            Ext.get("adminDatensatzIndexFormEditorImage").dom.src = src;
        }
    });
}


function fillTemplate(sprache){
    if(!programmId){
        showMsgBox('Bitte Programm auswählen');

        return;
    }

    var templateUrl = '/admin/datensatz/gettemplate/programId/' + programmId + '/city/' + cityId + '/sprache/' + sprache;
    var programmNummer = programmId;

    if(sprache == '1')
        var titel = 'deutsche Beschreibung, ID: ' + programmNummer + ', Programmname: ' + programmName;
    else
        var titel = 'englische Beschreibung, ID: ' + programmNummer + ', Programmname: ' + programmName;

    var fenster = new Ext.Window({
        width: 750,
        shadow: false,
        buttonAlign: 'right',
        title: titel,
        id: 'editorFenster',
        draggable: true,
        x: 20,
        y: 20,
        modal: true,
        border: false,
        closable: true,
        resizable: false,
        padding: 10
    });

    // Fenster leeren
    fenster.removeAll();

    // Bildanzeige
    var midiBild = {
        xtype: 'box',
        id: 'adminDatensatzIndexFormEditorBox',
        autoEl: {
            tag: 'img',
            id: 'adminDatensatzIndexFormEditorImage',
            src: '/img/leer.gif'
        }
    };

    // eintragen Bildanzeige
    fenster.add(midiBild);

    // baut ein Formular
    var formFenster = new Ext.form.FormPanel({
        labelWidth: 200,
        id: 'formFenster',
        method: 'post',
        fileUpload: true,
        border: false,
        autoHeight: true,
        frame: true,
        url: templateUrl,
        style: {
            marginTop: '10px'
        },
        items: [{
                fieldLabel: 'Programmname',
                xtype: 'textfield',
                frame: true,
                border: false,
                width: 300,
                allowBlank: false,
                name: 'progname',
                helpText: 'Bitte Text eintragen'
            },{
                fieldLabel: 'Programmbeschreibung',
                xtype: 'htmleditor',
                width: 700,
                frame: true,
                border: false,
                autoWidth: true,
                name: 'txt',
                id: 'txt',
                allowBlank: false,
                helpText: 'Bitte Text editieren'
            },{
                fieldLabel: 'Bild',
                xtype: 'textfield',
                inputType: 'file',
                frame: true,
                border: false,
                autoWidth: true,
                name: 'miniBild',
                helpText: 'Bitte Bild 150px * 100px auswählen'
            },{
                fieldLabel: 'Adresse/Treff',
                xtype: 'htmleditor',
                width: 700,
                frame: true,
                border: false,
                autoWidth: true,
                name: 'treffpunkt_de',
                id: 'treffpunkt_de',
                allowBlank: false,
                helpText: 'Treffpunkt der Veranstaltung'
            },{
                fieldLabel: 'Anreise ÖPNV',
                xtype: 'htmleditor',
                width: 700,
                frame: true,
                border: false,
                autoWidth: true,
                name: 'opnv_de',
                id: 'opnv_de',
                allowBlank: false
            }
        ]
    });

    fenster.add(formFenster);



    /*** Bereich der Button ***/

    var buttonEintragen = {
        xtype: 'button',
        text: 'eintragen',
        icon: '/buttons/vor.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'right',
        handler: function(){

            Ext.getCmp('formFenster').getForm().submit({
                url: '/admin/datensatz/setvalue/',
                params: {
                    programmId: programmNummer,
                    sprache: sprache
                },
                success: function(){
                    // gridStore.reload();

                    if(sprache == 1){
                        fenster.close();
                        fillTemplate(2);
                    }
                    else{
                        fenster.close();
                        fillSprache();
                    }
                },
                failure: function(){
                    showMsgBox('Werte wurde nicht geändert');
                }
            });

        }
    };

    var cleanButton = {
        xtype: 'button',
        text: 'Wordformatierung entfernen',
        handler: function(){

            var clean = new cleanHtmlEditor();

            if(clean.start('txt'))
               showMsgBox('Wordformatierung entfernt');

            return;
        }
    };

    // Button für zurück - Operation

    var deutscheTextButton = {
        xtype: 'button',
        text: 'deutsche Beschreibung',
        icon: '/buttons/zurueck.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'left',
        handler: function(){
            fenster.close();
            fillTemplate(1);
        }
    }

    var diversesButton = {
        xtype: 'button',
        text: 'Diverses',
        icon: '/buttons/zurueck.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'left',
        handler: function(){
            fillDiverses();
            fenster.close();
        }
    }

    var bildLoeschenButton = {
        xtype: 'button',
        text: 'Bild löschen',
        handler: function(){
            Ext.Ajax.request({
                url: '/admin/datensatz/loeschen-programm-bild',
                method: 'post',
                params: {
                    programmId: programmNummer
                },
                success: function(){
                    Ext.get("adminDatensatzIndexFormEditorImage").dom.src = '/img/leer.gif';
                }
            });
        }
    }

    // Button zurück
    if(sprache == 1)
        fenster.addButton(diversesButton);
    else
        fenster.addButton(deutscheTextButton);

    // Button - clean Worddokument
    fenster.addButton(cleanButton);

    // Button Bild löschen
    fenster.addButton(bildLoeschenButton);

    // Button - nächster Baustein
    fenster.addButton(buttonEintragen);

    fenster.doLayout();

    // anzeigen Fenster
    fenster.show();

    formFenster.load();

    // Kontrolle ob Bild vorhanden ist
    checkImage();
}
