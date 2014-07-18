/**
 * Created with JetBrains PhpStorm.
 * User: PC Nutzer
 * Date: 18.07.12
 * Time: 14:01
 * To change this template use File | Settings | File Templates.
 */

var Admin_Programmbuchungen_IndexButton = function(){
    // begin private

    var buttonProgrammTabelle = null;

    function buttonsProgrammBuchung(){

        if(buttonProgrammTabelle != null)
            return;

        buttonProgrammTabelle = new Ext.Panel({
            layout: 'table',
            defaultType: 'button',
            baseCls: 'x-plain',
            cls: 'btn-panel',
            renderTo: 'buttonProgrammbuchung',
            menu: undefined,
            split: false,
            defaults: {
                style: 'margin: 3px;',
                width: 190
            },
            layoutConfig: {
                columns: 3
            },
            items:[{
                    text: 'Statusänderung',
                    tooltip: 'verändert den Status der Programmbuchung',
                    handler: function(){
                        var tabelle = Ext.getCmp('adminProgrammbuchungenIndexProgrammtabelle');
                        if(!tabelle.getSelectionModel().hasSelection()){
                            showMsgBox('Bitte Programmbuchung auswählen');

                            return;
                        }
                        var row = tabelle.getSelectionModel().getSelected();
                        var programmbuchungId = row.data.id;

                        var statusaenderung = new AdminProgrammbuchungenIndexStatusaenderung();
                        statusaenderung.start(programmbuchungId);
                    }
                },{
                    text: 'Programmdaten',
                    tooltip: 'grudsätzliche Informationen zum Programm'
                },{
                    text: 'Information an den Kunden',
                    tooltip: 'Mail an den Kunden'
                },{
                    text: 'Information an Programmanbieter',
                    tooltip: 'Mail an den Programmanbieter'
                },{
                    text: 'Kundendaten',
                    tooltip: 'Übersicht über die Kundendaten'
                },{
                    text: 'Anbieterdaten',
                    tooltip: 'Übersicht über die Programmanbieterdaten'
                },{
                    text: 'Buchungshistorie',
                    tooltip: 'Übersicht der Buchungshistorie'
                }]
        });

        return;
    }

    // end private
    // begin public
    return {
        buildButton: function(){
            buttonsProgrammBuchung();
            buttonProgrammTabelle.show();
        }
    }
    // end public
};
