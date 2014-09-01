/**
 * Bestätigungstexte einer Firma
 *
 * Created with JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 08.10.12
 * Time: 15:10
 * To change this template use File | Settings | File Templates.
 */

var adminCompanyIndexBestaetigungstexte = function(){
    // begin private

    var formZusatztexte = null;
    var fensterZusatztexte = null;

    // end private
    // begin public
    return{
        start: function(){
            this.fenster.add(this.form);
            this.fenster.show();
            this.form.load({
                url: '/admin/zusatztexte-Firma/view/',
                params: {
                    id: memoryCompanyId
                }
            });

            formZusatztexte = this.form;
            fensterZusatztexte = this.fenster;
        },
        fenster: new Ext.Window({
            title: 'Bestätigungstexte, ID: '+ memoryCompanyId + ", Firma: " + memoryCompanyName,
            width: 700,
            autoHeight: true,
            padding: 10,
            border: true,
            shadow: false,
            x: 20,
            y: 20
        }),
        form: new Ext.form.FormPanel({
            url: '/admin/zusatztexte-Firma/edit/',
            method: 'post',
            frame: true,
            padding: 10,
            items: [{
                xtype: 'textfield',
                fieldLabel: 'ID',
                name: 'companyId',
                value: memoryCompanyId,
                fieldLabel: 'ID der Firma',
                readOnly: true
            },{
                xtype: 'textarea',
                width: 400,
                height: 100,
                fieldLabel: 'Bestätigungstext deutsch',
                name: 'confirm_1_dt'
            },{
                xtype: 'textarea',
                width: 400,
                height: 100,
                fieldLabel: 'Bestätigungstext englisch',
                name: 'confirm_1_en'
            }],
            buttons: [{
                text: 'speichern',
                handler: function(){
                    formZusatztexte.getForm().submit({
                        success: function(){
                            fensterZusatztexte.close();
                            showMsgBox('Bestätigungstexte überarbeitet!');
                        }
                    });
                }
            }]
        })
    }
    // end public
}
