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

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Water;
use pocketmine\block\Waterloggable;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\WorldBlockLayerUtils;

class Bucket extends Item{

	public function getMaxStackSize() : int{
		return 16;
	}

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		if($blockClicked instanceof Waterloggable && $blockClicked->canBeWaterlogged()){
			$pos = $blockClicked->getPosition();
			$secondaryBlock = WorldBlockLayerUtils::getBlockAtLayer($player->getWorld(), $pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ(), 1);
			if($secondaryBlock instanceof Water && $secondaryBlock->isSource()){
				$resultItem = VanillaItems::WATER_BUCKET();
				$ev = new PlayerBucketFillEvent($player, $blockClicked, $face, $this, $resultItem);
				$ev->call();
				if($ev->isCancelled()){
					return ItemUseResult::FAIL;
				}

				WorldBlockLayerUtils::setBlockAtLayer(
					$player->getWorld(),
					$pos->getFloorX(),
					$pos->getFloorY(),
					$pos->getFloorZ(),
					1,
					VanillaBlocks::AIR()
				);
				$player->getWorld()->addSound($pos->add(0.5, 0.5, 0.5), $secondaryBlock->getBucketFillSound());

				$this->pop();
				$returnedItems[] = $ev->getItem();
				return ItemUseResult::SUCCESS;
			}
		}

		//TODO: move this to generic placement logic
		if($blockClicked instanceof Liquid && $blockClicked->isSource()){
			$resultItem = match($blockClicked->getTypeId()){
				BlockTypeIds::LAVA => VanillaItems::LAVA_BUCKET(),
				BlockTypeIds::WATER => VanillaItems::WATER_BUCKET(),
				default => null
			};
			if($resultItem === null){
				return ItemUseResult::FAIL;
			}

			$ev = new PlayerBucketFillEvent($player, $blockReplace, $face, $this, $resultItem);
			$ev->call();
			if(!$ev->isCancelled()){
				$player->getWorld()->setBlock($blockClicked->getPosition(), VanillaBlocks::AIR());
				$player->getWorld()->addSound($blockClicked->getPosition()->add(0.5, 0.5, 0.5), $blockClicked->getBucketFillSound());

				$this->pop();
				$returnedItems[] = $ev->getItem();
				return ItemUseResult::SUCCESS;
			}

			return ItemUseResult::FAIL;
		}

		return ItemUseResult::NONE;
	}
}
