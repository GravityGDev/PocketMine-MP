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

use pocketmine\block\Water;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Drowned;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use function count;
use function mt_rand;

final class DrownedSpawnRule implements NaturalSpawnRule{
	private const COLUMN_ATTEMPTS = 4;
	private const GROUP_POSITION_ATTEMPTS = 8;

	private const OCEAN_WEIGHT = 100;
	private const OCEAN_DENSITY_LIMIT = 5;
	private const RIVER_WEIGHT = 5;
	private const RIVER_DENSITY_LIMIT = 2;
	private const DRIPSTONE_WEIGHT = 100;
	private const DRIPSTONE_DENSITY_LIMIT = 2;

	public function getPopulationControl() : string{
		return NaturalSpawner::POPULATION_MONSTER;
	}

	public function findSpawnPosition(World $world, int $chunkX, int $chunkZ) : ?Vector3{
		$chunk = $world->getChunk($chunkX, $chunkZ);
		if($chunk === null){
			return null;
		}

		for($attempt = 0; $attempt < self::COLUMN_ATTEMPTS; ++$attempt){
			$localX = mt_rand(0, Chunk::COORD_MASK);
			$localZ = mt_rand(0, Chunk::COORD_MASK);
			$highestY = $chunk->getHighestBlockAt($localX, $localZ);
			if($highestY === null){
				continue;
			}

			$x = ($chunkX << Chunk::COORD_BIT_SIZE) + $localX;
			$z = ($chunkZ << Chunk::COORD_BIT_SIZE) + $localZ;
			$position = $this->findWaterSurfaceInColumn($world, $x, $z, $highestY, $world->getMinY() + 1);
			if($position !== null){
				return $position;
			}
		}

		return null;
	}

	public function findGroupSpawnPosition(World $world, Vector3 $origin) : ?Vector3{
		$originX = $origin->getFloorX();
		$originY = $origin->getFloorY();
		$originZ = $origin->getFloorZ();

		for($attempt = 0; $attempt < self::GROUP_POSITION_ATTEMPTS; ++$attempt){
			$x = $originX + mt_rand(-4, 4);
			$z = $originZ + mt_rand(-4, 4);
			if(!$world->isChunkLoaded($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE)){
				continue;
			}

			$position = $this->findWaterSurfaceInColumn(
				$world,
				$x,
				$z,
				$originY + 4,
				$originY - 4
			);
			if($position !== null){
				return $position;
			}
		}

		return null;
	}

	public function canSpawnAt(World $world, Vector3 $position) : bool{
		if($world->getDifficulty() === World::DIFFICULTY_PEACEFUL){
			return false;
		}

		$x = $position->getFloorX();
		$y = $position->getFloorY();
		$z = $position->getFloorZ();
		if(!$world->isInWorld($x, $y, $z) || !$world->isInWorld($x, $y + 1, $z)){
			return false;
		}
		if(!$world->isChunkLoaded($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE)){
			return false;
		}
		if(!$world->getBlockAt($x, $y, $z) instanceof Water || !$world->getBlockAt($x, $y + 1, $z) instanceof Water){
			return false;
		}
		if(count($world->getBlockAt($x, $y - 1, $z)->getCollisionBoxes()) === 0){
			return false;
		}
		if($world->getFullLightAt($x, $y, $z) > 7){
			return false;
		}

		return $this->getBiomeSpawnData($world, $position) !== null;
	}

	public function getWeight(World $world, Vector3 $position) : int{
		return $this->getBiomeSpawnData($world, $position)[0] ?? 0;
	}

	public function getDensityLimit(World $world, Vector3 $position) : int{
		return $this->getBiomeSpawnData($world, $position)[1] ?? 0;
	}

	public function getMinGroupSize() : int{
		return 2;
	}

	public function getMaxGroupSize() : int{
		return 4;
	}

	public function matchesPopulationEntity(Entity $entity) : bool{
		return $entity instanceof Drowned;
	}

	public function createEntity(World $world, Vector3 $position) : Entity{
		return new Drowned(new Location(
			$position->x + 0.5,
			$position->y,
			$position->z + 0.5,
			$world,
			(float) mt_rand(0, 359),
			0.0
		));
	}

	private function findWaterSurfaceInColumn(World $world, int $x, int $z, int $startY, int $endY) : ?Vector3{
		$maxY = $world->getMaxY() - 2;
		$minY = $world->getMinY() + 1;
		$startY = min($maxY, $startY);
		$endY = max($minY, $endY);
		if($startY < $endY){
			return null;
		}

		for($y = $startY; $y >= $endY; --$y){
			$position = new Vector3($x, $y, $z);
			if($this->canSpawnAt($world, $position)){
				return $position;
			}
		}
		return null;
	}

	/** @return array{int, int}|null */
	private function getBiomeSpawnData(World $world, Vector3 $position) : ?array{
		$x = $position->getFloorX();
		$y = $position->getFloorY();
		$z = $position->getFloorZ();
		$chunk = $world->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
		if($chunk === null){
			return null;
		}

		$biomeId = $chunk->getBiomeId($x & Chunk::COORD_MASK, $y, $z & Chunk::COORD_MASK);
		return match($biomeId){
			BiomeIds::RIVER,
			BiomeIds::FROZEN_RIVER => [self::RIVER_WEIGHT, self::RIVER_DENSITY_LIMIT],
			BiomeIds::DRIPSTONE_CAVES => [self::DRIPSTONE_WEIGHT, self::DRIPSTONE_DENSITY_LIMIT],
			BiomeIds::OCEAN,
			BiomeIds::LEGACY_FROZEN_OCEAN,
			BiomeIds::DEEP_OCEAN,
			BiomeIds::WARM_OCEAN,
			BiomeIds::DEEP_WARM_OCEAN,
			BiomeIds::LUKEWARM_OCEAN,
			BiomeIds::DEEP_LUKEWARM_OCEAN,
			BiomeIds::COLD_OCEAN,
			BiomeIds::DEEP_COLD_OCEAN,
			BiomeIds::FROZEN_OCEAN,
			BiomeIds::DEEP_FROZEN_OCEAN => [self::OCEAN_WEIGHT, self::OCEAN_DENSITY_LIMIT],
			default => null
		};
	}
}
