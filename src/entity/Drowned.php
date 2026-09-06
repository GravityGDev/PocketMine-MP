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

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\item\VanillaSpawnEggs;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use function mt_rand;

class Drowned extends Zombie{
	public static function getNetworkTypeId() : string{ return EntityIds::DROWNED; }

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
