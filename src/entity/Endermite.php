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
use pocketmine\entity\ai\goal\MeleeAttackGoal;
use pocketmine\entity\ai\goal\NearestPlayerTargetGoal;
use pocketmine\entity\ai\goal\RandomStrollGoal;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Endermite extends HostileMob{
	private const TAG_LIFETIME = "Lifetime"; //TAG_Int
	private const DESPAWN_AFTER_TICKS = 120 * 20;

	private int $lifetime = 0;

	public static function getNetworkTypeId() : string{ return EntityIds::ENDERMITE; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(0.3, 0.4);
	}

	protected function initEntity(CompoundTag $nbt) : void{
		$this->setMaxHealth(8);
		parent::initEntity($nbt);
		$this->lifetime = $nbt->getInt(self::TAG_LIFETIME, 0);
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setInt(self::TAG_LIFETIME, $this->lifetime);
		return $nbt;
	}

	protected function registerGoals() : void{
		$this->getTargetSelector()->addGoal(1, new NearestPlayerTargetGoal($this, 16.0));
		$this->getGoalSelector()->addGoal(2, new MeleeAttackGoal($this, 0.13, 2.0, 1.2));
		$this->getGoalSelector()->addGoal(7, new RandomStrollGoal($this, 0.11, 6, 60));
		$this->getGoalSelector()->addGoal(8, new LookAtPlayerGoal($this, 6.0));
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);
		if(!$this->isFlaggedForDespawn() && $this->getNameTag() === ""){
			$this->lifetime += $tickDiff;
			if($this->lifetime >= self::DESPAWN_AFTER_TICKS){
				$this->flagForDespawn();
			}
		}
		return $hasUpdate;
	}

	public function getName() : string{
		return "Endermite";
	}

	public function getDrops() : array{
		return [];
	}

	public function getXpDropAmount() : int{
		return 3;
	}

	public function getPickedItem() : ?Item{
		return VanillaItems::ENDERMITE_SPAWN_EGG();
	}
}
