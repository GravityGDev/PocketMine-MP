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

namespace pocketmine\world\spawn;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\world\World;

interface NaturalSpawnRule{
	public function getPopulationControl() : string;

	public function findSpawnPosition(World $world, int $chunkX, int $chunkZ) : ?Vector3;

	public function findGroupSpawnPosition(World $world, Vector3 $origin) : ?Vector3;

	public function canSpawnAt(World $world, Vector3 $position) : bool;

	public function getWeight(World $world, Vector3 $position) : int;

	public function getDensityLimit(World $world, Vector3 $position) : int;

	public function getMinGroupSize() : int;

	public function getMaxGroupSize() : int;

	public function matchesPopulationEntity(Entity $entity) : bool;

	public function createEntity(World $world, Vector3 $position) : Entity;
}
