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

use PHPUnit\Framework\TestCase;

class LiquidTest extends TestCase{

	public function testFluidSurfaceHeight() : void{
		$water = VanillaBlocks::WATER();

		self::assertSame(1.0, $water->setDecay(0)->setFalling(false)->getFluidSurfaceHeight());
		self::assertEqualsWithDelta(8 / 9, $water->setDecay(1)->getFluidSurfaceHeight(), 0.000001);
		self::assertEqualsWithDelta(2 / 9, $water->setDecay(Liquid::MAX_DECAY)->getFluidSurfaceHeight(), 0.000001);
		self::assertSame(1.0, $water->setFalling(true)->getFluidSurfaceHeight());
	}
}
