<?php

namespace Staff;

use Staff\Loader;
use pocketmine\Player;
use pocketmine\utils\TextFormat as T;
use pocketmine\command\{CommandSender, PluginCommand};

class Command extends PluginCommand{
	
	private $plugin;
	
	public function __construct($command, Loader $plugin){
	parent::__construct($command, $plugin);
	$this->setDescription("Staff Command");
	$this->plugin = $plugin;
	}
	
	public function getPlugin(){
	return $this->plugin;
	}
	
	public function execute(CommandSender $sender, $label, array $args){
	
	if(!$sender->isOp()){
	$sender->sendMessage(T::RED."No permission");
	return;
	}
	
	if(!$sender instanceof Player){
	return;
	}
	
	$name = $sender->getName();
	
	if(!$this->getPlugin()->isStaff($name)){
	$sender->sendMessage(T::GREEN."Staff Mode: ON");
	$this->getPlugin()->Backup($sender);
	$this->getPlugin()->setKit($sender);
	$this->getPlugin()->setStaff($name);
	}else{
	$sender->sendMessage(T::RED."Staff Mode: OFF");
	$this->getPlugin()->quitVanish($sender->getName());
	$this->getPlugin()->Restore($sender);
	$this->getPlugin()->quitStaff($name);
	}
	return;
	
	}
	
}