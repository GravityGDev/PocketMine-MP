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

namespace pocketmine\entity\ai\goal;

/**
 * Base class for lightweight vanilla-style mob AI goals.
 *
 * Goals declare which control channels they need. The selector prevents goals
 * that want the same channel from running at the same time unless a higher
 * priority goal is allowed to interrupt the current one.
 */
abstract class Goal{
	public const FLAG_MOVE = 0;
	public const FLAG_LOOK = 1;
	public const FLAG_JUMP = 2;
	public const FLAG_TARGET = 3;

	/** @var array<int, int> */
	private array $flags = [];

	abstract public function canStart() : bool;

	public function canContinue() : bool{
		return $this->canStart();
	}

	public function isInterruptible() : bool{
		return true;
	}

	public function start() : void{
		//NOOP
	}

	public function tick() : void{
		//NOOP
	}

	public function stop() : void{
		//NOOP
	}

	/**
	 * @return array<int, int>
	 */
	public function getFlags() : array{
		return $this->flags;
	}

	public function setFlags(int ...$flags) : void{
		$this->flags = [];
		foreach($flags as $flag){
			if($flag < self::FLAG_MOVE || $flag > self::FLAG_TARGET){
				throw new \InvalidArgumentException("Invalid goal control flag $flag");
			}
			$this->flags[$flag] = $flag;
		}
	}
}
