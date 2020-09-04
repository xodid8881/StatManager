<?php
declare(strict_types=1);
namespace StatManager\Inventory;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\inventory\BaseInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\tile\Spawnable;

class DoubleChestInventory extends InventoryBase{

	/** @var Vector3 */
	protected $vec;

	protected $invName;

	public function __construct(string $name){
		parent::__construct([], 54);
		$this->invName = $name;
	}

	public function getName() : string{
		return "DoubleChestInventory";
	}

	public function getNetworkType() : int{
		return WindowTypes::CONTAINER;
	}

	public function getDefaultSize() : int{
		return 54;
	}

	public function onOpen(Player $who) : void{
		BaseInventory::onOpen($who);
		$this->handleOpen($who);

		$plugin = Server::getInstance()->getPluginManager()->getPlugin("StatManager");

		$this->vec = $who->floor()->add(0, 5);

		$x = $this->vec->x;
		$y = $this->vec->y;
		$z = $this->vec->z;

		for($i = 0; $i < 2; $i++){
			$pk = new UpdateBlockPacket();
			$pk->x = $x + $i;
			$pk->y = $y;
			$pk->z = $z;
			$pk->blockRuntimeId = BlockFactory::get(BlockIds::CHEST)->getRuntimeId();
			$pk->flags = UpdateBlockPacket::FLAG_ALL;
			$who->sendDataPacket($pk);

			$pk = new BlockActorDataPacket();
			$pk->x = $x + $i;
			$pk->y = $y;
			$pk->z = $z;
			$pk->namedtag = (new NetworkLittleEndianNBTStream())->write(new CompoundTag("", [
				new StringTag("id", "Chest"),
				new IntTag("x", $x + $i),
				new IntTag("y", $y),
				new IntTag("z", $z),
				new IntTag("pairx", $x + (1 - $i)),
				new IntTag("pairz", $z),
				new StringTag("CustomName", $this->invName)
			]));
			$who->sendDataPacket($pk);
		}

		$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $unused) use ($who, $x, $y, $z) : void{
			$pk = new ContainerOpenPacket();
			$pk->windowId = $who->getWindowId($this);
			$pk->x = $x;
			$pk->y = $y;
			$pk->z = $z;
			$who->sendDataPacket($pk);

			$this->sendContents($who);
		}), 10);
	}

	public function onClose(Player $who) : void{
		BaseInventory::onClose($who);
		$this->handleClose($who);

		$x = $this->vec->x;
		$y = $this->vec->y;
		$z = $this->vec->z;

		for($i = 0; $i < 2; $i++){
			$block = $who->getLevel()->getBlock(new Vector3($x + $i, $y, $z));
			$pk = new UpdateBlockPacket();
			$pk->x = $x + $i;
			$pk->y = $y;
			$pk->z = $z;
			$pk->blockRuntimeId = RuntimeBlockMapping::toStaticRuntimeId($block->getId(), $block->getDamage());
			$pk->flags = UpdateBlockPacket::FLAG_ALL_PRIORITY;
			$who->sendDataPacket($pk);

			$tile = $block->getLevel()->getTile($block);
			if($tile instanceof Spawnable){
				$who->sendDataPacket($tile->createSpawnPacket());
			}else{
				$pk = new BlockActorDataPacket();
				$pk->x = $x;
				$pk->y = $y;
				$pk->z = $z;
				$pk->namedtag = (new NetworkLittleEndianNBTStream())->write(new CompoundTag());
				$who->sendDataPacket($pk);
			}
		}

		/*
		$pk = new ContainerClosePacket();
		$pk->windowId = $who->getWindowId($this);
		$who->sendDataPacket($pk);
		*/
	}
}
