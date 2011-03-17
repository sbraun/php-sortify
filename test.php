<?php

include 'Sortify.php';


$Sortify = new Sortify('/path/to/survey');

echo "Sortify ".$Sortify->version."\n";

// Filter images (jpeg, gif, png, ...)
$f = new SortifyFilter();
$f->addTypeRule('image');
$Sortify->addFilter($f, '/sorted/Images');

// Filter .avi bigger than 700MB
$f = new SortifyFilter();
$f->addExtRule('avi');
$f->addWeightRule('700M');
$Sortify->addFilter($f, '/sorted/Movies');

// Filter PDF or XML files which are at least 6 month old
$f = new SortifyFilter();
$f->addAgeRule(6, 'm');
$f->addExtRule('pdf', false);
$f->addExtRule('xml', false);
$Sortify->addFilter($f, '/sorted/OLD');

// Execute all filters
if (!$Sortify->scan()) {
	echo "ERROR\n";
	die(1);
}

?>