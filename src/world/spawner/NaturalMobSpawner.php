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
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\world\spawner;

use pocketmine\block\Liquid;
use pocketmine\block\utils\SupportType;
use pocketmine\entity\Creeper;
use pocketmine\entity\Entity;
use pocketmine\entity\HostileMob;
use pocketmine\entity\Location;
use pocketmine\entity\Skeleton;
use pocketmine\entity\Zombie;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\world\World;
use function count;
use function floor;
use function max;
use function min;
use function mt_rand;

final class NaturalMobSpawner{
	private const SPAWN_INTERVAL_TICKS = 20;
	private const ATTEMPTS_PER_PLAYER = 8;
	private const MAX_SPAWNS_PER_CYCLE = 8;
	private const MAX_NATURAL_HOSTILES = 70;
	private const MIN_SPAWN_DISTANCE_SQUARED = 24 * 24;
	private const MAX_SPAWN_DISTANCE_SQUARED = 128 * 128;
	private const MAX_HOSTILE_LIGHT = 7;
	private const UNDERGROUND_SEARCH_DEPTH = 24;

	private const ZOMBIE_WEIGHT = 100;
	private const SKELETON_WEIGHT = 80;
	private const CREEPER_WEIGHT = 80;

	private const MOB_ZOMBIE = 0;
	private const MOB_SKELETON = 1;
	private const MOB_CREEPER = 2;

	public static function isHostileSpawningAllowed(int $difficulty) : bool{
		return $difficulty >= World::DIFFICULTY_EASY && $difficulty <= World::DIFFICULTY_HARD;
	}

	public static function tick(World $world, int $currentTick) : void{
		if(!self::isHostileSpawningAllowed($world->getDifficulty())){
			self::despawnForPeaceful($world);
			return;
		}

		if($currentTick % self::SPAWN_INTERVAL_TICKS !== 0){
			return;
		}

		$players = [];
		foreach($world->getPlayers() as $player){
			if($player->isAlive() && !$player->isSpectator()){
				$players[] = $player;
			}
		}
		if(count($players) === 0){
			return;
		}

		self::despawnDistantNaturalHostiles($world, $players);

		$naturalHostileCount = 0;
		foreach($world->getEntities() as $entity){
			if(self::isNaturalHostile($entity) && !$entity->isFlaggedForDespawn()){
				++$naturalHostileCount;
			}
		}

		$remainingCapacity = self::MAX_NATURAL_HOSTILES - $naturalHostileCount;
		if($remainingCapacity <= 0){
			return;
		}

		$spawnedThisCycle = 0;
		foreach($players as $player){
			$playerPosition = $player->getPosition();
			$playerX = (int) floor($playerPosition->x);
			$playerZ = (int) floor($playerPosition->z);

			for($attempt = 0; $attempt < self::ATTEMPTS_PER_PLAYER; ++$attempt){
				if($remainingCapacity <= 0 || $spawnedThisCycle >= self::MAX_SPAWNS_PER_CYCLE){
					return;
				}

				$x = $playerX + mt_rand(-128, 128);
				$z = $playerZ + mt_rand(-128, 128);
				if(!$world->isChunkLoaded($x >> 4, $z >> 4)){
					continue;
				}

				$surface = mt_rand(0, 1) === 0;
				$y = self::findSpawnY($world, $x, $z, $surface);
				if($y === null || !self::isWithinSpawnDistance($players, $x + 0.5, $y, $z + 0.5)){
					continue;
				}

				$mobType = self::pickMobType();
				$groupSize = match($mobType){
					self::MOB_ZOMBIE => mt_rand(2, 4),
					self::MOB_SKELETON => mt_rand(1, 2),
					self::MOB_CREEPER => 1
				};
				$usedPositions = [];
				for($member = 0; $member < $groupSize; ++$member){
					if($remainingCapacity <= 0 || $spawnedThisCycle >= self::MAX_SPAWNS_PER_CYCLE){
						return;
					}

					$groupX = $x;
					$groupZ = $z;
					$groupY = $y;
					if($member !== 0){
						$placed = false;
						for($placementAttempt = 0; $placementAttempt < 4; ++$placementAttempt){
							$candidateX = $x + mt_rand(-4, 4);
							$candidateZ = $z + mt_rand(-4, 4);
							if(!$world->isChunkLoaded($candidateX >> 4, $candidateZ >> 4)){
								continue;
							}

							$candidateY = self::findSpawnY($world, $candidateX, $candidateZ, $surface, $surface ? null : $y + 4);
							if($candidateY === null || !self::isWithinSpawnDistance($players, $candidateX + 0.5, $candidateY, $candidateZ + 0.5)){
								continue;
							}

							$key = "$candidateX:$candidateY:$candidateZ";
							if(isset($usedPositions[$key])){
								continue;
							}

							$groupX = $candidateX;
							$groupY = $candidateY;
							$groupZ = $candidateZ;
							$placed = true;
							break;
						}
						if(!$placed){
							continue;
						}
					}

					$usedPositions["$groupX:$groupY:$groupZ"] = true;
					$location = new Location($groupX + 0.5, $groupY, $groupZ + 0.5, $world, (float) mt_rand(0, 359), 0.0);
					$hostile = match($mobType){
						self::MOB_ZOMBIE => new Zombie($location),
						self::MOB_SKELETON => new Skeleton($location),
						self::MOB_CREEPER => new Creeper($location)
					};
					$hostile->setNaturallySpawned();
					$hostile->spawnToAll();
					--$remainingCapacity;
					++$spawnedThisCycle;
				}
			}
		}
	}

	private static function pickMobType() : int{
		$roll = mt_rand(1, self::ZOMBIE_WEIGHT + self::SKELETON_WEIGHT + self::CREEPER_WEIGHT);
		if($roll <= self::ZOMBIE_WEIGHT){
			return self::MOB_ZOMBIE;
		}
		if($roll <= self::ZOMBIE_WEIGHT + self::SKELETON_WEIGHT){
			return self::MOB_SKELETON;
		}
		return self::MOB_CREEPER;
	}

	private static function findSpawnY(World $world, int $x, int $z, bool $surface, ?int $preferredY = null) : ?int{
		$highestY = $world->getHighestBlockAt($x, $z);
		if($highestY === null){
			return null;
		}

		$minimumY = $world->getMinY() + 1;
		$maximumY = min($highestY + 1, $world->getMaxY() - 2);
		if($maximumY < $minimumY){
			return null;
		}

		if($surface){
			return self::isValidSpawnSpace($world, $x, $maximumY, $z) ? $maximumY : null;
		}

		$undergroundMaximumY = min($highestY, $world->getMaxY() - 2);
		if($undergroundMaximumY < $minimumY){
			return null;
		}

		$startY = $preferredY === null ? mt_rand($minimumY, $undergroundMaximumY) : min($undergroundMaximumY, max($minimumY, $preferredY));
		$endY = max($minimumY, $startY - self::UNDERGROUND_SEARCH_DEPTH);
		for($y = $startY; $y >= $endY; --$y){
			if(self::isValidSpawnSpace($world, $x, $y, $z)){
				return $y;
			}
		}

		return null;
	}

	private static function isValidSpawnSpace(World $world, int $x, int $y, int $z) : bool{
		if($world->getFullLightAt($x, $y, $z) > self::MAX_HOSTILE_LIGHT){
			return false;
		}

		$ground = $world->getBlockAt($x, $y - 1, $z);
		$feet = $world->getBlockAt($x, $y, $z);
		$head = $world->getBlockAt($x, $y + 1, $z);

		if($ground instanceof Liquid || $feet instanceof Liquid || $head instanceof Liquid){
			return false;
		}
		if(!$ground->isSolid() || $ground->getSupportType(Facing::UP) !== SupportType::FULL){
			return false;
		}

		return count($feet->getCollisionBoxes()) === 0 && count($head->getCollisionBoxes()) === 0;
	}

	/**
	 * @param list<Player> $players
	 */
	private static function isWithinSpawnDistance(array $players, float $x, float $y, float $z) : bool{
		$withinMaximum = false;
		foreach($players as $player){
			$position = $player->getPosition();
			$dx = $position->x - $x;
			$dy = $position->y - $y;
			$dz = $position->z - $z;
			$distanceSquared = $dx * $dx + $dy * $dy + $dz * $dz;
			if($distanceSquared < self::MIN_SPAWN_DISTANCE_SQUARED){
				return false;
			}
			if($distanceSquared <= self::MAX_SPAWN_DISTANCE_SQUARED){
				$withinMaximum = true;
			}
		}

		return $withinMaximum;
	}

	private static function isNaturalHostile(Entity $entity) : bool{
		return $entity instanceof HostileMob && $entity->isNaturallySpawned();
	}

	/**
	 * @param list<Player> $players
	 */
	private static function despawnDistantNaturalHostiles(World $world, array $players) : void{
		foreach($world->getEntities() as $entity){
			if(!self::isNaturalHostile($entity) || $entity->isFlaggedForDespawn()){
				continue;
			}

			$position = $entity->getPosition();
			$nearPlayer = false;
			foreach($players as $player){
				$playerPosition = $player->getPosition();
				$dx = $playerPosition->x - $position->x;
				$dy = $playerPosition->y - $position->y;
				$dz = $playerPosition->z - $position->z;
				if($dx * $dx + $dy * $dy + $dz * $dz <= self::MAX_SPAWN_DISTANCE_SQUARED){
					$nearPlayer = true;
					break;
				}
			}

			if(!$nearPlayer){
				$entity->flagForDespawn();
			}
		}
	}

	private static function despawnForPeaceful(World $world) : void{
		foreach($world->getEntities() as $entity){
			if($entity instanceof HostileMob && !$entity->isFlaggedForDespawn()){
				$entity->flagForDespawn();
			}
		}
	}
}
