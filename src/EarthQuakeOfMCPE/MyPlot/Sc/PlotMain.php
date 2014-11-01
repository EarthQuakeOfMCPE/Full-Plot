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
class PlotMain extends PluginBase implements Listener {
	
	//Priivate Plugins
	public $plots = [ ];
	public $workingCopies = [ ];
	public $block_display_flag = 0;
	public $editMode = 0;
	public $cachedPlotBlocks = [ ];
	public $plotLayout;
	public $authenticatedUsers = [ ];
	
	/**
	 * OnLoad
	 * (non-PHPdoc)
	 *
	 * @see \pocketmine\plugin\PluginBase::onLoad()
	 */
	public function onLoad() {
	}
	
	/**
	 * OnEnable
	 *
	 * (non-PHPdoc)
	 *
	 * @see \pocketmine\plugin\PluginBase::onEnable()
	 */
	public function onEnable() {
		if (! file_exists ( $this->getDataFolder () . "config.yml" )) {
			@mkdir ( $this->getDataFolder () );
			file_put_contents ( $this->getDataFolder () . "config.yml", $this->getResource ( "config.yml" ) );
		}
		// read restriction
		// $this->config = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
		$this->getConfig ()->getAll ();
		$this->log ( TextFormat::GREEN . "####################################" );
		$this->log ( TextFormat::GREEN . "####################################" );
		$this->log ( TextFormat::GREEN . "####################################" );
		$this->log ( TextFormat::GREEN . "##MyPplot  Team EarthQuakeOfMCPE####" );
	    $this->log ( TextFormat::GREEN     . "#####################################" );
		$this->log ( TextFormat::GREEN . "#####################################" );
		$this->log ( TextFormat::GREEN . "#####################################" );
		// $this->loadPlots ();
		
		$plotLoadingTask = new PlotsLoader ( $this );
		$this->getServer ()->getScheduler ()->scheduleDelayedTask ( $plotLoadingTask, 50 );
		$this->log ( TextFormat::GREEN . "-parcelle chargeur à exécuter dans 50 s" );
		
		$this->log ( TextFormat::GREEN . "-------------------------------------------------" );
		$this->enabled = true;
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
		$this->log ( TextFormat::GREEN . "- EarthQuakeOfMCPE|MyPlot - Enabled!" );
		
		$this->workingCopies = [ ];
		$this->plotLayout = new PrivatePlotLayout ( $this );
	}
	
	/**
	 * OnDisable
	 * (non-PHPdoc)
	 *
	 * @see \pocketmine\plugin\PluginBase::onDisable()
	 */
	public function onDisable() {
		$this->log ( TextFormat::RED . "EarthQuakeOfMCPE|MyPlot - Disabled" );
		$this->enabled = false;
	}
	
	/**
	 * OnCommand
	 * (non-PHPdoc)
	 *
	 * @see \pocketmine\plugin\PluginBase::onCommand()
	 */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if (! ($sender instanceof Player)) {
			$sender->sendMessage ( "Cette commande ne fonctionne que le mode de jeu!" );
			return;
		}
		if ((strtolower ( $command->getName () ) == "pplot") && isset ( $args [0] )) {
			//$this->log ( $command->getName () . " " . count ( $args ) . " " . $args [0] );
			
			if (strtolower ( $args [0] ) == "blockon") {
				$this->block_display_flag = 1;
				$sender->sendMessage ( "==bloc affichage de la position ON==" );
			}
			
			if (strtolower ( $args [0] ) == "blockoff") {
				$this->block_display_flag = 0;
				$sender->sendMessage ( "==bloc affichage de la position OFF==" );
			}
			
			//authenticate user
			$authenticatedUsers;
			if (strtolower($args[0]) == "login") {
				
				if (!isset ( $args [1]) && !isset ( $args [2])) {
					$sender->sendMessage ( "*utilisation incorrecte: / pplot connexion [nom de la parcelle] [mot de passe]" );
					return;
				}
				$plotName = $sender->getName () . "_" . $args [1];
				if (! isset ( $this->plots [$plotName] )) {
					$sender->sendMessage ( "Nom de la parcelle ne se trouve pas!" );
					return;
				}				
				$newplot = $this->plots [$plotName];				
				if ($newplot->password!=$args [2]) {
					$sender->sendMessage ( "* Échec de la connexion mot de passe incorrect !,." );
					return;						
				}
				$player = $sender->getPlayer();	
				$loginkey = $player->getName()."_".$player->getAddress();			
				$this->authenticatedUsers[$loginkey] = $args [2];				
				$sender->sendMessage ( "Nous saluons le retour de [".$sender->getName()."], succès connexion!" );
				return;				
			}
			
			
			if (strtolower ( $args [0] ) == "create") {

				//check player existing plot count
				$plotcount = $this->getPlayerPlotsCount($sender->getName());
				$maxAllowPlots = $this->getConfig ()->get ( "max_plots_per_player" );
				if ($maxAllowPlots==null) {
					$sender->sendMessage ( "Attention! max parcelles par la configuration du lecteur" );
					$this->log("manquant max permet parcelles par la configuration du lecteur. utilisation par défaut 3");
					return;
				}
				if ($plotcount==null) {
					$plotcount==0;
				}
				if ($plotcount > $maxAllowPlots) {
					$sender->sendMessage ( "Attention! Créer un nouveau Terrain arrêté!" );
					$sender->sendMessage ( "Vous avez plus de permettre parcelles par joueur - ".$plotcount );					
					return;
				}
				
				$sender->sendMessage ( "==Création d'un nouveau terrain==" );
				if (!isset ( $args [1]) && !isset ( $args [2])) {
					$sender->sendMessage ( "Une utilisation incorrecte: / pplot créer [nom] [mot de passe]" );
					return;
				}
				$this->editMode = 1;
				// prefix with player name
				
				$plotName = $sender->getName () . "_" . $args [1];
				
				if (isset ( $this->plots [$plotName] )) {
					$sender->sendMessage ( "Attention! nom de la parcelle déjà exister !. se il vous plaît utiliser un autre nom!" );
					return;
				}
				$newplot = new Plot ( $this, $plotName, $sender );
				$newplot->name = $args [1];
				$newplot->password = $args [2];
				
				// capture level name here
				$newplot->plotLevelName = $sender->getPlayer ()->getLevel ()->getName ();
				$this->log ( "parcelle ensemble nom de niveau " . $newplot->plotLevelName );
				
				$this->workingCopies [$sender->getName ()] = $newplot;
				$sender->sendMessage ( "créé à la copie de travail du nouveau nom de la parcelle située à côté des positions de l'intrigue, utilisez [/ pplot setpos1] et [/ pplot setpos3]" );
				return;
			}
			
			if (strtolower ( $args [0] ) == "update") {
				$sender->sendMessage ( "=Créer une copie de travail de terrain enregistrée existante=" );
				if (! isset ( $args [1] )) {
					$sender->sendMessage ( "manquante nom de la parcelle! utilisation: / update à jour de pplot [nom]" );
					return;
				}
				$this->editMode = 1;
				// prefix with player name
				
				$plotName = $sender->getName () . "_" . $args [1];
				if (! isset ( $this->plots [$plotName] )) {
					$sender->sendMessage ( "Nom de la parcelle ne se trouve pas " . $plotName );
					return;
				}
				$newplot = $this->plots [$plotName];
				$this->workingCopies [$sender->getName ()] = $newplot;
				$sender->sendMessage ( "créé une copie de travail du complot, maintenant vous pouvez apporter des modifications" );
				return;
			}
			
			if (strtolower ( $args [0] ) == "setpos1") {
				if ($this->editMode == 0) {
					$sender->sendMessage ( "copie de travail de terrain ne existe pas, de changer première édition / update à jour de pplot [nom de la parcelle]" );
					return;
				}
				$sender->sendMessage ( "==Réglez travail parcelle Position #1 ==" );
				$newplot = $this->workingCopies [$sender->getName ()];
				$newplot->state = "setpos1";
				$this->workingCopies [$sender->getName ()] = $newplot;
				$this->editMode = 1;
				$sender->sendMessage ( "Pour compléter setpos1, sélectionnez une position de la pause qui bloquent" );
				// $this->displayPlotInfo ( $sender, $newplot );
				return;
			}
			if (strtolower ( $args [0] ) == "setpos3") {
				if ($this->editMode == 0) {
					$sender->sendMessage ( "copie de travail de terrain ne existe pas, de changer première édition / update à jour de pplot [nom de la parcelle]" );
					return;
				}
				$sender->sendMessage ( "==Réglez travail parcelle Position #3 ==" );
				$newplot = $this->workingCopies [$sender->getName ()];
				$newplot->state = "setpos3";
				$this->workingCopies [$sender->getName ()] = $newplot;
				$this->editMode = 1;
				$sender->sendMessage ( "Pour compléter setpos3, sélectionnez une position de la pause qui bloquent" );
				// $this->displayPlotInfo ( $sender, $newplot );
				return;
			}
			if (strtolower ( $args [0] ) == "delete") {
				$sender->sendMessage ( "==Supprimer Terrain enregistrée existante==" );
				if (isset ( $args [1] )) {
					// $plotName = $args [1];
					$plotName = $sender->getName () . "_" . $args [1];
					$sender->sendMessage ( "delete Plot " . $plotName );
					if (! isset ( $this->plots [$plotName] )) {
						$sender->sendMessage ( "Nom de la parcelle ne se trouve pas!" );
						return;
					}
										
					$newplot = $this->plots [$plotName];
					if ($newplot != null) {
						
						$player =$sender->getPlayer();
						$loginkey = $player->getName()."_".$player->getAddress();
						if (!isset($this->authenticatedUsers[$loginkey])) {
							$player->sendMessage("-------------------------");
							$player->sendMessage ( "avertissement!!!" );
							$player->sendMessage ( "propriétaire de la parcelle seulement[" . $ownerName . "] avoir accès supprimer!" );
							$player->sendMessage ( "Si vous êtes le propriétaire, se il vous plaît vous connecter d'abord " );
							$player->sendMessage ( "/pplot login[nom de la parcelle] [mot de passe]" );
							$player->sendMessage("-------------------------");
							return;
						}
						
						// delete file
						$newplot->delete ();
						$sender->sendMessage ( "Fichier Terrain supprimé!" );
						// removed from cache
						$plevel;
						if ($newplot->plotLevelName != null) {
							$$plevel = $this->getServer ()->getLevelByName ( $newplot->plotLevelName );
						}
						$this->removecachePlotBlocks ( $newplot, $plevel );
						$sender->sendMessage ( "cache effacé" );
						$sender->sendMessage ( "fini!" );
					} else {
						$sender->sendMessage ( "Plot pas trouvé!" );
					}
				} else {
					$sender->sendMessage ( "Usage /pplot delete [nom de la parcelle]" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "save") {
				$sender->sendMessage ( "==Enregistrer ma copie de travail intrigue change==" );
				$newplot = $this->workingCopies [$sender->getName ()];
				$newplot->state = "save";
				if ($newplot->isComplete ()) {
					$newplot->save ();
					$sender->sendMessage ( "Terrain enregistré en tant que " . $newplot->plotName . ".yml" );
					$sender->sendMessage ( "-vous pouvez quitter travaille maintenant par[/pplot exit]" );
					$sender->sendMessage ( "-ou continuer à faire d'autres changements" );
					$sender->sendMessage ( "-à voir sauver problème de fichier [/pplot view [$newplot->name]" );
					$sender->sendMessage ( "-à voir copie de travail actuel [/pplot work]" );
					return;
				} else {
					$sender->sendMessage ( "Terrain pas encore terminée. se il vous plaît mettre tous les 4 position devant sauver!" );
					return;
				}
				
				$this->workingCopies [$sender->getName ()] = $newplot;
				$this->plots [$newplot->plotName] = $newplot;
				if ($newplot->plotLevelName != null) {
					$$plevel = $this->getServer ()->getLevelByName ( $newplot->plotLevelName );
				}
				// update cache
				$this->cachePlotBlocks ( $newplot, $plevel );
				// $sender->sendMessage ( "updated!" );
				
				$sender->sendMessage ( "continuer ou tapez /pplot exit à compléter." );
				$this->editMode = 1;
				return;
			}
			
			if (strtolower ( $args [0] ) == "work") {
				$sender->sendMessage ( "==Voir ma copie de travail Terrain==" );
				$newplot = $this->workingCopies [$sender->getName ()];
				$this->displayPlotInfo ( $sender, $newplot );
				return;
			}
			
			if (strtolower ( $args [0] ) == "myplots") {
				$sender->sendMessage ( "==Inscrivez Mes Parcelles==" );
				$this->listPlayerPlots ( $sender, $sender->getName () );
				return;
			}
			
			if (strtolower ( $args [0] ) == "allplots") {
				$sender->sendMessage ( "== Liste tous les Plot==" );
				$newplot = $this->plots [$sender->getName ()];
				$this->listAllPlots ( $sender, $sender->getName () );
				return;
			}
			
			if (strtolower ( $args [0] ) == "adduser") {
				if ($this->editMode == 0) {
					$sender->sendMessage ( "copie de travail de terrain ne existe pas, de changer première question /pplot update [plot nom]" );
					return;
				}
				$sender->sendMessage ( "==Ajouter un utilisateur autorisé de terrain de travail==" );
				$plotName;
				$user;
				if (isset ( $args [1] )) {
					$user = $args [1];
				}
				
				$newplot = $this->workingCopies [$sender->getName ()];
				if ($newplot != null) {
					$newplot->authorizedUsers [] = $user;
					// update block access
					$this->workingCopies [$sender->getName ()] = $newplot;
					$this->displayPlotInfo ( $sender, $newplot );
					$sender->sendMessage ( "Ajouté utilisateur de tracer " . $newplot->name );
				} else {
					$sender->sendMessage ( "Terrain non trouvé!" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "deluser") {
				if ($this->editMode == 0) {
					$sender->sendMessage ( "copie de travail de terrain ne existe pas, de changer première question /pplot update [plot nom]" );
					return;
				}
				$sender->sendMessage ( "==Supprimer Authorisation utilisateur de Terrain travail==" );
				$plotName;
				$user;
				
				if (isset ( $args [1] )) {
					$user = $args [1];
				}
				if (! isset ( $this->workingCopies [$sender->getName ()] )) {
					$sender->sendMessage ( "Nom de la parcelle ne se trouve pas!" );
					return;
				}
				$newplot = $this->workingCopies [$sender->getName ()];
				if ($newplot != null) {
					// if (isset ( $newplot->authorizedUsers [$user] )) {
					unset ( $newplot->authorizedUsers [$user] );
					// update block access
					$this->workingCopies [$sender->getName ()] = $newplot;
					
					$this->displayPlotInfo ( $sender, $newplot );
					$sender->sendMessage ( "Utilisateur retiré de l'intrigue " . $newplot->name );
					// }
				} else {
					$sender->sendMessage ( "Plot pas trouvé!" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "view") {
				$sender->sendMessage ( "==Voir un terrain Saved==" );
				if (isset ( $args [1] )) {
					// $plotName = $args [1];
					$plotName = $sender->getName () . "_" . $args [1];
					if (! isset ( $this->plots [$plotName] )) {
						$sender->sendMessage ( "Nom de la parcelle non trouvé!" );
						return;
					}
					$newplot = $this->plots [$plotName];
					if ($newplot != null) {
						$this->displayPlotInfo ( $sender, $newplot );
					} else {
						$sender->sendMessage ( "Terrain non trouvé!" );
					}
				} else {
					$sender->sendMessage ( "Usage /pplot view [plot nom]" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "exit") {
				$sender->sendMessage ( "==Sortie Terrain travail==" );
				$newplot = $this->workingCopies [$sender->getName ()];
				$newplot->state = "exit";
				if ($newplot->isComplete ()) {
					$newplot->save ();
					$sender->sendMessage ( "Plot saved!" );
					
					$this->plots [$newplot->plotName] = $newplot;
					
					// show all records
					// $this->listAllPlots($sender, $sender->getName());
					// update cache
					unset ( $this->workingCopies [$sender->getName ()] );
					
					$plevel;
					if ($newplot->plotLevelName != null) {
						$plevel = $this->getServer ()->getLevelByName ( $newplot->plotLevelName );
					}
					$this->cachePlotBlocks ( $newplot, $plevel );
				}
				
				$this->editMode = 0;
				return;
			}
			
			if (strtolower ( $args [0] ) == "setview") {
				if ($this->editMode == 0) {
					$sender->sendMessage ( "copie de travail de terrain ne existe pas, de changer première question/pplot update [plot nom]" );
					return;
				}
				$sender->sendMessage ( "==réglage [Plot View] Access==" );
				if (isset ( $args [1] )) {
					$newplot = $this->workingCopies [$sender->getName ()];
					if ($newplot == null) {
						$sender->sendMessage ( "Terrain non trouvé!" );
					}
					if ($args [1] == "public" || $args [1] == "private") {
						$newplot->allowView = $args [1];
						// update block access
						$this->workingCopies [$sender->getName ()] = $newplot;
						$sender->sendMessage ( "fait! vue de l'intrigue accès mis à [" . $args [1] . "]" );
						return;
					}
				} else {
					$sender->sendMessage ( "Usage /pplot setViewAccess [plot nom] [public|private]" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "setplaceblock") {
				if ($this->editMode == 0) {
					$sender->sendMessage ( "copie de travail de terrain ne existe pas, de changer première question /pplot update [plot nom]" );
					return;
				}
				$sender->sendMessage ( "Réglage de terrain de travail [Bloquer Lieu] Accès" );
				$newplot = $this->workingCopies [$sender->getName ()];
				if ($newplot == null) {
					$sender->sendMessage ( "Plot pas trouvé!" );
				}
				if ($args [1] == "public" || $args [1] == "private") {
					$newplot->allowPlaceBlock = $args [1];
					// update block access
					$this->workingCopies [$sender->getName ()] = $newplot;
					$sender->sendMessage ( "fait! vue de l'intrigue accès mis à [" . $args [1] . "]" );
					return;
				} else {
					$sender->sendMessage ( "Usage /pplot setPlaceBlockAccess [plot nom] [public|private]" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "setbreakblock") {
				if ($this->editMode == 0) {
					$sender->sendMessage ( "copie de travail de terrain ne existe pas, de changer première question /pplot update [plot nom]" );
					return;
				}
				$sender->sendMessage ( "==Réglage parcelle travail sur l'accès [Bloquer Pause]==" );
				$sender->sendMessage ( "Réglage de terrain de travail [Bloquer Lieu] Accès" );
				$newplot = $this->workingCopies [$sender->getName ()];
				if ($newplot == null) {
					$sender->sendMessage ( "Plot pas trouvé!" );
				}
				if ($args [1] == "public" || $args [1] == "private") {
					$newplot->allowBreakBlock = $args [1];
					// update block access
					$this->workingCopies [$sender->getName ()] = $newplot;
					$sender->sendMessage ( "fait! vue de l'intrigue accès mis à [" . $args [1] . "]" );
					return;
				} else {
					$sender->sendMessage ( "Usage /pplot setBlockPlaceAccess [plot nom] [public|private]" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "setheight") {
				if ($this->editMode == 0) {
					$sender->sendMessage ( "copie de travail de terrain ne existe pas, de changer première question /pplot update [plot nom]" );
					return;
				}
				$sender->sendMessage ( "==Réglage de terrain de travail [Height]==" );
				if (isset ( $args [1] )) {
					//validate allowable height
					$maxAllowPlotHeight = $this->getConfig ()->get ( "plot_max_height" );
					if ($maxAllowPlotHeight==null) {
						$sender->sendMessage ( "Attention! max Plot configuration de hauteur manquant! se il vous plaît contacter l'administrateur." );
						$this->log("manquant réglages de la hauteur de l'intrigue max");
						return;
					}					
					$up = $args [1];	
					if (!is_numeric($up)) {
						$sender->sendMessage ( "Attention! Profondeur doit être une valeur numérique.");
						return;
					}						
					if ($up > $maxAllowPlotHeight) {
						$sender->sendMessage ( "Attention! Max permet hauteur est ".$maxAllowPlotHeight);						
						return;
					}					
					$newplot = $this->workingCopies [$plotName];
					if ($newplot == null) {
						$sender->sendMessage ( "* Plot non trouvé! *" );
					}
					if (is_numeric ( $up ) && $up > 1 && $up < $maxAllowPlotHeight) {
						$newplot->plotGroundUp = $up;
						$this->workingCopies [$plotName] = $newplot;
						
						$sender->sendMessage ( "fait! Hauteur terrain mis à [" . $args [2] . "]" );
					} else {
						$sender->sendMessage ( "Usage /pplot setHeight [plot nom] [1..128]" );
					}
					return;
				} else {
					$sender->sendMessage ( "Usage /pplot setBlockPlaceAccess [plot nom] [public|private]" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "setdepth") {
				if ($this->editMode == 0) {
					$sender->sendMessage ( "copie de travail de terrain ne existe pas, de changer première question /pplot update [plot nom]" );
					return;
				}
				$sender->sendMessage ( "==Réglage de terrain de travail [Profondeur]==" );
				if (isset ( $args [1] )) {
										
					//validate allowable height
					$maxAllowPlotDepth = $this->getConfig ()->get ( "plot_max_depth" );
					if ($maxAllowPlotDepth==null) {
						$sender->sendMessage ( "Attention! max Plot configuration de profondeur manquant! se il vous plaît contacter l'administrateur." );
						$this->log("manquant de la profondeur de l'intrigue max");
						return;
					}
					$down = $args [1];
					if (!is_numeric($down)) {
						$sender->sendMessage ( "Attention! Profondeur doit être une valeur numérique.");
						return;
					}					
					if ( $down < 0  || $down > $maxAllowPlotDepth) {
						$sender->sendMessage ( "Attention! Valeur max de hauteur est ".$maxAllowPlotDepth);
						return;
					}
					
					$newplot = $this->workingCopies [$plotName];
					if ($newplot == null) {
						$sender->sendMessage ( "* Plot non trouvé *" );
					}

					if (is_numeric ( $down ) && $down > 1 && $down < $maxAllowPlotDepth) {
						$newplot->plotGroundDown = $down;
						$this->workingCopies [$plotName] = $newplot;
						$sender->sendMessage ( "fait! parcelle Depth est réglé à [" . $args [2] . "]" );
					} else {
						$sender->sendMessage ( "Usage /pplot setDepth [plot nom] [1..128]" );
					}
					return;
				} else {
					$sender->sendMessage ( "Usage /pplot setBlockPlaceAccess [plot nom] [public|private]" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "teston") {
				$sender->sendMessage ( "==Plot test view ON==" );
				if (isset ( $args [1] )) {
					// $plotName = $args [1];
					$plotName = $sender->getName () . "_" . $args [1];
					if (! isset ( $this->plots [$plotName] )) {
						$sender->sendMessage ( "Plot nom pas trouvé!" );
						return;
					}
					
					$newplot = $this->plots [$plotName];					
					if ($newplot != null) {						
						$this->plotLayout->plotTest ( $sender, $newplot, "ON" );
					} else {
						$sender->sendMessage ( "Plot non trouvé!" );
					}
				} else {
					$sender->sendMessage ( "Usage /pplot teston [plot nom]" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "testoff") {
				$sender->sendMessage ( "==Plot Voir le test OFF==" );
				if (isset ( $args [1] )) {
					// $plotName = $args [1];
					$plotName = $sender->getName () . "_" . $args [1];
					if (! isset ( $this->plots [$plotName] )) {
						$sender->sendMessage ( "Plot nom non trouvé!" );
						return;
					}					
					$newplot = $this->plots [$plotName];					
					if ($newplot != null) {
						$this->plotLayout->plotTest ( $sender, $newplot, "OFF" );
					} else {
						$sender->sendMessage ( "Plot non trouvé!" );
					}
				} else {
					$sender->sendMessage ( "Usage /pplot testoff [plot nom]" );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) == "testmap") {
				$sender->sendMessage ( "==Plot test Map ==" );

				$block = $sender->getPlayer()->getLevel()->getBlock($sender->getPlayer()->getPosition());
				$level = $sender->getPlayer()->getLevel();
				$floorwidth = 10;
				$floorheight = 2;
				
				$this->plotLayout->plotMap($level, $floorwidth, $floorheight, $block, 45, 10);	
				return;
			}
		}
	}
	
	/**
	 * Cache Plot Blocks
	 *
	 * @param unknown $newplot        	
	 * @param unknown $plevel        	
	 */
	public function cachePlotBlocks($newplot, $plevel) {
		$wallheighSize = $newplot->p1->y;
		if (is_numeric ( $newplot->plotGroundUp ) && $newplot->plotGroundUp > 0) {
			$wallheighSize = $newplot->p1->y + $newplot->plotGroundUp;
		} else {
			$wallheighSize = $newplot->p1->y + 5;
		}
		$level = $this->getServer ()->getLevelByName ( $newplot->plotLevelName );
		if ($level == null) {
			$level = $plevel;
		}
		
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
					// $this->resetBlock ( $bk, $level, 0 );
					// $this->log ( TextFormat::GREEN . "block: " . $bk->x . " " . $bk->y . " " . $bk->z );
					$key = round ( $bk->x ) . "*" . round ( $bk->y ) . "*" . round ( $bk->z );
					$this->cachedPlotBlocks [$key] = $newplot;
				}
			}
		}
		
		// build it DOWN
		$wallheighSize = 2;
		if (is_numeric ( $newplot->plotGroundDown ) && $newplot->plotGroundDown > 0) {
			$wallheighSize = $newplot->plotGroundDown;
		}
		// build it UP
		For($z = $pzs; $z <= $pzm; $z ++) {
			For($x = $pxs; $x <= $pxm; $x ++) {
				For($y = 0; $y <= $wallheighSize; $y ++) {
					$bk = $level->getBlock ( new Vector3 ( $x, $y, $z ) );
					// $this->resetBlock ( $bk, $level, 0 );
					// $this->log ( TextFormat::GREEN . "block: " . $bk->x . " " . $bk->y . " " . $bk->z );
					$key = round ( $bk->x ) . "*" . round ( $bk->y ) . "*" . round ( $bk->z );
					$this->cachedPlotBlocks [$key] = $newplot;
				}
			}
		}
		
		$this->log ( "updated cache " . count ( $this->cachedPlotBlocks ) );
	}
	public function removecachePlotBlocks($newplot, Level $plevel) {
		$wallheighSize = $newplot->p1->y + 3;
		$level = $this->getServer ()->getLevelByName ( $newplot->plotLevelName );
		if ($level == null) {
			$level = $plevel;
		}
		// build it UP
		For($z = $newplot->p3->z; $z <= $newplot->p1->z; $z ++) {
			For($x = $newplot->p1->x; $x <= $newplot->p3->x; $x ++) {
				For($y = $newplot->p1->y; $y <= $wallheighSize; $y ++) {
					$bk = $level->getBlock ( new Vector3 ( $x, $y, $z ) );
					// $this->resetBlock ( $bk, $level, 20 );
					// $this->log ( TextFormat::GREEN . "block: " . $bk->x . " " . $bk->y . " " . $bk->z );
					$key = $bk->x . "*" . $bk->y . "*" . $bk->z;
					unset ( $this->cachedPlotBlocks [$key] );
				}
			}
		}
		
		// build it DOWN
		$wallheighSize = 3;
		For($z = $newplot->p3->z; $z <= $newplot->p1->z; $z ++) {
			For($x = $newplot->p1->x; $x <= $newplot->p3->x; $x ++) {
				For($y = 0; $y <= $wallheighSize; $y ++) {
					$bk = $level->getBlock ( new Vector3 ( $x, $y, $z ) );
					// $this->resetBlock ( $bk, $level, 20 );
					$key = $bk->x . "*" . $bk->y . "*" . $bk->z;
					unset ( $this->cachedPlotBlocks [$key] );
				}
			}
		}
		$this->log ( "blocks removed " . count ( $this->cachedPlotBlocks ) );
	}
	public function displayPlotInfo($sender, $newplot) {
		$sender->sendMessage ( "*plot: " . $newplot->plotName );
		$sender->sendMessage ( ">owner: " . $newplot->ownerName );
		$sender->sendMessage ( ">pos1: " . $newplot->p1 );
		$sender->sendMessage ( ">pos3: " . $newplot->p3 );
		$sender->sendMessage ( ">plotGroundUp: " . $newplot->plotGroundUp );
		$sender->sendMessage ( ">plotGroundDown: " . $newplot->plotGroundDown );
		$sender->sendMessage ( ">allow viewing: " . $newplot->allowView );
		$sender->sendMessage ( ">allow block place: " . $newplot->allowPlaceBlock );
		$sender->sendMessage ( ">allow block break: " . $newplot->allowBreakBlock );
		$sender->sendMessage ( ">authorized users: " . count ( $newplot->authorizedUsers ) );
		foreach ( $newplot->authorizedUsers as $user ) {
			$sender->sendMessage ( "> " . $user );
		}
		$sender->sendMessage ( "------------------------------" );
	}
	
	/**
	 * OnBlockBreak
	 *
	 * @param BlockBreakEvent $event        	
	 */
	public function onBlockBreak(BlockBreakEvent $event) {
		$b = $event->getBlock ();
		if ($this->block_display_flag == 1) {
			$event->getPlayer ()->sendMessage ( "bloc PLACÉ: [x=" . $b->x . " y=" . $b->y . " z=" . $b->z . "]" );
		}
		$player = $event->getPlayer ();
		if (isset ( $this->workingCopies [$player->getName ()] )) {
			$newplot = $this->workingCopies [$player->getName ()];
			
			if ($newplot->state == "setpos1") {
				$newplot->p1 = new Position ( $b->x, $b->y, $b->z );
				$event->getPlayer ()->sendMessage ( "plot ensemble pos#1: [x=" . $b->x . " y=" . $b->y . " z=" . $b->z . "]" );
				return;
			}
			
			if ($newplot->state == "setpos3") {
				$newplot->p3 = new Position ( $b->x, $b->y, $b->z );
				$event->getPlayer ()->sendMessage ( "plot ensemble pos#3: [x=" . $b->x . " y=" . $b->y . " z=" . $b->z . "]" );
				return;
			}
			
			$this->workingCopies [$player->getName ()] = $newplot;
		}
		
		$bk = $b;
		$key = round ( $bk->x ) . "*" . round ( $bk->y ) . "*" . round ( $bk->z );
		
		if (isset ( $this->cachedPlotBlocks [$key] )) {
			// check if owner allow this action for the plot
			//$this->log("bb is cached ");
			$plot = $this->cachedPlotBlocks [$key];
			
			// world checking
			if ($plot->plotLevelName == null || $player->getLevel ()->getName () != $plot->plotLevelName) {
				return;
			}
			
			if ($plot->allowBreakBlock == "public") {
				//$this->log("bb is public ");
				return;
			}
			
// 			// check authorized users
// 			foreach ( $plot->authorizedUsers as $user ) {
// 				if ($player->getName () == $user) {
// 					//$this->log("bb is authorized user ");
// 					return;
// 				}
// 				//$this->log ( "authorized users :" . $user );
// 			}

			// check ownership
			// $ownerName = $this->cachedPlotBlocks [$key];
			$ownerName = $plot->ownerName;
			
			//$this->log("bb owner ".$ownerName);
			
			if ($ownerName != $player->getName ()) {
				$event->setCancelled ( true );
				$player->sendMessage ( "Attention! propriété privée!" );
				$player->sendMessage ( "s'il  vous plaît contacter le propriétaire de la parcelle [" . $ownerName . "]." );
				return;
			}
						
			$loginkey = $player->getName()."_".$player->getAddress();
			if (!isset($this->authenticatedUsers[$loginkey])) {
				$event->setCancelled ( true );
				$player->sendMessage("-------------------------");
				$player->sendMessage ( "Attention! zone réglementée!" );
				$player->sendMessage ( "propriétaire de la parcelle seulement [" . $ownerName . "] pouvant y acceder!" );
				$player->sendMessage ( "Si vous êtes le propriétaire, s'il vous plaît connecter" );
				$player->sendMessage ( "/pplot login [plot nom] [Mot de Passe]" );
				$player->sendMessage("-------------------------");
// 				// reject player
// 				$plot->p1->x ++;
// 				$player->teleport ( $plot->p1 );
				return;
			}
		}
		
	}
	
	/**
	 * onBlockPlace
	 *
	 * @param BlockPlaceEvent $event        	
	 */
	public function onBlockPlace(BlockPlaceEvent $event) {
		$bk = $event->getBlock ();
		if ($this->block_display_flag == 1) {
			$event->getPlayer ()->sendMessage ( "bloc PLACÉ: [x=" . $bk->x . " y=" . $bk->y . " z=" . $bk->z . "]" );
		}
		$player = $event->getPlayer ();
		$key = round ( $bk->x ) . "*" . round ( $bk->y ) . "*" . round ( $bk->z );
		
		if (isset ( $this->cachedPlotBlocks [$key] )) {
			$plot = $this->cachedPlotBlocks [$key];
			// world checking
			if ($plot->plotLevelName == null || $player->getLevel ()->getName () != $plot->plotLevelName) {
				return;
			}
			// check access level
			if ($plot->allowPlaceBlock == "public") {
				return;
			}
			
// 			// check authorized users
// 			foreach ( $plot->authorizedUsers as $user ) {
// 				if ($player->getName () == $user) {
// 					return;
// 				}
// 				//$this->log ( "authorized users :" . $user );
// 			}
			// check ownership
			// $ownerName = $this->cachedPlotBlocks [$key];
			$ownerName = $plot->ownerName;
			if ($ownerName != $player->getName ()) {
				$event->setCancelled ( true );
				$player->sendMessage ( "Attention! propriété privée!" );
				$player->sendMessage ( "s'il vous plaît contacter le propriétaire de la parcelle [" . $ownerName . "]." );
				// reject player
// 				$plot->p1->x ++;
// 				$player->teleport ( $plot->p1 );				
				return;
			}
			
			$loginkey = $player->getName()."_".$player->getAddress();
			if (!isset($this->authenticatedUsers[$loginkey])) {
				$event->setCancelled ( true );
				$player->sendMessage("-------------------------");
				$player->sendMessage ( "Attention! zone réglementée!" );
				$player->sendMessage ( "propriétaire de la parcelle seulement [" . $ownerName . "] pouvant y acceder!" );
				$player->sendMessage ( "Si vous êtes le propriétaire, s'il vous plaît connecter" );
				$player->sendMessage ( "/pplot login [plot nom] [Mot de Passe]" );
				$player->sendMessage("-------------------------");
				// reject player
// 				$plot->p1->x ++;
// 				$player->teleport ( $plot->p1 );
				return;
			}
		}
	}
	
	/**
	 * Handle Player Move Event
	 *
	 * @param EntityMoveEvent $event        	
	 */
	public function onPlayerMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer ();
		$bk = $player->getPosition ();
		$key = round ( $bk->x ) . "*" . round ( $bk->y ) . "*" . round ( $bk->z );
		//$this->log("bb ".$key);
		if (isset ( $this->cachedPlotBlocks [$key] )) {
			$plot = $this->cachedPlotBlocks [$key];
			// world checking
			//$this->log("bb 0 ");
			if ($plot->plotLevelName == null || $player->getLevel ()->getName () != $plot->plotLevelName) {
				return;
			}
			// check plot access level
			//$this->log("bb 1 ");
			if ($plot->allowView == "public") {
				//$this->log("bb 2 ");
				return;
			}
			// check authorized users
			foreach ( $plot->authorizedUsers as $user ) {
				if ($player->getName () == $user) {
					//$this->log("bb 3 ");
					return;
				}
				$this->log ( "utilisateurs autorisés :" . $user );
			}
			// check ownership
			$ownerName = $plot->ownerName;
			//$this->log("bb 4 ".$ownerName);
			if ($ownerName != $player->getName ()) {
				//$this->log("bb 5 ");
				$event->setCancelled ( true );
				$player->sendMessage("-------------------------");
				$player->sendMessage ( "Attention! propriété privée!" );
				$player->sendMessage ( "propriétaire de la parcelle seulement [" . $ownerName . "] pouvant y acceder!" );
				$player->sendMessage("-------------------------");
// 				// reject player
				//$plot->p1->x ++;
				$player->teleport ( $event->getFrom() );				
				return;
			}
			
			//challenge user authentication 
			$loginkey = $player->getName()."_".$player->getAddress();			
			if (!isset($this->authenticatedUsers[$loginkey])) {
				$event->setCancelled ( true );
				$player->sendMessage("-------------------------");
				$player->sendMessage ( "Attention! zone réglementée!" );
				$player->sendMessage ( "propriétaire de la parcelle seulement [" . $ownerName . "]  pouvant y acceder!" );
				$player->sendMessage ( "Si vous êtes le propriétaire, s'il vous plaît connecter" );
				$player->sendMessage ( "/pplot login [plot nom] [Mot  de Passe]" );				
				$player->sendMessage("-------------------------");
				// reject player
// 				$plot->p1->x ++;
				$player->teleport ( $event->getFrom() );
				return;				
			}			
			
		}
	}
	
	public function listPlayerPlots($player, $playerName) {
		$i = 1;
		foreach ( $this->plots as $plot ) {
			if ($plot->ownerName==$playerName ) {
				$player->sendMessage ( $i . "." . $plot->plotName );
				$i ++;
			}
		}
	}
	
	public function getPlayerPlotsCount($playerName) {
		$i = 0;
		foreach ( $this->plots as $plot ) {
			if ($plot->ownerName==$playerName ) {
				$i ++;
			}
		}		
		return $i;
	}
	
	
	public function listAllPlots($player, $playerName) {
		$i = 1;
		foreach ( $this->plots as $plot ) {
			$player->sendMessage ( $i . "." . $plot->plotName );
			$i ++;
		}
	}
	public function loadAllPlotFiles() {
		$path = $this->getDataFolder () . "plots/";
		if (! file_exists ( $path )) {
			@mkdir ( $this->getDataFolder () );
			@mkdir ( $path );
			return;
		}
		
		$this->log ( "parcelles de chargement sur " . $path );
		
		$handler = opendir ( $path );
		while ( ($filename = readdir ( $handler )) !== false ) {
			// $this->log ( "file - ".$filename );
			
			if ($filename != "." && $filename != "..") {
				$data = new Config ( $path . $filename, Config::YAML );
				
				// $this->log ( "loading : " . $data->get ( "ownerName" ) );
				
				if (($pLevel = Server::getInstance ()->getLevelByName ( $data->get ( "plotLevelName" ) )) === null)
					continue;
				$name = str_replace ( ".yml", "", $filename );
				
				$p1 = new Position ( $data->get ( "point1X" ), $data->get ( "point1Y" ), $data->get ( "point1Z" ), $pLevel );
				$p3 = new Position ( $data->get ( "point3X" ), $data->get ( "point3Y" ), $data->get ( "point3Z" ), $pLevel );
				$ownerName = $data->get ( "ownerName" );
				$plotLevelName = $data->get ( "plotLevelName" );
				$authorizedUsers = $data->get ( "authorizedUsers" );
				$allowView = $data->get ( "allowView" );
				$allowPlaceBlock = $data->get ( "allowPlaceBlock" );
				$allowBreakBlock = $data->get ( "allowBreakBlock" );
				$plotGroundUp = $data->get ( "plotGroundUp" );
				$plotGroundDown = $data->get ( "plotGroundDown" );
				$xname = $data->get ( "name" );
				$password = $data->get ( "password" );
				
				$this->log ( "Chargement plot: " . $name );
				
				$owner = $this->getServer ()->getOfflinePlayer ( $ownerName );
				if ($owner == null) {
					$this->log ( "propriétaire de la parcelle pas en ligne: " . ownerName );
				}
				$p = new Plot ( $this, $name, $owner );
				$p->p1 = $p1;
				$p->p3 = $p3;
				$p->ownerName = $ownerName;
				$p->plotLevelName = $plotLevelName;
				$p->allowBreakBlock = $allowBreakBlock;
				$p->allowPlaceBlock = $allowPlaceBlock;
				$p->allowView = $allowView;
				$p->plotGroundUp = $plotGroundUp;
				$p->plotGroundDown = $plotGroundDown;
				$p->name = $xname;
				$p->plotName = $name;
				$p->password = $password;
				
				$this->plots [$name] = $p;
				
				// load all block up - single world only
				$this->cachePlotBlocks ( $p, $pLevel );
			}
		}
		closedir ( $handler );
	}
	
	/**
	 * OnQuit
	 *
	 * @param PlayerQuitEvent $event
	 */
	public function onQuit(PlayerQuitEvent $event) {
		//clear players login session
		$player = $event->getPlayer();	
		$loginkey = $player->getName()."_".$player->getAddress();
		if (isset($this->authenticatedUsers[$loginkey])) {
			unset($this->authenticatedUsers[$loginkey]);
		}
	}
	
	/**
	 * Logging util function
	 *
	 * @param unknown $msg        	
	 */
	public function log($msg) {
		$this->getLogger ()->info ( $msg );
	}
}
