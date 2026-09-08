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

use Closure;
use pocketmine\entity\Mob;
use pocketmine\entity\object\ItemEntity;
use function is_int;

/**
 * Moves a mob to a reachable dropped item and delegates the actual pickup to
 * the mob. The evaluator returns a lower score for more desirable items, or
 * null when the item isn't interesting to this mob.
 */
final class PickupItemGoal extends Goal{
	private ?ItemEntity $target = null;
	private bool $pickupAttempted = false;
	private int $retryDelay = 0;

	/**
	 * @phpstan-param Closure(ItemEntity) : (?int) $priorityEvaluator
	 * @phpstan-param Closure(ItemEntity) : bool $pickupHandler
	 */
	public function __construct(
		private Mob $mob,
		private Closure $priorityEvaluator,
		private Closure $pickupHandler,
		private float $speed = 0.1,
		private float $goalRadius = 2.0,
		private float $maxDistance = 3.0,
		private float $searchHeight = 1.0
	){
		$this->setFlags(self::FLAG_MOVE);
	}

	public function canStart() : bool{
		if($this->retryDelay > 0){
			--$this->retryDelay;
			return false;
		}

		$best = null;
		$bestPriority = PHP_INT_MAX;
		$bestDistanceSquared = INF;
		$origin = $this->mob->getPosition();
		$maxDistanceSquared = $this->maxDistance * $this->maxDistance;

		foreach($this->mob->getWorld()->getNearbyEntities(
			$this->mob->getBoundingBox()->expandedCopy($this->maxDistance, $this->searchHeight, $this->maxDistance),
			$this->mob
		) as $entity){
			if(!$entity instanceof ItemEntity || $entity->isFlaggedForDespawn() || $entity->getPickupDelay() !== 0){
				continue;
			}

			$distanceSquared = $origin->distanceSquared($entity->getPosition());
			if($distanceSquared > $maxDistanceSquared){
				continue;
			}

			$priority = ($this->priorityEvaluator)($entity);
			if(!is_int($priority)){
				continue;
			}
			if($priority > $bestPriority || ($priority === $bestPriority && $distanceSquared >= $bestDistanceSquared)){
				continue;
			}
			if(!$this->mob->getNavigation()->canReach($entity->getPosition())){
				continue;
			}

			$best = $entity;
			$bestPriority = $priority;
			$bestDistanceSquared = $distanceSquared;
		}

		$this->target = $best;
		$this->pickupAttempted = false;
		return $this->target !== null;
	}

	public function canContinue() : bool{
		if($this->pickupAttempted){
			return false;
		}

		$target = $this->target;
		if($target === null || $target->isFlaggedForDespawn() || $target->getPickupDelay() !== 0){
			return false;
		}
		if(!is_int(($this->priorityEvaluator)($target))){
			return false;
		}

		$maxTrackingDistance = $this->maxDistance + $this->goalRadius;
		return $this->mob->getPosition()->distanceSquared($target->getPosition()) <= $maxTrackingDistance * $maxTrackingDistance;
	}

	public function start() : void{
		if($this->target !== null){
			$this->mob->getNavigation()->moveTo($this->target->getPosition(), $this->speed);
		}
	}

	public function tick() : void{
		$target = $this->target;
		if($target === null){
			return;
		}

		$this->mob->getNavigation()->moveTo($target->getPosition(), $this->speed);
		if($this->mob->getPosition()->distanceSquared($target->getPosition()) <= $this->goalRadius * $this->goalRadius){
			($this->pickupHandler)($target);
			$this->pickupAttempted = true;
			$this->retryDelay = 20;
		}
	}

	public function stop() : void{
		$this->target = null;
		$this->pickupAttempted = false;
		$this->mob->getNavigation()->stop();
	}
}
