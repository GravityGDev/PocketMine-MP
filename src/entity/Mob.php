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

use pocketmine\entity\ai\control\JumpControl;
use pocketmine\entity\ai\control\LookControl;
use pocketmine\entity\ai\control\MoveControl;
use pocketmine\entity\ai\goal\GoalSelector;
use pocketmine\entity\ai\navigation\GroundNavigation;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\CompoundTag;

/**
 * Base class for server-driven vanilla-style mobs.
 */
abstract class Mob extends Living{
	private const TAG_NO_AI = "NoAI"; //TAG_Byte

	private GoalSelector $goalSelector;
	private GoalSelector $targetSelector;
	private MoveControl $moveControl;
	private LookControl $lookControl;
	private JumpControl $jumpControl;
	private GroundNavigation $navigation;
	private bool $hasAi = true;

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);

		$this->hasAi = $nbt->getByte(self::TAG_NO_AI, 0) === 0;
		$this->goalSelector = new GoalSelector();
		$this->targetSelector = new GoalSelector();
		$this->jumpControl = new JumpControl($this);
		$this->moveControl = new MoveControl($this, $this->jumpControl);
		$this->lookControl = new LookControl($this);
		$this->navigation = new GroundNavigation($this);
		$this->stepHeight = 0.6;
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
		if(!$hasAi){
			$this->goalSelector->clear();
			$this->targetSelector->clear();
			$this->navigation->stop();
			$this->lookControl->clear();
		}
	}

	public function getGoalSelector() : GoalSelector{
		return $this->goalSelector;
	}

	public function getTargetSelector() : GoalSelector{
		return $this->targetSelector;
	}

	public function getMoveControl() : MoveControl{
		return $this->moveControl;
	}

	public function getLookControl() : LookControl{
		return $this->lookControl;
	}

	public function getJumpControl() : JumpControl{
		return $this->jumpControl;
	}

	public function getNavigation() : GroundNavigation{
		return $this->navigation;
	}

	public function canSee(Entity $target) : bool{
		if($target->getWorld() !== $this->getWorld()){
			return false;
		}

		$start = $this->getEyePos();
		$end = $target->getEyePos();
		if($start->distanceSquared($end) <= 1.0e-10){
			return true;
		}

		$world = $this->getWorld();
		foreach(VoxelRayTrace::betweenPoints($start, $end) as $blockPosition){
			$x = (int) $blockPosition->x;
			$y = (int) $blockPosition->y;
			$z = (int) $blockPosition->z;
			if(!$world->isChunkLoaded($x >> 4, $z >> 4)){
				return false;
			}

			if($world->getBlockAt($x, $y, $z)->calculateIntercept($start, $end) !== null){
				return false;
			}
		}

		return true;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->hasAi && $this->isAlive()){
			$this->targetSelector->tick();
			$this->goalSelector->tick();
			$this->navigation->tick();
			$this->moveControl->tick();
			$this->lookControl->tick();
			$this->jumpControl->tick();
		}

		return $hasUpdate;
	}
}
