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
/*	$idp->load();
	$idp->saveSession();*/
	$idp->loadSession();
	$id = isset($_REQUEST["id"]) ? $_REQUEST["id"] : "";
	echo "{\n";
	if (is_array($idp->getTree())){	
		if ($id == "root" ){
			echo "\t\"name\" : \"Input Deck\",\n";
			echo "\t\"type\" : \"root\",\n";		
			echo "\t\"id\" : \"root\",\n";
			echo "\t\"children\": [\n";
			foreach( $idp->getTree() as $node ){
				echo "\t\t{\n";		
				echo "\t\t\t\"name\": \"" . ( isset($node->name) ? str_replace('"','',$node->name) : $node->_type) . "\",\n";
				echo "\t\t\t\"type\": \"" . ( $node->_type ) . "\",\n";
				echo "\t\t\t\"id\": \"" . $node->_id . "\",\n";
				if (isset($node->_children)){
					echo "\t\t\t\"children\": \"true\",\n";
				}
				echo "\t\t},\n";		
			}		
			echo "\t]\n";
		} else {
			try {
				$obj = $idp->searchNode( $id );	
				echo "\t\"name\" : \"Input Deck\",\n";
				echo "\t\"id\": \"" . $obj->_id . "\",\n";
				if (isset($obj->_children)){
					echo "\t\"children\": [\n";
					foreach( $obj->_children as $node ){
						echo "\t\t{\n";		
						echo "\t\t\t\"name\": \"" . ( isset($node->name) ? str_replace('"', '', $node->name) : $node->_type) . "\",\n";
						echo "\t\t\t\"type\": \"" . ( $node->_type ) . "\",\n";
						echo "\t\t\t\"id\": \"" . $node->_id . "\",\n";
						if (isset($node->_children)){
							echo "\t\t\t\"children\": \"true\",\n";
						}					
						echo "\t\t},\n";		
					}		
					echo "\t]\n";
				}
			} catch (Exception $e){
			}
		}
	} else {
		echo "\t\"name\" : \"Input Deck\",\n";
		echo "\t\"type\" : \"root\",\n";		
		echo "\t\"id\" : \"root\"\n";		
	}
	echo "}\n";
?>