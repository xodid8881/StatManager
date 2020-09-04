<?php
declare(strict_types=1);

namespace StatManager\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;
use StatManager\StatManager;

class EventCommand extends Command
{

  protected $plugin;

  public function __construct(StatManager $plugin)
  {
    $this->plugin = $plugin;
    parent::__construct('스탯관리', '스탯관리 명령어.', '/스탯관리');
  }

  public function execute(CommandSender $sender, string $commandLabel, array $args)
  {
    $this->plugin->onStatOpen ($sender);
    return true;
  }
}
