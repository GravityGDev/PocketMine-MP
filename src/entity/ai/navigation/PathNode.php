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

use pocketmine\math\Vector3;

final class PathNode{
	public function __construct(
		public int $x,
		public int $y,
		public int $z,
		public float $gCost,
		public float $hCost,
		public ?string $parent
	){}

	public static function makeKey(int $x, int $y, int $z) : string{
		return $x . ":" . $y . ":" . $z;
	}

	public function getKey() : string{
		return self::makeKey($this->x, $this->y, $this->z);
	}

	public function getEstimatedTotalCost() : float{
		return $this->gCost + $this->hCost;
	}

	public function asWaypoint() : Vector3{
		return new Vector3($this->x + 0.5, $this->y, $this->z + 0.5);
	}
}
