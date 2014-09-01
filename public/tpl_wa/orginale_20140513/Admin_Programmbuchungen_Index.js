/**
 * Allgemeine Steuerung des Baustein 'Programmbuchungen'
 *
 *
 *
 */

var tabelle = null;

Ext.onReady(function(){
    // Darstellung Tabelle gebuchte Programme
    tabelle = new Admin_Programmbuchungen_IndexProgrammtabelle();
    tabelle.storeLoad();
    tabelle.tableShow();

    // Button
    var button = new Admin_Programmbuchungen_IndexButton();
    button.buildButton();
});

/**
 * Lädt den Store der Programmbuchungstabelle
 */
function reloadTabelleProgrammbuchungen(){
    var store = tabelle.getStore();
    store.load();
}
