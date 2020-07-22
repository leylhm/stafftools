<?php

namespace Staff;

use Staff\{Command as CommandManager, Events as EventsManager};
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\{Server, Player};
use pocketmine\plugin\PluginBase;
use pocketmine\nbt\tag\{CompoundTag, StringTag, IntTag, ListTag};
use pocketmine\tile\{Tile, Chest};
use pocketmine\utils\TextFormat as T;

class Loader extends PluginBase{
	
	public $freeze = [];
	public $vanish = [];
	public $inventory = [];
	public $staff = [];
	
	public function onLoad(){
	$this->getLogger()->info(T::YELLOW."Loading!");
	}
	
	public function onEnable(){
	$this->getLogger()->info(T::GREEN."Loaded!");
	$this->getServer()->getCommandMap()->register("staff", new CommandManager("staff", $this));
	$this->getEvents();
	}
	
	public function onDisable(){
	$this->getLogger()->info(T::RED."Disabled!");
	}
	
	public function isStaff($name){
	return in_array($name, $this->staff);
	}
	
	public function getEvents(){
	return new EventsManager($this);
	}
	
	public function setStaff($name){
	$this->staff[$name] = $name;
	}
	
	public function quitStaff($name){
	if(!$this->isStaff($name)){
	return;
	}
	unset($this->staff[$name]);
	}
	
	public function isFreezed($name){
	return in_array($name, $this->freeze);
	}
	
	public function setFreezed($name){
	$this->freeze[$name] = $name;
	}
	
	public function unFreeze($name){
	if(!$this->isFreezed($name)){
	return;
	}
	unset($this->freeze[$name]);
	}
	
	public function playerInfo(Player $target){
	$ip = $target->getAddress();
	$name = $target->getName();
	$mode = $target->getGamemode();
	$vida = T::RED.str_repeat("|", $target->getHealth()).T::GRAY.str_repeat("|", 20-$target->getHealth());
	return "\n".
	T::GOLD."Name: ".T::GREEN.$name."\n".
	T::GOLD."Address: ".T::GREEN.$ip."\n".
	T::GOLD."Health: ".$vida.T::GREEN." {$target->getHealth()}%\n".
	T::GOLD."Mode: ".$mode."\n";
	}
	
	public function Backup(Player $player){
	$contents = $player->getInventory()->getContents();
	$items = [];
	foreach($contents as $slot => $item){
	$items[$slot] = [$item->getId(), $item->getDamage(), $item->getCount()];
	}
	$this->inventory[$player->getName()] = $items;
	}
	
	public function Restore(Player $player){
	if(!$this->isStaff($player->getName())){
	return;
	}
	$cloud = $this->inventory[$player->getName()];
	$player->getInventory()->clearAll();
	foreach($cloud as $slot => $item){
	$player->getInventory()->setItem($slot, Item::get($item[0], $item[1], $item[2]));
	}
	unset($this->inventory[$player->getName()]);
	return true;
	}
	
	public function Chest(Player $player, Player $target){
	$block = Block::get(54);
	$x = floor($player->x);
	$y = floor($player->y);
	$z = floor($player->z);
	$block->x = $x;
	$block->y = $y-3;
	$block->z = $z;
	$level = $player->getLevel();
	$name = $target->getName();
	$nbt = new CompoundTag("", [
	new ListTag("Items", []),
	new StringTag("Tile", Tile::CHEST),
	new StringTag("player", $name),
	new IntTag("x", $x),
	new IntTag("y", $y-3),
	new IntTag("z", $z)
	]);
	$chest = Tile::createTile("Chest", $player->chunk, $nbt);
	$inventory = $chest->getInventory();
	$contents = $target->getInventory()->getContents();
	$inventory->setContents($contents);
	$level->sendBlocks([$player], [$block]);
	$chest->spawnTo($player);
	$player->addWindow($inventory);
	}
	
	public function isVanish($name){
	return in_array($name, $this->vanish);
	}
	
	public function quitVanish($name){
	if(!$this->isVanish($name)){
	$player = $this->getServer()->getPlayer($name);
	$player->setAllowFlight(false);
	return;
	}
	unset($this->vanish[$name]);
	}
	
	public function setVanish($name){
	if($this->isVanish($name)){
	$player = $this->getServer()->getPlayer($name);
	$player->setAllowFlight(true);
	return;
	}
	$this->vanish[$name] = $name;
	}
	
	public function Vanish(Player $player){
	$name = $player->getName();
	if($this->isVanish($name)){
	$online = $this->getServer()->getOnlinePlayers();
	$this->quitVanish($name);
	$player->sendMessage(T::RED."Vanish: OFF");
	foreach($online as $players){
	$players->showPlayer($player);
	}
	return true;
	}else
	{
	$online = $this->getServer()->getOnlinePlayers();
	foreach($online as $players){
	$players->hidePlayer($player);
	}
	$player->sendMessage(T::GREEN."Vanish: ON");
	$this->setVanish($name);
	return true;
	}
	
	}
	
	public function setKit(Player $player){
	$compass = Item::get(345);
	$compass->setDamage(100);
	$com = T::GOLD.T::BOLD."Random Teleport";
	$compass->setCustomName($com);
	
	$vanish = Item::get(347);
	$vanish->setDamage(100);
	$vanish->setCustomName("");
	
	$freeze = Item::get(352);
	$freeze->setDamage(100);
	$fre = T::GRAY.T::BOLD."Freeze";
	$freeze->setCustomName($fre);
	
	$chest = Item::get(340);
	$chest->setDamage(100);
	$ches = T::GREEN.T::BOLD."Inventory";
	$chest->setCustomName($ches);
	
	$info = Item::get(369);
	$info->setDamage(100);
	$inf = T::YELLOW.T::BOLD."Information";
	$info->setCustomName($inf);
	
	$tele = Item::get(339);
	$tele->setDamage(100);
	$telen = T::GOLD.T::BOLD."Teleport";
	$tele->setCustomName($telen);
	
	$player->getInventory()->setContents([$compass, $vanish, $freeze, $chest, $info, $tele]);
	}
	
	
}