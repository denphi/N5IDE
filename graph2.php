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
	require_once( dirname ( dirname ( dirname ( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "php" . DIRECTORY_SEPARATOR . "constants.php");
	require_once( dirname ( dirname ( dirname ( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . "publications" .DIRECTORY_SEPARATOR . "com_pubmanager" . DIRECTORY_SEPARATOR . "source" . DIRECTORY_SEPARATOR . "config.inc.php" );
	require_once(SOURCE_PATH . DS . "InputDeck" . DS . "InputDeckParser.class.php");	


	function createHash($node, &$solvers, $prefix = ""){
		if ($node->_type == "solver"){
			$solvers[$prefix . $node->name] = $node;
		}
		if (isset($node->_children))
			foreach($node->_children as $p)
				if ($node->_type == "solver"){
					createHash($p, $solvers, $prefix . $node->name . ":");
				} else {
					createHash($p, $solvers, $prefix);
				}
	}

	function createRelations($node, &$solvers, &$relations, $prefix = ""){
		if ($node->_type == "solver"){
			foreach(get_object_vars($node) as $k=>$v){
				if (!is_array($v))
					if ($k != "name" && $k != "type" && strpos($k, "_") !== 0){
						$v = trim(str_replace(" " , "", $v), "()");
						$list = explode(",", $v);
						foreach($list as $k2 => $v2){
							if (isset($solvers[$v2])){
								$relations[$prefix . $node->name][$v2][$k] = 1;
							} else if (isset($solvers[$prefix . $v2])){
								$relations[$prefix . $node->name][$prefix . $v2][$k] = 1;
							}							
						}
					}
			}
			$solvers[$prefix . $node->name] = $node;
		}
		if (isset($node->_children))
			foreach($node->_children as $p)
				if ($node->_type == "solver"){
					createRelations($p, $solvers, $relations, $prefix . $node->name . ":");
				} else {
					createRelations($p, $solvers, $relations, $prefix);
				}
	}

	$idp = new InputDeckParser();
	$idp->loadSession();
	$solvers = array();
	$relations = array();
	$tree = $idp->getTree();
	foreach ($tree as $t){
		createHash($t, $solvers);		
	}
	foreach ($solvers as $k => $v){
		$relations[$k] = array();
		foreach ($solvers as $k2 => $v2){
			$relations[$k][$k2] = array();
		}
	}

	
	foreach ($tree as $t){
		createRelations($t, $solvers, $relations);		
	}


?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <title>Chord Diagram</title>
    <script type="text/javascript" src="http://mbostock.github.com/d3/d3.js?1.22.1"></script>
    <script type="text/javascript" src="http://mbostock.github.com/d3/d3.layout.js?1.22.1"></script>
    <style type="text/css">

path.link {
  fill: none;
  stroke: #666;
  stroke-width: 1.5px;
}

marker#Green {
  fill: green;
}

path.link.Green {
  stroke: green;
}

path.link.Dash{
  stroke-dasharray: 0,2 1;
}

circle {
  fill: #ccc;
  stroke: #333;
  stroke-width: 1.5px;
}

text {
  font: 10px sans-serif;
  pointer-events: none;
}

text.shadow {
  stroke: #fff;
  stroke-width: 3px;
  stroke-opacity: .8;
}

    </style>
  </head>
  <body>
    <script type="text/javascript">
// http://blog.thomsonreuters.com/index.php/mobile-patent-suits-graphic-of-the-day/
<?php
	$types = array();
	echo "var links = [\n";	
	foreach($relations as $k => $v){
		foreach($v as $k2 => $v2){
			foreach($v2 as $k3 => $v3){
				  $types[$k3] = $k3;
				  echo "{source: \"". $k ."\", target: \"" . $k2 . "\", type: \"" . $k3 ."\"},\n";
			}
		}
	}
	echo "];\n";
	echo "var types = [ ";	
	foreach($types as $k => $v){
		echo "\"".$k."\",";
	}
	echo "];\n";

?>

var nodes = {};

// Compute the distinct nodes from the links.
links.forEach(function(link) {
  link.source = nodes[link.source] || (nodes[link.source] = {name: link.source});
  link.target = nodes[link.target] || (nodes[link.target] = {name: link.target});
});

var w = 900,
    h = 700;

var force = d3.layout.force()
    .nodes(d3.values(nodes))
    .links(links)
    .size([w, h])
    .linkDistance(200)
    .charge(-500)
    .on("tick", tick)
    .start();

var svg = d3.select("body").append("svg:svg")
    .attr("width", w)
    .attr("height", h);

// Per-type markers, as they don't inherit styles.
svg.append("svg:defs").selectAll("marker")
    .data(types)
  .enter().append("svg:marker")
    .attr("id", String)
    .attr("viewBox", "0 -5 10 10")
    .attr("refX", 15)
    .attr("refY", -1.5)
    .attr("markerWidth", 6)
    .attr("markerHeight", 6)
    .attr("orient", "auto")
  .append("svg:path")
    .attr("d", "M0,-5L10,0L0,5");

var path = svg.append("svg:g").selectAll("path")
    .data(force.links())
  .enter().append("svg:path")
    .attr("class", function(d) { return "link " + d.type; })
    .attr("marker-end", function(d) { return "url(#" + d.type + ")"; });

var circle = svg.append("svg:g").selectAll("circle")
    .data(force.nodes())
  .enter().append("svg:circle")
    .attr("r", 6)
    .call(force.drag);

var text = svg.append("svg:g").selectAll("g")
    .data(force.nodes())
  .enter().append("svg:g");

// A copy of the text with a thick white stroke for legibility.
text.append("svg:text")
    .attr("x", 8)
    .attr("y", ".31em")
    .attr("class", "shadow")
    .text(function(d) { return d.name; });

text.append("svg:text")
    .attr("x", 8)
    .attr("y", ".31em")
    .text(function(d) { return d.name; });

// Use elliptical arc path segments to doubly-encode directionality.
function tick() {
  path.attr("d", function(d) {
    var dx = d.target.x - d.source.x,
        dy = d.target.y - d.source.y,
        dr = Math.sqrt(dx * dx + dy * dy);
    return "M" + d.source.x + "," + d.source.y + "A" + dr + "," + dr + " 0 0,1 " + d.target.x + "," + d.target.y;
  });

  circle.attr("transform", function(d) {
    return "translate(" + d.x + "," + d.y + ")";
  });

  text.attr("transform", function(d) {
    return "translate(" + d.x + "," + d.y + ")";
  });
}
    </script>
  </body>
</html>
