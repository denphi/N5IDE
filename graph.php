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

p.node {
  font: 10px sans-serif;
}

.link {
  stroke: steelblue;
  stroke-opacity: .4;
  fill: none;
}
    </style>
  </head>
  <body>
      <div id="chart"></div>
    <script type="text/javascript">
(function() {
  packages = {

    // Lazily construct the package hierarchy from class names.
    root: function(classes) {
      var map = {};

      function find(name, data) {
        var node = map[name], i;
        if (!node) {
          node = map[name] = data || {name: name, children: []};
          if (name.length) {
            node.parent = find(name.substring(0, i = name.lastIndexOf(".")));
            node.parent.children.push(node);
            node.key = name.substring(i + 1);
          }
        }
        return node;
      }

      classes.forEach(function(d) {
        find(d.name, d);
      });

      return map[""];
    },

    // Return a list of imports for the given array of nodes.
    imports: function(nodes) {
      var map = {},
          imports = [];

      // Compute a map from name to node.
      nodes.forEach(function(d) {
        map[d.name] = d;
      });

      // For each import, construct a link from the source to target node.
      nodes.forEach(function(d) {
        if (d.imports) d.imports.forEach(function(i) {
          imports.push({source: map[d.name], target: map[i]});
        });
      });

      return imports;
    }

  };
})();	
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
var r = 960 / 2,
    splines = [];

var cluster = d3.layout.cluster()
    .size([360, r - 120])
    .sort(null)
    .value(function(d) { return d.size; });

var bundle = d3.layout.bundle();

var line = d3.svg.line.radial()
    .interpolate("bundle")
    .tension(.85)
    .radius(function(d) { return d.y; })
    .angle(function(d) { return d.x / 180 * Math.PI; });

var vis = d3.select("#chart").append("svg:svg")
    .attr("width", r * 2)
    .attr("height", r * 2)
  .append("svg:g")
    .attr("transform", "translate(" + r + "," + r + ")");

d3.json("readme.json", function(classes) {
  var nodes = cluster.nodes(packages.root(classes)),
      links = packages.imports(nodes);

  vis.selectAll("path.link")
      .data(splines = bundle(links))
    .enter().append("svg:path")
      .attr("class", "link")
      .attr("d", line);

  vis.selectAll("g.node")
      .data(nodes.filter(function(n) { return !n.children; }))
    .enter().append("svg:g")
      .attr("class", "node")
      .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + d.y + ")"; })
    .append("svg:text")
      .attr("dx", function(d) { return d.x < 180 ? 8 : -8; })
      .attr("dy", ".31em")
      .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
      .attr("transform", function(d) { return d.x < 180 ? null : "rotate(180)"; })
      .text(function(d) { return d.key; });
});

d3.select(window).on("mousemove", function() {
  vis.selectAll("path.link")
      .data(splines)
      .attr("d", line.tension(Math.min(1, d3.event.clientX / 960)));
});
    </script>
  </body>
</html>
