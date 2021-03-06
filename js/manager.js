/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
        {name: '{C#MODNAME}', files: ['sman.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang,
		R = NS.roles,
		buildTemplate = this.buildTemplate;
	
	var ManagerWidget = function(container){
		ManagerWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget' 
		});
	};
	YAHOO.extend(ManagerWidget, Brick.mod.widget.Widget, {
		init: function(){
			this.wsMenuItem = 'manager'; // использует wspace.js
		},
		onLoad: function(seventid){
			var __self = this;
			NS.initManager(function(){
				__self._onLoadManager();
			});
		},
		_onLoadManager: function(){
			this.elHide('loading');
			this.elShow('view');
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bbuild']: this.build(); return true;
			}
		},
		build: function(){
			this.elShow('bloading');
			this.elHide('btns');
			
			var __self = this;
			NS.manager.build(function(){
				__self.elShow('btns');
				__self.elHide('bloading');
			});
		}
	});
	NS.ManagerWidget = ManagerWidget;
};