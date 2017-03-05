<?php

namespace TheFox\Smtp;

class Thread{
	
	private $exit = 0;
	
	public function setExit($exit = 1){
		$this->exit = $exit;
	}
	
	public function getExit(){
		return (int)$this->exit;
	}
	
}
