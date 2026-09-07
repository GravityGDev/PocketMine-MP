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

namespace pocketmine\entity;

use pocketmine\entity\ai\goal\LookAtPlayerGoal;
use pocketmine\entity\ai\goal\MeleeAttackGoal;
use pocketmine\entity\ai\goal\NearestPlayerTargetGoal;
use pocketmine\entity\ai\goal\RandomStrollGoal;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use function mt_rand;

class Zombie extends Undead{
	private const TAG_DROWNED_CONVERSION_WATER_TICKS = "DrownedConversionWaterTicks";
	private const TAG_DROWNED_CONVERSION_DELAY_TICKS = "DrownedConversionDelayTicks";
	private const DROWNED_CONVERSION_WATER_TICKS = 30 * 20;
	private const DROWNED_CONVERSION_DELAY_TICKS = 15 * 20;

	private int $drownedConversionWaterTicks = 0;
	private int $drownedConversionDelayTicks = -1;

	public static function getNetworkTypeId() : string{ return EntityIds::ZOMBIE; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.9, 0.6); //TODO: eye height ??
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		if($this->canConvertToDrowned()){
			$this->drownedConversionWaterTicks = $nbt->getInt(self::TAG_DROWNED_CONVERSION_WATER_TICKS, 0);
			$this->drownedConversionDelayTicks = $nbt->getInt(self::TAG_DROWNED_CONVERSION_DELAY_TICKS, -1);
		}
	}

	protected function registerGoals() : void{
		$this->getTargetSelector()->addGoal(1, $this->createPlayerTargetGoal());
		$this->getGoalSelector()->addGoal(2, new MeleeAttackGoal($this, 0.1, 3.0, 1.8));
		$this->getGoalSelector()->addGoal(7, new RandomStrollGoal($this, 0.08, 8, 80));
		$this->getGoalSelector()->addGoal(8, new LookAtPlayerGoal($this, 8.0));
	}

	protected function createPlayerTargetGoal() : NearestPlayerTargetGoal{
		return new NearestPlayerTargetGoal($this, 32.0);
	}

	protected function canConvertToDrowned() : bool{
		return static::getNetworkTypeId() === EntityIds::ZOMBIE;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);
		if(!$this->canConvertToDrowned() || !$this->isAlive() || $this->isFlaggedForDespawn()){
			return $hasUpdate;
		}

		if($this->drownedConversionDelayTicks >= 0){
			$this->drownedConversionDelayTicks -= $tickDiff;
			if($this->drownedConversionDelayTicks <= 0){
				$this->convertToDrowned();
			}
			return true;
		}

		if($this->isUnderwater()){
			$this->drownedConversionWaterTicks += $tickDiff;
			if($this->drownedConversionWaterTicks >= self::DROWNED_CONVERSION_WATER_TICKS){
				$this->drownedConversionDelayTicks = self::DROWNED_CONVERSION_DELAY_TICKS;
			}
			return true;
		}

		$this->drownedConversionWaterTicks = 0;
		return $hasUpdate;
	}

	private function convertToDrowned() : void{
		$location = clone $this->getLocation();
		$nbt = $this->saveNBT();
		$drowned = new Drowned($location, $nbt);
		$this->close();
		$drowned->spawnToAll();
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		if($this->canConvertToDrowned()){
			$nbt->setInt(self::TAG_DROWNED_CONVERSION_WATER_TICKS, $this->drownedConversionWaterTicks);
			$nbt->setInt(self::TAG_DROWNED_CONVERSION_DELAY_TICKS, $this->drownedConversionDelayTicks);
		}
		return $nbt;
	}

	public function getName() : string{
		return "Zombie";
	}

	public function getDrops() : array{
		$drops = [
			VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 2))
		];

		if(mt_rand(0, 199) < 5){
			switch(mt_rand(0, 2)){
				case 0:
					$drops[] = VanillaItems::IRON_INGOT();
					break;
				case 1:
					$drops[] = VanillaItems::CARROT();
					break;
				case 2:
					$drops[] = VanillaItems::POTATO();
					break;
			}
		}

		return $drops;
	}

	public function getXpDropAmount() : int{
		//TODO: check for equipment and whether it's a baby
		return 5;
	}

	public function getPickedItem() : ?Item{
		return VanillaItems::ZOMBIE_SPAWN_EGG();
	}
}
