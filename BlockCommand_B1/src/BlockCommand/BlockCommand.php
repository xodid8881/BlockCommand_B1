<?php

namespace BlockCommand;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;
// use function file_exists;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
// 레벨
use pocketmine\level\particle\FloatingTextParticle;
// math
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

// 한글깨짐방지
class BlockCommand extends PluginBase implements Listener {
	public function onEnable() {
		@mkdir ( $this->getDataFolder () );
		$this->player = new Config ( $this->getDataFolder () . "players.yml", Config::YAML );
		$this->pldb = $this->player->getAll ();
		$this->block = new Config ( $this->getDataFolder () . "Block.yml", Config::YAML );
		$this->blockdb = $this->block->getAll ();
		$this->getScheduler ()->scheduleRepeatingTask ( new BlockCommandTask ( $this, $this->player ), 160 );
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
	}
	public function BlockCommandTaskEvent(Player $player) {
		foreach ( $this->blockdb as $tg => $args ) {
			$tg = explode ( ':', $tg );
			$x = $tg [0];
			$y = $tg [1];
			$z = $tg [2];
			$level = $tg [3];
			$player->getLevel ()->addParticle ( new FloatingTextParticle ( new Vector3 ( $x + 0.5, $y - 0.2, $z + 0.5, $level ), '', "§l§e[ §f블럭커맨드 §e]§f" . "§f\n" . $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭이름"] . "\n§e[ §f터치시 명령어 실행 §e]" ) );
		}
	}
	public function OnJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer ();
		$name = $player->getName ();
		if ($player->isOp ()) {
			if (! isset ( $this->pldb [strtolower ( $name )] )) {
				$this->pldb [strtolower ( $name )] ["블럭설치"] = "준비중";
				$this->pldb [strtolower ( $name )] ["블럭이름"] = "없음";
				$this->pldb [strtolower ( $name )] ["블럭커맨드"] = "없음";
				$this->pldb [strtolower ( $name )] ["블럭제거모드"] = "준비중";
				$this->save ();
			}
		}
	}
	public function MainUI(Player $player) {
		$encode = [
			'type' => 'form',
			'title' => '§l§e[ §f블럭커맨드 §e]§f',
			'content' => '§e§o§l§w[ §f버튼을 눌러주세요. §e]',
			'buttons' => [
				[
					'text' => '§e§o§l§w[ §f블럭커맨드 생성모드 §e]'
				],
				[
					'text' => '§e§o§l§w[ §f블럭커맨드 제거모드 §e]'
				]
			]
		];
		$packet = new ModalFormRequestPacket ();
		$packet->formId = 888;
		$packet->formData = json_encode ( $encode );
		$player->sendDataPacket ( $packet );
	}
	public function NewUI(Player $player) {
		$encode = [
			'type' => 'custom_form',
			'title' => '§e§o§l§w[ §f블럭커맨드 생성 §e]',
			'content' => [
				[
					'type' => 'input',
					'text' => '§e§o§l§w[ §f커맨드의 §e이름§f을 적어주세요. §e]'
				]
			]
		];
		$packet = new ModalFormRequestPacket ();
		$packet->formId = 898;
		$packet->formData = json_encode ( $encode );
		$player->sendDataPacket ( $packet );
		return true;
	}
	public function RatingUI(Player $player) {
		$encode = [
			'type' => 'custom_form',
			'title' => '§e§o§l§w[ §f블럭커맨드 생성 §e]',
			'content' => [
				[
					'type' => 'input',
					'text' => '§e§o§l§w[ §f커맨드의 §e명령어§f를 적어주세요. §e]'
				]
			]
		];
		$packet = new ModalFormRequestPacket ();
		$packet->formId = 899;
		$packet->formData = json_encode ( $encode );
		$player->sendDataPacket ( $packet );
		return true;
	}
	public function onPacket(DataPacketReceiveEvent $event) {
		$packet = $event->getPacket ();
		$player = $event->getPlayer ();
		$name = $player->getName ();
		$tag = "§l§e[ §f블럭커맨드 §e]§f";
		if ($packet instanceof ModalFormResponsePacket) {
			$id = $packet->formId;
			$data = json_decode ( $packet->formData, true );
			if ($id === 898) {
				if ($data [0]) {
					$this->pldb [strtolower ( $name )] ["블럭이름"] = $data [0];
					$this->save ();
					$this->RatingUI ( $player );
					return true;
				}
			}
			if ($id === 899) {
				if ($data [0]) {
					$player->sendMessage ( $tag . " 설치할 블럭을 터치하세요." );
					$this->pldb [strtolower ( $name )] ["블럭커맨드"] = $data [0];
					$this->pldb [strtolower ( $name )] ["블럭설치"] = "진행중";
					$this->save ();
				}
			}
			if ($id === 888) {
				if ($data === 0) {
					$this->NewUI ( $player );
					return true;
				} elseif ($data === 1) {
					if ( isset ( $this->pldb [strtolower ( $name )] )) {
						if ($this->pldb [strtolower ( $name )] ["블럭제거모드"] == "준비중") {
							$player->sendMessage ( $tag . " 블럭커맨드 제거모드를 실행 했습니다." );
							$player->sendMessage ( $tag . " 블럭커맨드 제거모드를 종료하려면 한번더 실행하세요." );
							$this->pldb [strtolower ( $name )] ["블럭제거모드"] = "실행중";
							$this->save ();
							return true;
						}
						if ($this->pldb [strtolower ( $name )] ["블럭제거모드"] == "실행중") {
							$player->sendMessage ( $tag . " 블럭커맨드 제거모드를 종료 했습니다." );
							$player->sendMessage ( $tag . " 블럭커맨드 제거모드를 실행하려면 한번더 실행하세요." );
							$this->pldb [strtolower ( $name )] ["블럭제거모드"] = "준비중";
							$this->save ();
							return true;
						}
					}
				}
			}
		}
	}
	public function Interact(PlayerInteractEvent $event) {
		$player = $event->getPlayer ();
		$name = $player->getName ();
		$tag = "§l§e[ §f블럭커맨드 §e]§f";
		$block = $event->getBlock ();
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		$level = $block->getLevel ()->getFolderName ();
		if ( isset ( $this->pldb [strtolower ( $name )] )) {
			if ($this->pldb [strtolower ( $name )] ["블럭제거모드"] == "실행중") {
				if (isset ( $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] )) {
					$player->sendMessage ( $tag . " 해당위치의 블럭커맨드를 제거 했습니다." );
					$player->sendMessage ( $tag . " 재접속시에 팝업이 제거됩니다." );
					unset ( $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] );
					$this->save ();
					return true;
				}
			}
			if ($this->pldb [strtolower ( $name )] ["블럭설치"] == "진행중") {
				$player->sendMessage ( $tag . " 해당위치의 블럭커맨드를 생성 했습니다." );
				$this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭이름"] = $this->pldb [strtolower ( $name )] ["블럭이름"];
				$this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭커맨드"] = $this->pldb [strtolower ( $name )] ["블럭커맨드"];
				$player->getLevel ()->addParticle ( new FloatingTextParticle ( new Vector3 ( $x + 0.5, $y - 0.2, $z + 0.5, $level ), '', "§l§e[ §f블럭커맨드 §e]§f" . "§f\n" . $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭이름"] . "\n§e[ §f터치시 명령어 실행 §e]" ) );
				$this->pldb [strtolower ( $name )] ["블럭설치"] = "준비중";
				$this->pldb [strtolower ( $name )] ["블럭이름"] = "없음";
				$this->pldb [strtolower ( $name )] ["블럭커맨드"] = "없음";
				$this->save ();
				return true;
			}
		}
		if (isset ( $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] )) {
			$blockcommand = $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭커맨드"];
			$this->getServer ()->getCommandMap ()->dispatch ( $player, $blockcommand );
			return true;
		}
	}
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
		if ($cmd->getName () === '블럭커맨드') {
			$this->MainUI ( $sender );
		}
		return true;
	}
	public function onDisable() {
		$this->save ();
	}
	public function save() {
		$this->player->setAll ( $this->pldb );
		$this->player->save ();
		$this->block->setAll ( $this->blockdb );
		$this->block->save ();
	}
}
class BlockCommandTask extends Task {
	protected $owner;
	protected $player;
	protected $pldb;
	public function __construct(BlockCommand $owner, Config $player) {
		$this->owner = $owner;
		$this->player = $player;
		$this->pldb = $this->player->getAll ();
	}
	public function onRun(int $currentTick) {
		foreach ( $this->owner->getServer ()->getOnlinePlayers () as $player ) {
			$this->owner->BlockCommandTaskEvent ( $player );
		}
	}
}
