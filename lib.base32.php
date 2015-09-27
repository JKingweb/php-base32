<?php
/*
   RFC 4648 base32 library for PHP
    by J. King (http://jkingweb.ca/)
   Licensed under MIT license

   Last revised 2014-09-06
*/

/*
Copyright (c) 2015 J. King

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/


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
		$out .= $ab[(0b11100 & $b1 << 2) + ($b2 >> 6)];
		if($a >= $m) break;
		$out .= $ab[0b11111 & $b2 >> 1];
		$b1 = $b2;
		if(++$a < $m) {$b2 = ord($str[$a]);} else {$b2 = 0;}
		$out .= $ab[(0b10000 & $b1 << 4) + ($b2 >> 4)];
		$b1 = $b2;
		if(++$a < $m) {$b2 = ord($str[$a]);} else {$b2 = 0;}
		$out .= $ab[(0b11110 & $b1 << 1) + ($b2 >> 7)];
		if($a >= $m) break;
		$out .= $ab[0b11111 & $b2 >> 2];
		$b1 = $b2;
		if(++$a < $m) {$b2 = ord($str[$a]);} else {$b2 = 0;}
		$out .= $ab[(0b11000 & $b1 << 3) + ($b2 >> 5)];
		if($a >= $m) break;
		$out .= $ab[0b11111 & $b2];
	}
	$out .= str_repeat("=", ($pad) ? (8 - strlen($out) % 8) % 8 : 0);
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
		$out .= chr(((0b00011 & $c1) << 6) + ($c2 << 1) + (0b00001 & $c3 >> 4));
		if($a+1 >= $m) break;
		$c1 = $c3;
		if(++$a < $m) {$c2 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		$out .= chr(((0b01111 & $c1) << 4) + ($c2 >> 1));
		if($a+1 >= $m) break;
		$c1 = $c2;
		if(++$a < $m) {$c2 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		if(++$a < $m) {$c3 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		$out .= chr(((0b00001 & $c1) << 7) + ($c2 << 2) + (0b00011 & $c3 >> 3));
		if($a+1 >= $m) break;
		$c1 = $c3;
		if(++$a < $m) {$c2 = $ab[$str[$a]];} else {throw new Base32Exception("Premature end of input; expecting digit at position $m.", 4);}
		$out .= chr(((0b00111 & $c1) << 5) + $c2);
	}
	return $out;
}

class Base32Exception Extends \Exception {
}
