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
	$name = isset($_REQUEST["name"]) ? $_REQUEST["name"] : "";
	if ($id == "root"){
		echo "Error";
	} else {
		try {
			$obj = $idp->searchNode( $id );	
			$obj2 = InputDeckParser::cloneNode( $obj, $name );
			$a = $idp->insertSibbling($obj, $obj2);
			$idp->saveSession();	
			echo "Saved";
		} catch ( Exception $e){
			echo "Error";
		}
	}
?>
