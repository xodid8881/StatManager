<?php
declare(strict_types=1);

namespace StatManager;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\network\mcpe\protocol\OnScreenTextureAnimationPacket;
use pocketmine\utils\Config;
use pocketmine\scheduler\Task;
use pocketmine\item\Item;
use StatManager\Commands\EventCommand;
use StatManager\Inventory\DoubleChestInventory;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\block\Block;
use pocketmine\tile\Chest;
// monster
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;

class StatManager extends PluginBase
{
  protected $config;
  public $db;
  public $get = [];
  private static $instance = null;

  public static function getInstance(): StatManager
  {
    return static::$instance;
  }

  public function onLoad()
  {
    self::$instance = $this;
  }

  public function onEnable()
  {
    $this->stat = new Config ( $this->getDataFolder () . "stat.yml", Config::YAML );
    $this->stdb = $this->stat->getAll ();
    $this->point = new Config ( $this->getDataFolder () . "points.yml", Config::YAML, [
      "공격력 최대포인트" => 40,
      "방어력 최대포인트" => 30,
      "회피 최대포인트" => 20,
      "치명타 최대포인트" => 20,
      "체력 최대포인트" => 10,
      "공격력 퍼센트" => 0.025,
      "방어력 퍼센트" => 0.025,
      "회피 퍼센트" => 0.025,
      "치명타 퍼센트" => 0.025,
      "체력 퍼센트" => 0.5
    ]);
    $this->pointdb = $this->point->getAll ();
    $this->player = new Config ( $this->getDataFolder () . "player.yml", Config::YAML );
    $this->pldb = $this->player->getAll ();
    $this->getServer()->getCommandMap()->register('StatManager', new EventCommand($this));
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
  }
  public function GivePoint ($player, $point) {
    $name = $player->getName ();
    if (isset ( $this->stdb [strtolower ( $name )] )){
      $this->stdb [strtolower ( $name )] ["스탯포인트"] += $point;
      $this->save ();
      return true;
    }
  }
  public function MyEvent ($player) {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player) extends Task {
      protected $owner;
      public function __construct(StatManager $owner,Player $player) {
        $this->owner = $owner;
        $this->player = $player;
      }
      public function onRun($currentTick) {
        $this->owner->SetStatUI($this->player);
      }
    }, 20);
  }
  public function MyUI(Player $player)
  {
    $name = $player->getName ();
    $encode = [
      'type' => 'form',
      'title' => '§l§6[ §f스탯 내정보 §6]',
      'content' => "§r§7공격력 포인트 : {$this->stdb [strtolower ( $name )] ["공격"]}\n방어력 포인트 : {$this->stdb [strtolower ( $name )] ["방어"]}\n회피 포인트 : {$this->stdb [strtolower ( $name )] ["회피"]}\n치명타 포인트 : {$this->stdb [strtolower ( $name )] ["치명타"]}\n체력 포인트 : {$this->stdb [strtolower ( $name )] ["체력"]}\n남은 포인트 : {$this->stdb [strtolower ( $name )] ["스탯포인트"]}",
      'buttons' => [
        [
          'text' => '§l§6[ §f나가기 §6]'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 313230;
    $packet->formData = json_encode($encode);
    $player->sendDataPacket($packet);
    return true;
  }
  public function SetStatTaskEvent ($player) {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player) extends Task {
      protected $owner;
      public function __construct(StatManager $owner,Player $player) {
        $this->owner = $owner;
        $this->player = $player;
      }
      public function onRun($currentTick) {
        $this->owner->SetStatUI($this->player);
      }
    }, 20);
  }
  public function SetStatUI(Player $player)
  {
    $name = $player->getName ();
    $StatName = $this->pldb [strtolower($name)] ["관리스탯"];
    $StatPoint = $this->stdb [strtolower($name)] ["스탯포인트"];
    $encode = [
      'type' => 'custom_form',
      'title' => '§l§6[ §f스탯 §6]',
      'content' => [
        [
          'type' => 'input',
          'text' => "§r§7{$StatName} 의 스탯을 얼마나 올릴지 적어주세요.\n남은 스탯포인트 : {$StatPoint}개"
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 313231;
    $packet->formData = json_encode($encode);
    $player->sendDataPacket($packet);
    return true;
  }
  public function onStatOpen($player) {
    $name = $player->getName ();
    $inv = new DoubleChestInventory("§6§l[ §f스탯 §6]");
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f공격력")->setLore([ "§r§7공격력 스탯을 관리합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 19 , $CheckItem );
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f방어력")->setLore([ "§r§7방어력 스탯을 관리합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 22 , $CheckItem );
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f회피")->setLore([ "§r§7회피 스탯을 관리합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 25 , $CheckItem );
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f치명타")->setLore([ "§r§7치명타 스탯을 관리합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 29 , $CheckItem );
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f체력")->setLore([ "§r§7체력 스탯을 관리합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 33 , $CheckItem );
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f내정보")->setLore([ "§r§7나의 스탯정보를 확인합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 40 , $CheckItem );
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f나가기")->setLore([ "§r§7스탯관리 창에서 나갑니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 53 , $CheckItem );
    $inv->sendContents($inv->getViewers());
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
      }
    }, 20);
  }
  public function onDisable()
  {
    $this->save();
  }
  public function save()
  {
    $this->stat->setAll($this->stdb);
    $this->stat->save();
    $this->player->setAll($this->pldb);
    $this->player->save();
    $this->point->setAll($this->pointdb);
    $this->point->save();
  }
}
