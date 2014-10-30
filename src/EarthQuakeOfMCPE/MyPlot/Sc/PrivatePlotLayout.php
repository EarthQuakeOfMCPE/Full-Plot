<?php

namespace EarthQuakeOfMCPE\MyPlot\Sc;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\OfflinePlayer;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\level\Explosion;
use pocketmine\event\block\BlockEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3 as Vector3;
use pocketmine\math\Vector2 as Vector2;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\block\Block;
use pocketmine\block\WallSign;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\entity\FallingBlock;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\entity\Arrow;
use pocketmine\item\Bow;
use pocketmine\event\entity\EntityDamageByEntityEvent;

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
class PrivatePlotLayout {
	public $plugin;
	public function __construct(PlotMain $plugin) {
		$this->plugin = $plugin;
	}
	public function plotTest(CommandSender $sender, Plot $newplot, $type) {
		$this->plugin->displayPlotInfo ( $sender, $newplot );
		
		$wallheighSize = $newplot->p1->y;
		if (is_numeric ( $newplot->plotGroundUp ) && $newplot->plotGroundUp > 0) {
			$wallheighSize = $newplot->p1->y + $newplot->plotGroundUp;
		} else {
			$wallheighSize = $newplot->p1->y + 5;
		}
		
		$level = $this->plugin->getServer ()->getLevelByName ( $newplot->plotLevelName );
		
		$pzs = $newplot->p3->z;
		$pzm = $newplot->p1->z;
		if ($newplot->p3->z > $newplot->p1->z) {
			$pzs = $newplot->p1->z;
			$pzm = $newplot->p3->z;
		}
		
		$pxs = $newplot->p1->x;
		$pxm = $newplot->p3->x;
		if ($newplot->p1->x > $newplot->p3->x) {
			$pxs = $newplot->p3->x;
			$pxm = $newplot->p1->x;
		}
		// $this->log ( "z" . $pzs . " " . $pzm . " x " . $pxs . " " . $pxm );
		
		// build it UP
		For($z = $pzs; $z <= $pzm; $z ++) {
			For($x = $pxs; $x <= $pxm; $x ++) {
				For($y = $newplot->p1->y; $y <= $wallheighSize; $y ++) {
					$bk = $level->getBlock ( new Vector3 ( $x, $y, $z ) );
					$key = round ( $bk->x ) . "*" . round ( $bk->y ) . "*" . round ( $bk->z );
					if ($type == "ON") {
						$this->resetBlock ( $bk, $level, 20 );
						$this->plugin->cachedPlotBlocks [$key] = $newplot;
					}
					if ($type == "OFF") {
						$this->resetBlock ( $bk, $level, 0 );
					}
					// $this->log ( TextFormat::GREEN . "block: " . $bk->x . " " . $bk->y . " " . $bk->z );
				}
			}
		}
		
		// build it DOWN
		$wallheighSize = 2;
		if (is_numeric ( $newplot->plotGroundDown ) && $newplot->plotGroundDown > 0) {
			$wallheighSize = $newplot->plotGroundDown;
		}
		For($z = $pzs; $z <= $pzm; $z ++) {
			For($x = $pxs; $x <= $pxm; $x ++) {
				For($y = 0; $y <= $wallheighSize; $y ++) {
					$bk = $level->getBlock ( new Vector3 ( $x, $y, $z ) );
					$key = round ( $bk->x ) . "*" . round ( $bk->y ) . "*" . round ( $bk->z );
					if ($type == "ON") {
						$this->resetBlock ( $bk, $level, 20 );
						$this->plugin->cachedPlotBlocks [$key] = $newplot;
					}
					if ($type == "OFF") {
						$this->resetBlock ( $bk, $level, 0 );
					}
				}
			}
		}
		$sender->sendMessage ( "zone de traçage rendu!" );
		$this->log ( "Mise à jour parcelle cache " . count ( $this->plugin->cachedPlotBlocks ) );
	}
	public function resetBlock(Block $block, Level $level, $blockType) {
		$players = $level->getPlayers ();
		foreach ( $players as $p ) {
			$pk = new UpdateBlockPacket ();
			$pk->x = $block->getX ();
			$pk->y = $block->getY ();
			$pk->z = $block->getZ ();
			$pk->block = $blockType;
			$pk->meta = 0;
			$p->dataPacket ( $pk );
			$p->getLevel ()->setBlockIdAt ( $block->getX (), $block->getY (), $block->getZ (), $pk->block );
			
			$pos = new Position ( $block->x, $block->y, $block->z );
			$block = $p->getLevel ()->getBlock ( $pos );
			$direct = true;
			$update = true;
			$p->getLevel ()->setBlock ( $pos, $block, $direct, $update );
		}
	}
	public function ScanArea(Level $level, $xx, $yy, $zz, $bsize) {
		// $wallheighSize = $this->pgin->getConfig ()->get ( "wallheight" );
		$bheight = $bsize;
		$wallheighSize = $yy + $bsize + $bheight;
		$xmax = $bsize + 3;
		$ymax = $bsize;
		
		For($z = 0; $z <= $xmax; $z ++) {
			For($x = 0; $x <= $xmax; $x ++) {
				For($y = 0; $y <= $wallheighSize; $y ++) {
					$mx = $xx + $x;
					$my = $yy + $y;
					$mz = $zz + $z;
					$bk = $level->getBlock ( new Vector3 ( $mx, $my, $mz ) );
					// $this->log ( TextFormat::GREEN . ".removed: " . $bk . " at " . $bk->x . " " . $bk->y . " " . $bk->z );
					$arena1 = new ProtectedArea ( $p1, $p2 );
					$inside = $arena1->isPointInCircle ( $xx, $yy, $zz, $pos->x, $pos->y );
					if ($inside == 1) {
						$this->log ( "inside circle:" . $inside );
						$this->resetBlock ( $bk, $level, 20 );
					}
				}
			}
		}
	}
	public function CircleRadiusChecking(Player $player) {
		

		$level = $player->getLevel ();
		$pos = $event->getPlayer ()->getPosition ();
		$pos->x = abs ( round ( $event->getPlayer ()->getFloorX () ) );
		$pos->y = abs ( round ( $event->getPlayer ()->getFloorY () ) );
		$pos->z = abs ( round ( $event->getPlayer ()->getFloorZ () ) );
		
	
		
		$p1 = new Position ( 123, 6, 105 );
		$p2 = new Position ( 123, 4, 105 );
		$radius = 5;
		
	
		
		$area1 = new Plot ( $this, "test1" );
		$area1->p1 = $p1;
		$area1->p2 = $p2;
		
		$inside = $area1->isPointInCircle ( $p1->x, $p1->y, $radius, $pos->x, $pos->y );
		if ($inside == 1) {
			$this->log ( "inside circle:" . $inside );
	
		}
	}
	
	
	public function plotMap(Level $level, $floorwidth, $floorheight, Block $block, $wallType, $count) {
		$dataX = $block->x;
		$dataY = $block->y-2;
		$dataZ = $block->z;

		for ($h=0; $h < $count; $h++) {			
			$this->buildWall($level, $floorwidth, $floorheight, $dataX, $dataY, $dataZ, $wallType);
			$dataX = $dataX + 10;
		}
		
	}

	public function renderFloor(Level $level, $floorwidth, $floorheight, Block $block, $blockType) {

		$x = $block->x;		
		$this->log ( "étage de rendu " . $floorwidth . " x " . $floorheight );

		for($rx = 0; $rx < $floorwidth; $rx ++) {
			$y = $block->y;
			for($ry = 0; $ry < $floorheight; $ry ++) {
				$z = $block->z;
				for($rz = 0; $rz < $floorwidth; $rz ++) {
					$rb = $level->getBlock ( new Vector3 ( $x, $y, $z ) );
					$this->resetBlock ( $rb, $level, $blockType );

					$z ++;
				}
				$y ++;
			}
			$x ++;
		}
	}
	
	
	public function buildWall(Level $level, $width, $height, $dataX, $dataY, $dataZ, $wallType) {

		$status = false;
		try {
			$doorExist = 0;
			$x = $dataX;
			for($rx = 0; $rx < $width; $rx ++) {
				$y = $dataY;
				for($ry = 0; $ry < $height; $ry ++) {
					$z = $dataZ;
					for($rz = 0; $rz < $width; $rz ++) {
						$rb = $level->getBlock ( new Vector3 ( $x, $y, $z ) );
						$this->resetBlock ( $rb, $level, 0 );

						if ($rx == ($width - 1) || $rz == ($width - 1) || $rx == 0 || $rz == 0 || $ry == ($width - 1) || $ry == 0) {
							if ($rx == 2 && $ry > 0 && $ry < ($width - 1)) {
								$this->resetBlock ( $rb, $level, $wallType);
							} else if ($ry == 0) {

								$this->resetBlock ( $rb, $level, 1);
							} else if ($ry == ($width - 1)) {

								$this->resetBlock ( $rb, $level, 0);
							} else if ($rx == 0 || $rz == 0) {
								$this->resetBlock ( $rb, $level, $wallType );
							} else if ($rx == ($width - 1)) {
								$this->resetBlock ( $rb, $level, $wallType );
							} else {
								$this->resetBlock ( $rb, $level, $wallType );
							}
						}
						$z ++;
					}
					$y ++;
				}
				$x ++;
			}
			// Crash Fixed By Prax
			$status = true;
		} catch ( \Exception $e ) {
			$this->log ( "Error:" . $e->getMessage () );
		}
		return $status;
	}
	

	public function log($msg) {
		$this->plugin->getLogger ()->info ( $msg );
	}
}