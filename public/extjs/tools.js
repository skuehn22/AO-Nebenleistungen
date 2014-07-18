// Fehler / Anzeigebox
function showMsgBox(information){

    Ext.MessageBox.show({
        msg: information,
        width:300,
        x: 270,
        y: 160
    });

    setTimeout(function(){
        Ext.MessageBox.hide();
    }, 2000);
    
    return; 
}

function changeheightMainDiv(){

    var height = Ext.get('content').getHeight();
    height += 20;

    Ext.get('main').setHeight(height);

    return;
}

// Hilfetext für Formulare
Ext.onReady(function(){
    Ext.intercept(Ext.form.Field.prototype, 'initComponent', function(){
        var fl = this.fieldLabel, h = this.helpText;

        if(h && h !== '' && fl){
            this.fieldLabel = fl + '<span style="color: green;" ext:qtip="' + h + '"> <img src="/buttons/information.png"> </span>';
        }
    });

    Ext.util.Observable.observeClass(Ext.data.Connection);

    Ext.data.Connection.on('requestexception',function(conn, response, options){
//        var status = response.status;
//        Ext.Msg.alert('Serverfehler','Entschuldigung ! Ein Serverfehler ist aufgetreten.<br> Die Verarbeitung wurde abgebrochen.<br> Sie müssen sich neu anmelden.', function(){
//            window.location.href = '/front/login/';
//        });

        showMsgBox('Exception');
    });

    Ext.data.Connection.on('requestcomplete', function(conn, response, options){
        if(response.responseText.indexOf('message:') > 0){
            var responseInformation = Array();
            responseInformation =  Ext.util.JSON.decode(response.responseText);

            if(responseInformation.message){
                showMsgBox(responseInformation.message);
            }
        }

        return;
    });

});


var childWindow = new Ext.Window({
    x: 270,
    y: 160,
    border: false,
    modal: true,
    id: 'kindFenster',
    autoWidth: true,
    autoHeight: true,
    shadow: false,
    closeAction: 'hide',
    resizable: false,
    draggable: false
});

function removeMsWordChars(str){
    var myReplacements = new Array();
    var myCode, intReplacement;

    myReplacements[8216] = 39;
    myReplacements[8217] = 39;
    myReplacements[8220] = 39;
    myReplacements[8221] = 39;
    myReplacements[8222] = 39;
    myReplacements[180] = 39;
    myReplacements[184] = 44;
    myReplacements[8242] = 39;

    myReplacements[8212] = 45;

    
    for(c=0; c < str.length; c++) {
        var myCode = str.charCodeAt(c);
        if(myReplacements[myCode] != undefined) {
            intReplacement = myReplacements[myCode];
            str = str.substr(0,c) + String.fromCharCode(intReplacement) + str.substr(c+1);
        }
    }
  
    return str;
}
