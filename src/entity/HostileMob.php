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

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

abstract class HostileMob extends Mob{
	private const TAG_NATURALLY_SPAWNED = "NaturallySpawned"; //TAG_Byte

	private bool $naturallySpawned = false;

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->naturallySpawned = $nbt->getByte(self::TAG_NATURALLY_SPAWNED, 0) !== 0;
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setByte(self::TAG_NATURALLY_SPAWNED, $this->naturallySpawned ? 1 : 0);
		return $nbt;
	}

	public function isNaturallySpawned() : bool{
		return $this->naturallySpawned;
	}

	public function setNaturallySpawned(bool $naturallySpawned = true) : void{
		$this->naturallySpawned = $naturallySpawned;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->getWorld()->getDifficulty() === World::DIFFICULTY_PEACEFUL){
			$this->flagForDespawn();
		}

		return parent::entityBaseTick($tickDiff);
	}
}
