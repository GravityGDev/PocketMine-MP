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

namespace pocketmine\entity\ai\navigation;

use pocketmine\entity\Mob;
use pocketmine\math\Vector3;

/**
 * First-stage ground navigation. This drives a mob directly towards a target
 * and delegates collision jumping to MoveControl. Terrain path search is added
 * separately so the movement layer can be tested before A* is introduced.
 */
final class GroundNavigation{
	private ?Vector3 $target = null;
	private float $speed = 0.1;

	public function __construct(private Mob $mob){}

	public function moveTo(Vector3 $target, float $speed = 0.1) : void{
		$this->target = clone $target;
		$this->speed = $speed;
	}

	public function stop() : void{
		$this->target = null;
		$this->mob->getMoveControl()->stop();
	}

	public function isDone() : bool{
		return $this->target === null;
	}

	public function tick() : void{
		if($this->target === null){
			return;
		}

		$position = $this->mob->getPosition();
		$dx = $this->target->x - $position->x;
		$dy = $this->target->y - $position->y;
		$dz = $this->target->z - $position->z;
		if(($dx * $dx + $dy * $dy + $dz * $dz) <= 0.36){
			$this->stop();
			return;
		}

		$this->mob->getMoveControl()->setWantedPosition($this->target, $this->speed);
	}
}
