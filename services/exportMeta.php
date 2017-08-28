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
	ini_set('display_errors', 'on');
	require_once( dirname ( dirname ( dirname ( dirname ( dirname( __FILE__ ) ) ) ) ) . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "php" . DIRECTORY_SEPARATOR . "constants.php");
	require_once( dirname ( dirname ( dirname ( dirname ( dirname( __FILE__ ) ) ) ) ) . DIRECTORY_SEPARATOR . "publications" .DIRECTORY_SEPARATOR . "com_pubmanager" . DIRECTORY_SEPARATOR . "source" . DIRECTORY_SEPARATOR . "config.inc.php" );
	require_once(SOURCE_PATH . DS . "InputDeck" . DS . "InputDeckParser.class.php");	
	$idp = new InputDeckParser();
	$idp->loadSession();
	header('Content-type: text/plain');
	header('Content-Disposition: attachment;filename="' . str_replace(".in", ".py", "Meta".$idp->getName()) . '"');
	header('Cache-Control: max-age=0');
	$map = array();
	$map2 = array();
	$print = true;
	function print_node( $node, $level = "", $end = "", $last_solver=NULL, &$globalpos=0 ){
		global $map, $map2, $print;
		$commands = array('_step', '_reinit', '_output', '_init', '_solve', 'solve');
		$private = array('_name', '_id', '_children', '_type', '_nested', '_comment', "_loop_count");		
		$type = ($node->_type == "Global") ? "Nemo" : $node->_type;
		if ($type == "Structure"){
			$print = false;
		} else if ($level == ""){
			$print = true;
		}
		
		$s = "  ";		
		if(	$type == "loop"){
			if ($last_solver==NULL)
				return;
/*			if ($node->_comment != ""){
				echo "#  ".$level."\n";
				echo "#  " . $level . str_replace("\n","\n#",str_replace("\n\n", "  \n", $node->_comment ) );
				echo "\n#  ".$level."\n";
			}*/
			if ($globalpos == 0){
				if ($print) echo $level  . "Set([\n";
				foreach(get_object_vars($last_solver) as $k => $v){
					if (in_array($k, $private)) ;
					else if ( isset($last_solver->$k) ){
						if ($print) echo $level  . $s . "{ '" . $k . "' : '" . $v . "' }," . "\n";
					}
				}
				if ($print) echo $level . "]),\n";
				$globalpos ++;
			}	
			
			/*get Current State*/
			if (isset($last_solver->_children[0]->_children)){
				$temp = array();
				$vtemp = array();
				$gtemp = array();
				$gvtemp = array();
				foreach(get_object_vars($last_solver) as $k => $v){
						if (in_array($k, $private)) ;
						else{
							$temp[$k] = $v*1;
							$vtemp[$k] = $v;
							$gtemp[$k] = $v*1;
						}
				}				
				foreach($last_solver->_children[0]->_children as $node2){
					if ($node2->_id == $node->_id){
						break;
					}						
					if ($node2->_type == "set"){
						foreach(get_object_vars($node2) as $k => $v){
							if (in_array($k, $private)) ;
							else{
								$temp[$k] = $v*1;							
								$vtemp[$k] = $v;
								$gtemp[$k] = $v*1;																
							}
						}										
					} else if ($node2->_type == "loop"){
						for ($i=0; $i<$node2->_loop_count;$i++){
							foreach(get_object_vars($node2) as $k => $v){
								if (in_array($k, $private)) ;
								else{								
									$global = true;
									if ( ($pos = strpos($k,"\$") ) !== false){
										$global = false;
										$k = substr($k,0,$pos);
									}	
									$tmp = 0;
									if($tmp = @eval("return " . str_replace($k, $temp[$k], $v) . ";")){
										if ($global)
											$gtemp[$k] = $tmp*1;
										$temp[$k] = $tmp*1;
									} else {
										if ($global)
											$gtemp[$k] = 0;
										$temp[$k] = 0;
									}
								}
							}
						}
					}
				}							
				foreach ($temp as $k => $v){
					$v2 = $vtemp[$k];
					if (($pos = strrpos($v2,"_")) !== false && (substr($v2,$pos+1)*1 > 0 || substr($v2,$pos+1) == "0") ){
						$v2 = substr($v2,0,$pos);
					}							
					$gvtemp[$k] = ($vtemp[$k]*1 > 0 || $vtemp[$k] == "0") ? $gtemp[$k] : $v2."_".$gtemp[$k]*1;
					$vtemp[$k] = ($vtemp[$k]*1 > 0 || $vtemp[$k] == "0") ? $temp[$k] : $v2."_".$temp[$k]*1;
				}
//				print_r($vtemp);
//				print_r($gvtemp);
			}			
			/**/

			if ($print) echo $level . "[\n";
			if ($print) echo $level . $s . "Set([\n";
			$i=0;
			$total = 0;
			foreach(get_object_vars($node) as $k => $v){
				if (!in_array($k, $private))
					$total++;
			}
			if (isset($node->_children)){
				foreach($node->_children as $node2){
					$total++;
				}
			}			
			$j = 0;
			foreach(get_object_vars($node) as $k => $v){
				$rule = false;
				if ( ($pos = strpos($k,"\$") ) !== false){
					$rule = "str( i + " . $globalpos ." " . substr($k,$pos+1) .")";
					$k = substr($k,0,$pos);
				}
				if (in_array($k, $private)) ;
				else if ( isset($last_solver->$k) ){
						if (($rule!==false))
							if ($print) echo $level . $s . $s . "{ '" . $k . "' : str(v[" . $j++ . "]) },\n";
						if ($print) echo $level . $s . $s . "{ '" . $k . (($rule!==false)?"_' + ".$rule."":"'"). " : str(v[" . $j++ . "]) }" . ((++$i == $total)?"":",") . "\n";
				}
			}
			if ($print) echo $level . $s . "])\n";
			if ($print) echo $level . $s . "for i, v in enumerate\n";
			if ($print) echo $level . $s . "([[r[col] for r in [\n";
			foreach(get_object_vars($node) as $k => $v){
				$rule = false;
				if ( ($pos = strpos($k,"\$") ) !== false){
					$k = substr($k,0,$pos);
					$rule = true;
				}
				if (in_array($k, $private));
				else if ( isset($vtemp[$k]) ){
					$v = str_replace($k,"", $v);
					$v = str_replace(array("$","{","}"), "", $v);
					$v2 = $vtemp[$k];
					if (($pos = strrpos($v2,"_")) !== false && (substr($v2,$pos+1)*1 > 0 || substr($v2,$pos+1) == "0") ){
						$v2 = substr($v2,0,$pos);
					}
//					if ($print) echo '$a = ' . $temp[$k] . ' ' . $v . ';';
					if (($rule!==false))
						if ($print) echo $level . $s . $s . $s . "map(lambda x: '" . $gvtemp[$k] ."', range(".($node->_loop_count*1).")),\n";
					if (@eval('$a = ' . $temp[$k] . ' ' . $v . ';')!==false){
						if ( $vtemp[$k] == "0" || $vtemp[$k]*1 > 0 ){
							if ($print) echo $level . $s . $s . $s . "map(lambda n: (lambda f, *a: f(f, *a))(lambda rec, n: n <= 0 and " . $a . " or rec(rec, n-1)" . $v . ", n), range(".($node->_loop_count*1)."))" . ((++$i == $total)?"":",") . "\n";
						} else {
							if ($print) echo $level . $s . $s . $s . "map(lambda n: '" . $v2 . "_' +str( (lambda f, *a: f(f, *a))(lambda rec, n: n <= 0 and " . $a . " or rec(rec, n-1)" . $v . ", n)), range(".($node->_loop_count*1)."))" . ((++$i == $total)?"":",") . "\n";
						}
					} else {
						if ($print) echo $level . $s . $s . $s . "map(lambda x: '" . $v ."', range(".($node->_loop_count*1)."))" . ((++$i == $total)?"":",") . "\n";
					}
				}
			}
			if ($print) echo $level . $s . "]] for col in range(".($node->_loop_count*1).")])\n";
			if (isset($node->_children)){
				foreach($node->_children as $node2){
					print_node($node2, $level.$s, ((++$i == $total)?"":","));
				}
			}			
			if ($print) echo $level . "]". $end ."\n";
			$globalpos += $node->_loop_count;						
		} else {
			if ($level == ""){
				if ($print) echo strtolower($type) . " = ";
			}
			if ($print) echo $level . ucwords( strtolower( $type ) ) . " ";	
		
			if ($print) echo  "([" . ( ($type == "Nemo") ? "solvers, ":"") . "" . "\n";
/*			if ($node->_comment != ""){
				if ($print) echo "#  ".$level."\n";
				if ($print) echo "#  " . $level . str_replace("\n","\n#",str_replace("\n\n", "  \n", $node->_comment ) );
				if ($print) echo "\n#  ".$level."\n";
			}*/
			$i=0;
			$total = 0;
			foreach(get_object_vars($node) as $k => $v){
				if (!in_array($k, $private))
					$total++;
			}
			if (isset($node->_children)){
				foreach($node->_children as $node2){
					$total++;
				}
			}

			foreach(get_object_vars($node) as $k => $v){
				if (in_array($k, $commands) || in_array($k, $private))
					;
				else{
					$str_sep = "'";
					if ($k == "name" && $type=="Domain"){
						$map[$v] = "p_" . strtolower($v);
						echo $map[$v] . " = ms.get_option('".$v."')\n";
					} else if (isset($map[$v])){
						$v = $map[$v];
						$str_sep = "";
					} else if (isset($map2[$k])){
						$v = $map2[$k];
						$str_sep = "";
					} else if (strpos($v,"d1:")===0){
						$v = "ms_path + ':" .$v. "'";
						$str_sep = "";
					} else if ($k == "database" || $k == "message_level" ){
						continue;
					}
					if ($print) echo $s . $level . "{ '" . $k . "' : ". $str_sep . $v . $str_sep . " }" . ((++$i == $total)?"":",") . "\n";
				}
			}
			foreach(get_object_vars($node) as $k => $v){
				if (in_array($k, $commands)){
					if ($print) echo $s . $level . "{ '" . $k . "' : '" . $v . "' }" . ((++$i == $total)?"":",") . "\n";
				}
			}
			if (isset($node->_children)){
				foreach($node->_children as $node2){					
					if ($type == "solver"){
						$iterator = 0;
						print_node($node2, $level.$s, ((++$i == $total)?"":","), $node, $iterator);
					} else {
						print_node($node2, $level.$s, ((++$i == $total)?"":","), $last_solver, $globalpos);
					}
				}
			}
			if ($print) echo $level . "])" . $end . "\n";
			$globalpos ++;
		}
	}
	if ($idp->getType() == "input_deck"){
		if (is_array($idp->getTree())){	
			echo "#\n";
			echo "#  Meta Solver Base (Python version) Generated By NEMO5 InputDeckEditor\n";
			echo "#  version 0.1\n";
			echo "#  http://www.http://nanohub.org\n";
			/*if ($idp->getComment() != ""){
				echo "#  /\n\n";
				echo "#  " . str_replace("\n","\n#",str_replace("\n\n", "  \n", $idp->getComment()));
			}*/
			echo "#\n";
			echo "ms = __metasolver__\n";
			echo "ms_path = ms.get_name()\n";
			echo "p_tb_basis = ms.get_option('tb_basis')\n";
			echo "p_active_regions = ms.get_option('active_regions')\n";
			$map2['active_regions'] = "p_active_regions";
			$map2['tb_basis'] = "p_tb_basis";
//			split($_REQUEST[])

//			echo "from Container import *\n";
			foreach( $idp->getTree() as $node ){
				print_node($node);
			}
//			echo "nemo.start()\n";
		}
	} else {
		echo "Only Input_deck are supported to export";
	}
?>
