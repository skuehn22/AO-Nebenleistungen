/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 04.06.12
 * Time: 11:14
 *
 * Erstellt ein Fenster zur Eingabe einer Zusatzinformation.
 * Zusatzinformation stammt aus einer Worddatei
 *
 */

var fillZusatzinformationWindow = function(){
    // Beginn private

    var zusatzinformationWindow = new Ext.Window({
        autoWidth: true,
        autoHeight: true,
        modal: true,
        shadow: false,
        border: false,
        resizable: false,
        padding: 10,
        x: 20,
        y: 20,
        closable: showCloseButton
    });

    function speichernZusatzinformation(){
        var form = Ext.getCmp('formZusatzinformation').getForm().submit({
            url: '/admin/datensatz/setzusatzinformation/',
            method: 'post',
            params: {
                programId: programmId
            },
            success: function(){
                zusatzinformationWindow.close();
                fillDiverses();
            }
        });
    }

    function clearZusatzinformation(){
        var form = Ext.getCmp('formZusatzinformation').getForm().submit({
            url: '/admin/datensatz/clearzusatzinformation/',
            method: 'post',
            params: {
                programId: programmId
            },
            success: function(){
                ladeFormular();
            }
        });
    }

    function ladeFormular(){

         Ext.getCmp('formZusatzinformation').getForm().load({
            url: '/admin/datensatz/getzusatzinformation/',
            method: 'post',
            params: {
                programmId: programmId
            }
        });

        return;
    }

    var zusatzinformationForm = new Ext.form.FormPanel({
        id: 'formZusatzinformation',
        title: 'Zusatzinformationen, ID: ' + programmId + ', Programmname: ' + programmName,
        padding: 0,
        items: [{
            xtype: 'htmleditor',
            height: 600,
            width: 890,
            id: 'fieldZusatzinformation',
            name: 'fieldZusatzinformation',
            fieldLabel: 'Label',
            border: false,
            hideLabel: true
        }],
        tbar: [{
            xtype: 'tbspacer',
            width: 20
        },{
            xtype: 'tbseparator'
        },{
            text: 'Word Formatierung löschen',
            tooltip: 'Word Formatierung löschen',
            handler: function(){
                clearZusatzinformation();
            }
        },{
            xtype: 'tbseparator'
        }],
        buttons:[{
            xtype: 'button',
            text: 'eintragen',
            handler: function(){
                speichernZusatzinformation();
            }
        }]
    });

    // Ende private

    // Beginn public
    return{
        checkprogrammId: function(){
             if(!programmId){
                showMsgBox('Bitte Programm auswählen');
                return false;
             }

            return true;
        },
        addForm: function(){
            zusatzinformationWindow.add(zusatzinformationForm);
        },
        showWindow: function(){
            zusatzinformationWindow.show();
        },
        loadForm: function(){
            ladeFormular();
        }
    }
    // Ende public
}


function fillZusatzinformation(){

    var myWindow = new fillZusatzinformationWindow();
    var check = myWindow.checkprogrammId();
    if(check){
        myWindow.addForm();
        myWindow.showWindow();
        myWindow.loadForm();
    }
}

