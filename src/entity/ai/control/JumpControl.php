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

use pocketmine\entity\Mob;
use pocketmine\math\Vector3;

final class JumpControl{
	private bool $jumpRequested = false;

	public function __construct(private Mob $mob){}

	public function jump() : void{
		$this->jumpRequested = true;
	}

	public function tick() : void{
		if(!$this->jumpRequested){
			return;
		}
		$this->jumpRequested = false;

		if(!$this->mob->onGround){
			return;
		}

		$motion = $this->mob->getMotion();
		$this->mob->setMotion(new Vector3($motion->x, 0.42, $motion->z));
	}
}
