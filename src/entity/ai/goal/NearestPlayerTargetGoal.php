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

final class NearestPlayerTargetGoal extends Goal{
	private ?Player $candidate = null;

	/** @phpstan-param (\Closure(Player) : bool)|null $acquireFilter */
	public function __construct(
		private Mob $mob,
		private float $range = 32.0,
		private ?\Closure $acquireFilter = null
	){
		$this->setFlags(self::FLAG_TARGET);
	}

	public function canStart() : bool{
		$this->candidate = $this->findNearestTarget();
		return $this->candidate !== null;
	}

	public function canContinue() : bool{
		$target = $this->mob->getTargetEntity();
		return $target instanceof Player && $this->isValidTarget($target);
	}

	public function start() : void{
		$this->mob->setTargetEntity($this->candidate);
	}

	public function stop() : void{
		$this->candidate = null;
		$this->mob->setTargetEntity(null);
	}

	private function findNearestTarget() : ?Player{
		$nearest = null;
		$nearestDistance = $this->range * $this->range;
		$position = $this->mob->getPosition();
		foreach($this->mob->getWorld()->getPlayers() as $player){
			if(!$this->isValidTarget($player) || ($this->acquireFilter !== null && !($this->acquireFilter)($player)) || !$this->mob->canSee($player)){
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

	private function isValidTarget(Player $player) : bool{
		$gamemode = $player->getGamemode();
		if(!$player->isAlive() || ($gamemode !== GameMode::SURVIVAL && $gamemode !== GameMode::ADVENTURE)){
			return false;
		}

		$position = $this->mob->getPosition();
		$playerPosition = $player->getPosition();
		$dx = $playerPosition->x - $position->x;
		$dy = $playerPosition->y - $position->y;
		$dz = $playerPosition->z - $position->z;
		return ($dx * $dx + $dy * $dy + $dz * $dz) <= $this->range * $this->range;
	}
}
