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
use function cos;
use function mt_rand;
use function sin;

final class RandomLookAroundGoal extends Goal{
	private int $remainingTicks = 0;
	private ?Vector3 $lookTarget = null;

	public function __construct(
		private Mob $mob,
		private int $interval = 40,
		private float $lookDistance = 4.0
	){
		$this->setFlags(self::FLAG_LOOK);
	}

	public function canStart() : bool{
		if(mt_rand(1, $this->interval) !== 1){
			return false;
		}

		$angle = mt_rand(0, 6283) / 1000;
		$eye = $this->mob->getEyePos();
		$this->lookTarget = new Vector3(
			$eye->x + cos($angle) * $this->lookDistance,
			$eye->y,
			$eye->z + sin($angle) * $this->lookDistance
		);
		$this->remainingTicks = mt_rand(20, 40);
		return true;
	}

	public function canContinue() : bool{
		return $this->remainingTicks > 0 && $this->lookTarget !== null;
	}

	public function start() : void{
		if($this->lookTarget !== null){
			$this->mob->getLookControl()->lookAt($this->lookTarget);
		}
	}

	public function tick() : void{
		--$this->remainingTicks;
	}

	public function stop() : void{
		$this->remainingTicks = 0;
		$this->lookTarget = null;
		$this->mob->getLookControl()->clear();
	}
}
