/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 01.08.12
 * Time: 14:02
 * Formular zur Statusänderung der Programmbuchung
 */

var AdminProgrammbuchungenIndexStatusaenderung = function(){
    // beginn private

    var formular = null;
    var fenster = null;

    // ende private
    // beginn public
    return {
        fenster: new Ext.Window({
            autoHeight: true,
            shadow: false,
            autoHeight: true,
            border: false,
            modal: true,
            x: 20,
            y: 20,
            title: 'Statusänderung'
        }),

        formular: new Ext.form.FormPanel({
            autoHeight: true,
            width: 300,
            padding: 10,
            items: [{
                    xtype: 'hidden',
                    name: 'id'
                },{
                    xtype: 'displayfield',
                    name: 'grid_buchungsnummer',
                    fieldLabel: 'Buchungsnummer'
                },{
                    xtype: 'displayfield',
                    name: 'grid_datum',
                    fieldLabel: 'Buchungsdatum'
                },{
                    xtype: 'displayfield',
                    name: 'grid_name',
                    fieldLabel: 'Name'
                },{
                    xtype: 'displayfield',
                    name: 'grid_vorname',
                    fieldLabel: 'Vorname'
                },{
                    xtype: 'displayfield',
                    name: 'grid_programmname',
                    fieldLabel: 'Programmname'
                },{
                    xtype: 'combo',
                    name: 'grid_status',
                    hiddenName: 'grid_status',
                    triggerAction: 'all',
                    selectOnFocus: true,
                    typeAhead: true,
                    mode: 'local',
                    store: [[2,'Kundenwunsch'],[3, 'Kunde hat Angebot'],[4,'Kunde hat gebucht'],[5,'gebucht beim Anbieter'],[6,'Anbieter hat bestätigt'],[7,'Bestätigung an Kunden'],[8,'Kunde hat Unterlagen']]
            }],
            bbar: {
                xtype: 'toolbar',
                items: [{
                    xtype: 'tbspacer',
                    width: 20
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    text: 'speichern',
                    handler: function(){
                        formular.getForm().submit({
                            url: '/admin/programmbuchungen/setstatus/',
                            method: 'post',
                            success: function(){
                                reloadTabelleProgrammbuchungen();
                                fenster.close();
                            }
                        });
                    }
                },{
                    xtype: 'tbseparator'
                }]
            }
        }),

        ladeProgrammdaten : function(programmBuchungId){
            this.formular.load({
                url: '/admin/programmbuchungen/programmgrunddaten/',
                method: 'post',
                params: {
                    programmBuchungId:  programmBuchungId
                }
            });
        },

        start: function(programmbuchungId){
            formular = this.formular;
            this.fenster.add(this.formular);
            this.fenster.show();
            fenster = this.fenster;
            this.ladeProgrammdaten(programmbuchungId);
        }

    }
    // ende public
}
