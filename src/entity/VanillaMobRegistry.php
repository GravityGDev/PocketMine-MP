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

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

/**
 * Registers vanilla living entities restored by the vanilla parity project.
 * Keeping these registrations together avoids repeatedly modifying the core
 * EntityFactory while the vanilla mob roster is expanded.
 *
 * @internal
 */
final class VanillaMobRegistry{
	private function __construct(){
		//NOOP
	}

	public static function register(EntityFactory $factory) : void{
		$factory->register(Bogged::class, function(World $world, CompoundTag $nbt) : Bogged{
			return new Bogged(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['Bogged', 'minecraft:bogged']);

		$factory->register(CaveSpider::class, function(World $world, CompoundTag $nbt) : CaveSpider{
			return new CaveSpider(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['CaveSpider', 'minecraft:cave_spider']);

		$factory->register(Endermite::class, function(World $world, CompoundTag $nbt) : Endermite{
			return new Endermite(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['Endermite', 'minecraft:endermite']);

		$factory->register(Husk::class, function(World $world, CompoundTag $nbt) : Husk{
			return new Husk(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['Husk', 'minecraft:husk']);

		$factory->register(Parched::class, function(World $world, CompoundTag $nbt) : Parched{
			return new Parched(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['Parched', 'minecraft:parched']);

		$factory->register(Silverfish::class, function(World $world, CompoundTag $nbt) : Silverfish{
			return new Silverfish(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['Silverfish', 'minecraft:silverfish']);

		$factory->register(Stray::class, function(World $world, CompoundTag $nbt) : Stray{
			return new Stray(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['Stray', 'minecraft:stray']);

		$factory->register(WitherSkeleton::class, function(World $world, CompoundTag $nbt) : WitherSkeleton{
			return new WitherSkeleton(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['WitherSkeleton', 'minecraft:wither_skeleton']);
	}
}
