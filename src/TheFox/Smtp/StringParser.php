<?php

namespace TheFox\Smtp;

class StringParser{
	
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
	
	private function reset(){
		$this->argsId = -1;
		$this->args = array();
		$this->argsLen = 0;
	}
	
	private function fixPrev(){
		if($this->argsId >= 0){
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
		}
	}
	
	private function charNew($char = ''){
		if($this->argsMax === null || $this->argsLen < $this->argsMax){
			$this->fixPrev();
			$this->argsId++;
			$this->args[$this->argsId] = $char;
			$this->argsLen = count($this->args);
		}
		/*else{
			$this->charAppend($char);
		}*/
	}
	
	private function charAppend($char){
		if($this->argsId != -1){
			$this->args[$this->argsId] .= $char;
		}
		/*else{
			$this->charNew($char);
		}*/
	}
	
	public function parse(){
		$this->reset();
		
		$str = $this->str;
		$in = false;
		$prevChar = ' ';
		$endChar = '';
		
		for($pos = 0; $pos < $this->len; $pos++){
			
			$char = $str[$pos];
			$nextChar = ($pos < $this->len - 1) ? $str[$pos + 1] : '';
			
			#fwrite(STDOUT, 'pos: '.$pos.' /'.$char.'/'."\n");
			
			if($in){
				#fwrite(STDOUT, ' -> in'."\n");
				if($char == $endChar){
					if($pos == $this->len - 1 || $this->argsMax === null || $this->argsLen < $this->argsMax){
						if($char == '"'){
							$this->charAppend($char);
						}
						$in = false;
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
				#fwrite(STDOUT, ' -> not in: '.(int)($this->argsMax === null).' '.$this->argsLen.' '.$this->argsMax."\n");
				if($this->argsMax === null || $this->argsLen < $this->argsMax){
					if($char == '"'){
						$this->charNew($char);
						$endChar = '"';
						$in = true;
					}
					elseif($char == ' '){
						if($nextChar != ' ' && $nextChar != '"'){
							$this->charNew();
							$endChar = ' ';
							$in = true;
						}
					}
					else{
						$this->charNew($char);
						$endChar = ' ';
						$in = true;
					}
				}
				/*else{
					fwrite(STDOUT, ' -> char append'."\n");
					$this->charAppend($char);
				}*/
			}
			
			$prevChar = $char;
		}
		
		$this->fixPrev();
		
		return $this->args;
	}
	
}
