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
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaSpawnEggs;

final class ParitySpawnEggItemMappings{

	private function __construct(){
		//NOOP
	}

	/** @return array<string, SpawnEgg> */
	private static function getMappings() : array{
		return [
			ItemTypeNames::BOGGED_SPAWN_EGG => VanillaSpawnEggs::BOGGED(),
			ItemTypeNames::CAVE_SPIDER_SPAWN_EGG => VanillaSpawnEggs::CAVE_SPIDER(),
			ItemTypeNames::CREEPER_SPAWN_EGG => VanillaSpawnEggs::CREEPER(),
			ItemTypeNames::DROWNED_SPAWN_EGG => VanillaSpawnEggs::DROWNED(),
			ItemTypeNames::ENDERMITE_SPAWN_EGG => VanillaSpawnEggs::ENDERMITE(),
			ItemTypeNames::HUSK_SPAWN_EGG => VanillaSpawnEggs::HUSK(),
			ItemTypeNames::PARCHED_SPAWN_EGG => VanillaSpawnEggs::PARCHED(),
			ItemTypeNames::SILVERFISH_SPAWN_EGG => VanillaSpawnEggs::SILVERFISH(),
			ItemTypeNames::SKELETON_SPAWN_EGG => VanillaSpawnEggs::SKELETON(),
			ItemTypeNames::SPIDER_SPAWN_EGG => VanillaSpawnEggs::SPIDER(),
			ItemTypeNames::STRAY_SPAWN_EGG => VanillaSpawnEggs::STRAY(),
			ItemTypeNames::WITHER_SKELETON_SPAWN_EGG => VanillaSpawnEggs::WITHER_SKELETON(),
			ItemTypeNames::ZOMBIE_VILLAGER_SPAWN_EGG => VanillaSpawnEggs::ZOMBIE_VILLAGER(),
		];
	}

	public static function register(?ItemDeserializer $deserializer, ?ItemSerializer $serializer) : void{
		$parser = StringToItemParser::getInstance();
		foreach(self::getMappings() as $bedrockId => $item){
			$deserializer?->map($bedrockId, fn() => clone $item);
			$serializer?->map($item, fn() => new SavedItemData($bedrockId));
			if($parser->parse($bedrockId) === null){
				$parser->register($bedrockId, fn() => clone $item);
			}
		}
	}
}
