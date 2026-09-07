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

use pocketmine\entity\Mob;
use pocketmine\math\Vector3;
use pocketmine\utils\ReversePriorityQueue;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use function abs;
use function array_reverse;
use function count;
use function floor;

/**
 * Bounded A* pathfinder for ordinary walking mobs.
 *
 * This first implementation intentionally searches cardinal neighbours only.
 * It supports one-block step-ups and drops of up to three blocks, avoids cells
 * with block collision boxes in the mob's two-block-tall body space, and never
 * asks the world to generate chunks just to find a path.
 */
class GroundPathfinder{
	protected const MAX_STEP_UP = 1;
	protected const MAX_DROP = 3;

	public function __construct(
		protected Mob $mob,
		protected int $maxVisitedNodes = 768
	){}

	/**
	 * @return list<Vector3>|null
	 */
	public function findPath(Vector3 $target, float $maxDistance = 40.0) : ?array{
		$position = $this->mob->getPosition();
		$startX = (int) floor($position->x);
		$startY = $this->findNearestWalkableY($startX, (int) floor($position->z), (int) floor($position->y));
		$startZ = (int) floor($position->z);
		if($startY === null){
			return null;
		}

		$goalX = (int) floor($target->x);
		$goalZ = (int) floor($target->z);
		$goalY = $this->findNearestWalkableY($goalX, $goalZ, (int) floor($target->y));
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
			$this->heuristic($startX, $startY, $startZ, $goalX, $goalY, $goalZ),
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

			foreach([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$offsetX, $offsetZ]){
				$nextX = $current->x + $offsetX;
				$nextZ = $current->z + $offsetZ;
				$fromStartX = $nextX - $startX;
				$fromStartZ = $nextZ - $startZ;
				if(($fromStartX * $fromStartX + $fromStartZ * $fromStartZ) > $maxDistanceSquared){
					continue;
				}

				$nextY = $this->findNeighbourY($nextX, $nextZ, $current->y);
				if($nextY === null){
					continue;
				}

				$key = PathNode::makeKey($nextX, $nextY, $nextZ);
				if(isset($closed[$key])){
					continue;
				}

				$verticalDistance = abs($nextY - $current->y);
				$gCost = $current->gCost + 1.0 + ($verticalDistance * 0.35);
				$existing = $nodes[$key] ?? null;
				if($existing !== null && $gCost >= $existing->gCost){
					continue;
				}

				$node = new PathNode(
					$nextX,
					$nextY,
					$nextZ,
					$gCost,
					$this->heuristic($nextX, $nextY, $nextZ, $goalX, $goalY, $goalZ),
					$currentKey
				);
				$nodes[$key] = $node;
				$open->insert($key, $node->getEstimatedTotalCost());
			}
		}

		return null;
	}

	protected function findNeighbourY(int $x, int $z, int $fromY) : ?int{
		if($this->isWalkableNode($x, $fromY, $z)){
			return $fromY;
		}

		for($step = 1; $step <= self::MAX_STEP_UP; ++$step){
			if($this->isWalkableNode($x, $fromY + $step, $z)){
				return $fromY + $step;
			}
		}

		for($drop = 1; $drop <= self::MAX_DROP; ++$drop){
			if($this->isWalkableNode($x, $fromY - $drop, $z)){
				return $fromY - $drop;
			}
		}

		return null;
	}

	protected function findNearestWalkableY(int $x, int $z, int $preferredY) : ?int{
		foreach([0, 1, -1, 2, -2, 3, -3] as $offset){
			$y = $preferredY + $offset;
			if($this->isWalkableNode($x, $y, $z)){
				return $y;
			}
		}
		return null;
	}

	protected function isWalkableNode(int $x, int $y, int $z) : bool{
		if($y <= World::Y_MIN || $y + 1 >= World::Y_MAX){
			return false;
		}

		$world = $this->mob->getWorld();
		if($world->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE) === null){
			return false;
		}

		if(count($world->getBlockAt($x, $y, $z)->getCollisionBoxes()) !== 0){
			return false;
		}
		if(count($world->getBlockAt($x, $y + 1, $z)->getCollisionBoxes()) !== 0){
			return false;
		}

		return count($world->getBlockAt($x, $y - 1, $z)->getCollisionBoxes()) !== 0;
	}

	protected function heuristic(int $x, int $y, int $z, int $goalX, int $goalY, int $goalZ) : float{
		return abs($goalX - $x) + abs($goalZ - $z) + (abs($goalY - $y) * 0.35);
	}

	/**
	 * @param array<string, PathNode> $nodes
	 * @return list<Vector3>|null
	 */
	protected function reconstructPath(PathNode $end, array $nodes) : ?array{
		$path = [];
		$current = $end;
		while($current->parent !== null){
			$path[] = $current->asWaypoint();
			$parent = $nodes[$current->parent] ?? null;
			if($parent === null){
				return null;
			}
			$current = $parent;
		}

		return array_reverse($path);
	}
}
