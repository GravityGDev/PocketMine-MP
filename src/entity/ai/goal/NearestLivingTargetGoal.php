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

use pocketmine\entity\Living;
use pocketmine\entity\Mob;

/**
 * Finds the nearest living entity matching a configurable filter.
 */
final class NearestLivingTargetGoal extends Goal{
	private ?Living $candidate = null;

	/**
	 * @phpstan-param (\Closure(Living) : bool)|null $acquireFilter
	 * @phpstan-param (\Closure(Living) : bool)|null $continueFilter
	 */
	public function __construct(
		private Mob $mob,
		private float $range = 32.0,
		private ?\Closure $acquireFilter = null,
		private ?\Closure $continueFilter = null,
		private bool $mustSee = true
	){
		$this->setFlags(self::FLAG_TARGET);
	}

	public function canStart() : bool{
		$this->candidate = $this->findNearestTarget();
		return $this->candidate !== null;
	}

	public function canContinue() : bool{
		$target = $this->mob->getTargetEntity();
		return $target instanceof Living &&
			$this->isInRangeAndAlive($target) &&
			($this->continueFilter === null || ($this->continueFilter)($target));
	}

	public function start() : void{
		$this->mob->setTargetEntity($this->candidate);
	}

	public function stop() : void{
		$this->candidate = null;
		$this->mob->setTargetEntity(null);
	}

	private function findNearestTarget() : ?Living{
		$nearest = null;
		$nearestDistance = $this->range * $this->range;
		$position = $this->mob->getPosition();

		foreach($this->mob->getWorld()->getEntities() as $entity){
			if(
				$entity === $this->mob ||
				!$entity instanceof Living ||
				!$entity->isAlive() ||
				($this->acquireFilter !== null && !($this->acquireFilter)($entity)) ||
				($this->mustSee && !$this->mob->canSee($entity))
			){
				continue;
			}

			$targetPosition = $entity->getPosition();
			$dx = $targetPosition->x - $position->x;
			$dy = $targetPosition->y - $position->y;
			$dz = $targetPosition->z - $position->z;
			$distance = $dx * $dx + $dy * $dy + $dz * $dz;
			if($distance <= $nearestDistance){
				$nearestDistance = $distance;
				$nearest = $entity;
			}
		}

		return $nearest;
	}

	private function isInRangeAndAlive(Living $target) : bool{
		if(!$target->isAlive() || $target->getWorld() !== $this->mob->getWorld()){
			return false;
		}

		$position = $this->mob->getPosition();
		$targetPosition = $target->getPosition();
		$dx = $targetPosition->x - $position->x;
		$dy = $targetPosition->y - $position->y;
		$dz = $targetPosition->z - $position->z;
		return ($dx * $dx + $dy * $dy + $dz * $dz) <= $this->range * $this->range;
	}
}
