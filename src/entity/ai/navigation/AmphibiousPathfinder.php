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

namespace pocketmine\entity\ai\navigation;

use pocketmine\block\Water;
use pocketmine\math\Vector3;
use pocketmine\utils\ReversePriorityQueue;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use function abs;
use function count;
use function floor;

/**
 * Hybrid A* pathfinder for mobs which can walk on land and swim through water.
 * Water nodes may move vertically, while shore transitions continue to use the
 * same step-up and drop rules as ordinary ground navigation.
 */
final class AmphibiousPathfinder extends GroundPathfinder{

	/**
	 * @return list<Vector3>|null
	 */
	public function findPath(Vector3 $target, float $maxDistance = 40.0) : ?array{
		$position = $this->mob->getPosition();
		$startX = (int) floor($position->x);
		$startZ = (int) floor($position->z);
		$startY = $this->findNearestNavigableY($startX, $startZ, (int) floor($position->y));
		if($startY === null){
			return null;
		}

		$goalX = (int) floor($target->x);
		$goalZ = (int) floor($target->z);
		$goalY = $this->findNearestNavigableY($goalX, $goalZ, (int) floor($target->y));
		if($goalY === null){
			return null;
		}

		$maxDistanceSquared = $maxDistance * $maxDistance;
		$goalDx = $goalX - $startX;
		$goalDz = $goalZ - $startZ;
		if(($goalDx * $goalDx + $goalDz * $goalDz) > $maxDistanceSquared){
			return null;
		}

		$start = new PathNode(
			$startX,
			$startY,
			$startZ,
			0.0,
			$this->amphibiousHeuristic($startX, $startY, $startZ, $goalX, $goalY, $goalZ),
			null
		);

		/** @var array<string, PathNode> $nodes */
		$nodes = [$start->getKey() => $start];
		/** @var array<string, true> $closed */
		$closed = [];
		/** @var ReversePriorityQueue<float, string> $open */
		$open = new ReversePriorityQueue();
		$open->setExtractFlags(\SplPriorityQueue::EXTR_DATA);
		$open->insert($start->getKey(), $start->getEstimatedTotalCost());

		$visited = 0;
		while(!$open->isEmpty() && $visited < $this->maxVisitedNodes){
			$currentKey = $open->extract();
			if(isset($closed[$currentKey])){
				continue;
			}

			$current = $nodes[$currentKey] ?? null;
			if($current === null){
				continue;
			}

			$closed[$currentKey] = true;
			++$visited;
			if($current->x === $goalX && $current->y === $goalY && $current->z === $goalZ){
				return $this->reconstructPath($current, $nodes);
			}

			foreach($this->getNavigableNeighbours($current) as [$nextX, $nextY, $nextZ]){
				$fromStartX = $nextX - $startX;
				$fromStartZ = $nextZ - $startZ;
				if(($fromStartX * $fromStartX + $fromStartZ * $fromStartZ) > $maxDistanceSquared){
					continue;
				}

				$key = PathNode::makeKey($nextX, $nextY, $nextZ);
				if(isset($closed[$key])){
					continue;
				}

				$verticalDistance = abs($nextY - $current->y);
				$gCost = $current->gCost + 1.0 + ($verticalDistance * 0.25);
				$existing = $nodes[$key] ?? null;
				if($existing !== null && $gCost >= $existing->gCost){
					continue;
				}

				$node = new PathNode(
					$nextX,
					$nextY,
					$nextZ,
					$gCost,
					$this->amphibiousHeuristic($nextX, $nextY, $nextZ, $goalX, $goalY, $goalZ),
					$currentKey
				);
				$nodes[$key] = $node;
				$open->insert($key, $node->getEstimatedTotalCost());
			}
		}

		return null;
	}

	private function findNearestNavigableY(int $x, int $z, int $preferredY) : ?int{
		foreach([0, 1, -1, 2, -2, 3, -3] as $offset){
			$y = $preferredY + $offset;
			if($this->isSwimmableNode($x, $y, $z)){
				return $y;
			}
		}

		return $this->findNearestWalkableY($x, $z, $preferredY);
	}

	/**
	 * @return list<array{int, int, int}>
	 */
	private function getNavigableNeighbours(PathNode $current) : array{
		/** @var array<string, array{int, int, int}> $neighbours */
		$neighbours = [];
		$currentIsWater = $this->isSwimmableNode($current->x, $current->y, $current->z);

		foreach([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$offsetX, $offsetZ]){
			$nextX = $current->x + $offsetX;
			$nextZ = $current->z + $offsetZ;

			foreach([0, 1, -1] as $verticalOffset){
				$nextY = $current->y + $verticalOffset;
				if($this->isSwimmableNode($nextX, $nextY, $nextZ)){
					$neighbours[PathNode::makeKey($nextX, $nextY, $nextZ)] = [$nextX, $nextY, $nextZ];
					break;
				}
			}

			$walkableY = $this->findNeighbourY($nextX, $nextZ, $current->y);
			if($walkableY !== null){
				$neighbours[PathNode::makeKey($nextX, $walkableY, $nextZ)] = [$nextX, $walkableY, $nextZ];
			}
		}

		if($currentIsWater){
			foreach([1, -1] as $verticalOffset){
				$nextY = $current->y + $verticalOffset;
				if($this->isSwimmableNode($current->x, $nextY, $current->z)){
					$neighbours[PathNode::makeKey($current->x, $nextY, $current->z)] = [$current->x, $nextY, $current->z];
				}
			}
		}

		return array_values($neighbours);
	}

	private function isSwimmableNode(int $x, int $y, int $z) : bool{
		if($y <= World::Y_MIN || $y + 1 >= World::Y_MAX){
			return false;
		}

		$world = $this->mob->getWorld();
		if($world->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE) === null){
			return false;
		}

		if(!($world->getBlockAt($x, $y, $z) instanceof Water)){
			return false;
		}

		return count($world->getBlockAt($x, $y, $z)->getCollisionBoxes()) === 0 &&
			count($world->getBlockAt($x, $y + 1, $z)->getCollisionBoxes()) === 0;
	}

	private function amphibiousHeuristic(int $x, int $y, int $z, int $goalX, int $goalY, int $goalZ) : float{
		return abs($goalX - $x) + abs($goalZ - $z) + (abs($goalY - $y) * 0.75);
	}
}
