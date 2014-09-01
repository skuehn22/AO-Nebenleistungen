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
			xtype: 'tbsplit',
			text: 'allgemeine Informationen',
			menu: normal
		},{
			xtype: 'tbseparator'
		},{
			xtype: 'tbsplit',
			text: 'Anbieter Einstellungen',
			menu: provider
		},{
			xtype: 'tbseparator'
		},{
			xtype: 'tbsplit',
			text: 'Administrator Einstellungen',
			menu: admin
		},{
			xtype: 'tbseparator'
		}]
	});
	
});