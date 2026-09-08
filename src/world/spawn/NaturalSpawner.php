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

use pocketmine\entity\Mob;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use function abs;
use function array_values;
use function count;
use function mt_rand;

/**
 * Bedrock-style environmental spawn dispatcher.
 *
 * The world already maintains the exact set of chunks eligible for ticking.
 * This layer applies Bedrock's per-chunk environmental-spawn attempt roll and
 * delegates biome/entity-specific validation to NaturalSpawnRule instances.
 */
final class NaturalSpawner{
	public const POPULATION_MONSTER = "monster";
	public const POPULATION_ANIMAL = "animal";
	public const POPULATION_WATER_ANIMAL = "water_animal";
	public const POPULATION_AMBIENT = "ambient";

	private const SPAWN_ATTEMPT_NUMERATOR = 11;
	private const SPAWN_ATTEMPT_DENOMINATOR = 2000;
	private const GLOBAL_ENVIRONMENTAL_MOB_CAP = 200;
	private const MIN_PLAYER_DISTANCE = 24.0;
	private const SIMULATION_DISTANCE_FOUR_MAX_DISTANCE = 44.0;
	private const LARGE_SIMULATION_MAX_DISTANCE = 128.0;
	private const LARGE_SIMULATION_MAX_HORIZONTAL_DISTANCE = 96.0;
	private const DENSITY_CHUNK_RADIUS = 4;

	private static ?self $instance = null;

	/** @var NaturalSpawnRule[] */
	private array $rules;
	/** @var array<int, int> world ID => last processed server tick */
	private array $lastWorldTick = [];

	private function __construct(){
		$this->rules = [
			new DrownedSpawnRule()
		];
	}

	public static function getInstance() : self{
		return self::$instance ??= new self();
	}

	/**
	 * Called from Player random updates. Multiple players can be updated during
	 * one world tick, so each world is explicitly processed at most once.
	 */
	public function tickPlayer(Player $player) : void{
		$world = $player->getWorld();
		$currentTick = $world->getServer()->getTick();
		$worldId = $world->getId();
		if(($this->lastWorldTick[$worldId] ?? -1) === $currentTick){
			return;
		}
		$this->lastWorldTick[$worldId] = $currentTick;

		$this->tickWorld($world);
	}

	private function tickWorld(World $world) : void{
		$players = array_values($world->getPlayers());
		if(count($players) === 0 || count($this->rules) === 0){
			return;
		}
		if($this->countLoadedMobs($world) >= self::GLOBAL_ENVIRONMENTAL_MOB_CAP){
			return;
		}

		foreach($world->getTickingChunks() as $chunkHash){
			if(mt_rand(1, self::SPAWN_ATTEMPT_DENOMINATOR) > self::SPAWN_ATTEMPT_NUMERATOR){
				continue;
			}
			World::getXZ($chunkHash, $chunkX, $chunkZ);
			$this->attemptChunkSpawn($world, $players, $chunkX, $chunkZ);
			if($this->countLoadedMobs($world) >= self::GLOBAL_ENVIRONMENTAL_MOB_CAP){
				return;
			}
		}
	}

	/** @param Player[] $players */
	private function attemptChunkSpawn(World $world, array $players, int $chunkX, int $chunkZ) : void{
		/** @var list<array{NaturalSpawnRule, Vector3, int}> $eligible */
		$eligible = [];
		$totalWeight = 0;

		foreach($this->rules as $rule){
			$position = $rule->findSpawnPosition($world, $chunkX, $chunkZ);
			if($position === null || !$this->isWithinPlayerSpawnShell($world, $position, $players)){
				continue;
			}
			if(!$rule->canSpawnAt($world, $position)){
				continue;
			}

			$weight = $rule->getWeight($world, $position);
			if($weight <= 0 || !$this->hasPopulationRoom($world, $rule, $position)){
				continue;
			}

			$eligible[] = [$rule, $position, $weight];
			$totalWeight += $weight;
		}

		if($totalWeight <= 0){
			return;
		}

		$roll = mt_rand(1, $totalWeight);
		$selected = null;
		foreach($eligible as $entry){
			$roll -= $entry[2];
			if($roll <= 0){
				$selected = $entry;
				break;
			}
		}
		if($selected === null){
			return;
		}

		[$rule, $origin] = $selected;
		$this->spawnGroup($world, $players, $rule, $origin);
	}

	/** @param Player[] $players */
	private function spawnGroup(World $world, array $players, NaturalSpawnRule $rule, Vector3 $origin) : void{
		$targetSize = mt_rand($rule->getMinGroupSize(), $rule->getMaxGroupSize());
		$position = $origin;

		for($spawned = 0; $spawned < $targetSize; ++$spawned){
			if($spawned > 0){
				$position = $rule->findGroupSpawnPosition($world, $origin);
				if($position === null){
					continue;
				}
			}

			if(!$this->isWithinPlayerSpawnShell($world, $position, $players) || !$rule->canSpawnAt($world, $position)){
				continue;
			}
			if(!$this->hasPopulationRoom($world, $rule, $position) || $this->countLoadedMobs($world) >= self::GLOBAL_ENVIRONMENTAL_MOB_CAP){
				break;
			}

			$entity = $rule->createEntity($world, $position);
			$entity->spawnToAll();
		}
	}

	/** @param Player[] $players */
	private function isWithinPlayerSpawnShell(World $world, Vector3 $position, array $players) : bool{
		$simulationDistance = $world->getChunkTickRadius();
		$maxDistance = $simulationDistance <= 4 ? self::SIMULATION_DISTANCE_FOUR_MAX_DISTANCE : self::LARGE_SIMULATION_MAX_DISTANCE;
		$maxHorizontalDistance = $simulationDistance <= 4 ?
			self::SIMULATION_DISTANCE_FOUR_MAX_DISTANCE :
			($simulationDistance >= 8 ? self::LARGE_SIMULATION_MAX_HORIZONTAL_DISTANCE : self::LARGE_SIMULATION_MAX_DISTANCE);
		$minDistanceSquared = self::MIN_PLAYER_DISTANCE * self::MIN_PLAYER_DISTANCE;
		$maxDistanceSquared = $maxDistance * $maxDistance;
		$maxHorizontalDistanceSquared = $maxHorizontalDistance * $maxHorizontalDistance;
		$withinMaximum = false;

		foreach($players as $player){
			$playerPosition = $player->getPosition();
			$dx = $position->x - $playerPosition->x;
			$dy = $position->y - $playerPosition->y;
			$dz = $position->z - $playerPosition->z;
			$distanceSquared = $dx * $dx + $dy * $dy + $dz * $dz;
			if($distanceSquared < $minDistanceSquared){
				return false;
			}
			if($distanceSquared <= $maxDistanceSquared && ($dx * $dx + $dz * $dz) <= $maxHorizontalDistanceSquared){
				$withinMaximum = true;
			}
		}

		return $withinMaximum;
	}

	private function hasPopulationRoom(World $world, NaturalSpawnRule $rule, Vector3 $position) : bool{
		$densityLimit = $rule->getDensityLimit($world, $position);
		if($densityLimit <= 0 || $this->countRuleDensity($world, $rule, $position) >= $densityLimit){
			return false;
		}

		$populationCap = $this->getPopulationCap($rule->getPopulationControl());
		return $populationCap < 0 || $this->countPopulation($world, $rule->getPopulationControl(), $position) < $populationCap;
	}

	private function countRuleDensity(World $world, NaturalSpawnRule $rule, Vector3 $position) : int{
		$count = 0;
		$chunkX = $position->getFloorX() >> Chunk::COORD_BIT_SIZE;
		$chunkZ = $position->getFloorZ() >> Chunk::COORD_BIT_SIZE;
		foreach($world->getEntities() as $entity){
			if(!$rule->matchesPopulationEntity($entity)){
				continue;
			}
			$entityPosition = $entity->getPosition();
			if(abs(($entityPosition->getFloorX() >> Chunk::COORD_BIT_SIZE) - $chunkX) <= self::DENSITY_CHUNK_RADIUS &&
				abs(($entityPosition->getFloorZ() >> Chunk::COORD_BIT_SIZE) - $chunkZ) <= self::DENSITY_CHUNK_RADIUS){
				++$count;
			}
		}
		return $count;
	}

	private function countPopulation(World $world, string $populationControl, Vector3 $position) : int{
		$count = 0;
		$chunkX = $position->getFloorX() >> Chunk::COORD_BIT_SIZE;
		$chunkZ = $position->getFloorZ() >> Chunk::COORD_BIT_SIZE;
		foreach($world->getEntities() as $entity){
			$entityPosition = $entity->getPosition();
			if(abs(($entityPosition->getFloorX() >> Chunk::COORD_BIT_SIZE) - $chunkX) > self::DENSITY_CHUNK_RADIUS ||
				abs(($entityPosition->getFloorZ() >> Chunk::COORD_BIT_SIZE) - $chunkZ) > self::DENSITY_CHUNK_RADIUS){
				continue;
			}

			foreach($this->rules as $rule){
				if($rule->getPopulationControl() === $populationControl && $rule->matchesPopulationEntity($entity)){
					++$count;
					break;
				}
			}
		}
		return $count;
	}

	private function getPopulationCap(string $populationControl) : int{
		return match($populationControl){
			self::POPULATION_MONSTER => 8,
			self::POPULATION_ANIMAL => 4,
			self::POPULATION_WATER_ANIMAL => 36,
			self::POPULATION_AMBIENT => 2,
			default => -1
		};
	}

	private function countLoadedMobs(World $world) : int{
		$count = 0;
		foreach($world->getEntities() as $entity){
			if($entity instanceof Mob){
				++$count;
			}
		}
		return $count;
	}
}
