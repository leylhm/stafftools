<?php

namespace Staff;

use Staff\Loader;
use pocketmine\{Server, Player};
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as T;
use pocketmine\math\Vector3;
use pocketmine\tile\Chest;
use pocketmine\event\player\{PlayerMoveEvent, PlayerInteractEvent, PlayerItemHeldEvent, PlayerDropItemEvent, PlayerQuitEvent, PlayerDeathEvent, PlayerRespawnEvent};
use pocketmine\event\inventory\{InventoryCloseEvent};
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};

class Events implements Listener{
	
	private $plugin;
	
	public function __construct(Loader $plugin){
	$this->plugin = $plugin;
	$this->getServer()->getPluginManager()->registerEvents($this, $this->getPlugin());
	}
	
	public function getPlugin(){
	return $this->plugin;
	}
	
	public function getServer(){
	return $this->getPlugin()->getServer();
	}
	
	public function onDeath(PlayerDeathEvent $event){
	$player = $event->getPlayer();
	if($this->getPlugin()->isStaff($player->getName())){
	$event->setDrops([]);
	}
	}
	
	public function onRespawn(PlayerRespawnEvent $event){
	$player = $event->getPlayer();
	if($this->getPlugin()->isStaff($player->getName()) and $player->spawned){
	$this->getPlugin()->setKit($player);
	}
	}
	
	public function onQuit(PlayerQuitEvent $event){
	$player = $event->getPlayer();
	if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
	$this->getPlugin()->Restore($player->getName());
	$this->getPlugin()->quitVanish($player->getName());
	$this->getPlugin()->quitStaff($player->getName());
	return true;
	}
	
	public function dropItem(PlayerDropItemEvent $event){
	if($event->isCancelled()){
	return;
	}
	$item = $event->getItem();
	if($item->getDamage() == 100){
	$event->setCancelled(true);
	}
	}
	
	public function onMove(PlayerMoveEvent $event){
	if($event->isCancelled()){
	return;
	}
	$player = $event->getPlayer();
	if($this->getPlugin()->isFreezed($player->getName())){
	$to = clone $event->getFrom();
	$to->pitch = $event->getTo()->pitch;
	$event->setTo($to);
	}
	}
	
	public function onHeld(PlayerItemHeldEvent $event){
	$player = $event->getPlayer();
	if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
	$item = $player->getInventory()->getItemInHand();
	if($item->getId() == 347 and $item->getDamage() == 100){
	$vanish = "";
	if($this->getPlugin()->isVanish($player->getName())){
	$vanish = T::GREEN."You are on vanish mode!";
	}else{
	$vanish = T::RED."You are not in vanish mode!";
	}
	$player->sendTip($vanish);
	}
	}
	
	public function onClose(InventoryCloseEvent $event){
	$player = $event->getPlayer();
	if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
	$level = $player->getLevel();
	$x = floor($player->x);
	$y = floor($player->y);
	$z = floor($player->z);
	$tile = $level->getTile(new Vector3($x, $y-3, $z));
	if(!$tile instanceof Chest){
	return;
	}
	$contents = $tile->getInventory()->getContents();
	$target = $this->getServer()->getPlayer($tile->namedtag->player);
	if($target !== null){
	$target->getInventory()->setContents($contents);
	}
	$tile->getInventory()->setContents([]);
	}
	
	public function onInteract(PlayerInteractEvent $event){
	if($event->isCancelled()){
	return;
	}
	$player = $event->getPlayer();
	$block = $event->getBlock();
	if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
	$item = $player->getInventory()->getItemInHand();
	if($item->getDamage() == 100){
	switch($item->getId()){	
	case 345:
	$this->playerTeleport($player);
	return true;
	break;
	
	case 347:
	$this->getPlugin()->Vanish($player);
	return true;
	break;
	
	case 339:
	$this->setSpeed($player, $block->x, $block->y, $block->z);
	return true;
	break;
	}
	}
	}
	
	public function setSpeed(Player $player, int $x, int $y, int $z){
	$player->teleport(new Vector3($x, $y+1, $z));
	//$player->setMotion(new Vector3($x, $y, $z)); moved too fast >.<
	}
	
	public function playerTeleport(Player $player){
	$online = $this->getServer()->getOnlinePlayers();
	foreach($online as $target){
	$mode = $target->getGamemode();
	$found = 0;
	if($mode == 0 || $mode == 2){
	$found++;
	}
	if($found == 0){
	$player->sendMessage(T::RED."No players found!");
	return;
	}
	if($player !== $target){
	$player->teleport($target);
	$player->sendMessage(T::GREEN."Teleported to: ".T::GOLD.$target->getName());
	break;
	}
	$found = 0;
	}
	return true;
	}
	
	public function onDamage(EntityDamageEvent $event){
	if($event instanceof EntityDamageByEntityEvent){
	$target = $event->getEntity();
	if(!$target instanceof Player){
	return;
	}
	$player = $event->getDamager();
	if(!$player instanceof Player){
	return;
	}
	if(!$this->getPlugin()->isStaff($player->getName())){
	return;
	}
	$hand = $player->getInventory()->getItemInHand();
	$event->setCancelled(true);
	if($hand->getDamage() == 100){
	
	switch($hand->getId()){
	case 352:
	if($this->getPlugin()->isFreezed($target->getName())){
	$this->getPlugin()->unFreeze($target->getName());
	$player->sendMessage(T::RED."{$target->getName()} has been unfreezed!");
	$target->sendMessage(T::YELLOW."You have been unfreezed by: ".T::GREEN.$player->getName());
	return true;
	}else{
	if(!$this->getPlugin()->isFreezed($target->getName())){
	$this->getPlugin()->setFreezed($target->getName());
	$player->sendMessage(T::GREEN."{$target->getName()} is freezed!");
	$target->sendMessage(T::GOLD."You have been freezed by: ".T::GREEN.$player->getName());
	return true;
	}
	}
	break;
	
	case 340:
	$this->getPlugin()->Chest($player, $target);
	$player->sendMessage(T::GOLD."You are looking at ".T::YELLOW.$target->getName().T::GOLD."'s inventory");
	return true;
	break;
	
	case 369:
	$msg = $this->getPlugin()->playerInfo($target);
	$player->sendMessage($msg);
	return true;
	break;
	}
	}
	}
	}
	

}