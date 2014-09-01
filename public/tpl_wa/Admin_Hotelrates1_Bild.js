function fillBildAendern(){

    if(!memoryRatenId){
       showMsgBox('Bitte Rate auswählen');

       return;
    }

    fenster = new Ext.Window({
        title: 'Zuordnung Bild zu einer Rate, Rate:' + "'" + memoryRatenId + "'",
        width: 400,
        height: 400,
        modal: true,
        shadow: false,
        border: false,
        x: 20,
        y: 20
    });

    var template = new Ext.XTemplate("<h3>Bildzuordnung</h3>");

    fenster.add(template);

    fenster.show();


}



