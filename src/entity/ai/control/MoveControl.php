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

namespace pocketmine\entity\ai\control;

use pocketmine\block\Water;
use pocketmine\entity\Mob;
use pocketmine\math\Vector3;
use function atan2;
use function floor;
use function rad2deg;
use function sqrt;

final class MoveControl{
	private ?Vector3 $target = null;
	private float $speed = 0.0;

	public function __construct(
		private Mob $mob,
		private JumpControl $jumpControl
	){}

	public function setWantedPosition(Vector3 $target, float $speed) : void{
		$this->target = clone $target;
		$this->speed = $speed;
	}

	public function stop() : void{
		$this->target = null;
		$this->speed = 0.0;
		$motion = $this->mob->getMotion();
		$this->mob->setMotion(new Vector3(0.0, $motion->y, 0.0));
	}

	public function tick() : void{
		if($this->target === null){
			return;
		}

		$location = $this->mob->getLocation();
		$dx = $this->target->x - $location->x;
		$dy = $this->target->y - $location->y;
		$dz = $this->target->z - $location->z;
		$horizontalDistance = sqrt($dx * $dx + $dz * $dz);
		$movementSpeed = $this->speed > 0.0 ? $this->speed : 0.1;

		if($this->isSwimming()){
			$distance = sqrt($dx * $dx + $dy * $dy + $dz * $dz);
			if($distance < 0.05){
				$this->stop();
				return;
			}

			$this->mob->setMotion(new Vector3(
				$dx / $distance * $movementSpeed,
				$dy / $distance * $movementSpeed,
				$dz / $distance * $movementSpeed
			));
			if($horizontalDistance >= 0.001){
				$this->mob->setRotation(rad2deg(atan2(-$dx, $dz)), $location->pitch);
			}
			return;
		}

		if($horizontalDistance < 0.05){
			$this->stop();
			return;
		}

		$motion = $this->mob->getMotion();
		$this->mob->setMotion(new Vector3(
			$dx / $horizontalDistance * $movementSpeed,
			$motion->y,
			$dz / $horizontalDistance * $movementSpeed
		));
		$this->mob->setRotation(rad2deg(atan2(-$dx, $dz)), $location->pitch);

		if($this->mob->isCollidedHorizontally && $this->mob->onGround){
			$this->jumpControl->jump();
		}
	}

	private function isSwimming() : bool{
		if(!$this->mob->canNavigateInWater()){
			return false;
		}

		$position = $this->mob->getPosition();
		return $this->mob->getWorld()->getBlockAt(
			(int) floor($position->x),
			(int) floor($position->y),
			(int) floor($position->z)
		) instanceof Water;
	}
}
