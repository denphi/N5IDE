<?php
// N5IDE, Nemo5 InputDeck Editor
// NEMO5, The Nanoelectronics simulation package.
// Copyright (C) 2010 Purdue University
// Authors (in alphabetical order): Mejia, Daniel
//
// This package is a free software.
// It is distributed under the NEMO5 Non-Commercial License (NNCL).
// The license text is found in the subfolder 'license' in the top folder.
// To request an official license document please write to the following address:
// Purdue Research Foundation, 1281 Win Hentschel Blvd., West Lafayette, IN 47906, USA
	error_reporting(E_ALL);
	require_once( dirname ( dirname ( dirname ( dirname ( dirname( __FILE__ ) ) ) ) ) . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "php" . DIRECTORY_SEPARATOR . "constants.php");
	require_once( dirname ( dirname ( dirname ( dirname ( dirname( __FILE__ ) ) ) ) ) . DIRECTORY_SEPARATOR . "publications" .DIRECTORY_SEPARATOR . "com_pubmanager" . DIRECTORY_SEPARATOR . "source" . DIRECTORY_SEPARATOR . "config.inc.php" );
	require_once(SOURCE_PATH . DS . "InputDeck" . DS . "InputDeckParser.class.php");	
	$idp = new InputDeckParser();
	$idp->loadSession();
	$id = isset($_REQUEST["id"]) ? $_REQUEST["id"] : "";
	$commands = array('_step', '_reinit', '_output', '_init', '_solve', 
					  '_disable_step','_disable_reinit','_disable_output','_disable_init',
					  '_loop_count',
					  );
	$private = array('_name', '_id', '_children', '_type', '_nested', '_comment');
	if ($id == "root"){
	} else {
		try {
			$obj = $idp->searchNode( $id );	
			echo "<div align='center' style='border:1px solid #CCC;padding:5px'>" . strtoupper($obj->_type) . (isset($obj->name) ? " ( " . $obj->name . " )" : "") . "</div>";
			echo "<form id='nodeForm' action='services/savenode.php' onsubmit='return false' method='post'>";
			echo "<div>&nbsp;</div>";
			echo "<input type='hidden' value='" . $obj->_id . "' name='_id' value='_id'>";
			echo "<table style='border:1px solid #CCC' width='100%' id='nodeTable'><tr><td width='150'></td><td></td></tr>";
			if ( $obj->_type == "set" ){
				$it = $idp->getParent($obj);
				if($it != NULL){
					$it = $idp->getParent($it);
					if($it != NULL){
						$keys = array_keys(get_object_vars($obj));
						foreach(get_object_vars($it) as $k => $v){
							if (in_array($k, $private) || in_array($k, $commands) || in_array($k, $keys))
								;
							else
								echo "<tr><td style='color:#999999'>" . $k . "</td>".
									 "<td>
									 <div class='dijitReset dijitInputField dijitInputContainer' style='border:1px solid #CCC;color:#999999' width='100%'>
									 " . $v ."
									 </div>
									 </td></tr>";
						}
					}
				}
			}
			foreach(get_object_vars($obj) as $k => $v){
				if (in_array($k, $private) || in_array($k, $commands))
					;
				else
					echo "<tr><td>" . $k . "</td>".
						 "<td>
						 <div class='dijitReset dijitInputField dijitInputContainer' style='border:1px solid #CCC' width='100%'>
						 <input type='text' name='" . $k . "' id='" . $k . "' value='" . $v ."' class='dijitReset dijitInputInner'>
						 </div>
						 </td></tr>";
			}
			foreach(get_object_vars($obj) as $k => $v){
				if (in_array($k, $commands))
					echo "<tr><td style='color:#336699'>" . $k . "</td>".
						 "<td>
						 <div class='dijitReset dijitInputField dijitInputContainer' style='border:1px solid #CCC' width='100%'>
						 <input type='text' name='" . $k . "' id='" . $k . "' value='" . $v ."' class='dijitReset dijitInputInner'>
						 </div>
						 </td></tr>";
			}
			echo "<tr><td colspan='2' align='center'><button id='add' onclick=\"return addInput('nodeTable')\">Add Field</button>&nbsp;&nbsp;<button id='save' onclick=\"return sendForm('nodeForm')\">Commit Changes</button></td></tr>";					
			echo "</table>";
			echo "<div>&nbsp;</div>";
			echo "<div align='center' style='border:1px solid #CCC;padding:5px'>COMMENTS</div>";
			echo "<div>&nbsp;</div>";
			echo "<textarea id='_comment' name='_comment' style='width:100%;height:60px'>" . $obj->_comment. "</textarea>";
			echo "</form>";
			echo "<div>&nbsp;</div>";
			if (isset($obj->_children)){
				echo "<div align='center' style='border:1px solid #CCC;padding:5px'>CHILDREN</div>";
				echo "<div>&nbsp;</div><div style='border:1px solid #CCC;padding:5px' width='100%'>";
				foreach($obj->_children as $k => $v){
					echo "[" . strtoupper($v->_type) . (isset($v->name) ? " ( " . $v->name . " )" : "") . "]&nbsp;&nbsp;&nbsp;";
				}
				echo "</div>";
			}
		} catch (Exception $e){
		}
	}
?>