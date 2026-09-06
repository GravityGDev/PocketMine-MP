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

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Location;
use pocketmine\entity\Mob;
use pocketmine\entity\projectile\Arrow;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\BowShootSound;
use pocketmine\world\World;
use function sqrt;

final class RangedAttackGoal extends Goal{
	private int $attackCooldown = 0;

	public function __construct(
		private Mob $mob,
		private float $speed = 0.1,
		private float $attackRadius = 15.0,
		private int $normalAttackCooldown = 60,
		private int $hardAttackCooldown = 40,
		private ?EffectInstance $arrowHitEffect = null
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

		if($distanceSquared > $this->attackRadius * $this->attackRadius || !$this->mob->canSee($target)){
			$this->mob->getNavigation()->moveTo($targetPosition, $this->speed);
			return;
		}

		$this->mob->getNavigation()->stop();
		if($this->attackCooldown === 0){
			$this->shoot($target);
			$this->attackCooldown = $this->mob->getWorld()->getDifficulty() === World::DIFFICULTY_HARD ?
				$this->hardAttackCooldown :
				$this->normalAttackCooldown;
		}
	}

	public function stop() : void{
		$this->mob->getNavigation()->stop();
		$this->mob->getLookControl()->clear();
	}

	private function shoot(Player $target) : void{
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

		$arrow = new Arrow(
			new Location($source->x, $source->y - 0.1, $source->z, $this->mob->getWorld(), 0.0, 0.0),
			$this->mob,
			false
		);
		$arrow->setPickupMode(Arrow::PICKUP_NONE);
		if($this->arrowHitEffect !== null){
			$arrow->setHitEffect($this->arrowHitEffect);
		}
		$arrow->setMotion(new Vector3($dx / $length * 1.6, $dy / $length * 1.6, $dz / $length * 1.6));
		$arrow->spawnToAll();
		$this->mob->getWorld()->addSound($source, new BowShootSound());
	}

	private function hasValidTarget() : bool{
		$target = $this->mob->getTargetEntity();
		return $target instanceof Player && $target->isAlive();
	}
}
