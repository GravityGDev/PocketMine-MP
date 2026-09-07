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
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\data\bedrock\item;

use pocketmine\item\SpawnEgg;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Utils;

final class ParitySpawnEggItemMappings{

	private function __construct(){
		//NOOP
	}

	/** @return array<string, SpawnEgg> */
	private static function getMappings() : array{
		return [
			ItemTypeNames::BOGGED_SPAWN_EGG => VanillaItems::BOGGED_SPAWN_EGG(),
			ItemTypeNames::CAVE_SPIDER_SPAWN_EGG => VanillaItems::CAVE_SPIDER_SPAWN_EGG(),
			ItemTypeNames::CREEPER_SPAWN_EGG => VanillaItems::CREEPER_SPAWN_EGG(),
			ItemTypeNames::DROWNED_SPAWN_EGG => VanillaItems::DROWNED_SPAWN_EGG(),
			ItemTypeNames::ENDERMITE_SPAWN_EGG => VanillaItems::ENDERMITE_SPAWN_EGG(),
			ItemTypeNames::HUSK_SPAWN_EGG => VanillaItems::HUSK_SPAWN_EGG(),
			ItemTypeNames::PARCHED_SPAWN_EGG => VanillaItems::PARCHED_SPAWN_EGG(),
			ItemTypeNames::SILVERFISH_SPAWN_EGG => VanillaItems::SILVERFISH_SPAWN_EGG(),
			ItemTypeNames::SKELETON_SPAWN_EGG => VanillaItems::SKELETON_SPAWN_EGG(),
			ItemTypeNames::SPIDER_SPAWN_EGG => VanillaItems::SPIDER_SPAWN_EGG(),
			ItemTypeNames::STRAY_SPAWN_EGG => VanillaItems::STRAY_SPAWN_EGG(),
			ItemTypeNames::WITHER_SKELETON_SPAWN_EGG => VanillaItems::WITHER_SKELETON_SPAWN_EGG(),
			ItemTypeNames::ZOMBIE_VILLAGER_SPAWN_EGG => VanillaItems::ZOMBIE_VILLAGER_SPAWN_EGG(),
		];
	}

	public static function register(?ItemDeserializer $deserializer, ?ItemSerializer $serializer) : void{
		foreach(Utils::stringifyKeys(self::getMappings()) as $bedrockId => $item){
			$deserializer?->map($bedrockId, fn() => clone $item);
			$serializer?->map($item, fn() => new SavedItemData($bedrockId));
		}
	}
}
