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

use pocketmine\entity\ai\goal\MeleeAttackGoal;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\World;

class CaveSpider extends Spider{
	public static function getNetworkTypeId() : string{ return EntityIds::CAVE_SPIDER; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(0.5, 0.7);
	}

	protected function getInitialMaxHealth() : int{
		return 12;
	}

	protected function createMeleeAttackGoal() : MeleeAttackGoal{
		return new MeleeAttackGoal(
			$this,
			0.12,
			$this->getMeleeDamage(),
			1.5,
			function(Mob $_mob, Player $target) : void{
				$duration = match($this->getWorld()->getDifficulty()){
					World::DIFFICULTY_NORMAL => 7 * 20,
					World::DIFFICULTY_HARD => 15 * 20,
					default => 0
				};
				if($duration > 0){
					$target->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), $duration));
				}
			}
		);
	}

	public function getName() : string{
		return "Cave Spider";
	}

	public function getPickedItem() : ?Item{
		return VanillaItems::CAVE_SPIDER_SPAWN_EGG();
	}
}
