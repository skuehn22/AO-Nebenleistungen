Ext.onReady(function(){
	var adminNavigation = new Ext.Toolbar({
		renderTo: 'navigation',
		items: [{
			xtype: 'tbseparator'
		},{
			xtype: 'button',
			text: 'Home',
			handler: function(){
				window.open('/front/login/index/','_self');
			}
		},{
            xtype: 'tbseparator'
        },{
            xtype: 'button',
            text: 'Whiteboard',
            handler: function(){
                window.open('/admin/whiteboard/index/','_self');
            }
        },{
			xtype: 'tbseparator'
		},{
			xtype: 'tbsplit',
			text: 'Bereich Programme',
			menu: programme
		},{
            xtype: 'tbseparator'
        },{
            xtype: 'tbsplit',
            text: 'Bereich Übernachtungen',
            menu: uebernachtung
        },{
			xtype: 'tbseparator'
		},{
			xtype: 'tbsplit',
			text: 'Administration',
			menu: admin
		},{
			xtype: 'tbseparator'
		}]
	});
	
});