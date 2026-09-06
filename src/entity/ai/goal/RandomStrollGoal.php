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
use pocketmine\math\Vector3;
use function mt_rand;

final class RandomStrollGoal extends Goal{
	private ?Vector3 $wantedPosition = null;

	public function __construct(
		private Mob $mob,
		private float $speed = 0.08,
		private int $radius = 8,
		private int $interval = 80
	){
		$this->setFlags(self::FLAG_MOVE);
	}

	public function canStart() : bool{
		if(!$this->mob->getNavigation()->isDone() || mt_rand(1, $this->interval) !== 1){
			return false;
		}

		$position = $this->mob->getPosition();
		$this->wantedPosition = new Vector3(
			$position->x + mt_rand(-$this->radius, $this->radius),
			$position->y,
			$position->z + mt_rand(-$this->radius, $this->radius)
		);
		return true;
	}

	public function canContinue() : bool{
		return !$this->mob->getNavigation()->isDone();
	}

	public function start() : void{
		if($this->wantedPosition !== null){
			$this->mob->getNavigation()->moveTo($this->wantedPosition, $this->speed);
		}
	}

	public function stop() : void{
		$this->wantedPosition = null;
		$this->mob->getNavigation()->stop();
	}
}
