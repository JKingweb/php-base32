<?php
namespace base32;

function encode($str, $hex = FALSE, $pad = TRUE) {
	$out = "";
	$a = 0;
	$m = strlen($str);
	$ab = ($hex) ?
		["0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V"]:
		["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","2","3","4","5","6","7"];
	for($a = 0; $a < $m; $a++) {
		$b1 = ord($str[$a]);
		$out .= $ab[$b1 >> 3];
		if(++$a < $m) {$b2 = ord($str[$a]);} else {$b2 = 0;}
		$out .= $ab[((255 >> 5 & $b1) << 2) + ($b2 >> 6)];
		if($a >= $m) break;
		$out .= $ab[255 >> 3 & $b2 >> 1];
		$b1 = $b2;
		if(++$a < $m) {$b2 = ord($str[$a]);} else {$b2 = 0;}
		$out .= $ab[((255 >> 7 & $b1) << 4) + ($b2 >> 4)];
		$b1 = $b2;
		if(++$a < $m) {$b2 = ord($str[$a]);} else {$b2 = 0;}
		$out .= $ab[((255 >> 4 & $b1) << 1) + ($b2 >> 7)];
		if($a >= $m) break;
		$out .= $ab[255 >> 3 & $b2 >> 2];
		$b1 = $b2;
		if(++$a < $m) {$b2 = ord($str[$a]);} else {$b2 = 0;}
		$out .= $ab[((255 >> 6 & $b1) << 3) + ($b2 >> 5)];
		if($a >= $m) break;
		$out .= $ab[255 >> 3 & $b2];
	}
	$padn = [0, 8=>6,16=>4,24=>3,32=>1];
	$padn = ($pad) ? $padn[$m * 8 % 40] : 0;
	$out .= str_repeat("=", $padn);
	return $out;
}

function decode($str, $hex = FALSE, $pad = TRUE, $strict = TRUE) {
	$out = "";
	$err = [];
	$m = strlen($str);
	$ab = ($hex) ?
		['='=>0,'0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'A'=>10,'B'=>11,'C'=>12,'D'=>13,'E'=>14,'F'=>15,'G'=>16,'H'=>17,'I'=>18,'J'=>19,'K'=>20,'L'=>21,'M'=>22,'N'=>23,'O'=>24,'P'=>25,'Q'=>26,'R'=>27,'S'=>28,'T'=>29,'U'=>30,'V'=>31]:
		['='=>0,'A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6,'H'=>7,'I'=>8,'J'=>9,'K'=>10,'L'=>11,'M'=>12,'N'=>13,'O'=>14,'P'=>15,'Q'=>16,'R'=>17,'S'=>18,'T'=>19,'U'=>20,'V'=>21,'W'=>22,'X'=>23,'Y'=>24,'Z'=>25,'2'=>26,'3'=>27,'4'=>28,'5'=>29,'6'=>30,'7'=>31];
	$pattern = ($hex)?"/[^0-9A-V=]/":"/[^A-Z2-7=]/";
	if ($strict) {
		if(preg_match($pattern, $str, $err)) {
			$errc = $err[0][1];
			throw new Base32Exception("Invalid character '".$str[$errc]."' at position $errc.", 1);
		}
		if(preg_match("/=[^=]/", $str, $err)) {
			$errc = $err[0][1] + 1;
			throw new Base32Exception("Invalid character '".$str[$errc]."' after start of padding at position $errc.", 2);
		}
	} else {
		$str = strtoupper($str);
		$str = preg_replace($pattern, "", $str);
		$str = preg_replace("/=[^=]+/", "=", $str);
	}
	if($pad) {
		if($m % 8 > 0) throw new Base32Exception("Premature end of input; expecting padding at position $m.", 3);
	} else {
		$str .= str_repeat("=", (8 - $m % 8) % 8);
	}
	$m = strpos($str, "=");
	$m = ($m===FALSE) ? strlen($str) : $m;
	for($a = 0; $a < $m; $a++) {
		$c1 = $ab[$str[$a]];
		if(++$a < $m) {$c2 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		$out .= chr(($c1 << 3) + ($c2 >> 2));
		if($a+1 >= $m) break;
		$c1 = $c2;
		if(++$a < $m) {$c2 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		if(++$a < $m) {$c3 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		$out .= chr(((31 >> 3 & $c1) << 6) + ($c2 << 1) + (31 >> 4 & $c3 >> 4));
		if($a+1 >= $m) break;
		$c1 = $c3;
		if(++$a < $m) {$c2 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		$out .= chr(((31 >> 1 & $c1) << 4) + ($c2 >> 1));
		if($a+1 >= $m) break;
		$c1 = $c2;
		if(++$a < $m) {$c2 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		if(++$a < $m) {$c3 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		$out .= chr(((31 >> 4 & $c1) << 7) + ($c2 << 2) + (31 >> 3 & $c3 >> 3));
		if($a+1 >= $m) break;
		$c1 = $c3;
		if(++$a < $m) {$c2 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		$out .= chr(((31 >> 2 & $c1) << 5) + $c2);
	}
	return $out;
}

class Base32Exception Extends \Exception {
}