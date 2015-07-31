<?php

$imgW = 750;
$imgH = 1066;

// human skin
/*
$minarea = 500;
$minside = sqrt($minarea) * 0.6;
$minangle = 70 * 2 * 3.141592 / 360;
$retries = 250;
*/

// dragon skin
$minarea = 0;
$minside = 10;
$minangle = 70 * 3.141592 / 180;
$retries = 250;

function swap(&$a, &$b) {
	$x = $a;
	$a = $b;
	$b = $x;
}

function absmod($val, $mod) {
	$val %= $mod;
	if ($val < 0) {
		$val += $mod;
	}
	return $val;
}

function vecmod($vec) {
	$sum = 0;
	for ($dim = 0; $dim < count($vec); $dim++) {
		$sum += $vec[$dim] * $vec[$dim];
	}
	return sqrt($sum);
}

function vecnorm($orig) {
	$len = vecmod($orig);
	$norm = array();
	for ($i = 0; $i < count($orig); $i++) {
		$norm[] = $orig[$i] / $len;
	}
	return $norm;
}

function vecsum($a, $b) {
	$sum = array();
	if (is_array($b)) {
		for ($i = 0; $i < max(count($a), count($b)); $i++) {
			$sum[] = @$a[$i] + @$b[$i];
		}
	} else {
		for ($i = 0; $i < count($a); $i++) {
			$sum[] = @$a[$i] + $b;
		}
	}
	return $sum;
}

function vecmul($a, $b) {
	$sum = array();
	if (is_array($b)) {
		for ($i = 0; $i < max(count($a), count($b)); $i++) {
			$sum[] = @$a[$i] * @$b[$i];
		}
	} else {
		for ($i = 0; $i < count($a); $i++) {
			$sum[] = @$a[$i] * $b;
		}
	}
	return $sum;
}

function vechalf($a, $b) {
	return vecnorm(vecsum(vecnorm($a), vecnorm($b)));
}

function vecangle($v1, $v2) {
	return acos(($v1[0] * $v2[0] + $v1[1] * $v2[1]) / (vecmod($v1) * vecmod($v2)));
}

function vecrnd() {
	return vecnorm(array(mt_rand(), mt_rand()));
}

function poly_area($poly) {
	$sum = 0;
	for ($i = 0; $i < count($poly); $i++) {
		$last = ($i + 1) % count($poly);
		$sum = $sum + $poly[$i][0] * $poly[$last][1] - $poly[$i][1] * $poly[$last][0];
	}
	return $sum / 2;
}

function poly_side_vector($poly, $side) {
	$side = absmod($side, count($poly));
	$next = ($side + 1) % count($poly);
	return array($poly[$next][0] - $poly[$side][0], $poly[$next][1] - $poly[$side][1]);
}

function poly_side_len($poly, $side) {
	return vecmod(poly_side_vector($poly, $side));
}

function poly_vtx_angle($poly, $vtx) {
	$v1 = poly_side_vector($poly, $vtx);
	$v2 = poly_side_vector($poly, $vtx - 1);
	return vecangle($v1, $v2);
}

function poly_vtx_half_vector($poly, $vtx) {
	$v1 = poly_side_vector($poly, $vtx);
	$v2 = vecmul(poly_side_vector($poly, $vtx - 1), -1);
	return vechalf($v1, $v2);
}

function poly_perim($poly) {
	$sum = 0;
	for ($i = 0; $i < count($poly); $i++) {
		$sum += poly_side_len($poly, $i);
	}
	return $sum;
}

function poly_perimrel_to_siderel($poly, $perimrel) {
	if ($perimrel < 0 || $perimrel >= 1) {
		return null;
	}

	$perim = poly_perim($poly);
	$curpos = 0;
	for ($side = 0; $side < count($poly); $side++) {
		$siderel = poly_side_len($poly, $side) / $perim;
		if ($perimrel < $siderel) {
			return array('side' => $side, 'pos' => $perimrel / $siderel);
		}
		$perimrel -= $siderel;
	}
}

function poly_siderel_to_dot($poly, $siderel) {
	$side = $siderel['side'];
	$pos = $siderel['pos'];
	$v = poly_side_vector($poly, $side);
	return array($poly[$side][0] + $pos * $v[0], $poly[$side][1] + $pos * $v[1]);
}

function poly_slice($poly, $perimrel1, $perimrel2) {
	$siderel1 = poly_perimrel_to_siderel($poly, $perimrel1);
	$siderel2 = poly_perimrel_to_siderel($poly, $perimrel2);

	if ($siderel1['side'] == $siderel2['side']) {
		return null;
	}
	if ($siderel1['side'] > $siderel2['side']) {
		swap($siderel1, $siderel2);
	}

	$sliced = array(array(), array());
	for ($side = 0; $side < count($poly); $side++) {
		$sliced[0][] = $poly[$side];
		if ($siderel1['side'] == $side) {
			$newvtx = poly_siderel_to_dot($poly, $siderel1);
			$sliced[0][] = $newvtx;
			$sliced[1][] = $newvtx;
			swap($sliced[0], $sliced[1]);
			$siderel1 = $siderel2;
		}
	}

	return $sliced;
}

function poly_offset($srcpoly, $offset) {
	$newpoly = array();
	for ($vtx = 0; $vtx < count($srcpoly); $vtx++) {
		$newpoly[] = vecsum($srcpoly[$vtx], vecmul(poly_vtx_half_vector($srcpoly, $vtx), -$offset));
	}
	return $newpoly;
}

function poly_randomize($srcpoly, $offset) {
	$newpoly = array();
	for ($vtx = 0; $vtx < count($srcpoly); $vtx++) {
		$newpoly[] = vecsum($srcpoly[$vtx], vecsum(vecmul(vecrnd(), 2 * $offset), -$offset));
	}
	return $newpoly;
}

function validate_poly($poly, $minarea, $minside, $minangle) {
	if (poly_area($poly) < $minarea) {
		return false;
	}

	for ($side = 0; $side < count($poly); $side++) {
		if (poly_side_len($poly, $side) < $minside) {
			return false;
		}

		if (poly_vtx_angle($poly, $side) < $minangle) {
			return false;
		}
	}

	return true;
}

$work = array(array(array(0, 0), array($imgW, 0), array($imgW, $imgH), array(0, $imgH)));
$done = array();

$iter = 0;
while ($work) {
	$next = array();
	for ($workpoly = 0; $workpoly < count($work); $workpoly++) {
		$newpolys = null;

		if (poly_area($work[$workpoly]) >= 2 * $minarea) {
			for ($attempt = 0; $newpolys == null && $attempt < $retries; $attempt++) {
				$newpolys = poly_slice($work[$workpoly], (float) mt_rand() / mt_getrandmax(), (float) mt_rand() / mt_getrandmax());
				if ($newpolys) {
					if (!validate_poly($newpolys[0], $minarea, $minside, $minangle) || !validate_poly($newpolys[1], $minarea, $minside, $minangle)) {
						$newpolys = null;
					}
				}
			}
		}

		if ($newpolys) {
			$next[] = $newpolys[0];
			$next[] = $newpolys[1];
		} else {
			$done[] = $work[$workpoly];
		}
	}
	$work = $next;
	printf("%02d %04d %04d\n", $iter++, count($work), count($done));
}

$f = fopen("result.svg", "w");
fwrite($f, "<svg height=\"$imgH\" width=\"$imgW\">\n");
for ($poly = 0; $poly < count($done); $poly++) {
	fwrite($f, "<path d=\"");
	$modpoly = $done[$poly];
	//$modpoly = poly_offset($modpoly, -7);
	//$modpoly = poly_randomize($modpoly, 0.5);
	for ($vtx = 0; $vtx < count($modpoly); $vtx++) {
		if ($vtx == 0) {
			fwrite($f, "M");
		} else {
			fwrite($f, "L");
		}
		fwrite($f, $modpoly[$vtx][0] . ' ' . $modpoly[$vtx][1] . ' ');
	}
	fwrite($f, "Z\" />\n");
}
fwrite($f, "</svg>\n");
fclose($f);


