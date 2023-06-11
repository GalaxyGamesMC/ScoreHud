<?php

declare(strict_types = 1);

namespace Ifera\ScoreHud\event;

use Ifera\ScoreHud\scoreboard\ScoreTag;
use pocketmine\player\Player;

abstract class PlayerScoreTagEvent extends PlayerEvent{

	public function __construct(
        Player $player,
        protected ScoreTag $tag
    ){
		parent::__construct($player);
	}

	public function getTag(): ScoreTag{
		return $this->tag;
	}

	public function setTag(ScoreTag $tag): void{
		$this->tag = $tag;
	}
}