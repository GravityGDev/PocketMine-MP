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

use pocketmine\entity\ai\goal\CreeperSwellGoal;
use pocketmine\entity\ai\goal\LookAtPlayerGoal;
use pocketmine\entity\ai\goal\NearestPlayerTargetGoal;
use pocketmine\entity\ai\goal\RandomStrollGoal;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\world\Explosion;
use pocketmine\world\Position;
use pocketmine\world\World;
use function max;
use function min;
use function mt_rand;

class Creeper extends Mob implements Explosive{
	private const TAG_NATURALLY_SPAWNED = "NaturallySpawned"; //TAG_Byte
	private const TAG_FUSE_TICKS = "FuseTicks"; //TAG_Short

	private const FUSE_TICKS = 30;
	private const EXPLOSION_RADIUS = 3.0;

	private bool $naturallySpawned = false;
	private int $fuseTicks = 0;
	private int $previousFuseTicks = 0;
	private int $swellDirection = -1;

	public static function getNetworkTypeId() : string{ return EntityIds::CREEPER; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.7, 0.6);
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->naturallySpawned = $nbt->getByte(self::TAG_NATURALLY_SPAWNED, 0) !== 0;
		$this->fuseTicks = min(self::FUSE_TICKS - 1, max(0, $nbt->getShort(self::TAG_FUSE_TICKS, 0)));
		$this->previousFuseTicks = $this->fuseTicks;
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setByte(self::TAG_NATURALLY_SPAWNED, $this->naturallySpawned ? 1 : 0);
		$nbt->setShort(self::TAG_FUSE_TICKS, $this->fuseTicks);
		return $nbt;
	}

	public function isNaturallySpawned() : bool{
		return $this->naturallySpawned;
	}

	public function setNaturallySpawned(bool $naturallySpawned = true) : void{
		$this->naturallySpawned = $naturallySpawned;
	}

	public function getFuseTicks() : int{
		return $this->fuseTicks;
	}

	public function setSwellDirection(int $direction) : void{
		$direction = $direction > 0 ? 1 : -1;
		if($this->swellDirection !== $direction){
			$this->swellDirection = $direction;
			$this->networkPropertiesDirty = true;
		}
	}

	protected function registerGoals() : void{
		$this->getTargetSelector()->addGoal(1, new NearestPlayerTargetGoal($this, 32.0));
		$this->getGoalSelector()->addGoal(2, new CreeperSwellGoal($this, 0.1));
		$this->getGoalSelector()->addGoal(7, new RandomStrollGoal($this, 0.08, 8, 80));
		$this->getGoalSelector()->addGoal(8, new LookAtPlayerGoal($this, 8.0));
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->getWorld()->getDifficulty() === World::DIFFICULTY_PEACEFUL){
			$this->flagForDespawn();
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);
		if($this->isFlaggedForDespawn()){
			return $hasUpdate;
		}

		$this->previousFuseTicks = $this->fuseTicks;
		if($this->swellDirection > 0){
			$this->fuseTicks = min(self::FUSE_TICKS, $this->fuseTicks + $tickDiff);
		}elseif($this->fuseTicks > 0){
			$this->fuseTicks = max(0, $this->fuseTicks - $tickDiff);
		}

		if($this->fuseTicks !== $this->previousFuseTicks){
			$this->networkPropertiesDirty = true;
		}

		if($this->fuseTicks >= self::FUSE_TICKS){
			$this->flagForDespawn();
			$this->explode();
		}

		return $hasUpdate || $this->fuseTicks !== $this->previousFuseTicks;
	}

	public function explode() : void{
		$ev = new EntityPreExplodeEvent($this, self::EXPLOSION_RADIUS);
		$ev->call();
		if($ev->isCancelled()){
			return;
		}

		$explosion = new Explosion(
			Position::fromObject($this->location->add(0, $this->size->getHeight() / 2, 0), $this->getWorld()),
			$ev->getRadius(),
			$this,
			$ev->getFireChance()
		);
		if($ev->isBlockBreaking()){
			$explosion->explodeA();
		}
		$explosion->explodeB();
	}

	public function getName() : string{
		return "Creeper";
	}

	public function getDrops() : array{
		return [VanillaItems::GUNPOWDER()->setCount(mt_rand(0, 2))];
	}

	public function getXpDropAmount() : int{
		return 5;
	}

	public function getPickedItem() : ?Item{
		return VanillaItems::CREEPER_SPAWN_EGG();
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);
		$properties->setInt(EntityMetadataProperties::CREEPER_SWELL, $this->fuseTicks);
		$properties->setInt(EntityMetadataProperties::CREEPER_SWELL_PREVIOUS, $this->previousFuseTicks);
		$properties->setByte(EntityMetadataProperties::CREEPER_SWELL_DIRECTION, $this->swellDirection);
		$properties->setGenericFlag(EntityMetadataFlags::IGNITED, $this->swellDirection > 0);
	}
}
