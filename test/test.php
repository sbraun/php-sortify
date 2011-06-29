<?php

include '../Sortify.php';
include '../SortifyRulesFilter.php';

$Sortify = new Sortify('src');

echo "Sortify ".$Sortify->version."\n\n";

// setup
touch('src/test.gif');

// Filter images (jpeg, gif, png, ...)
$f = new SortifyRulesFilter();
$f->addTypeRule('image');
$Sortify->addFilter($f, 'dest/Images');

// Filter .avi bigger than 700MB
$f = new SortifyRulesFilter();
$f->addExtRule('avi');
$f->addWeightRule('700M');
$Sortify->addFilter($f, 'dest/Movies');

// Filter PDF or XML files which are at least 6 month old
$f = new SortifyRulesFilter();
$f->addAgeRule(6, 'm');
$f->addExtRule('pdf', false);
$f->addExtRule('xml', false);
$Sortify->addFilter($f, 'dest/OLD');

// Execute all filters
// Execute all filters
$result = $Sortify->scan();
if ($result === false) {
	echo "ERROR\n";
	die(1);
} else {
	echo count($result)." fichiers déplacés\n";
	echo "-------------------\n";
	foreach($result as $s=>$d) {
		echo $s." => ".$d."\n";
	}
}

// Garbage
// clean garbage
function rrmdir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
      }
    }
    reset($objects);
    rmdir($dir);
  }
}
@unlink('src/test.gif');
rrmdir('dest/Images');

?>