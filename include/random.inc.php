<?php
// This entire file is part of the dekky mod, shuffled slideshow.
// it is provided by dekarrin.

require_once 'include/mersenne.inc.php';
use mersenne_twister\twister;

function dkrn_get_microtime_seed() {
	$basis = microtime();
	$basis_sp = explode(' ', $basis);
	$micro = round($basis_sp[0] * 1000);
	$ts = $basis_sp[1] * 1000;
	$seed = $ts + $micro;
	return $seed;
}

// Shuffles the array with a specific seed, so we can be sure of the
// ordering. Returns the shuffled array, where each item is the image pid.
function dkrn_pic_array_shuffle($pids, $seed, $start) {
	$mt = new twister($seed);
	$shuffled = array();
	$shuffled[] = $pids[$start];
	array_splice($pids, $start, 1);
	while (count($pids) > 1) {
		$key = $mt->real_halfopen() * count($pids);
		$shuffled[] = $pids[$key];
		array_splice($pids, $key, 1);
	}
	$shuffled[] = $pids[0];
	return $shuffled;
}

?>
