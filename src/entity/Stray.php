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

use pocketmine\entity\ai\goal\LookAtPlayerGoal;
use pocketmine\entity\ai\goal\NearestPlayerTargetGoal;
use pocketmine\entity\ai\goal\RandomStrollGoal;
use pocketmine\entity\ai\goal\RangedAttackGoal;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Stray extends Skeleton{
	public static function getNetworkTypeId() : string{ return EntityIds::STRAY; }

	protected function registerGoals() : void{
		$this->getTargetSelector()->addGoal(1, new NearestPlayerTargetGoal($this, 32.0));
		$this->getGoalSelector()->addGoal(2, new RangedAttackGoal(
			$this,
			0.1,
			15.0,
			60,
			40,
			new EffectInstance(VanillaEffects::SLOWNESS(), 30 * 20)
		));
		$this->getGoalSelector()->addGoal(7, new RandomStrollGoal($this, 0.08, 8, 80));
		$this->getGoalSelector()->addGoal(8, new LookAtPlayerGoal($this, 8.0));
	}

	public function getName() : string{
		return "Stray";
	}
}
