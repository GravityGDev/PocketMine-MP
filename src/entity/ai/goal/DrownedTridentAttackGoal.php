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
use pocketmine\entity\Location;
use pocketmine\entity\Mob;
use pocketmine\entity\projectile\Trident as ThrownTrident;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Trident as TridentItem;
use pocketmine\math\Vector3;
use pocketmine\world\sound\TridentThrowSound;
use function mt_rand;
use function sqrt;

/**
 * Bedrock-style Drowned trident combat, including the 3/5 block melee/ranged
 * hysteresis used by the target-nearby mode switcher.
 */
final class DrownedTridentAttackGoal extends Goal{
	private int $attackCooldown = 0;
	private bool $rangedMode = true;

	public function __construct(
		private Mob $mob,
		private float $speed = 0.1,
		private float $attackRadius = 10.0,
		private float $meleeDamage = 3.0
	){
		$this->setFlags(self::FLAG_MOVE, self::FLAG_LOOK);
	}

	public function canStart() : bool{
		return $this->hasValidTarget() && $this->mob->getMainHandItem() instanceof TridentItem;
	}

	public function canContinue() : bool{
		return $this->hasValidTarget() && $this->mob->getMainHandItem() instanceof TridentItem;
	}

	public function tick() : void{
		if($this->attackCooldown > 0){
			--$this->attackCooldown;
		}

		$target = $this->mob->getTargetEntity();
		if(!$target instanceof Living){
			return;
		}

		$this->mob->getLookControl()->lookAtEntity($target);

		$position = $this->mob->getPosition();
		$targetPosition = $target->getPosition();
		$dx = $targetPosition->x - $position->x;
		$dy = $targetPosition->y - $position->y;
		$dz = $targetPosition->z - $position->z;
		$distanceSquared = $dx * $dx + $dy * $dy + $dz * $dz;

		if($distanceSquared <= 3.0 * 3.0){
			$this->rangedMode = false;
		}elseif($distanceSquared >= 5.0 * 5.0){
			$this->rangedMode = true;
		}

		if(!$this->rangedMode){
			$this->tickMelee($target, $distanceSquared);
			return;
		}

		if($distanceSquared > $this->attackRadius * $this->attackRadius || !$this->mob->canSee($target)){
			$this->mob->getNavigation()->moveTo($targetPosition, $this->speed);
			return;
		}

		$this->mob->getNavigation()->stop();
		if($this->attackCooldown === 0){
			$this->throwTrident($target);
			$this->attackCooldown = mt_rand(20, 60);
		}
	}

	private function tickMelee(Living $target, float $distanceSquared) : void{
		if($distanceSquared > 1.8 * 1.8 || !$this->mob->canSee($target)){
			$this->mob->getNavigation()->moveTo($target->getPosition(), $this->speed);
			return;
		}

		$this->mob->getNavigation()->stop();
		if($this->attackCooldown === 0){
			$target->attack(new EntityDamageByEntityEvent(
				$this->mob,
				$target,
				EntityDamageEvent::CAUSE_ENTITY_ATTACK,
				$this->meleeDamage
			));
			$this->attackCooldown = 20;
		}
	}

	private function throwTrident(Living $target) : void{
		$source = $this->mob->getEyePos();
		$targetEye = $target->getEyePos();
		$dx = $targetEye->x - $source->x;
		$dz = $targetEye->z - $source->z;
		$horizontalDistance = sqrt($dx * $dx + $dz * $dz);
		$dy = $targetEye->y - $source->y + $horizontalDistance * 0.2;
		$length = sqrt($dx * $dx + $dy * $dy + $dz * $dz);
		if($length <= 1.0e-10){
			return;
		}

		$item = $this->mob->getMainHandItem();
		if(!$item instanceof TridentItem){
			return;
		}

		$trident = new ThrownTrident(
			new Location($source->x, $source->y - 0.1, $source->z, $this->mob->getWorld(), 0.0, 0.0),
			$item,
			$this->mob
		);
		$trident->setPickupAllowed(false);
		$trident->setMotion(new Vector3($dx / $length * 1.6, $dy / $length * 1.6, $dz / $length * 1.6));
		$trident->spawnToAll();
		$this->mob->getWorld()->addSound($source, new TridentThrowSound());
	}

	public function stop() : void{
		$this->mob->getNavigation()->stop();
		$this->mob->getLookControl()->clear();
	}

	private function hasValidTarget() : bool{
		$target = $this->mob->getTargetEntity();
		return $target instanceof Living && $target->isAlive();
	}
}
