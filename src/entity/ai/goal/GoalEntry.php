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

final class GoalEntry{
	private bool $running = false;

	public function __construct(
		private int $priority,
		private Goal $goal
	){}

	public function getPriority() : int{
		return $this->priority;
	}

	public function getGoal() : Goal{
		return $this->goal;
	}

	public function isRunning() : bool{
		return $this->running;
	}

	public function canBeReplacedBy(self $replacement) : bool{
		return $this->goal->isInterruptible() && $replacement->priority < $this->priority;
	}

	public function start() : void{
		if(!$this->running){
			$this->running = true;
			$this->goal->start();
		}
	}

	public function tick() : void{
		$this->goal->tick();
	}

	public function stop() : void{
		if($this->running){
			$this->running = false;
			$this->goal->stop();
		}
	}
}
