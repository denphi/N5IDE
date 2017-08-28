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
	$depth_arr = array();
	$type_arr = array();	
	$default_member_height = 24;
	$default_member_width = 135;
	if (isset($_REQUEST['layout']) && $_REQUEST['layout'] == 2){
		$space_x = 10;
		$space_y = 60;
	} else {
		$space_x = 60;
		$space_y = 20;
	}
	$commands = array('_step', '_reinit', '_output', '_init', '_solve', 
					  '_disable_step','_disable_reinit','_disable_output','_disable_init',
					  '_loop_count',
					  );
	$private = array('_name', '_id', '_children', '_type', '_nested', '_comment', '_xy', '_in', '_out', '_parent', 'depth');
		
	function createHash($node, &$solvers, $prefix = "", $depth = 0, $parent = NULL){
		global $depth_arr, $default_member_height, $default_member_width, $space_x, $space_y;
		if ($node->_type == "solver"){
			$depth_arr[$depth] = (isset($depth_arr[$depth]))? $depth_arr[$depth] : 0;
			$node->depth = $depth;
			$node->_parent = $parent;
			if (isset($_REQUEST['layout']) && $_REQUEST['layout'] == 2){
				$node->_xy=array( $depth_arr[$node->depth] * ($default_member_width + $space_x), $node->depth * ($default_member_height + $space_y));
				$node->_in=array( $node->_xy[0] + $default_member_width/2, $node->_xy[1] );
				$node->_out=array( $node->_xy[0] + $default_member_width/2, $node->_xy[1] + $default_member_height );
			} else {			
				$node->_xy=array( $node->depth * ($default_member_width + $space_x), $depth_arr[$node->depth] * ($default_member_height + $space_y));
				$node->_in=array( $node->_xy[0], $node->_xy[1] + $default_member_height/2 );
				$node->_out=array( $node->_xy[0] + $default_member_width, $node->_xy[1] + $default_member_height/2 );
			}			
			$solvers[$prefix . $node->name] = $node;
			$parent = $prefix . $node->name;			
			$depth_arr[$depth]++;
		}
		if (isset($node->_children))
			foreach($node->_children as $p)
				if ($node->_type == "solver"){
					createHash($p, $solvers, $prefix . $node->name . ":", $depth+1, $parent);
				} else {
					createHash($p, $solvers, $prefix, $depth, $parent);
				}
	}

	function createRelations($node, &$solvers, &$relations, $prefix = ""){
		global $type_arr;
		if ($node->_type == "solver"){
			foreach(get_object_vars($node) as $k=>$v){
				if (!is_array($v))
					if ($k != "name" && $k != "type" && strpos($k, "_") !== 0){
						$v = trim(str_replace(" " , "", $v), "()");
						$list = explode(",", $v);
						foreach($list as $k2 => $v2){
							if (isset($solvers[$v2])){
								$relations[$prefix . $node->name][$v2][$k] = 1;
								$type_arr[$k] = $k;								
							} else if (isset($solvers[$prefix . $v2])){
								$relations[$prefix . $node->name][$prefix . $v2][$k] = 1;
								$type_arr[$k] = $k;								
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
		if (isset($_REQUEST['layout']) && $_REQUEST['layout'] == 2){
			$space = (max($depth_arr) - $depth_arr[$v->depth])/2;//$depth_arr[$v->depth];
			$v->_xy[0] += ceil(($space * ($default_member_width + $space_x)));
			$v->_in=array( $v->_xy[0] + $default_member_width/2, $v->_xy[1] );
			$v->_out=array( $v->_xy[0] + $default_member_width/2, $v->_xy[1] + $default_member_height );
			$solvers[$k] = $v;
		} else {
		}		
		$relations[$k] = array();
		foreach ($solvers as $k2 => $v2){
			$relations[$k][$k2] = array();
		}
	}

	
	foreach ($tree as $t){
		createRelations($t, $solvers, $relations);		
	}


?>
    <link href="static/css/model.css" rel="stylesheet">
	<div id='tooltip' style=""></div>
	<div id="chart" style="z-index:1"></div>
    <div style="padding:3px;border-top:1px solid #CCC;height:25px">
    	<form method="post" action="" id="form">
        	<?php foreach ($type_arr as $v){?>
	            <div style="float:left;width:320px;height:20px;overflow:hidden;font-size:9px;font-family:Verdana, Geneva, sans-serif">
                	<input type="checkbox" id='filter[<?php echo $v?>]' name='filter[<?php echo $v?>]' <?php echo (!isset($_REQUEST['filter']) || isset($_REQUEST['filter'][$v]))?"checked='checked'":""?>/>
					<?php echo $v?>&nbsp;&nbsp;
                </div>
            <?php } ?>
            <div style="float:left;width:320px;height:20px;overflow:hidden;font-size:9px;font-family:Verdana, Geneva, sans-serif">Layout: <select name="layout"><option value="1">Horizontal</option><option value="2" <?php (isset($_REQUEST['layout']) && $_REQUEST['layout'] == 2)? "selected='selected'" : "" ?>>Vertical</option></select></div>
            <div style="float:left;width:320px;height:20px;overflow:hidden;font-size:9px;font-family:Verdana, Geneva, sans-serif"><input type="submit" value="Submit" name="Submit" id="Submit"/></div>
        </form>
    </div>
    <script type="text/javascript" src="http://mbostock.github.com/d3/d3.js"></script> 
    <script type="text/javascript">
<?php
/*
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
*/
?>
	</script>
    <script>
		var default_member_height = <?php echo $default_member_height?>;
		var default_member_width = <?php echo $default_member_width?>;
		var h, pack, w;
		var tooltip = document.getElementById('tooltip');	
<?php if (isset($_REQUEST['layout']) && $_REQUEST['layout'] == 2){ ?>
		w = <?php echo (max($depth_arr)+1) * ($default_member_width + $space_x)?>;
		h = <?php echo (count($depth_arr)+1) * ($default_member_height + $space_y)?>;
<?php } else { ?>
		w = <?php echo (count($depth_arr)+1) * ($default_member_width + $space_x)?>;
		h = <?php echo (max($depth_arr)+1) * ($default_member_height + $space_y)?>;
<?php } ?>
		pack = d3.layout.pack().size([w - 4, h - 4]).value(function(d) {
			return d.size;
		});
		var svg = d3.select("#chart").append("svg").attr("width", w).attr("height", h).attr("class", "pack");
      	svg.append("defs").selectAll("marker").data(["inheritance-arrow"]).enter().append("marker").attr("id", String).attr("viewBox", "0 -5 10 10").attr("refX", 10).attr("refY", -0.5).attr("markerWidth", 10).attr("markerHeight", 10).attr("orient", "auto").append("svg:path").attr("d", "M0,-5L10,0L0,5");
		svg = svg.selectAll("g").data(["root"]).enter().append("g").attr("transform", "translate(2, 2)");
<?php 
	$i = 0;
	foreach ($solvers as $k => $v){		
		$description = "<div align=left>";
		foreach(get_object_vars($v) as $k2 => $v2){
			if (in_array($k2, $private) || in_array($k2, $commands))
				;
			else
				$description .= "<strong>" . $k2 . "</strong> - " . $v2 . "<br>";
		}
		$description .= "</div>";
?>
		y = <?php echo $v->_xy[1] ?>;
		x = <?php echo $v->_xy[0] ?>;
		var data, h, level, margin, path_d, points, sc, super_view, w, x, y, _i, _len, _ref,
		data = [];
		data.push('<?php echo $v->name?>');
		h = default_member_height;
		w = default_member_width;
		margin = 10;
		el = svg.append("g").attr("x", x).attr("y", y);
		el.append("rect").attr("x", x).attr("y", y).attr("rx", 5).attr("ry", 5).attr("width", w).attr("height", h)
			.on("mouseover", function(){ d3.select(this).attr("class", "selected"); return tooltip.style.opacity = "1";})
		  	.on("mousemove", function(){ var pos = d3.mouse(document.body); tooltip.style.top = pos[1]+20; tooltip.style.left = pos[0]+20; return tooltip.innerHTML = '<?php echo $description?>'})
		  	.on("mouseout", function(){  d3.select(this).attr("class", "");return tooltip.style.opacity = "0"; });
		
		el.selectAll("text").data(data).enter().append("text").attr("x", x + margin).attr("y", function(d, i) {
			return y + margin + 0 * default_member_height;
		}).attr("dy", ".35em").text('<?php echo $v->name?>');
<?php
	}
?>	

<?php 
	foreach ($solvers as $k => $v){
		if ($v->_parent == NULL)
			continue;
?>
		  points = [];
		  points[0] = {
			x: <?php echo $v->_in[0]?>,
			y: <?php echo $v->_in[1]?>
		  };
		  points[1] = {
			x: <?php echo $solvers[$v->_parent]->_out[0]?>,
			y: <?php echo $solvers[$v->_parent]->_out[1]?>
		  };
		  path_d = "M" + points[0].x + "," + points[0].y;
		  path_d += " " + points[1].x + "," + points[1].y;
		  el.append("path").attr("d", path_d).attr("class","parent").attr("fill", "none").attr("stroke-width" , "0.5");
		  
<?php
	}
?>	
		
<?php 
	foreach($relations as $k => $v){
		foreach($v as $k2 => $v2){
			foreach($v2 as $k3 => $v3){
				if ($k == $k2)
					continue;
				if (isset($_REQUEST['filter']) && !isset($_REQUEST['filter'][$k3]))
					continue;
?>	
		  points = [];
		  points[0] = {
			x: <?php echo $solvers[$k2]->_out[0]?>,
			y: <?php echo $solvers[$k2]->_out[1]?>
		  };
		  points[1] = {
			x: <?php echo $solvers[$k]->_in[0]?>,
			y: <?php echo $solvers[$k]->_in[1]?>
		  };
		  path_d = "M" + points[0].x + "," + points[0].y;
		  <?php if (isset($_REQUEST['layout']) && $_REQUEST['layout'] == 2){ ?>
  		  path_d += " C" + (points[0].x - <?php echo $space_x*2?>) + "," + (points[0].y);
		  path_d += " " + (points[1].x + <?php echo $space_x*2?>) + "," + (points[1].y);
		  <?php } else { ?>
		  path_d += " C" + points[0].x + "," + (points[0].y - <?php echo $space_y*2?>);
		  path_d += " " + points[1].x + "," + (points[1].y + <?php echo $space_y*2?>);
		  <?php } ?>	
		  path_d += " " + points[1].x + "," + points[1].y;

		  el.append("path").attr("d", path_d).attr("fill", "none").attr("marker-end", "url(#inheritance-arrow)").attr("stroke-dasharray" , "0,4 1").attr("stroke-width" , "0.5").attr("class" , "<?php echo $k3?>");
		  el.append("path").attr("d", path_d).attr("fill", "none").attr("marker-end", "url(#inheritance-arrow)").attr("opacity", "0").attr("stroke-width" , "3").attr("class" , "<?php echo $k3?>")
			.on("mouseover", function(){  d3.select(this).attr("opacity", "1"); return tooltip.style.opacity = "1";})
			.on("mousemove", function(){ var pos = d3.mouse(document.body); tooltip.style.top = pos[1]+20; tooltip.style.left = pos[0]+20; return tooltip.innerHTML = '<?php echo $k3?>'})
			.on("mouseout", function(){  d3.select(this).attr("opacity", "0"); return tooltip.style.opacity = "0";});
<?php
			}
		}
	}
?>	
		
    </script>

