<?php
declare(strict_types=1);

namespace StatManager;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\item\Item;
use pocketmine\tile\Chest;
use StatManager\Inventory\DoubleChestInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;

class EventListener implements Listener
{

  protected $plugin;

  public function __construct(StatManager $plugin)
  {
    $this->plugin = $plugin;
  }
  public function OnJoin(PlayerJoinEvent $event) {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if (! isset ( $this->plugin->stdb [strtolower ( $name )] )) {
      $this->plugin->stdb [strtolower ( $name )] ["스탯포인트"] = 0;
      $this->plugin->stdb [strtolower ( $name )] ["공격"] = 0;
      $this->plugin->stdb [strtolower ( $name )] ["방어"] = 0;
      $this->plugin->stdb [strtolower ( $name )] ["회피"] = 0;
      $this->plugin->stdb [strtolower ( $name )] ["치명타"] = 0;
      $this->plugin->stdb [strtolower ( $name )] ["체력"] = 0;
      $this->plugin->stdb [strtolower ( $name )] ["누적스탯포인트"] = 0;
      $this->plugin->stdb [strtolower ( $name )] ["남은체력"] = (int)$player->getHealth ();
      $this->plugin->save ();
    }
    if (! isset ( $this->plugin->pldb [strtolower ( $name )] )) {
      $this->plugin->pldb [strtolower ( $name )] ["관리스탯"] = "없음";
      $this->plugin->save ();
    }
  }
  public function OnQuit(PlayerQuitEvent $event) {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if ( isset ( $this->plugin->stdb [strtolower ( $name )] )) {
      $this->plugin->stdb [strtolower ( $name )] ["남은체력"] = (int)$player->getHealth ();
      $this->plugin->save ();
      return true;
    }
  }
  public function onPacketReceive (DataPacketReceiveEvent $event) {
    $packet = $event->getPacket();
    if(! $packet instanceof ContainerClosePacket)
    return;
    $player = $event->getPlayer();
    $inv = $player->getWindow ($packet->windowId);
    if ($inv instanceof DoubleChestInventory) {
      $pk = new ContainerClosePacket();
      $pk->windowId = $player->getWindowId($inv);
      $player->sendDataPacket($pk);
    }
  }
  public function onInvClose(InventoryCloseEvent $event) {
    $player = $event->getPlayer();
    $inv = $event->getInventory();
    if ($inv instanceof DoubleChestInventory) {
      $inv->onClose($player);
      return true;
    }
  }
  public function onTransaction(InventoryTransactionEvent $event) {
    $transaction = $event->getTransaction();
    $player = $transaction->getSource ();
    $name = $player->getName ();
    foreach($transaction->getActions() as $action){
      if($action instanceof SlotChangeAction){
        $inv = $action->getInventory();
        if ($inv instanceof DoubleChestInventory) {
          $slot = $action->getSlot ();
          $id = $inv->getItem ($slot)->getId ();
          $damage = $inv->getItem ($slot)->getDamage ();
          if ($id == 144) {
            if ($inv->getItem ($slot)->getCustomName() == "§r§f공격력"){
              $event->setCancelled ();
              $inv->onClose ($player);
              $this->plugin->SetStatTaskEvent ($player);
              $this->plugin->pldb [strtolower ( $name )] ["관리스탯"] = "공격력";
              $this->plugin->save ();
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f방어력"){
              $event->setCancelled ();
              $inv->onClose ($player);
              $this->plugin->SetStatTaskEvent ($player);
              $this->plugin->pldb [strtolower ( $name )] ["관리스탯"] = "방어력";
              $this->plugin->save ();
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f회피"){
              $event->setCancelled ();
              $inv->onClose ($player);
              $this->plugin->SetStatTaskEvent ($player);
              $this->plugin->pldb [strtolower ( $name )] ["관리스탯"] = "회피";
              $this->plugin->save ();
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f치명타"){
              $event->setCancelled ();
              $inv->onClose ($player);
              $this->plugin->SetStatTaskEvent ($player);
              $this->plugin->pldb [strtolower ( $name )] ["관리스탯"] = "치명타";
              $this->plugin->save ();
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f체력"){
              $event->setCancelled ();
              $inv->onClose ($player);
              $this->plugin->SetStatTaskEvent ($player);
              $this->plugin->pldb [strtolower ( $name )] ["관리스탯"] = "체력";
              $this->plugin->save ();
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f내정보"){
              $event->setCancelled ();
              $inv->onClose ($player);
              $this->plugin->MyEvent ($player);
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f나가기"){
              $event->setCancelled ();
              $inv->onClose ($player);
              return true;
            }
          }
        }
      }
    }
  }
  public function onPacket(DataPacketReceiveEvent $event)
  {
    $packet = $event->getPacket();
    $player = $event->getPlayer();
    $x = $player->getX ();
    $y = $player->getY ();
    $z = $player->getZ ();
    $level = $player->getLevel ()->getFolderName ();
    $name = $player->getName();
    $tag = "§l§6[ §f안내 §6] ";
    if ($packet instanceof ModalFormResponsePacket) {
      $id = $packet->formId;
      $data = json_decode($packet->formData, true);
      if ($id === 313231) {
        if (!isset($data[0])) {
          $player->sendMessage( $tag . '빈칸을 채워주세요.');
          return;
        }
        if (! is_numeric ($data[0])) {
          $player->sendMessage ( $tag . "숫자를 이용 해야됩니다. " );
          return true;
        }
        if ($this->plugin->pldb [strtolower ( $name )] ["관리스탯"] == "공격력"){
          if ((int)$this->plugin->stdb [strtolower ( $name )] ["공격"]+(int)$data[0] < $this->plugin->pointdb ["공격력 최대포인트"]){
            $this->plugin->stdb [strtolower ( $name )] ["스탯포인트"] -= (int)$data[0];
            $this->plugin->stdb [strtolower ( $name )] ["공격"] += (int)$data[0];
            $this->plugin->save ();
          } else {
            $point = $this->plugin->pointdb ["공격력 최대포인트"];
            $player->sendMessage ( $tag . " 공격력의 최대포인트는 {$point} 까지 가능합니다." );
            return true;
          }
        } else if ($this->plugin->pldb [strtolower ( $name )] ["관리스탯"] == "방어력"){
          if ((int)$this->plugin->stdb [strtolower ( $name )] ["방어력"]+(int)$data[0] < $this->plugin->pointdb ["공격력 최대포인트"]){
            $this->plugin->stdb [strtolower ( $name )] ["스탯포인트"] -= (int)$data[0];
            $this->plugin->stdb [strtolower ( $name )] ["방어력"] += (int)$data[0];
            $this->plugin->save ();
          } else {
            $point = $this->plugin->pointdb ["방어력 최대포인트"];
            $player->sendMessage ( $tag . " 방어력의 최대포인트는 {$point} 까지 가능합니다." );
            return true;
          }
        } else if ($this->plugin->pldb [strtolower ( $name )] ["관리스탯"] == "회피"){
          if ((int)$this->plugin->stdb [strtolower ( $name )] ["회피"]+(int)$data[0] < $this->plugin->pointdb ["공격력 최대포인트"]){
            $this->plugin->stdb [strtolower ( $name )] ["스탯포인트"] -= (int)$data[0];
            $this->plugin->stdb [strtolower ( $name )] ["회피"] += (int)$data[0];
            $this->plugin->save ();
          } else {
            $point = $this->plugin->pointdb ["회피 최대포인트"];
            $player->sendMessage ( $tag . " 회피의 최대포인트는 {$point} 까지 가능합니다." );
            return true;
          }
        } else if ($this->plugin->pldb [strtolower ( $name )] ["관리스탯"] == "치명타"){
          if ((int)$this->plugin->stdb [strtolower ( $name )] ["치명타"]+(int)$data[0] < $this->plugin->pointdb ["공격력 최대포인트"]){
            $this->plugin->stdb [strtolower ( $name )] ["스탯포인트"] -= (int)$data[0];
            $this->plugin->stdb [strtolower ( $name )] ["치명타"] += (int)$data[0];
            $this->plugin->save ();
          } else {
            $point = $this->plugin->pointdb ["치명타 최대포인트"];
            $player->sendMessage ( $tag . " 치명타의 최대포인트는 {$point} 까지 가능합니다." );
            return true;
          }
        } else if ($this->plugin->pldb [strtolower ( $name )] ["관리스탯"] == "체력"){
          if ((int)$this->plugin->stdb [strtolower ( $name )] ["체력"]+(int)$data[0] < $this->plugin->pointdb ["공격력 최대포인트"]){
            $this->plugin->stdb [strtolower ( $name )] ["스탯포인트"] -= (int)$data[0];
            $this->plugin->stdb [strtolower ( $name )] ["체력"] += (int)$data[0];
            $this->plugin->save ();
          } else {
            $point = $this->plugin->pointdb ["체력 최대포인트"];
            $player->sendMessage ( $tag . " 체력의 최대포인트는 {$point} 까지 가능합니다." );
            return true;
          }
        }
      }
    }
  }
  public function onRespawn(PlayerRespawnEvent $event) {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if (isset ( $this->plugin->stdb [strtolower ( $name )] ["체력"] )) {
      $player->setMaxHealth ( 20 + $this->plugin->stdb [strtolower ( $name )] ["체력"] );
      $player->setHealth ( 20 + $this->plugin->stdb [strtolower ( $name )] ["체력"] );
    }
  }
  public function EntityDamage(EntityDamageEvent $event) {
    $tag = "§l§6[ §f피해 §6]§r ";
    $tag1 = "§l§c[ §f피해 §c]§r ";
    $entity = $event->getEntity ();
    if ($event instanceof EntityDamageByEntityEvent) {
      $damager = $event->getDamager ();
      if (! $damager instanceof Player) {
        if ($entity instanceof Player) {
          $dname = $damager->getName ();
          $ename = $entity->getName ();
          $damage = $event->getFinalDamage () - $this->plugin->stdb [strtolower ( $ename )] ["방어"]*$this->plugin->pointdb ["방어력 퍼센트"] / 25;
          $event->setBaseDamage ( $damage );
          $entity->sendTip ( $tag . $ename . " 에게 데미지를 입었습니다." . "\n당신의 남은 체력 : " . $entity->getHealth () . " ♥" );
        }
      }
      if ($damager instanceof Player) {
        if (! $entity instanceof Player) {
          $dname = $damager->getName ();
          $ename = $entity->getName ();
          $damage = $this->plugin->stdb [strtolower ( $dname )] ["공격"]*$this->plugin->pointdb ["공격력 퍼센트"];
          $rand = mt_rand ( 0, 100 );
          if ($rand < $this->plugin->stdb [strtolower ( $dname )] ["치명타"]*$this->plugin->pointdb ["치명타 퍼센트"] and $this->plugin->stdb [strtolower ( $dname )] ["치명타"]*$this->plugin->pointdb ["치명타 퍼센트"] > 0) {
            $event->setBaseDamage ( $event->getFinalDamage () + $damage * 0.4 );
            $damager->sendTip ( $tag1 . $ename . " 에게 치명타를 입혔습니다." . "\n해당 몬스터의 남은 체력 : " . $event->getEntity ()->getHealth () . " ♥" );
            return true;
          }
          $event->setBaseDamage ( $event->getFinalDamage () + $damage );
          $damager->sendTip ( $tag . $ename . " 에게 데미지를 입혔습니다." . "\n해당 몬스터의 남은 체력 : " . $event->getEntity ()->getHealth () . " ♥" );
        }
        if ($entity instanceof Player) {
          $dname = $damager->getName ();
          $ename = $entity->getName ();
          $damage =  $this->plugin->stdb [strtolower ( $ename )] ["공격"]*$this->plugin->pointdb ["공격력 퍼센트"] / 25 -  $this->plugin->stdb [strtolower ( $ename )] ["방어"]*$this->plugin->pointdb ["방어력 퍼센트"] / 25;
          $rand = mt_rand ( 0, 100 );
          if ($rand < $this->plugin->stdb [strtolower ( $dname )] ["치명타"]*$this->plugin->pointdb ["치명타 퍼센트"] and $this->plugin->stdb [strtolower ( $dname )] ["치명타"]*$this->plugin->pointdb ["치명타 퍼센트"] > 0) {
            $entity->sendTip ( $tag1 . $dname . " 한테서 치명타를 입었습니다." );
            $event->setBaseDamage ( $event->getFinalDamage () + $damage * 0.4 );
            $damager->sendTip ( $tag1 . $ename . " 에게 치명타를 입혔습니다. " . "\n상대방의 남은 체력 : " . $event->getEntity ()->getHealth () . " ♥" );
            return true;
          }
          if ($rand < $this->plugin->stdb [strtolower ( $ename )] ["회피"]*$this->plugin->pointdb ["치명타 퍼센트"] and $this->plugin->stdb [strtolower ( $ename )] ["회피"]*$this->plugin->pointdb ["치명타 퍼센트"] > 0) {
            $event->setCancelled ();
            $damager->sendMessage ( $tag1 . " 미스가 떴습니다. " );
            return true;
          }
          $event->setBaseDamage ( $event->getFinalDamage () + $damage );
          $entity->sendTip ( $tag . $dname . " 한테서 데미지를 받았습니다" );
          $damager->sendTip ( $tag . $ename . " 에게 데미지를 주었습니다." . "\n상대방의 남은 체력 : " . $event->getEntity ()->getHealth () . " ♥" );
        }
      }
    }
  }
}
