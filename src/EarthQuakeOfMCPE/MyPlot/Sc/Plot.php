<?php

namespace EarthQuakeOfMCPE\MyPlot\Sc;

use pocketmine\math\Vector3 as Vector3;
use pocketmine\level\Position;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\Config;

/**
 * Private Plot PlugIn
 *
 *version 1.0.0
 *
 * Copyright (C) 2014Prathisnovcht CrackzNovitch
 * YouTube Channel:
 *
 * @ EarthQuakeOfMCPE
 *        
 */

class Plot {
	public $state;
	public $pgin;
	public $name;
	public $plotName;
	public $p1;
	public $p3;
	public $radius = 20;
	private $completed = false;
	public $owner;
	public $ownerName;	
	public $authorizedUsers=[];
	public $plotLevelName;
	public $password;
	
	//up blocks
	public $plotGroundUp=5;
	public $plotGroundDown=3;
	//down blocks
	//public or private	access	
	public $allowView = "private";
	public $allowPlaceBlock = "private";
	public $allowBreakBlock = "private";
	
	public function __construct(PrivatePlot $pg, $name, $owner){
		$this->pgin=$pg;
		if ($owner!=null) {
			//$this->owner = $owner;
			$this->ownerName = $owner->getName();
			$this->plotName = $name;
		}
		
		$this->$name = $name;
	}

	public function isComplete() {
		if ($this->plotName!=null && $this->p1!=null &&  $this->p3!=null) {
			$this->completed = true;
		}		
		return $this->completed;
	}
	
	
	public function save(){				
		$path = $this->pgin->getDataFolder() . "plots/";		
		$name = $this->plotName;
		$data = new Config($path . "$name.yml", Config::YAML);
		//$this->plotLevelName = $this->p1->getLevel()->getName();
		//this should not happen
		if ($this->plotLevelName==null) {
			$this->plotLevelName = "world";
			$this->pgin->getLogger()->info("Terrain Niveau Nom existait pas ".$this->plotLevelName);
		}
		
		$data->set("plotLevelName", $this->plotLevelName);
		$data->set("plotName", $this->plotName);
		$data->set("password", $this->password);
		$data->set("name", $this->name);		
		$data->set("point1X", $this->p1->x);
		$data->set("point1Y", $this->p1->y);
		$data->set("point1Z", $this->p1->z);
		$data->set("point3X", $this->p3->x);
		$data->set("point3Y", $this->p3->y);
		$data->set("point3Z", $this->p3->z);
		
		$data->set("allowView", $this->allowView);
		$data->set("allowPlaceBlock", $this->allowPlaceBlock);
		$data->set("allowBreakBlock", $this->allowBreakBlock);
		
		$data->set("plotGroundUp", $this->plotGroundUp);
		$data->set("plotGroundDown", $this->plotGroundDown);
		
		$data->set("authorizedUsers", $this->authorizedUsers);
		$data->set("ownerName", $this->ownerName);
		$data->save();
	}
	
	public function delete(){
		$path = $this->pgin->getDataFolder() . "plots/";		
		$name = $this->plotName;
		@unlink($path . "$name.yml");
	}
	
	public function isInRectable($centerX, $centerY, $radius, $x, $y) {
		return $x>=$centerX-$radius && $x<=$centerX + $radius && $y>=$centerY-$radius && $y<=$centerY + $radius;
	}

	public function isPointInCircle($centerX, $centerY, $radius, $x, $y) {
		if ($this->isInRectable($centerX,$centerY,$radius,$x,$y)) {
			$dx = $centerX- $x;
			$dy = $centerY - $y;
			$dx *= $dx;
			$dy *= $dy;
			$distanceSquared = $dx+$dy;
			$radiusSquared = $radius * $radius;
			return $distanceSquared <= $radiusSquared;  
		}
		return false;
	}
	
	public function inside(Position $p){
		//if($p->getLevel()->getName() == $this->p1->getLevel()->getName()){
		$bx = $this->between(round($this->p1->x), round($p->x), round($this->p2->x));
		$by = $this->between(round($this->p1->y), round($p->y), round($this->p2->y));
		$bz = $this->between(round($this->p1->z), round($p->z), round($this->p2->z));
				
		//Server::getInstance()->broadcastMessage("bx:".$bx." by:".$by." bz:".$bz);		
		if ($bx == 1 && $by==1 && $bz==1) {			
			//if (round($p->x) < round($this->p1->x) && round($p->z) < round($this->p1->z)) {
				return 1;
			//} 		
		} 
		return 0;
	}
	
	public function between($l, $m, $r){
		$lm = abs($l - $m);
		$rm = abs($r - $m);
		$lrm = $lm + $rm;
		$lr = abs($l - $r);
		//Server::getInstance()->broadcastMessage("lrm:".$lrm." lr:".$lr);
		if ($lrm <= $lr) {
			return 1;
		}
		return 0;
	}
	
}