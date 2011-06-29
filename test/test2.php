<?php

include '../Sortify.php';
include '../SortifySeriesFilter.php';

$Sortify = new Sortify('src');

echo "Sortify ".$Sortify->version."\n\n";

// setup
touch('src/Ma serie 1x10.avi');
touch('src/Ma serie S2E3.avi');
touch('src/Anime 235.avi');

// Filter series
$f = new SortifySeriesFilter();
$f->setWords('Ma serie');
$Sortify->addFilter($f, 'dest/Ma Serie Pref', 'Saison {saison}/Ma serie prefee S{saison2}E{episode}.{ext}');

// Filter series
$f = new SortifySeriesFilter();
$f->setWords('Anime');
$f->mapSaisons(array(2=>64, 3=>128, 4=>200));
$Sortify->addFilter($f, 'dest/Anime', 'Saison {saison}/My Anime S{saison2}E{episode} ({absolu}).{ext}');

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
@unlink('src/Ma serie 1x10.avi');
@unlink('src/Ma serie S2E3.avi');
@unlink('src/Anime 235.avi');
rrmdir('dest/Ma Serie Pref');
rrmdir('dest/Anime');

?>