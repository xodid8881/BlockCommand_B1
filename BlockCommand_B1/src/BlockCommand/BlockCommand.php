<?php

namespace BlockCommand;

// 플러그인
use pocketmine\plugin\PluginBase;
// 이벤트
use pocketmine\event\Listener;
// 이벤트 플레이어
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
// 커맨드
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
// 객체
use pocketmine\Player;
// 유틸
use pocketmine\utils\Config;
// 레벨
use pocketmine\level\particle\FloatingTextParticle;
// math
use pocketmine\math\Vector3;

class BlockCommand extends PluginBase implements Listener {
	public function onEnable() {
		@mkdir ( $this->getDataFolder () );
		$this->player = new Config ( $this->getDataFolder () . "players.yml", Config::YAML );
		$this->pldb = $this->player->getAll ();
		$this->block = new Config ( $this->getDataFolder () . "Block.yml", Config::YAML );
		$this->blockdb = $this->block->getAll ();
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
	}
	public function OnJoin(PlayerJoinEvent $event) {
      $player = $event->getPlayer ();
      $name = $player->getName ();
      if ( $player->isOp () ) {
         if (! isset ( $this->pldb [strtolower ( $name )] )) {
            $this->pldb [strtolower ( $name )] ["블럭설치"] = "준비중";
            $this->pldb [strtolower ( $name )] ["블럭이름"] = "없음";
            $this->pldb [strtolower ( $name )] ["블럭커맨드"] = "없음";
            $this->pldb [strtolower ( $name )] ["블럭제거모드"] = "준비중";
            $this->save ();
         }
      }
      foreach ( $this->blockdb as $tg => $args ) {
         $tg = explode ( ':', $tg );
         $x = $tg [0];
         $y = $tg [1];
         $z = $tg [2];
         $level = $tg [3];
         $player->getLevel ()->addParticle ( new FloatingTextParticle ( new Vector3 ( $x + 0.5, $y - 0.2, $z + 0.5, $level ), '', $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭이름"] ) );
      }
   }
   public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
      $command = $command->getName ();
      $name = $sender->getName ();
      $tag = "§a§0[ §f블럭커맨드 §0]§f";
      if ($command == "블럭커맨드") {
         if (! isset ( $args [0] )) {
            $sender->sendMessage ( $tag . " /블럭커맨드 생성 [ 이름 ] [ 명령어 ]§a§0[ §f블럭커맨드를 생성합니다. §0]§f" );
            $sender->sendMessage ( $tag . " /블럭커맨드 제거모드 §a§0[ §f블럭커맨드 제거모드를 실행합니다. §0]§f" );
            return true;
         }
         switch ($args [0]) {
            case "제거모드" :
               if ( $this->pldb [strtolower ( $name )] ["블럭설치"] == "준비중" ) {
                  $sender->sendMessage ( $tag . " 블럭커맨드 제거모드를 실행 했습니다." );
                  $sender->sendMessage ( $tag . " 블럭커맨드 제거모드를 종료시키려면 명령어를 한번더 사용하세요. " );
                  $this->pldb [strtolower ( $name )] ["블럭제거모드"] = "실행중";
                  $this->save ();
               } else {
                 $sender->sendMessage ( $tag . " 블럭커맨드 제거모드를 종료 했습니다." );
                 $this->pldb [strtolower ( $name )] ["블럭제거모드"] = "준비중";
                  $this->save ();
                 }
               break;
            case "생성" :
               if (! isset ( $args [1] )) {
                  $sender->sendMessage ( $tag . " /블럭커맨드 생성 [ 이름 ] [ 명령어 ]§a§0[ §f블럭커맨드를 생성합니다. §0]§f" );
                  $sender->sendMessage ( $tag . " /블럭커맨드 제거 §a§0[ §f블럭커맨드 제거모드를 실행합니다. §0]§f" );
                  return true;
               }
               switch ($args [1]) {
                  case $args [1] :
                     if (! isset ( $args [1] )) {
                        $sender->sendMessage ( $tag . " /블럭커맨드 생성 [ 이름 ] [ 명령어 ]§a§0[ §f블럭커맨드를 생성합니다. §0]§f" );
                        $sender->sendMessage ( $tag . " /블럭커맨드 제거 §a§0[ §f블럭커맨드 제거모드를 실행합니다. §0]§f" );
                        return true;
                     }
                     switch ($args [2]) {
                        case $args [2] :
                           if ( !$sender->isOp () ){
                              $sender->sendMessage ( $tag . " 당신은 권한이 없습니다.");
                              return true;
                           }
                           if ( $this->pldb [strtolower ( $name )] ["블럭설치"] == "준비중" ) {
                              $sender->sendMessage ( $tag . " 설치할 블럭을 터치하세요.");
                              $this->pldb [strtolower ( $name )] ["블럭설치"] = "진행중";
                              $this->pldb [strtolower ( $name )] ["블럭이름"] = $args [1];
                              $this->pldb [strtolower ( $name )] ["블럭커맨드"] = $args [2];
                              $this->save ();
                              break;
                           } else {
                              $sender->sendMessage ( $tag . " 당신은 벌써 블럭커맨드를 생성중입니다.");
                              return true;
                           }
                      }
               }
               break;
         }
         return true;
      }
   } //블럭커맨드
   public function Interact(PlayerInteractEvent $event) {
		$player = $event->getPlayer ();
		$name = $player->getName ();
		$tag = "§a§0[ §f블럭커맨드 §0]§f";
		$block = $event->getBlock ();
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		$level = $block->getLevel ()->getFolderName ();
		if ($this->pldb [strtolower ( $name )] ["블럭제거모드"] == "실행중") {
		   if (isset ( $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] )) {
			   unset ( $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] );
			   $this->save ();
			   return true;
			}
		}
		if ($this->pldb [strtolower ( $name )] ["블럭설치"] == "진행중") {
			$this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭이름"] = $this->pldb [strtolower ( $name )] ["블럭이름"];
			$this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭커맨드"] = $this->pldb [strtolower ( $name )] ["블럭커맨드"];
			$player->getLevel ()->addParticle ( new FloatingTextParticle ( new Vector3 ( $x + 0.5, $y- 0.2, $z + 0.5, $level ), '', $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭이름"] ) );
			$this->pldb [strtolower ( $name )] ["블럭설치"] = "준비중";
         $this->pldb [strtolower ( $name )] ["블럭이름"] = "없음";
         $this->pldb [strtolower ( $name )] ["블럭커맨드"] = "없음";
			$this->save ();
			return true;
		}
		if (isset ( $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] )) {
			$blockcommand = $this->blockdb [$x . ':' . $y . ':' . $z . ':' . $level] ["블럭커맨드"];
			$this->getServer()->getCommandMap()->dispatch($player, $blockcommand);
			return true;
		}
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