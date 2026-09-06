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

namespace pocketmine\entity\ai\goal;

use PHPUnit\Framework\TestCase;

final class GoalSelectorTest extends TestCase{
	public function testHigherPriorityGoalPreemptsLowerPriorityGoal() : void{
		$selector = new GoalSelector();
		$lowEnabled = true;
		$highEnabled = false;

		$low = new class(static function() use (&$lowEnabled) : bool{ return $lowEnabled; }) extends Goal{
			public int $starts = 0;
			public int $stops = 0;
			public int $ticks = 0;
			public function __construct(private \Closure $enabled){ $this->setFlags(self::FLAG_MOVE); }
			public function canStart() : bool{ return ($this->enabled)(); }
			public function canContinue() : bool{ return ($this->enabled)(); }
			public function start() : void{ ++$this->starts; }
			public function stop() : void{ ++$this->stops; }
			public function tick() : void{ ++$this->ticks; }
		};
		$high = new class(static function() use (&$highEnabled) : bool{ return $highEnabled; }) extends Goal{
			public int $starts = 0;
			public int $ticks = 0;
			public function __construct(private \Closure $enabled){ $this->setFlags(self::FLAG_MOVE); }
			public function canStart() : bool{ return ($this->enabled)(); }
			public function canContinue() : bool{ return ($this->enabled)(); }
			public function start() : void{ ++$this->starts; }
			public function tick() : void{ ++$this->ticks; }
		};

		$selector->addGoal(5, $low);
		$selector->addGoal(1, $high);
		$selector->tick();

		self::assertSame(1, $low->starts);
		self::assertSame(1, $low->ticks);
		self::assertSame(0, $high->starts);

		$highEnabled = true;
		$selector->tick();

		self::assertSame(1, $low->stops);
		self::assertSame(1, $high->starts);
		self::assertSame(1, $high->ticks);
	}

	public function testDisabledControlFlagBlocksGoal() : void{
		$selector = new GoalSelector();
		$goal = new class extends Goal{
			public int $starts = 0;
			public function __construct(){ $this->setFlags(self::FLAG_LOOK); }
			public function canStart() : bool{ return true; }
			public function start() : void{ ++$this->starts; }
		};

		$selector->addGoal(1, $goal);
		$selector->setControlFlag(Goal::FLAG_LOOK, false);
		$selector->tick();
		self::assertSame(0, $goal->starts);

		$selector->setControlFlag(Goal::FLAG_LOOK, true);
		$selector->tick();
		self::assertSame(1, $goal->starts);
	}
}
