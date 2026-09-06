<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\entity\ai\goal;

use pocketmine\entity\Mob;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

final class LookAtPlayerGoal extends Goal{
	private ?Player $player = null;

	public function __construct(
		private Mob $mob,
		private float $range = 8.0
	){
		$this->setFlags(self::FLAG_LOOK);
	}

	public function canStart() : bool{
		$this->player = $this->findNearestPlayer();
		return $this->player !== null;
	}

	public function canContinue() : bool{
		return $this->player !== null && $this->isValid($this->player);
	}

	public function tick() : void{
		if($this->player !== null){
			$this->mob->getLookControl()->lookAtEntity($this->player);
		}
	}

	public function stop() : void{
		$this->player = null;
		$this->mob->getLookControl()->clear();
	}

	private function findNearestPlayer() : ?Player{
		$nearest = null;
		$nearestDistance = $this->range * $this->range;
		$position = $this->mob->getPosition();
		foreach($this->mob->getWorld()->getPlayers() as $player){
			if(!$this->isValid($player)){
				continue;
			}
			$playerPosition = $player->getPosition();
			$dx = $playerPosition->x - $position->x;
			$dy = $playerPosition->y - $position->y;
			$dz = $playerPosition->z - $position->z;
			$distance = $dx * $dx + $dy * $dy + $dz * $dz;
			if($distance < $nearestDistance){
				$nearestDistance = $distance;
				$nearest = $player;
			}
		}
		return $nearest;
	}

	private function isValid(Player $player) : bool{
		return $player->isAlive() && $player->getGamemode() !== GameMode::SPECTATOR;
	}
}
