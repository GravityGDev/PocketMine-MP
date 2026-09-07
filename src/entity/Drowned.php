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

use pocketmine\block\Water;
use pocketmine\entity\ai\goal\NearestPlayerTargetGoal;
use pocketmine\entity\ai\navigation\AmphibiousPathfinder;
use pocketmine\entity\ai\navigation\GroundNavigation;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\item\VanillaSpawnEggs;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\World;
use function floor;
use function mt_rand;

class Drowned extends Zombie{
	public static function getNetworkTypeId() : string{ return EntityIds::DROWNED; }

	protected function createNavigation() : GroundNavigation{
		return new GroundNavigation($this, new AmphibiousPathfinder($this));
	}

	protected function createPlayerTargetGoal() : NearestPlayerTargetGoal{
		$targetFilter = fn(Player $player) : bool => $this->canTargetPlayer($player);
		return new NearestPlayerTargetGoal($this, 12.0, $targetFilter, $targetFilter);
	}

	private function canTargetPlayer(Player $player) : bool{
		return $this->isNight() || $this->isEntityInWater($player);
	}

	private function isNight() : bool{
		$time = $this->getWorld()->getTimeOfDay();
		return $time >= World::TIME_NIGHT && $time < World::TIME_SUNRISE;
	}

	private function isEntityInWater(Entity $entity) : bool{
		$position = $entity->getPosition();
		return $entity->isUnderwater() || $entity->getWorld()->getBlockAt(
			(int) floor($position->x),
			(int) floor($position->y),
			(int) floor($position->z)
		) instanceof Water;
	}

	public function canNavigateInWater() : bool{
		return true;
	}

	public function getName() : string{
		return "Drowned";
	}

	public function canBreathe() : bool{
		return true;
	}

	public function getDrops() : array{
		return [
			VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 2))
		];
	}

	public function getPickedItem() : ?Item{
		return VanillaSpawnEggs::DROWNED();
	}
}
