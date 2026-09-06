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

namespace pocketmine\world;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\world\format\Chunk;

/**
 * Internal helper for Bedrock's overlapping block-storage layers.
 *
 * Layer 0 is the ordinary block. Higher layers are used for overlapping blocks such as water in a waterlogged block.
 * This deliberately lives outside World for now so the parity implementation can use secondary layers without
 * changing the public World API until the semantics have settled.
 *
 * @internal
 */
final class WorldBlockLayerUtils{

	private function __construct(){
		//NOOP
	}

	public static function getBlockAtLayer(World $world, int $x, int $y, int $z, int $layer) : Block{
		if($layer < 0){
			throw new \InvalidArgumentException("Block layer must be non-negative");
		}
		if(!$world->isInWorld($x, $y, $z)){
			$block = VanillaBlocks::AIR();
		}else{
			$chunk = $world->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
			$block = $chunk === null ? VanillaBlocks::AIR() : RuntimeBlockStateRegistry::getInstance()->fromStateId(
				$chunk->getBlockStateIdAtLayer($x & Chunk::COORD_MASK, $y, $z & Chunk::COORD_MASK, $layer)
			);
		}

		$block->position($world, $x, $y, $z);
		return $block;
	}

	public static function setBlockAtLayer(World $world, int $x, int $y, int $z, int $layer, Block $block, bool $update = true) : void{
		if($layer < 1){
			throw new \InvalidArgumentException("WorldBlockLayerUtils is only for secondary block layers");
		}
		if(!$world->isInWorld($x, $y, $z)){
			throw new \InvalidArgumentException("Pos x=$x,y=$y,z=$z is outside of the world bounds");
		}

		$chunkX = $x >> Chunk::COORD_BIT_SIZE;
		$chunkZ = $z >> Chunk::COORD_BIT_SIZE;
		$chunk = $world->getChunk($chunkX, $chunkZ);
		if($chunk === null){
			throw new WorldException("Cannot set a block layer in unloaded terrain");
		}

		$stateId = $block->getStateId();
		$registry = RuntimeBlockStateRegistry::getInstance();
		if(!$registry->hasStateId($stateId)){
			throw new \LogicException("Block state ID not known to RuntimeBlockStateRegistry (probably not registered)");
		}

		$chunk->setBlockStateIdAtLayer($x & Chunk::COORD_MASK, $y, $z & Chunk::COORD_MASK, $layer, $stateId);

		$pos = new Vector3($x, $y, $z);
		foreach($world->getChunkListeners($chunkX, $chunkZ) as $listener){
			$listener->onBlockChanged($pos);
			//There isn't a single-block secondary-layer update API yet. Mark the chunk changed so clients receive the
			//complete multi-layer subchunk through the existing serializer on their next chunk request.
			$listener->onChunkChanged($chunkX, $chunkZ, $chunk);
		}
		if($update){
			$world->notifyNeighbourBlockUpdate($pos);
		}
	}
}
