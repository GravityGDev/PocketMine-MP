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

namespace pocketmine\entity;

use pocketmine\world\World;
use function count;
use function floor;

abstract class Undead extends HostileMob{
	private const DAYLIGHT_BURN_CHECK_INTERVAL = 10;
	private const DAYLIGHT_BURN_MIN_SKY_LIGHT = 12;
	private const DAYLIGHT_BURN_SECONDS = 8;

	private int $daylightBurnCheckTicks = 0;

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);
		if(!$this->isFlaggedForDespawn() && ($this->daylightBurnCheckTicks -= $tickDiff) <= 0){
			$this->daylightBurnCheckTicks = self::DAYLIGHT_BURN_CHECK_INTERVAL;
			$this->tryBurnInDaylight();
		}

		return $hasUpdate;
	}

	private function tryBurnInDaylight() : void{
		$world = $this->getWorld();
		$eyePosition = $this->getEyePos();
		$x = (int) floor($eyePosition->x);
		$y = (int) floor($eyePosition->y);
		$z = (int) floor($eyePosition->z);

		if($world->getRealBlockSkyLightAt($x, $y, $z) < self::DAYLIGHT_BURN_MIN_SKY_LIGHT || !self::hasUnobstructedSky($world, $x, $y, $z)){
			return;
		}

		$this->setOnFire(self::DAYLIGHT_BURN_SECONDS);
	}

	private static function hasUnobstructedSky(World $world, int $x, int $y, int $z) : bool{
		$highestBlockY = $world->getHighestBlockAt($x, $z);
		if($highestBlockY === null || $highestBlockY <= $y){
			return true;
		}

		for($checkY = $y + 1; $checkY <= $highestBlockY; ++$checkY){
			if(count($world->getBlockAt($x, $checkY, $z)->getCollisionBoxes()) !== 0){
				return false;
			}
		}

		return true;
	}
}
