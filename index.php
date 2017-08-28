<!DOCTYPE HTML>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Input Deck Editor</title>
		<link rel="stylesheet" href="style.css" media="screen">
		<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/dojo/1.7.1/dijit/themes/claro/claro.css" media="screen">
		<link rel="stylesheet" href="css/style.css" media="screen">
        <link href="visual/static/css/model.css" rel="stylesheet">
		<script src="//ajax.googleapis.com/ajax/libs/dojo/1.7.1/dojo/dojo.js"
				data-dojo-config="async: true, parseOnLoad: true">
		</script>
      
        <script>
			var timeout = undefined;
			function writeMessage( text ){				
				if (text == undefined)
					text = "";
				if (timeout != undefined)
					clearTimeout(timeout);					
				var a = document.getElementById("messageNav");
				a.style.color = "#000";
				if (a)
					a.innerHTML = text;
				if (text != "")
				    timeout = setTimeout('writeMessage()', 5000);
			}

			function writeError( text ){
				if (text == undefined)
					text = "";
				if (timeout != undefined)
					clearTimeout(timeout);					
				var a = document.getElementById("messageNav");
				a.style.color = "#A10";
				if (a)
					a.innerHTML = text;
			}
			
			function sendForm( form ){
				writeMessage("submiting changes ... ");
				var deferred = dojo.xhrPost({
					form: dojo.byId( form ),
					load: function(data){
						writeMessage(data);
					},
					error: function(error){
						writeError("Error");
					}
				});
				return false;
			}		
			
			function addInput( table ){
				var t = document.getElementById(table);
				if (t){
					var text = prompt("Enter field name", "field name");
					if (text && text != ""){
						var td1 = document.createElement("td");
						td1.appendChild(document.createTextNode(text));
						var td2 = document.createElement("td");
						var div = document.createElement("div");
						div.className='dijitReset dijitInputField dijitInputContainer';
						div.style.border="1px solid #CCC";
						var input = document.createElement("input");
						input.type = 'text' 
						input.name = text ;
						input.id= text 
						input.className='dijitReset dijitInputInner';
						div.appendChild(input);
						td2.appendChild(div);
						var tr = document.createElement("tr");
						tr.appendChild(td1);
						tr.appendChild(td2);
						var a = t.firstChild.lastChild;
						t.firstChild.insertBefore(tr, a);
					}
				}				
			}
        </script>        
		<script type="text/javascript">
			var tree;
			var activenode;
			var iconClass = [];
			iconClass["solver"] = "dijitNemoSolver";
			iconClass["Geometry"] = "dijitNemoGeometry";
			iconClass["Structure"] = "dijitNemoStructure"; 
			iconClass["Material"] = "dijitNemoMaterial"; 
			iconClass["Domain"] = "dijitNemoDomain"; 
			iconClass["Region"] = "dijitNemoRegion"; 
			iconClass["Boundary_region"] = "dijitNemoBoundaryRegion";
			iconClass["Solvers"] = "dijitNemoBoundarySolvers";
			iconClass["boundary_condition"] = "dijitNemoBoundaryCondition";
			iconClass["Global"] = "dijitNemoGlobal";
			iconClass["set"] = "dijitNemoSet";
			iconClass["loop"] = "dijitNemoLoop";
			iconClass["root"] = "dijitNemoRoot";
			iconClass["iterator"] = "dijitNemoIterator";
		
			function duplicateNode( ){
				if (activenode){
					var text = prompt("Duplicate [" + activenode.type + "]" , activenode.name + "_copy");
					if (text && text != ""){
						writeMessage("Duplicating nodes ...");
						var xhr = dojo.xhrPost({
							content: {
							  id: activenode.id,
							  name: text
							},
							url: "services/duplicateNode.php",
							load: function(response) {
								if (response.toLowerCase().indexOf("error") == -1){
									writeMessage(response);
									createTree();
								} else {
									writeError(response);								
								}
							},
							error: function() {
								writeError("Error");
							},
						});						
					}
				}
			}
			function addNode( ){
				if (activenode){
					var text = prompt("write node type for child of " + activenode.name + "  [" + activenode.type + "]" ,  "");
					if (text && text != ""){
						writeMessage("Adding node ...");
						var xhr = dojo.xhrPost({
							content: {
							  id: activenode.id,
							  name: text
							},
							url: "services/addNode.php",
							load: function(response) {
								if (response.toLowerCase().indexOf("error") == -1){
									writeMessage(response);
									createTree();
								} else {
									writeError(response);								
								}
							},
							error: function() {
								writeError("Error");
							},
						});
					}
				}
			}

			function deleteNode( ){
				if (activenode){
					if (confirm( "are you sure??")){
						writeMessage("Deleting node ...");
						var xhr = dojo.xhrPost({
							content: {
							  id: activenode.id,
							},
							url: "services/deleteNode.php",
							load: function(response) {
								if (response.toLowerCase().indexOf("error") == -1){
									writeMessage(response);
									createTree();
								} else {
									writeError(response);								
								}
							},
							error: function() {
								writeError("Error");
							},
						});						
					}
				}
			}
			
			function loadFlow( flow ){
				var bc = dijit.byId("mainContent")
				var tc = dijit.byId("tempcontent")
				if (tc){
					bc.removeChild(tc);
					tc.destroyRecursive();
				}
				var cp = new dijit.layout.ContentPane({
				   id: "tempcontent",
				   region: "center",
				   style: "width: 100px;height: 100px; overflow:hidden",
				   content: "<iframe frameborder='0' style='width: 100%; height: 100%; overflow:auto' src='" + flow + "/'></iframe>",
				});
				bc.addChild(cp);
				
			}
			
			function createTree( ){
				if (tree)
					tree.destroy();							

				NemoID = new dojo.store.JsonRest({
					target:"services/getChildren.php?id=",
					mayHaveChildren: function(object){
						return "children" in object;
					},
					getChildren: function(object, onComplete, onError){
						this.get(object.id).then(function(fullObject){
							object.children = fullObject.children;
							onComplete(fullObject.children);
						}, function(error){
							console.error(error);
							onComplete([]);
						});
					},
					getRoot: function(onItem, onError){
						this.get("root").then(onItem, onError);
					},
					getLabel: function(object){
						return object.name;
					},
				});
				
				var a = dojo.byId("treePanel")
				var div = document.createElement("div");
				div.id = "tree";
				div.name = "tree";
				a.appendChild(div);
				tree = new dijit.Tree({
					model: NemoID,
					getIconClass: function( item,  opened){
						return (iconClass[item.type]) ? iconClass[item.type] : "dijitNemoLeaf"; 
					},					
				}, "tree"); // make sure you have a target HTML element with this id	

				tree.on("click", function(object){
					var bc = dijit.byId("mainContent")
					var tc = dijit.byId("tempcontent")
					if (tc){
						bc.removeChild(tc);
						tc.destroyRecursive();
					}
					var xhr = dojo.xhrGet({
					    url: "services/node.php?id=" + object.id,
					    load: function(response) {
							var cp = new dijit.layout.ContentPane({
							   id: "tempcontent",
							   region: "center",
							   style: "width: 100px;height: 100px",
							   content: response,
							});
							bc.addChild(cp);							
					    },
					    error: function() {
					    },
					});
				}, true);

				tree.on("dblclick", function(object){
				}, true);
				
				menu = new dijit.Menu({
				})

				submenu = new dijit.MenuItem({
					label: "Add Child Node",
					name: "addNode",
					onClick: addNode,
					iconClass:"dijitEditorIcon dijitEditorIconCopy",
				});
				menu.addChild(submenu);

				submenu = new dijit.MenuItem({
					label: "Delete Node",
					name: "deleteNode",
					onClick: deleteNode,
					iconClass:"dijitEditorIcon dijitEditorIconCopy",
				});
				menu.addChild(submenu);


				submenu = new dijit.MenuItem({
					label: "Duplicate Node",
					name: "duplicateNode",
					onClick: duplicateNode,
					iconClass:"dijitEditorIcon dijitEditorIconCopy",
				});
				menu.addChild(submenu);

				menu.startup();
				
				menu.bindDomNode(tree.domNode);	
				dojo.connect(menu, "_openMyself", this, function(e){
					var tn = dijit.getEnclosingWidget(e.target);
					var disabled =  (tn.item.name != tn.item.type 
						|| (tn.item.type == "Region")
						|| (tn.item.type == "boundary_condition")
						|| (tn.item.type == "Boundary_region")
						|| (tn.item.type == "root")
					);
					var disableddel = (tn.item.type == "root");
//					activenode = (disabled) ? tn.item : undefined;
					activenode = tn.item;
					menu.getChildren().forEach(
						function(i){ 
							if (i.name == 'duplicateNode'){
								i.set('disabled', !disabled); 
							} else if (i.name == 'addNode'){
								i.set('disabled', false);							
							} else if (i.name == 'deleteNode'){
								i.set('disabled', disableddel);								
							} else  {
								i.set('disabled', true); 
							}
						}
					);
				});
				tree.startup();
			}
			
			require([
				"dojo/store/JsonRest", 
				"dojo/store/Observable",
				"dijit/Tree",
				"dojo/query", 
				"dijit/layout/BorderContainer", 
				"dijit/layout/ContentPane", 
				"dijit/layout/AccordionContainer", 
				"dijit/layout/StackController",
				"dijit/Menu",
				"dijit/MenuBar",
				"dijit/PopupMenuBarItem",
				"dijit/DropDownMenu",
				"dijit/MenuItem",
				"dijit/MenuSeparator",	
				"dojox/form/Uploader",
				"dojox/form/uploader/plugins/IFrame",
				"dojo/domReady!"
			], function(JsonRest, Observable, Tree, query, xhr) {

				createTree( );
				pMenuBar = new dijit.MenuBar({});
				pSubMenu = new dijit.DropDownMenu({});
			  	qSubMenu = new dijit.MenuItem({
					label:"Upload Input Deck",
					iconClass:"dijitIcon dijitIconEditTask"
				});
				dojo.connect(qSubMenu, "onClick", function(){
					dojo.byId("uploader").click();
				});
				dojo.byId("uploader").onchange = function(){
					writeMessage("Uploading Input Deck ...");
					dojo.io.iframe.send({
						form: "formUploader",
						handleAs: "text",
						load: function(response, ioArgs){
							writeMessage("Input Deck uploaded");
							var tc = dijit.byId("tempcontent")
							if (tc){
								tc.destroyRecursive();
							}							
							var cp = new dijit.layout.ContentPane({
							   id: "tempcontent",
							   region: "center",
							   style: "width: 100px;height: 100px",
							   content: "",
							});
							tree.startup();				
							var bc = dijit.byId("mainContent")						
							bc.addChild(cp);
							createTree();
						},
						error: function(response, ioArgs){
							writeError("error");
						}
					});				
				}
				pSubMenu.addChild( qSubMenu );

			  	qSubMenu = new dijit.MenuItem({
					label:"Upload Database",
					iconClass:"dijitIcon dijitIconEditTask"
				});
				dojo.connect(qSubMenu, "onClick", function(){
					dojo.byId("uploader2").click();
				});
				dojo.byId("uploader2").onchange = function(){
					writeMessage("Uploading Database ...");
					dojo.io.iframe.send({
						form: "formUploader2",
						handleAs: "text",
						load: function(response, ioArgs){
							writeMessage("Database uploaded");
							var tc = dijit.byId("tempcontent")
							if (tc){
								tc.destroyRecursive();
							}							
							var cp = new dijit.layout.ContentPane({
							   id: "tempcontent",
							   region: "center",
							   style: "width: 100px;height: 100px",
							   content: "",
							});
							tree.startup();				
							var bc = dijit.byId("mainContent")						
							bc.addChild(cp);
							createTree();
						},
						error: function(response, ioArgs){
							writeError("error");
						}
					});				
				}				
				pSubMenu.addChild( qSubMenu );


			  	qSubMenu = new dijit.MenuItem({
					label:"Close Input Deck",
					iconClass:"dijitIcon dijitIconDelete"					
				});
				dojo.connect(qSubMenu, "onClick", function(){	
					writeMessage("Closing File ...");								
					var xhr = dojo.xhrGet({
						url: "services/closefile.php",
						load: function(response) {
							var tc = dijit.byId("tempcontent")
							if (tc){
								tc.destroyRecursive();
							}							
							var cp = new dijit.layout.ContentPane({
							   id: "tempcontent",
							   region: "center",
							   style: "width: 100px;height: 100px",
							   content: "",
							});
							tree.startup();				
							var bc = dijit.byId("mainContent")						
							bc.addChild(cp);
							createTree();
							writeMessage("File Closed");
						},
						error: function() {
							writeError("Error");
						},
					});						
				});
				pSubMenu.addChild( qSubMenu );
				
			  	qSubMenu = new dijit.MenuItem({
					label:"Download Input Deck",
					iconClass:"dijitIcon dijitIconSave"					
				});
				dojo.connect(qSubMenu, "onClick", function(){
					window.open("services/export.php");
				});
				pSubMenu.addChild( qSubMenu );				

			  	qSubMenu = new dijit.MenuItem({
					label:"Download Python Input Deck",
					iconClass:"dijitIcon dijitIconSave"					
				});
				dojo.connect(qSubMenu, "onClick", function(){
					window.open("services/exportPython.php");
				});
				pSubMenu.addChild( qSubMenu );				

			  	qSubMenu = new dijit.MenuItem({
					label:"Download Meta Input Deck",
					iconClass:"dijitIcon dijitIconSave"					
				});
				dojo.connect(qSubMenu, "onClick", function(){
					window.open("services/exportMeta.php");
				});
				pSubMenu.addChild( qSubMenu );				

				pMenuBar.addChild(new dijit.PopupMenuBarItem({
					label:"File",
					popup:pSubMenu
				}));

				pSubMenu = new dijit.DropDownMenu({});
				
			  	qSubMenu = new dijit.MenuItem({
					label:"Visualize 'getData' request",
					iconClass:"dijitIcon"
				});
				dojo.connect(qSubMenu, "onClick", function(){loadFlow("visual")});
				pSubMenu.addChild( qSubMenu );

			  	qSubMenu = new dijit.MenuItem({
					label:"Visualize signal passing",
					iconClass:"dijitIcon"
				});
				dojo.connect(qSubMenu, "onClick", function(){loadFlow("communication")});
				pSubMenu.addChild( qSubMenu );


				pMenuBar.addChild(new dijit.PopupMenuBarItem({
					label:"View",
					popup:pSubMenu
				}));


				pSubMenu2 = new dijit.DropDownMenu({});
			  	pSubMenu2.addChild(new dijit.MenuItem({
					label:"..."
				}));
				
				pMenuBar.addChild(new dijit.PopupMenuBarItem({
					label:"About",
					popup:pSubMenu2
				}));
				pMenuBar.placeAt("navMenu");
				pMenuBar.startup();				
				
			});
		</script>
	</head>
	<body class="claro">
		<div id="appLayout" class="demoLayout" data-dojo-type="dijit.layout.BorderContainer" data-dojo-props="design: 'sidebar'">
			<div id="mainContent" class="centerPanel" data-dojo-type="dijit.layout.BorderContainer" data-dojo-props="region: 'center'">
			</div>
			<div id="menu" class="edgePanel" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region: 'top'">
	            <div id="navMenu">
    		    </div>	
			</div>
			<div id="message" class="edgePanel" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region: 'bottom'">
	            <div id="messageNav" style="height:30px">
    		    </div>	
			</div>
            <div id="leftCol" class="edgePanel" data-dojo-type="dijit.layout.AccordionContainer" data-dojo-props="region: 'leading', splitter: true, minSize:50" style="width: 300px;">
                <div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="title:'Input Deck Tree'" id="treePanel">
                </div>
<!--                <div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="title:'Input Deck Solvers Flow'" id="flowPanel">
                </div>-->
			</div>
		</div>
        <div id="uploadForm" style="filter:alpha(opacity=0); opacity: 0.0; width: 300px; cursor: pointer;">
        <form id="formUploader" name="formUploader" method="POST" action="services/loadfile.php" enctype="multipart/form-data">
	        <input type="file" id="uploader" name="uploader">
        </form>
        <form id="formUploader2" name="formUploader2" method="POST" action="services/loadfiledb.php" enctype="multipart/form-data">
	        <input type="file" id="uploader2" name="uploader">
        </form>
        </div>
	</body>
</html>
