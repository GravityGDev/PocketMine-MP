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

use pocketmine\entity\Entity;
use pocketmine\entity\Mob;
use pocketmine\math\Vector3;
use function atan2;
use function rad2deg;
use function sqrt;

final class LookControl{
	private ?Vector3 $target = null;

	public function __construct(private Mob $mob){}

	public function lookAt(Vector3 $target) : void{
		$this->target = clone $target;
	}

	public function lookAtEntity(Entity $entity) : void{
		$this->lookAt($entity->getEyePos());
	}

	public function clear() : void{
		$this->target = null;
	}

	public function tick() : void{
		if($this->target === null){
			return;
		}

		$eye = $this->mob->getEyePos();
		$dx = $this->target->x - $eye->x;
		$dy = $this->target->y - $eye->y;
		$dz = $this->target->z - $eye->z;
		$horizontalDistance = sqrt($dx * $dx + $dz * $dz);
		if($horizontalDistance < 0.0001 && $dy === 0.0){
			return;
		}

		$yaw = rad2deg(atan2(-$dx, $dz));
		$pitch = rad2deg(-atan2($dy, $horizontalDistance));
		$this->mob->setRotation($yaw, $pitch);
	}
}
