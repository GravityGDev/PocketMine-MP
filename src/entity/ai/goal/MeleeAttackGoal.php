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
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;

final class MeleeAttackGoal extends Goal{
	private int $attackCooldown = 0;

	public function __construct(
		private Mob $mob,
		private float $speed = 0.1,
		private float $damage = 3.0,
		private float $attackReach = 1.8
	){
		$this->setFlags(self::FLAG_MOVE, self::FLAG_LOOK);
	}

	public function canStart() : bool{
		return $this->hasValidTarget();
	}

	public function canContinue() : bool{
		return $this->hasValidTarget();
	}

	public function tick() : void{
		if($this->attackCooldown > 0){
			--$this->attackCooldown;
		}

		$target = $this->mob->getTargetEntity();
		if(!$target instanceof Player){
			return;
		}

		$this->mob->getLookControl()->lookAtEntity($target);

		$position = $this->mob->getPosition();
		$targetPosition = $target->getPosition();
		$dx = $targetPosition->x - $position->x;
		$dy = $targetPosition->y - $position->y;
		$dz = $targetPosition->z - $position->z;
		$distanceSquared = $dx * $dx + $dy * $dy + $dz * $dz;
		$attackReachSquared = $this->attackReach * $this->attackReach;

		if($distanceSquared > $attackReachSquared || !$this->mob->canSee($target)){
			$this->mob->getNavigation()->moveTo($targetPosition, $this->speed);
			return;
		}

		$this->mob->getNavigation()->stop();
		if($this->attackCooldown === 0){
			$target->attack(new EntityDamageByEntityEvent(
				$this->mob,
				$target,
				EntityDamageEvent::CAUSE_ENTITY_ATTACK,
				$this->damage
			));
			$this->attackCooldown = 20;
		}
	}

	public function stop() : void{
		$this->mob->getNavigation()->stop();
		$this->mob->getLookControl()->clear();
	}

	private function hasValidTarget() : bool{
		$target = $this->mob->getTargetEntity();
		return $target instanceof Player && $target->isAlive();
	}
}
