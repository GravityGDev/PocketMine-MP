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

use pocketmine\entity\Living;
use pocketmine\entity\Mob;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use function spl_object_id;

/**
 * Retaliates against the most recent living attacker and can alert nearby
 * mobs of the exact same type, matching Bedrock's hurt_by_target behaviour.
 */
final class HurtByTargetGoal extends Goal{
	private ?Living $attacker = null;
	private ?Living $alertedAttacker = null;
	private ?int $lastHandledDamageEventId = null;

	public function __construct(
		private Mob $mob,
		private float $range = 35.0,
		private bool $alertSameType = false
	){
		$this->setFlags(self::FLAG_TARGET);
	}

	public function setAlertTarget(Living $attacker) : void{
		if($attacker !== $this->mob && $attacker->isAlive() && $attacker->getWorld() === $this->mob->getWorld()){
			$this->alertedAttacker = $attacker;
		}
	}

	public function canStart() : bool{
		if($this->alertedAttacker !== null){
			$attacker = $this->alertedAttacker;
			$this->alertedAttacker = null;
			if($this->isValidAttacker($attacker)){
				$this->attacker = $attacker;
				return true;
			}
		}

		$damageEvent = $this->mob->getLastDamageCause();
		if(!$damageEvent instanceof EntityDamageByEntityEvent){
			return false;
		}

		$eventId = spl_object_id($damageEvent);
		if($eventId === $this->lastHandledDamageEventId){
			return false;
		}

		$attacker = $damageEvent->getDamager();
		if(!$attacker instanceof Living || !$attacker->isAlive() || $attacker === $this->mob){
			$this->lastHandledDamageEventId = $eventId;
			return false;
		}

		$this->lastHandledDamageEventId = $eventId;
		$this->attacker = $attacker;
		return true;
	}

	public function canContinue() : bool{
		return $this->attacker !== null && $this->isValidAttacker($this->attacker);
	}

	public function start() : void{
		if($this->attacker === null){
			return;
		}

		$this->mob->setTargetEntity($this->attacker);
		if($this->alertSameType){
			$this->alertNearbyMobs($this->attacker);
		}
	}

	public function stop() : void{
		if($this->mob->getTargetEntity() === $this->attacker){
			$this->mob->setTargetEntity(null);
		}
		$this->attacker = null;
	}

	private function alertNearbyMobs(Living $attacker) : void{
		$position = $this->mob->getPosition();
		$rangeSquared = $this->range * $this->range;
		$mobClass = $this->mob::class;

		foreach($this->mob->getWorld()->getEntities() as $entity){
			if($entity === $this->mob || !$entity instanceof Mob || $entity::class !== $mobClass || !$entity->isAlive()){
				continue;
			}

			$otherPosition = $entity->getPosition();
			$dx = $otherPosition->x - $position->x;
			$dy = $otherPosition->y - $position->y;
			$dz = $otherPosition->z - $position->z;
			if(($dx * $dx + $dy * $dy + $dz * $dz) > $rangeSquared){
				continue;
			}

			foreach($entity->getTargetSelector()->getAvailableGoals() as $entry){
				$goal = $entry->getGoal();
				if($goal instanceof self){
					$goal->setAlertTarget($attacker);
					break;
				}
			}
		}
	}

	private function isValidAttacker(Living $attacker) : bool{
		if(!$attacker->isAlive() || $attacker->getWorld() !== $this->mob->getWorld()){
			return false;
		}

		$position = $this->mob->getPosition();
		$attackerPosition = $attacker->getPosition();
		$dx = $attackerPosition->x - $position->x;
		$dy = $attackerPosition->y - $position->y;
		$dz = $attackerPosition->z - $position->z;
		return ($dx * $dx + $dy * $dy + $dz * $dz) <= $this->range * $this->range;
	}
}
