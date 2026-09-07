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
use pocketmine\entity\ai\goal\DrownedTridentAttackGoal;
use pocketmine\entity\ai\goal\FleeSunGoal;
use pocketmine\entity\ai\goal\HurtByTargetGoal;
use pocketmine\entity\ai\goal\LookAtPlayerGoal;
use pocketmine\entity\ai\goal\MeleeAttackGoal;
use pocketmine\entity\ai\goal\NearestLivingTargetGoal;
use pocketmine\entity\ai\goal\RandomLookAroundGoal;
use pocketmine\entity\ai\goal\RandomStrollGoal;
use pocketmine\entity\ai\navigation\AmphibiousPathfinder;
use pocketmine\entity\ai\navigation\GroundNavigation;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\item\VanillaSpawnEggs;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\World;
use function floor;
use function mt_rand;

class Drowned extends Zombie{
	private const TAG_EQUIPMENT_INITIALIZED = "DrownedEquipmentInitialized";
	private const TAG_RANGED_MODE = "DrownedRangedMode";
	private const TAG_FROM_ZOMBIE_CONVERSION = "DrownedFromZombieConversion";

	private bool $equipmentInitialized = false;
	private bool $rangedMode = false;

	public static function getNetworkTypeId() : string{ return EntityIds::DROWNED; }

	public static function markZombieConversion(CompoundTag $nbt) : void{
		$nbt->setByte(self::TAG_FROM_ZOMBIE_CONVERSION, 1);
	}

	protected function initEntity(CompoundTag $nbt) : void{
		$this->equipmentInitialized = $nbt->getByte(self::TAG_EQUIPMENT_INITIALIZED, 0) !== 0;
		$fromZombieConversion = $nbt->getByte(self::TAG_FROM_ZOMBIE_CONVERSION, 0) !== 0;
		$this->rangedMode = $this->equipmentInitialized ?
			$nbt->getByte(self::TAG_RANGED_MODE, 0) !== 0 :
			(!$fromZombieConversion && mt_rand(1, 400) <= 25);

		parent::initEntity($nbt);

		if(!$this->equipmentInitialized){
			$this->rollInitialEquipment();
			$this->equipmentInitialized = true;
		}
	}

	private function rollInitialEquipment() : void{
		if($this->rangedMode){
			$this->setMainHandItem(VanillaItems::TRIDENT());
		}elseif(mt_rand(1, 10000) <= 375){
			$this->setMainHandItem(VanillaItems::FISHING_ROD());
		}

		if(mt_rand(1, 100) <= 8){
			$this->setOffHandItem(VanillaItems::NAUTILUS_SHELL());
		}
	}

	protected function registerGoals() : void{
		$this->getTargetSelector()->addGoal(1, new HurtByTargetGoal($this, 35.0, true));
		$targetFilter = fn(Living $target) : bool => $this->canTargetLiving($target);
		$this->getTargetSelector()->addGoal(2, new NearestLivingTargetGoal($this, 12.0, $targetFilter, $targetFilter));

		$this->getGoalSelector()->addGoal(2, new FleeSunGoal($this, 0.1));
		$this->getGoalSelector()->addGoal(3, $this->rangedMode ?
			new DrownedTridentAttackGoal($this, 0.1, 10.0, 3.0) :
			new MeleeAttackGoal($this, 0.1, 3.0, 1.8)
		);
		$this->getGoalSelector()->addGoal(7, new RandomStrollGoal($this, 0.08, 8, 80));
		$this->getGoalSelector()->addGoal(8, new LookAtPlayerGoal($this, 6.0));
		$this->getGoalSelector()->addGoal(9, new RandomLookAroundGoal($this));
	}

	protected function createNavigation() : GroundNavigation{
		return new GroundNavigation($this, new AmphibiousPathfinder($this));
	}

	private function canTargetLiving(Living $target) : bool{
		if($target instanceof Player){
			$gamemode = $target->getGamemode();
			return ($gamemode === GameMode::SURVIVAL || $gamemode === GameMode::ADVENTURE) &&
				($this->isNight() || $this->isEntityInWater($target));
		}

		return $target instanceof Villager;
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

	public function isRangedMode() : bool{
		return $this->rangedMode;
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setByte(self::TAG_EQUIPMENT_INITIALIZED, $this->equipmentInitialized ? 1 : 0);
		$nbt->setByte(self::TAG_RANGED_MODE, $this->rangedMode ? 1 : 0);
		$nbt->removeTag(self::TAG_FROM_ZOMBIE_CONVERSION);
		return $nbt;
	}

	public function getName() : string{
		return "Drowned";
	}

	public function canBreathe() : bool{
		return true;
	}

	public function getDrops() : array{
		$drops = [
			VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 2))
		];

		$offHand = $this->getOffHandItem();
		if(!$offHand->isNull()){
			$drops[] = $offHand;
		}

		$lastDamageCause = $this->getLastDamageCause();
		if(
			$lastDamageCause instanceof EntityDamageByEntityEvent &&
			$lastDamageCause->getDamager() instanceof Player &&
			mt_rand(1, 100) <= 11
		){
			$drops[] = VanillaItems::COPPER_INGOT();
		}

		return $drops;
	}

	public function getPickedItem() : ?Item{
		return VanillaSpawnEggs::DROWNED();
	}
}
