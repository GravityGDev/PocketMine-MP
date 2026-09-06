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

use function array_values;
use function usort;

/**
 * Schedules mob goals by priority while enforcing MOVE/LOOK/JUMP/TARGET locks.
 * Lower priority numbers win, matching vanilla's goal selector convention.
 */
final class GoalSelector{
	/** @var list<GoalEntry> */
	private array $entries = [];
	/** @var array<int, GoalEntry> */
	private array $lockedFlags = [];
	/** @var array<int, true> */
	private array $disabledFlags = [];

	public function addGoal(int $priority, Goal $goal) : GoalEntry{
		if($priority < 0){
			throw new \InvalidArgumentException("Goal priority must be zero or greater");
		}
		$entry = new GoalEntry($priority, $goal);
		$this->entries[] = $entry;
		usort($this->entries, static fn(GoalEntry $a, GoalEntry $b) : int => $a->getPriority() <=> $b->getPriority());
		return $entry;
	}

	public function removeGoal(Goal $goal) : void{
		foreach($this->entries as $key => $entry){
			if($entry->getGoal() === $goal){
				$entry->stop();
				unset($this->entries[$key]);
			}
		}
		$this->entries = array_values($this->entries);
		$this->releaseStoppedLocks();
	}

	public function clear() : void{
		foreach($this->entries as $entry){
			$entry->stop();
		}
		$this->entries = [];
		$this->lockedFlags = [];
	}

	public function tick() : void{
		foreach($this->entries as $entry){
			if($entry->isRunning() && ($this->usesDisabledFlag($entry) || !$entry->getGoal()->canContinue())){
				$entry->stop();
			}
		}
		$this->releaseStoppedLocks();

		foreach($this->entries as $entry){
			if($entry->isRunning() || $this->usesDisabledFlag($entry) || !$this->canAcquireFlags($entry)){
				continue;
			}
			if(!$entry->getGoal()->canStart()){
				continue;
			}

			foreach($entry->getGoal()->getFlags() as $flag){
				if(isset($this->lockedFlags[$flag])){
					$this->lockedFlags[$flag]->stop();
				}
				$this->lockedFlags[$flag] = $entry;
			}
			$entry->start();
		}

		foreach($this->entries as $entry){
			if($entry->isRunning()){
				$entry->tick();
			}
		}
	}

	private function canAcquireFlags(GoalEntry $entry) : bool{
		foreach($entry->getGoal()->getFlags() as $flag){
			if(isset($this->lockedFlags[$flag]) && !$this->lockedFlags[$flag]->canBeReplacedBy($entry)){
				return false;
			}
		}
		return true;
	}

	private function usesDisabledFlag(GoalEntry $entry) : bool{
		foreach($entry->getGoal()->getFlags() as $flag){
			if(isset($this->disabledFlags[$flag])){
				return true;
			}
		}
		return false;
	}

	private function releaseStoppedLocks() : void{
		foreach($this->lockedFlags as $flag => $entry){
			if(!$entry->isRunning()){
				unset($this->lockedFlags[$flag]);
			}
		}
	}

	public function setControlFlag(int $flag, bool $enabled) : void{
		if($flag < Goal::FLAG_MOVE || $flag > Goal::FLAG_TARGET){
			throw new \InvalidArgumentException("Invalid goal control flag $flag");
		}
		if($enabled){
			unset($this->disabledFlags[$flag]);
		}else{
			$this->disabledFlags[$flag] = true;
		}
	}

	/** @return list<GoalEntry> */
	public function getAvailableGoals() : array{
		return $this->entries;
	}

	/** @return list<GoalEntry> */
	public function getRunningGoals() : array{
		$result = [];
		foreach($this->entries as $entry){
			if($entry->isRunning()){
				$result[] = $entry;
			}
		}
		return $result;
	}
}
