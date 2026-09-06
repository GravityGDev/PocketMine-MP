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

namespace pocketmine\world\spawner;

use PHPUnit\Framework\TestCase;
use pocketmine\world\World;

final class NaturalMobSpawnerTest extends TestCase{
	public function testHostileSpawningDifficultyRange() : void{
		self::assertFalse(NaturalMobSpawner::isHostileSpawningAllowed(World::DIFFICULTY_PEACEFUL));
		self::assertTrue(NaturalMobSpawner::isHostileSpawningAllowed(World::DIFFICULTY_EASY));
		self::assertTrue(NaturalMobSpawner::isHostileSpawningAllowed(World::DIFFICULTY_NORMAL));
		self::assertTrue(NaturalMobSpawner::isHostileSpawningAllowed(World::DIFFICULTY_HARD));
		self::assertFalse(NaturalMobSpawner::isHostileSpawningAllowed(-1));
		self::assertFalse(NaturalMobSpawner::isHostileSpawningAllowed(4));
	}
}
