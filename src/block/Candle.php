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

namespace pocketmine\block;

use pocketmine\block\utils\CandleTrait;
use pocketmine\block\utils\Lightable;
use pocketmine\block\utils\SupportType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\entity\projectile\Projectile;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\BlockTransaction;
use pocketmine\world\WorldBlockLayerUtils;
use pocketmine\world\sound\FireExtinguishSound;

class Candle extends Transparent implements Lightable, Waterloggable{
	use CandleTrait {
		describeBlockOnlyState as encodeLitState;
		getLightLevel as getBaseLightLevel;
		onInteract as private candleOnInteract;
		onProjectileHit as private candleOnProjectileHit;
	}
	use WaterloggableTrait;

	public const MIN_COUNT = 1;
	public const MAX_COUNT = 4;

	private int $count = self::MIN_COUNT;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$this->encodeLitState($w);
		$w->boundedIntAuto(self::MIN_COUNT, self::MAX_COUNT, $this->count);
	}

	public function getCount() : int{ return $this->count; }

	/** @return $this */
	public function setCount(int $count) : self{
		if($count < self::MIN_COUNT || $count > self::MAX_COUNT){
			throw new \InvalidArgumentException("Count must be in range " . self::MIN_COUNT . " ... " . self::MAX_COUNT);
		}
		$this->count = $count;
		return $this;
	}

	public function getLightLevel() : int{
		return $this->getBaseLightLevel() * $this->count;
	}

	public function canBeWaterlogged() : bool{
		return true;
	}

	protected function recalculateCollisionBoxes() : array{
		return [
			(match($this->count){
				1 => AxisAlignedBB::one()
					->squash(Axis::X, 7 / 16)
					->squash(Axis::Z, 7 / 16),
				2 => AxisAlignedBB::one()
					->squash(Axis::X, 5 / 16)
					->trim(Facing::NORTH, 7 / 16) //0.3 thick on the Z axis
					->trim(Facing::SOUTH, 6 / 16),
				3 => AxisAlignedBB::one()
					->trim(Facing::WEST, 5 / 16)
					->trim(Facing::EAST, 6 / 16)
					->trim(Facing::NORTH, 6 / 16)
					->trim(Facing::SOUTH, 5 / 16),
				4 => AxisAlignedBB::one()
					->squash(Axis::X, 5 / 16)
					->trim(Facing::NORTH, 5 / 16)
					->trim(Facing::SOUTH, 6 / 16),
				default => throw new AssumptionFailedError("Unreachable")
			})->trim(Facing::UP, 10 / 16)
		];
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE;
	}

	protected function getCandleIfCompatibleType(Block $block) : ?Candle{
		return $block instanceof Candle && $block->hasSameTypeId($this) ? $block : null;
	}

	public function canBePlacedAt(Block $blockReplace, Vector3 $clickVector, int $face, bool $isClickedBlock) : bool{
		$candle = $this->getCandleIfCompatibleType($blockReplace);
		return $candle !== null ? $candle->count < self::MAX_COUNT : parent::canBePlacedAt($blockReplace, $clickVector, $face, $isClickedBlock);
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if(!$blockReplace->getAdjacentSupportType(Facing::DOWN)->hasCenterSupport()){
			return false;
		}
		$existing = $this->getCandleIfCompatibleType($blockReplace);
		if($existing !== null){
			if($existing->count >= self::MAX_COUNT){
				return false;
			}

			$this->count = $existing->count + 1;
			$this->lit = $existing->lit;
		}
		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []) : bool{
		if($this->isWaterlogged() && (
			$item->getTypeId() === ItemTypeIds::FIRE_CHARGE ||
			$item->getTypeId() === ItemTypeIds::FLINT_AND_STEEL ||
			$item->hasEnchantment(VanillaEnchantments::FIRE_ASPECT())
		)){
			return true;
		}

		return $this->candleOnInteract($item, $face, $clickVector, $player, $returnedItems);
	}

	public function onNearbyBlockChange() : void{
		if($this->lit && $this->isWaterlogged()){
			$this->position->getWorld()->addSound($this->position, new FireExtinguishSound());
			$this->position->getWorld()->setBlock($this->position, $this->setLit(false));
		}
	}

	public function onProjectileHit(Projectile $projectile, RayTraceResult $hitResult) : void{
		if(!$this->isWaterlogged()){
			$this->candleOnProjectileHit($projectile, $hitResult);
		}
	}

	private function isWaterlogged() : bool{
		$pos = $this->position;
		return WorldBlockLayerUtils::getBlockAtLayer(
			$pos->getWorld(),
			$pos->getFloorX(),
			$pos->getFloorY(),
			$pos->getFloorZ(),
			1
		) instanceof Water;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [$this->asItem()->setCount($this->count)];
	}
}
