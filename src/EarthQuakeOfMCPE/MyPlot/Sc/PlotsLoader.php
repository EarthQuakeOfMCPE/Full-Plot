<?php

namespace EarthQuakeOfMCPE\MyPlot\Sc;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\level\Explosion;
use pocketmine\level\Position;

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
class PlotsLoader extends PluginTask {
	public $plugin;
	
	public function __construct(PlotMain $plugin) {
		$this->plugin = $plugin;
		parent::__construct ( $plugin );
	}
	
	public function onRun($ticks) {
		$this->plugin->log("Terrain privé chargeur course...");
		$this->plugin->loadAllPlotFiles();
	}
	
	public function onCancel() {

	}
}
