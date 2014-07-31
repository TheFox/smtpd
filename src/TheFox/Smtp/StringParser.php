<?php

namespace TheFox\Smtp;

class StringParser{
	
	const DEBUG = 0;
	
	private $str = '';
	private $len = 0;
	private $argsMax;
	private $argsId = -1;
	private $args = array();
	private $argsLen = 0;
	
	public function __construct($str, $argsMax = null){
		$this->str = $str;
		$this->str = trim($this->str);
		$this->len = strlen($this->str);
		$this->argsMax = $argsMax;
	}
	
	private function debug($text){
		if(static::DEBUG)
			fwrite(STDOUT, $text."\n");
	}
	
	private function reset(){
		$this->argsId = -1;
		$this->args = array();
		$this->argsLen = 0;
	}
	
	private function fixPrev(){
		if($this->argsId >= 0){
			#$this->debug('    fix old A /'.$this->args[$this->argsId].'/');
			if($this->args[$this->argsId]
				&& $this->args[$this->argsId][0] == '"'
				&& substr($this->args[$this->argsId], -1) == '"'
			){
				$tmp = substr(substr($this->args[$this->argsId], 1), 0, -1);
				if(strpos($tmp, '"') === false){
					$this->args[$this->argsId] = $tmp;
					$this->argsLen = count($this->args);
				}
			}
			#$this->debug('    fix old B /'.$this->args[$this->argsId].'/');
		}
	}
	
	private function charNew($char = ''){
		if($this->argsMax === null || $this->argsLen < $this->argsMax){
			$this->fixPrev();
			#$this->debug('    new /'.$char.'/');
			$this->argsId++;
			$this->args[$this->argsId] = $char;
			$this->argsLen = count($this->args);
		}
		else{
			$this->charAppend($char);
		}
	}
	
	private function charAppend($char){
		if($this->argsId == -1){
			$this->charNew($char);
		}
		else{
			#$this->debug('    append /'.$char.'/');
			$this->args[$this->argsId] .= $char;
		}
	}
	
	public function parse(){
		$this->reset();
		
		$str = $this->str;
		$in = false;
		$prevChar = ' ';
		$endChar = '';
		
		#$this->debug('len: '.$this->len);
		
		for($pos = 0; $pos < $this->len; $pos++){
			$char = $str[$pos];
			$nextChar = ($pos < $this->len - 1) ? $str[$pos + 1] : '';
			
			#$this->debug('raw '.$pos.'/'.$this->len.'['.$this->argsId.']: /'.$char.'/');
			
			if($in){
				#$this->debug('    in ');
				if($char == $endChar){
					#$this->debug('    is null: '.(int)($this->argsMax === null));
					#$this->debug('    is end char: '.$this->argsLen.', '.(int)$this->argsMax);
					
					if($pos == $this->len - 1 || $this->argsMax === null || $this->argsLen < $this->argsMax){
						if($char == '"'){
							$this->charAppend($char);
						}
						
						$in = false;
						#$this->debug('    close ');
					}
					else{
						$this->charAppend($char);
					}
				}
				else{
					$this->charAppend($char);
				}
			}
			else{
				if($this->argsMax === null || $this->argsLen < $this->argsMax){
					if($char == '"'){
						#$this->debug('    new Ab (next /'.$nextChar.'/)');
						$this->charNew($char);
						$endChar = '"';
						$in = true;
					}
					elseif($char == ' '){
						/*if($nextChar == ' '){
							#$this->debug('    new Ba (next / /)');
						}
						elseif($nextChar == '"'){
							#$this->debug('    new Bb (next /"/)');
						}
						else{
							#$this->debug('    new Bc (next /'.$nextChar.'/)');
							$this->charNew();
							$endChar = ' ';
							$in = true;
						}*/
						if($nextChar != ' ' && $nextChar != '"'){
							#$this->debug('    new Bc (next /'.$nextChar.'/)');
							$this->charNew();
							$endChar = ' ';
							$in = true;
						}
					}
					else{
						#$this->debug('    new C');
						$this->charNew($char);
						$endChar = ' ';
						$in = true;
					}
				}
				else{
					#$this->debug('    limit');
					$this->charAppend($char);
				}
			}
			
			#$this->debug('    text /'.$this->args[$this->argsId].'/');
			
			$prevChar = $char;
			
			#sleep(1);
		}
		
		$this->fixPrev();
		#ve($this->args);
		#exit();
		
		return $this->args;
	}
	
}
