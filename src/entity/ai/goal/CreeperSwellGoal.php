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

use pocketmine\entity\Creeper;
use pocketmine\player\Player;

final class CreeperSwellGoal extends Goal{
	private const IGNITE_DISTANCE_SQUARED = 3.0 * 3.0;
	private const ABORT_DISTANCE_SQUARED = 7.0 * 7.0;

	public function __construct(
		private Creeper $creeper,
		private float $speed = 0.1
	){
		$this->setFlags(self::FLAG_MOVE, self::FLAG_LOOK);
	}

	public function canStart() : bool{
		return $this->hasValidTarget();
	}

	public function canContinue() : bool{
		return $this->hasValidTarget() || $this->creeper->getFuseTicks() > 0;
	}

	public function tick() : void{
		$target = $this->creeper->getTargetEntity();
		if(!$target instanceof Player || !$target->isAlive()){
			$this->creeper->setSwellDirection(-1);
			$this->creeper->getNavigation()->stop();
			return;
		}

		$this->creeper->getLookControl()->lookAtEntity($target);

		$position = $this->creeper->getPosition();
		$targetPosition = $target->getPosition();
		$dx = $targetPosition->x - $position->x;
		$dy = $targetPosition->y - $position->y;
		$dz = $targetPosition->z - $position->z;
		$distanceSquared = $dx * $dx + $dy * $dy + $dz * $dz;

		if($distanceSquared <= self::IGNITE_DISTANCE_SQUARED && $this->creeper->canSee($target)){
			$this->creeper->getNavigation()->stop();
			$this->creeper->setSwellDirection(1);
			return;
		}

		$this->creeper->setSwellDirection(-1);
		if($distanceSquared <= self::ABORT_DISTANCE_SQUARED || $this->creeper->getFuseTicks() === 0){
			$this->creeper->getNavigation()->moveTo($targetPosition, $this->speed);
		}else{
			$this->creeper->getNavigation()->stop();
		}
	}

	public function stop() : void{
		$this->creeper->setSwellDirection(-1);
		$this->creeper->getNavigation()->stop();
		$this->creeper->getLookControl()->clear();
	}

	private function hasValidTarget() : bool{
		$target = $this->creeper->getTargetEntity();
		return $target instanceof Player && $target->isAlive();
	}
}
