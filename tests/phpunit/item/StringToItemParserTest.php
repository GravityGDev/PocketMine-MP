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

namespace pocketmine\item;

use PHPUnit\Framework\TestCase;
use function str_replace;
use function strtoupper;

class StringToItemParserTest extends TestCase{

	public function testOverrideRemovesOldAlias() : void{
		$parser = new StringToItemParser();

		$item1 = VanillaItems::DIAMOND();
		$item2 = VanillaItems::EMERALD();

		$parser->register("test_alias", fn() => $item1);

		self::assertContains("test_alias", $parser->lookupAliases($item1));
		self::assertNotContains("test_alias", $parser->lookupAliases($item2));

		$parser->override("test_alias", fn() => $item2);

		self::assertNotContains("test_alias", $parser->lookupAliases($item1));
		self::assertContains("test_alias", $parser->lookupAliases($item2));
	}

	public function testOverrideWithNewAlias() : void{
		$parser = new StringToItemParser();

		$item = VanillaItems::DIAMOND();

		$parser->override("new_alias", fn() => $item);

		self::assertContains("new_alias", $parser->lookupAliases($item));
		self::assertSame($item, $parser->parse("new_alias"));
	}

	public function testOverrideMultipleAliases() : void{
		$parser = new StringToItemParser();

		$item1 = VanillaItems::DIAMOND();
		$item2 = VanillaItems::EMERALD();

		$parser->register("alias1", fn() => $item1);
		$parser->register("alias2", fn() => $item1);
		$parser->register("alias3", fn() => $item1);

		self::assertCount(3, $parser->lookupAliases($item1));

		$parser->override("alias2", fn() => $item2);

		self::assertCount(2, $parser->lookupAliases($item1));
		self::assertContains("alias1", $parser->lookupAliases($item1));
		self::assertContains("alias3", $parser->lookupAliases($item1));
		self::assertNotContains("alias2", $parser->lookupAliases($item1));

		self::assertCount(1, $parser->lookupAliases($item2));
		self::assertContains("alias2", $parser->lookupAliases($item2));
	}

	public function testRestoredSpawnEggAliases() : void{
		$eggs = [
			"bogged_spawn_egg" => ItemTypeIds::BOGGED_SPAWN_EGG,
			"cave_spider_spawn_egg" => ItemTypeIds::CAVE_SPIDER_SPAWN_EGG,
			"creeper_spawn_egg" => ItemTypeIds::CREEPER_SPAWN_EGG,
			"drowned_spawn_egg" => ItemTypeIds::DROWNED_SPAWN_EGG,
			"endermite_spawn_egg" => ItemTypeIds::ENDERMITE_SPAWN_EGG,
			"husk_spawn_egg" => ItemTypeIds::HUSK_SPAWN_EGG,
			"parched_spawn_egg" => ItemTypeIds::PARCHED_SPAWN_EGG,
			"silverfish_spawn_egg" => ItemTypeIds::SILVERFISH_SPAWN_EGG,
			"skeleton_spawn_egg" => ItemTypeIds::SKELETON_SPAWN_EGG,
			"spider_spawn_egg" => ItemTypeIds::SPIDER_SPAWN_EGG,
			"stray_spawn_egg" => ItemTypeIds::STRAY_SPAWN_EGG,
			"wither_skeleton_spawn_egg" => ItemTypeIds::WITHER_SKELETON_SPAWN_EGG,
			"zombie_villager_spawn_egg" => ItemTypeIds::ZOMBIE_VILLAGER_SPAWN_EGG,
		];
		$registeredItems = VanillaItems::getAll();
		$parser = StringToItemParser::getInstance();

		foreach($eggs as $name => $typeId){
			self::assertArrayHasKey(strtoupper($name), $registeredItems);
			foreach([$name, "minecraft:" . $name, str_replace("_", " ", $name)] as $alias){
				$item = $parser->parse($alias);
				self::assertInstanceOf(SpawnEgg::class, $item, "Failed to parse $alias");
				self::assertSame($typeId, $item->getTypeId(), "Parsed the wrong item for $alias");
			}
		}
	}
}
