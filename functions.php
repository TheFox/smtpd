<?php

function ve($v = null){
	try{
		$rv = var_export($v, true);
		#print "\n";
		fwrite(STDOUT, $rv."\n");
	}
	catch(Exception $e){
		print "ERROR: ".$e->getMessage()."\n";
	}
}

function vej($v = null){
	try{
		ve(json_encode($v));
	}
	catch(Exception $e){
		print "ERROR: ".$e->getMessage()."\n";
	}
}
