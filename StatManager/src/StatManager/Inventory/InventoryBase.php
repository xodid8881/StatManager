<?php
declare(strict_types=1);
namespace StatManager\Inventory;

use Closure;
use pocketmine\inventory\BaseInventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\Utils;

abstract class InventoryBase extends BaseInventory{

	/** @var Closure|null */
	protected $transactionHandler = null;

	/** @var Closure|null  */
	protected $openHandler = null;

	/** @var Closure|null  */
	protected $closeHandler = null;

	public function setOpenHandler(Closure $closure) : void{
		Utils::validateCallableSignature(function(Player $player) : void{}, $closure);
		$this->openHandler = $closure;
	}

	public function setCloseHandler(Closure $closure) : void{
		Utils::validateCallableSignature(function(Player $player) : void{}, $closure);
		$this->closeHandler = $closure;
	}

	public function setTransactionHandler(Closure $closure) : void{
		Utils::validateCallableSignature(function(Player $player, Item $input, Item $output, int $slot, &$cancelled = false) : void{}, $closure);
		$this->transactionHandler = $closure;
	}

	public function handleOpen(Player $player) : void{
		if($this->openHandler instanceof Closure){
			($this->openHandler)($player);
		}
	}

	public function handleClose(Player $player) : void{
		if($this->closeHandler instanceof Closure){
			($this->closeHandler)($player);
		}
	}

	public function handleTransaction(Player $player, Item $input, Item $output, int $slot, &$cancelled = false) : void{
		if($this->transactionHandler instanceof Closure){
			($this->transactionHandler)($player, $input, $output, $slot, $cancelled);
		}
	}

	public function send(Player $who) : void{
		$who->addWindow($this);
	}
}
