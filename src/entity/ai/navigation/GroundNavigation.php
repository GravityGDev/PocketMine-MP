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
use function abs;
use function count;

/**
 * Ground navigation backed by a bounded terrain-aware A* pathfinder.
 *
 * Paths are cached and only recalculated when the target moves far enough,
 * the current path finishes away from the target, or the mob appears stuck.
 */
final class GroundNavigation{
	private const TARGET_REPATH_DISTANCE_SQUARED = 2.25;
	private const WAYPOINT_REACHED_DISTANCE_SQUARED = 0.36;
	private const STUCK_CHECK_INTERVAL = 20;
	private const STUCK_DISTANCE_SQUARED = 0.04;
	private const REPATH_COOLDOWN = 10;

	private GroundPathfinder $pathfinder;
	private ?Vector3 $target = null;
	private float $speed = 0.1;
	/** @var list<Vector3> */
	private array $path = [];
	private int $pathIndex = 0;
	private bool $pathDirty = false;
	private int $repathCooldown = 0;
	private int $progressCheckTicks = 0;
	private ?Vector3 $lastProgressPosition = null;

	public function __construct(private Mob $mob, ?GroundPathfinder $pathfinder = null){
		$this->pathfinder = $pathfinder ?? new GroundPathfinder($mob);
	}

	public function moveTo(Vector3 $target, float $speed = 0.1) : void{
		$targetChanged = $this->target === null || $this->target->distanceSquared($target) > self::TARGET_REPATH_DISTANCE_SQUARED;
		$this->target = clone $target;
		$this->speed = $speed;
		if($targetChanged){
			$this->pathDirty = true;
		}
	}

	/**
	 * Checks whether the pathfinder can currently produce a path to a position
	 * without changing this navigation's active target.
	 */
	public function canReach(Vector3 $target) : bool{
		return $this->pathfinder->findPath($target) !== null;
	}

	public function stop() : void{
		$this->target = null;
		$this->path = [];
		$this->pathIndex = 0;
		$this->pathDirty = false;
		$this->repathCooldown = 0;
		$this->progressCheckTicks = 0;
		$this->lastProgressPosition = null;
		$this->mob->getMoveControl()->stop();
	}

	public function isDone() : bool{
		return $this->target === null;
	}

	public function tick() : void{
		if($this->repathCooldown > 0){
			--$this->repathCooldown;
		}

		if($this->target === null){
			return;
		}

		if($this->pathDirty && $this->repathCooldown === 0){
			$this->recalculatePath();
			if($this->target === null){
				return;
			}
		}

		if(count($this->path) === 0){
			return;
		}

		$position = $this->mob->getPosition();
		$pathCount = count($this->path);
		while($this->pathIndex < $pathCount){
			$waypoint = $this->path[$this->pathIndex];
			$dx = $waypoint->x - $position->x;
			$dy = $waypoint->y - $position->y;
			$dz = $waypoint->z - $position->z;
			if($this->mob->canNavigateInWater()){
				if(($dx * $dx + $dy * $dy + $dz * $dz) > self::WAYPOINT_REACHED_DISTANCE_SQUARED){
					break;
				}
			}elseif(($dx * $dx + $dz * $dz) > self::WAYPOINT_REACHED_DISTANCE_SQUARED || abs($dy) > 1.25){
				break;
			}
			++$this->pathIndex;
		}

		if($this->pathIndex >= $pathCount){
			$target = $this->target;
			$dx = $target->x - $position->x;
			$dy = $target->y - $position->y;
			$dz = $target->z - $position->z;
			$distanceSquared = $this->mob->canNavigateInWater() ?
				($dx * $dx + $dy * $dy + $dz * $dz) :
				($dx * $dx + $dz * $dz);
			if($distanceSquared <= self::WAYPOINT_REACHED_DISTANCE_SQUARED){
				$this->stop();
				return;
			}

			$this->pathDirty = true;
			if($this->repathCooldown === 0){
				$this->recalculatePath();
			}
			return;
		}

		$this->mob->getMoveControl()->setWantedPosition($this->path[$this->pathIndex], $this->speed);
		$this->checkForStuckMob($position);
	}

	private function recalculatePath() : void{
		$target = $this->target;
		if($target === null){
			return;
		}

		$path = $this->pathfinder->findPath($target);
		$this->pathDirty = false;
		$this->repathCooldown = self::REPATH_COOLDOWN;
		if($path === null){
			$this->abandonCurrentTarget();
			return;
		}
		if(count($path) === 0){
			$this->stop();
			return;
		}

		$this->path = $path;
		$this->pathIndex = 0;
		$this->progressCheckTicks = 0;
		$this->lastProgressPosition = $this->mob->getPosition();
	}

	private function abandonCurrentTarget() : void{
		$this->target = null;
		$this->path = [];
		$this->pathIndex = 0;
		$this->pathDirty = false;
		$this->progressCheckTicks = 0;
		$this->lastProgressPosition = null;
		$this->mob->getMoveControl()->stop();
	}

	private function checkForStuckMob(Vector3 $position) : void{
		++$this->progressCheckTicks;
		if($this->progressCheckTicks < self::STUCK_CHECK_INTERVAL){
			return;
		}

		if($this->lastProgressPosition !== null && $position->distanceSquared($this->lastProgressPosition) < self::STUCK_DISTANCE_SQUARED){
			$this->pathDirty = true;
			$this->repathCooldown = 0;
		}
		$this->lastProgressPosition = clone $position;
		$this->progressCheckTicks = 0;
	}
}
