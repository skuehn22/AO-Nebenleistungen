/**
 * Buttonleise Baustein: 'company'
 *
 * Created with JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 08.10.12
 * Time: 14:48
 * To change this template use File | Settings | File Templates.
 */

var adminCompanyIndexButtons = function(){

    // begin private

    // end private
    // begin public
    return {
        buttonPanel: new Ext.Panel({
            renderTo: 'buttons',
            layout: 'table',
            defaultType: 'button',
            baseCls: 'x-plain',
            cls: 'btn-panel',
            menu: 'undefined',
            split: false,
            layoutConfig: {
                columns: 2
            },
            defaults: {
               style: 'margin: 3px;',
               width: 150
            },
            items:[{
                text: 'aktiv / passiv / zum löschen vormerken',
                id: 'formButtonAktiv',
                handler: firmaAktivPassiv
            },{
                text: 'neuer Programmanbieter',
                handler: newProgramProvider
            },{
                text: 'Programmanbieter bearbeiten',
                handler: getVorhandenerProgrammanbieter
            },{
                text: 'Bestätigungstexte',
                handler: function(){
                    fillBestaetigungstexte();
                }
            }]
        })
    }
    // end public

}

Ext.onReady(function(){
    var gridButtons = new adminCompanyIndexButtons();
});