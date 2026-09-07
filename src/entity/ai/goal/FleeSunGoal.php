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
use function count;
use function floor;
use function mt_rand;

/**
 * Looks for nearby shade while an undead mob is burning in daylight.
 */
final class FleeSunGoal extends Goal{
	private const MAX_SKY_LIGHT_IN_SHELTER = 11;

	private ?Vector3 $shelter = null;

	public function __construct(
		private Mob $mob,
		private float $speed = 0.1,
		private int $horizontalRadius = 10,
		private int $verticalRadius = 3,
		private int $searchAttempts = 20
	){
		$this->setFlags(self::FLAG_MOVE);
	}

	public function canStart() : bool{
		if(!$this->mob->isOnFire() || $this->mob->isUnderwater()){
			return false;
		}

		$this->shelter = $this->findShelter();
		return $this->shelter !== null;
	}

	public function canContinue() : bool{
		return $this->mob->isOnFire() && !$this->mob->getNavigation()->isDone();
	}

	public function start() : void{
		if($this->shelter !== null){
			$this->mob->getNavigation()->moveTo($this->shelter, $this->speed);
		}
	}

	public function stop() : void{
		$this->shelter = null;
		$this->mob->getNavigation()->stop();
	}

	private function findShelter() : ?Vector3{
		$world = $this->mob->getWorld();
		$position = $this->mob->getPosition();
		$baseX = (int) floor($position->x);
		$baseY = (int) floor($position->y);
		$baseZ = (int) floor($position->z);

		for($attempt = 0; $attempt < $this->searchAttempts; ++$attempt){
			$x = $baseX + mt_rand(-$this->horizontalRadius, $this->horizontalRadius);
			$y = $baseY + mt_rand(-$this->verticalRadius, $this->verticalRadius);
			$z = $baseZ + mt_rand(-$this->horizontalRadius, $this->horizontalRadius);

			if(!$world->isInWorld($x, $y, $z) || !$world->isChunkLoaded($x >> 4, $z >> 4)){
				continue;
			}
			if($world->getRealBlockSkyLightAt($x, $y + 1, $z) > self::MAX_SKY_LIGHT_IN_SHELTER){
				continue;
			}
			if(count($world->getBlockAt($x, $y, $z)->getCollisionBoxes()) !== 0 || count($world->getBlockAt($x, $y + 1, $z)->getCollisionBoxes()) !== 0){
				continue;
			}

			return new Vector3($x + 0.5, $y, $z + 0.5);
		}

		return null;
	}
}
