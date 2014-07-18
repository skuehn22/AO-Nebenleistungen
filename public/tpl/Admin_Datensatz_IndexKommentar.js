/**
 * Child View zur Bearbeitung
 * der Zusatzinformation zu einem Programm.
 * View wird von der Rolle 'externer Redakteur' verwendet.
 *
 * User: Stephan.Krauss
 * Date: 12.11.12
 * Time: 12:09
 */

var AdminDatensatzIndexKommentar = function(){
    // begin private
    var fenster = null;
    var formular = null;


    // end private
    // begin public
    return {
        start: function(){
            fenster = this.fenster;

            formular = this.formular;
            fenster.add(formular);

            fenster.addButton(this.button);
            fenster.show();

            this.loadForm();

        },
        fenster: new Ext.Window({
            title: 'Kommentar externer Redakteur, ID: ' +  programmId + ', Programmname: ' + programmName,
            autoWidth: true,
            autoHeight: true,
            x: 20,
            y: 20,
            modal: true,
            shadow: false,
            border: true,
            padding: 10,
            draggable: true,
            resizable: false,
            buttonAlign: 'right',
            closable: true,
            resizable: false
        }),
        formular: new Ext.form.FormPanel({
            method: 'post',
            url: '/admin/datensatz-externer-redakteur/edit/',
            autoWidth: true,
            frame: true,
            autoHeight: true,
            border: false,
            padding: 10,
            labelWidth: 200,
            items: [{
                xtype: 'displayfield',
                name: 'datum',
                fieldLabel: 'Datum'
            },{
                xtype: 'displayfield',
                name: 'nameRedakteur',
                fieldLabel: 'Name Redakteur'
            },{
                xtype: 'combo',
                fieldLabel: 'Bearbeitungsstand',
                allowBlank: false,
                forceSelection: true,
                triggerAction: 'all',
                name: 'status',
                hiddenName: 'status',
                helpText: 'Bearbeitungsstand des Kommentares',
                store: [[1,'offen'],[2,'erledigt']],
                mode: 'local',
                lazyRender:true
            },{
                xtype: 'textarea',
                name: 'kommentar',
                fieldLabel: 'Kommentar',
                width: 400,
                height: 200
            }]
        }),
        button: {
            text: 'eintragen',
            handler: function(){
                formular.getForm().submit({
                    params: {
                        programmdetails_id: programmId
                    },
                    success: function(){
                        fenster.close();
                    }
                });
            }
        },
        loadForm: function(){
            formular.getForm().load({
                url: '/admin/datensatz-externer-redakteur/view/',
                params: {
                    programmdetails_id: programmId
                }
            });
        }
    }


    // end public
}

function fillKommentar(){
    if(!programmId){
        showMsgBox('Bitte Programm auswählen !');

        return;
    }

    var klasse = new AdminDatensatzIndexKommentar();
    klasse.start();
}
