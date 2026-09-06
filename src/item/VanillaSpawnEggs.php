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

use pocketmine\entity\Bogged;
use pocketmine\entity\CaveSpider;
use pocketmine\entity\Creeper;
use pocketmine\entity\Drowned;
use pocketmine\entity\Endermite;
use pocketmine\entity\Entity;
use pocketmine\entity\Husk;
use pocketmine\entity\Location;
use pocketmine\entity\Parched;
use pocketmine\entity\Silverfish;
use pocketmine\entity\Skeleton;
use pocketmine\entity\Spider;
use pocketmine\entity\Stray;
use pocketmine\entity\WitherSkeleton;
use pocketmine\entity\ZombieVillager;
use pocketmine\item\ItemIdentifier as IID;
use pocketmine\math\Vector3;
use pocketmine\world\World;

final class VanillaSpawnEggs{

	private function __construct(){
		//NOOP
	}

	/** @phpstan-param \Closure(World, Vector3, float, float) : Entity $factory */
	private static function create(int $typeId, string $name, \Closure $factory) : SpawnEgg{
		return new class(new IID($typeId), $name, $factory) extends SpawnEgg{
			/** @phpstan-param \Closure(World, Vector3, float, float) : Entity $factory */
			public function __construct(ItemIdentifier $identifier, string $name, private \Closure $factory){
				parent::__construct($identifier, $name);
			}

			protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
				return ($this->factory)($world, $pos, $yaw, $pitch);
			}
		};
	}

	public static function BOGGED() : SpawnEgg{
		return self::create(ItemTypeIds::BOGGED_SPAWN_EGG, "Bogged Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Bogged(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function CAVE_SPIDER() : SpawnEgg{
		return self::create(ItemTypeIds::CAVE_SPIDER_SPAWN_EGG, "Cave Spider Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new CaveSpider(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function CREEPER() : SpawnEgg{
		return self::create(ItemTypeIds::CREEPER_SPAWN_EGG, "Creeper Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Creeper(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function DROWNED() : SpawnEgg{
		return self::create(ItemTypeIds::DROWNED_SPAWN_EGG, "Drowned Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Drowned(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function ENDERMITE() : SpawnEgg{
		return self::create(ItemTypeIds::ENDERMITE_SPAWN_EGG, "Endermite Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Endermite(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function HUSK() : SpawnEgg{
		return self::create(ItemTypeIds::HUSK_SPAWN_EGG, "Husk Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Husk(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function PARCHED() : SpawnEgg{
		return self::create(ItemTypeIds::PARCHED_SPAWN_EGG, "Parched Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Parched(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function SILVERFISH() : SpawnEgg{
		return self::create(ItemTypeIds::SILVERFISH_SPAWN_EGG, "Silverfish Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Silverfish(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function SKELETON() : SpawnEgg{
		return self::create(ItemTypeIds::SKELETON_SPAWN_EGG, "Skeleton Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Skeleton(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function SPIDER() : SpawnEgg{
		return self::create(ItemTypeIds::SPIDER_SPAWN_EGG, "Spider Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Spider(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function STRAY() : SpawnEgg{
		return self::create(ItemTypeIds::STRAY_SPAWN_EGG, "Stray Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new Stray(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function WITHER_SKELETON() : SpawnEgg{
		return self::create(ItemTypeIds::WITHER_SKELETON_SPAWN_EGG, "Wither Skeleton Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new WitherSkeleton(Location::fromObject($pos, $world, $yaw, $pitch)));
	}

	public static function ZOMBIE_VILLAGER() : SpawnEgg{
		return self::create(ItemTypeIds::ZOMBIE_VILLAGER_SPAWN_EGG, "Zombie Villager Spawn Egg", fn(World $world, Vector3 $pos, float $yaw, float $pitch) => new ZombieVillager(Location::fromObject($pos, $world, $yaw, $pitch)));
	}
}
