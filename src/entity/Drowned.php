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
use pocketmine\entity\ai\goal\PickupItemGoal;
use pocketmine\entity\ai\goal\RandomLookAroundGoal;
use pocketmine\entity\ai\goal\RandomStrollGoal;
use pocketmine\entity\ai\navigation\AmphibiousPathfinder;
use pocketmine\entity\ai\navigation\GroundNavigation;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\item\VanillaSpawnEggs;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\World;
use function count;
use function floor;
use function intdiv;
use function max;
use function mt_rand;

class Drowned extends Zombie{
	private const TAG_EQUIPMENT_INITIALIZED = "DrownedEquipmentInitialized";
	private const TAG_RANGED_MODE = "DrownedRangedMode";
	private const TAG_FROM_ZOMBIE_CONVERSION = "DrownedFromZombieConversion";
	private const TAG_CAN_PICK_UP_LOOT = "DrownedCanPickUpLoot";
	private const TAG_PICKED_UP_MAIN_HAND = "DrownedPickedUpMainHand";
	private const TAG_PICKED_UP_ARMOR_MASK = "DrownedPickedUpArmorMask";
	private const TAG_ARMOR_ITEMS = "DrownedArmorItems";
	private const TAG_ARMOR_SLOT = "Slot";
	private const LAND_MOVEMENT_SPEED = 0.23;
	private const UNDERWATER_MOVEMENT_SPEED = 0.06;

	private bool $equipmentInitialized = false;
	private bool $rangedMode = false;
	private bool $canPickUpLoot = false;
	private bool $pickedUpMainHand = false;
	private int $pickedUpArmorMask = 0;

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

		$pickupState = $nbt->getByte(self::TAG_CAN_PICK_UP_LOOT, -1);
		$this->canPickUpLoot = $pickupState >= 0 ? $pickupState !== 0 : $this->rollCanPickUpLoot();
		$this->pickedUpMainHand = $nbt->getByte(self::TAG_PICKED_UP_MAIN_HAND, 0) !== 0;
		$this->pickedUpArmorMask = $nbt->getByte(self::TAG_PICKED_UP_ARMOR_MASK, 0);

		parent::initEntity($nbt);
		$this->setMovementSpeed(self::LAND_MOVEMENT_SPEED, true);
		$this->loadArmorItems($nbt);

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

	private function rollCanPickUpLoot() : bool{
		$difficulty = $this->getWorld()->getDifficulty();
		if($difficulty <= World::DIFFICULTY_EASY){
			return false;
		}

		$phase = intdiv($this->getWorld()->getTime(), World::TIME_FULL) % 8;
		$moonBrightness = [1.0, 0.75, 0.5, 0.25, 0.0, 0.25, 0.5, 0.75][$phase];
		$chance = $difficulty === World::DIFFICULTY_HARD ?
			0.06875 + (0.48125 * $moonBrightness) :
			0.55 * $moonBrightness;

		return mt_rand(1, 10000) <= (int) floor($chance * 10000);
	}

	protected function registerGoals() : void{
		$this->getTargetSelector()->addGoal(1, new HurtByTargetGoal($this, 35.0));

		$playerFilter = fn(Living $target) : bool => $this->canTargetPlayer($target);
		$this->getTargetSelector()->addGoal(2, new NearestLivingTargetGoal($this, 12.0, $playerFilter, $playerFilter));

		$villagerFilter = fn(Living $target) : bool => $target instanceof Villager && ($this->isNight() || $this->isEntityInWater($target));
		$this->getTargetSelector()->addGoal(2, new NearestLivingTargetGoal($this, 12.0, $villagerFilter, $villagerFilter, false));

		$this->getGoalSelector()->addGoal(2, new FleeSunGoal($this, 0.1));
		$this->getGoalSelector()->addGoal(3, $this->rangedMode ?
			new DrownedTridentAttackGoal($this, 0.1, 10.0, 3.0) :
			new MeleeAttackGoal($this, 0.1, 3.0, 1.8)
		);
		if($this->canPickUpLoot){
			$this->getGoalSelector()->addGoal(6, new PickupItemGoal(
				$this,
				fn(ItemEntity $itemEntity) : ?int => $this->getPickupPreference($itemEntity->getItem()),
				fn(ItemEntity $itemEntity) : bool => $this->pickupItemEntity($itemEntity),
				0.1,
				2.0,
				3.0,
				1.0
			));
		}
		$this->getGoalSelector()->addGoal(7, new RandomStrollGoal($this, 0.08, 8, 80));
		$this->getGoalSelector()->addGoal(8, new LookAtPlayerGoal($this, 6.0));
		$this->getGoalSelector()->addGoal(9, new RandomLookAroundGoal($this));
	}

	protected function createNavigation() : GroundNavigation{
		return new GroundNavigation($this, new AmphibiousPathfinder($this));
	}

	private function canTargetPlayer(Living $target) : bool{
		if(!$target instanceof Player){
			return false;
		}

		$gamemode = $target->getGamemode();
		return ($gamemode === GameMode::SURVIVAL || $gamemode === GameMode::ADVENTURE) &&
			($this->isNight() || $this->isEntityInWater($target));
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

	/**
	 * Bedrock switches Drowned navigation into hunter mode only while an attack
	 * target is acquired. Without a target they remain in wander mode and walk
	 * along the bottom instead of freely swimming through the water column.
	 */
	public function canNavigateInWater() : bool{
		return $this->getTargetEntity() instanceof Living;
	}

	public function getNavigationMovementSpeed(float $requestedSpeed, bool $inWater) : float{
		return $inWater ? self::UNDERWATER_MOVEMENT_SPEED : self::LAND_MOVEMENT_SPEED;
	}

	public function isRangedMode() : bool{
		return $this->rangedMode;
	}

	private function getPickupPreference(Item $item) : ?int{
		if(!$this->canPickUpLoot || $item->isNull()){
			return null;
		}
		if($item->getTypeId() === VanillaItems::GLOW_INK_SAC()->getTypeId() || $item->getTypeId() === VanillaItems::TRIDENT()->getTypeId()){
			return null;
		}

		if($item instanceof Armor){
			return $this->shouldEquipArmor($item) ? max(2, 20 - $item->getDefensePoints()) : null;
		}

		if($item->getTypeId() === VanillaItems::NAUTILUS_SHELL()->getTypeId()){
			return $this->getOffHandItem()->getTypeId() !== $item->getTypeId() ? 1 : null;
		}

		$current = $this->getMainHandItem();
		if($current->isNull()){
			return $item instanceof Sword ? max(2, 12 - $item->getAttackPoints()) : 50;
		}
		if($current->getTypeId() === VanillaItems::TRIDENT()->getTypeId()){
			return null;
		}
		if($item instanceof Sword && $this->isBetterHandItem($item, $current)){
			return max(2, 12 - $item->getAttackPoints());
		}

		return null;
	}

	private function shouldEquipArmor(Armor $candidate) : bool{
		$current = $this->getArmorInventory()->getItem($candidate->getArmorSlot());
		if($current->isNull() || !$current instanceof Armor){
			return true;
		}
		if($candidate->getDefensePoints() !== $current->getDefensePoints()){
			return $candidate->getDefensePoints() > $current->getDefensePoints();
		}
		if(count($candidate->getEnchantments()) !== count($current->getEnchantments())){
			return count($candidate->getEnchantments()) > count($current->getEnchantments());
		}
		return $candidate->getDamage() < $current->getDamage();
	}

	private function isBetterHandItem(Sword $candidate, Item $current) : bool{
		if(!$current instanceof Sword){
			return true;
		}
		if($candidate->getAttackPoints() !== $current->getAttackPoints()){
			return $candidate->getAttackPoints() > $current->getAttackPoints();
		}
		if(count($candidate->getEnchantments()) !== count($current->getEnchantments())){
			return count($candidate->getEnchantments()) > count($current->getEnchantments());
		}
		return $candidate->getDamage() < $current->getDamage();
	}

	private function pickupItemEntity(ItemEntity $itemEntity) : bool{
		if($itemEntity->isFlaggedForDespawn() || $itemEntity->getPickupDelay() !== 0){
			return false;
		}

		$itemStack = $itemEntity->getItem();
		if($this->getPickupPreference($itemStack) === null){
			return false;
		}

		$singleItem = clone $itemStack;
		$singleItem->setCount(1);
		$event = new EntityItemPickupEvent($this, $itemEntity, $singleItem, null);
		$event->call();
		if($event->isCancelled()){
			return false;
		}

		$receivedItem = $event->getItem();
		$receivedItem->setCount(1);
		if($receivedItem->isNull() || $this->getPickupPreference($receivedItem) === null || !$this->equipPickedItem($receivedItem)){
			return false;
		}

		NetworkBroadcastUtils::broadcastEntityEvent(
			$itemEntity->getViewers(),
			fn(EntityEventBroadcaster $broadcaster, array $recipients) => $broadcaster->onPickUpItem($recipients, $this, $itemEntity)
		);

		if($itemStack->getCount() <= 1){
			$itemEntity->flagForDespawn();
		}else{
			$itemEntity->setStackSize($itemStack->getCount() - 1);
		}
		return true;
	}

	private function equipPickedItem(Item $item) : bool{
		if($item instanceof Armor){
			if(!$this->shouldEquipArmor($item)){
				return false;
			}
			$slot = $item->getArmorSlot();
			$current = $this->getArmorInventory()->getItem($slot);
			if(!$current->isNull()){
				$this->getWorld()->dropItem($this->getPosition(), $current);
			}
			$this->getArmorInventory()->setItem($slot, $item);
			$this->pickedUpArmorMask |= 1 << $slot;
			return true;
		}

		if($item->getTypeId() === VanillaItems::NAUTILUS_SHELL()->getTypeId()){
			$current = $this->getOffHandItem();
			if(!$current->isNull()){
				$this->getWorld()->dropItem($this->getPosition(), $current);
			}
			$this->setOffHandItem($item);
			return true;
		}

		$current = $this->getMainHandItem();
		if(!$current->isNull()){
			$this->getWorld()->dropItem($this->getPosition(), $current);
		}
		$this->setMainHandItem($item);
		$this->pickedUpMainHand = true;
		$this->rangedMode = false;
		return true;
	}

	private function loadArmorItems(CompoundTag $nbt) : void{
		$items = $nbt->getListTag(self::TAG_ARMOR_ITEMS, CompoundTag::class);
		if($items === null){
			return;
		}

		$inventory = $this->getArmorInventory();
		foreach($items as $itemTag){
			$slot = $itemTag->getByte(self::TAG_ARMOR_SLOT, -1);
			if($slot < 0 || $slot >= $inventory->getSize()){
				continue;
			}
			$item = Item::safeNbtDeserialize($itemTag, "Drowned armor item");
			if($item instanceof Armor && $item->getArmorSlot() === $slot){
				$inventory->setItem($slot, $item);
			}
		}
	}

	private function saveArmorItems(CompoundTag $nbt) : void{
		$list = new ListTag();
		foreach($this->getArmorInventory()->getContents() as $slot => $item){
			if($item->isNull()){
				continue;
			}
			$itemTag = $item->nbtSerialize();
			$itemTag->setByte(self::TAG_ARMOR_SLOT, $slot);
			$list->push($itemTag);
		}

		if($list->count() > 0){
			$nbt->setTag(self::TAG_ARMOR_ITEMS, $list);
		}else{
			$nbt->removeTag(self::TAG_ARMOR_ITEMS);
		}
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setByte(self::TAG_EQUIPMENT_INITIALIZED, $this->equipmentInitialized ? 1 : 0);
		$nbt->setByte(self::TAG_RANGED_MODE, $this->rangedMode ? 1 : 0);
		$nbt->setByte(self::TAG_CAN_PICK_UP_LOOT, $this->canPickUpLoot ? 1 : 0);
		$nbt->setByte(self::TAG_PICKED_UP_MAIN_HAND, $this->pickedUpMainHand ? 1 : 0);
		$nbt->setByte(self::TAG_PICKED_UP_ARMOR_MASK, $this->pickedUpArmorMask);
		$this->saveArmorItems($nbt);
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

		$mainHand = $this->getMainHandItem();
		if($this->pickedUpMainHand && !$mainHand->isNull()){
			$drops[] = $mainHand;
		}

		$offHand = $this->getOffHandItem();
		if(!$offHand->isNull()){
			$drops[] = $offHand;
		}

		foreach($this->getArmorInventory()->getContents() as $slot => $item){
			if(($this->pickedUpArmorMask & (1 << $slot)) !== 0 && !$item->isNull()){
				$drops[] = $item;
			}
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
