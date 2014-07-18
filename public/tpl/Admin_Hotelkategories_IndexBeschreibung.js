Ext.namespace('hotelkategoriesBeschreibung');

hotelkategoriesBeschreibung = function(){
    // Beginn private
    var self;

    function ladenDesKategoriebildes(){
        Ext.getDom('kategoriePic').src = '/images/kategorieImages/midi/' + memoryCategoryId + '.jpg';
    }

    function saveDescriptionCategory(){
        self.formBeschreibung.getForm().submit({
            params: {
                language: memorySprache,
                hotelId: memoryHotelId,
                kategorieId: memoryCategoryId
            },
            success: function(){
                var now = new Date();
                Ext.getDom('kategoriePic').src = '/images/kategorieImages/midi/' + memoryCategoryId + '.jpg?' + now.getTime();

                self.fenster.close();
            }
        });
    }

    // Beginn public
    return{

        sprache: null,

        setSprache: function(sprache){
            this.sprache = sprache;
        },

        formBeschreibung: new Ext.form.FormPanel({
            width: 900,
            autoHeight: true,
            url: '/admin/hotelkategories/setdescription/',
            fileUpload: true,
            frame: true,
            layout: 'column',
            labelWidth: 130,
            items: [{
                columnWidth: 0.7,
                layout: 'form',
                bodyStyle: 'padding: 10px;',
                    items:[{
                            xtype: 'textfield',
                            id: 'headline',
                            width: 400,
                            name: 'headline',
                            fieldLabel: 'Überschrift',
                            helpText: 'Überschrift der Kategoriebeschreibung',
                            allowBlank: false
                        },{
                            xtype: 'textarea',
                            id: 'description_short',
                            height: 75,
                            width: 400,
                            name: 'description_short',
                            fieldLabel: 'Kurzbeschreibung',
                            helpText: 'Kurzbeschreibung der Kategorie',
                            allowBlank: false
                        },{
                            xtype: 'htmleditor',
                            id: 'description_long',
                            height: 200,
                            width: 400,
                            fieldLabel: 'Beschreibung der Kategorie',
                            helpText: 'Beschreibung der Kategorie'
                        },{
                            xtype: 'label',
                            text: 'nur Bilder im *.jpg Format'
                        },{
                            xtype: 'textfield',
                            id: 'kategorieImage',
                            width: 400,
                            inputType: 'file',
                            fieldLabel: 'Bild',
                            helpText: 'Bild, Breite 150px'
                }]
            },{
                columnWidth: 0.3,
                bodyStyle: 'padding: 10px;',
                items: [{
                    xtype: 'box',
                    autoEl: {
                        tag: 'div',
                        html: '<img id="kategoriePic" src="/images/kategorieImages/midi/' + memoryCategoryId + '.jpg">'
                    }
                }]
            }],
            buttons: [{
                text: 'speichern der Kategoriebeschreibung',
                tooltip: 'speichern der Kategoriebeschreibung',
                handler: function(){
                    saveDescriptionCategory();
                }
            }]
        }),

        loadForm: function(){
            // leeren Formular
            this.formBeschreibung.getForm().reset();
            this.fenster.setTitle("Beschreibung der Kategorie in der Sprache '" + this.sprache + "'");
            memorySprache = this.sprache;

            // Bild
            var now = new Date();
            Ext.getDom('kategoriePic').src = '/images/kategorieImages/midi/' + memoryCategoryId + '.jpg?' + now.getTime();

            // holen der Daten
            this.formBeschreibung.getForm().load({
                url: '/admin/hotelkategories/getdescription/',
                params: {
                    sprache: memorySprache,
                    kategorieId: memoryCategoryId
                }
            });
        },

         fenster: new Ext.Window({
            title: 'Beschreibung der Kategorie, Kategorie ID: ' + memoryCategoryId,
            closable: true,
            autoHeight: true,
            resizable: false,
            width: 950,
            autoHeight: true,
            padding: 10,
            buttonAlign: 'right',
            shadow: false,
            modal: true,
            layout: 'hbox',
            x: 20,
            y: 20
        }),

        workAdmin: function(){

            self = this;
        }
    }
}

function hotelkategoriesBeschreibungAdmin(sprache){
    if(!memoryCategoryId){
        showMsgBox('Bitte Kategorie auswählen');

        return;
    }

    var view = new hotelkategoriesBeschreibung();

    view.setSprache(sprache);
    view.fenster.add(view.formBeschreibung);
    view.workAdmin();
    view.fenster.show();
    view.loadForm();
}
