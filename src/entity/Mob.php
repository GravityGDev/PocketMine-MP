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

namespace pocketmine\entity;

use pocketmine\entity\ai\goal\GoalSelector;
use pocketmine\nbt\tag\CompoundTag;

/**
 * Base class for server-driven vanilla-style mobs.
 *
 * 5.46 introduces the goal scheduler first; navigation, sensing and natural
 * spawning are layered onto this class in subsequent development patches.
 */
abstract class Mob extends Living{
	private const TAG_NO_AI = "NoAI"; //TAG_Byte

	private GoalSelector $goalSelector;
	private GoalSelector $targetSelector;
	private bool $hasAi = true;

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);

		$this->hasAi = $nbt->getByte(self::TAG_NO_AI, 0) === 0;
		$this->goalSelector = new GoalSelector();
		$this->targetSelector = new GoalSelector();
		$this->registerGoals();
	}

	protected function registerGoals() : void{
		//Implemented by concrete mobs as vanilla behaviours are restored.
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setByte(self::TAG_NO_AI, $this->hasAi ? 0 : 1);
		return $nbt;
	}

	public function hasAi() : bool{
		return $this->hasAi;
	}

	public function setHasAi(bool $hasAi = true) : void{
		$this->hasAi = $hasAi;
	}

	public function getGoalSelector() : GoalSelector{
		return $this->goalSelector;
	}

	public function getTargetSelector() : GoalSelector{
		return $this->targetSelector;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->hasAi && $this->isAlive()){
			$this->targetSelector->tick();
			$this->goalSelector->tick();
		}

		return $hasUpdate;
	}
}
