# Vanilla Bedrock mob parity

Target: **Minecraft: Bedrock Edition 26.45 (latest stable release as of 2026-09-06)**.

The protocol bundle may already contain identifiers introduced by newer Preview builds. Those identifiers are useful for forward compatibility, but the gameplay target for this branch is the latest stable Bedrock release. Preview-only behaviour must not be enabled as stable vanilla gameplay until Mojang ships it.

Status key:

- `[x]` implemented/restored in this branch
- `[~]` partially implemented; important vanilla mechanics remain
- `[ ]` not yet restored

A mob is only considered complete when its relevant health/size/metadata, persistence, AI, combat, drops/XP, interactions, special mechanics, spawn rules, despawning and dimension/biome/structure rules match Bedrock closely enough for normal survival gameplay.

## Core mob systems

- [x] Goal selectors and mob tick AI
- [x] Navigation and A* pathfinding
- [x] Line-of-sight sensing
- [x] Melee combat goal
- [x] Ranged bow combat goal
- [x] Ranged tipped-arrow status effects with NBT persistence
- [x] Per-mob status-effect immunity hook
- [x] Shared hostile-mob lifecycle and natural-spawn persistence
- [x] Hostile mob cap/distance despawning
- [x] Darkness checks for hostile natural spawning
- [x] Daylight burning base for undead
- [~] Natural spawning pools (common Overworld monsters implemented; biome/dimension/structure pools still expanding)
- [ ] Passive/ambient/water creature natural spawn pools
- [ ] Full mob equipment/pickup/equipment loot tables
- [ ] Full breeding/age/baby system
- [ ] Full taming/owner/sitting system
- [ ] Full riding/passenger/mount AI
- [ ] Full village/raid/reputation/trading AI
- [ ] Full structure and monster-spawner mob rules
- [ ] Full biome- and dimension-specific mob pool registry

## Hostile and monster mobs

- [x] Zombie
- [x] Skeleton
- [x] Creeper
- [x] Spider
- [x] Cave Spider
- [x] Silverfish
- [x] Endermite
- [x] Husk
- [x] Wither Skeleton
- [ ] Drowned
- [ ] Zombie Villager
- [~] Stray
- [~] Bogged
- [~] Parched
- [ ] Slime
- [ ] Magma Cube
- [ ] Sulfur Cube
- [ ] Enderman
- [ ] Witch
- [ ] Blaze
- [ ] Ghast
- [ ] Guardian
- [ ] Elder Guardian
- [ ] Shulker
- [ ] Phantom
- [ ] Breeze
- [ ] Creaking
- [ ] Warden
- [ ] Pillager
- [ ] Vindicator
- [ ] Evoker
- [ ] Vex
- [ ] Ravager
- [ ] Piglin
- [ ] Piglin Brute
- [ ] Hoglin
- [ ] Zoglin
- [ ] Zombified Piglin
- [ ] Camel Husk
- [ ] Zombie Nautilus

## Bosses

- [ ] Wither
- [ ] Ender Dragon

## Passive, neutral and utility mobs

- [ ] Allay
- [ ] Armadillo
- [ ] Axolotl
- [ ] Bat
- [ ] Bee
- [ ] Camel
- [ ] Cat
- [ ] Chicken
- [ ] Copper Golem
- [ ] Cow
- [ ] Dolphin
- [ ] Donkey
- [ ] Fox
- [ ] Frog
- [ ] Goat
- [ ] Happy Ghast
- [ ] Horse
- [ ] Iron Golem
- [ ] Llama
- [ ] Mooshroom
- [ ] Mule
- [ ] Nautilus
- [ ] Ocelot
- [ ] Panda
- [ ] Parrot
- [ ] Pig
- [ ] Polar Bear
- [ ] Rabbit
- [ ] Sheep
- [ ] Skeleton Horse
- [ ] Sniffer
- [ ] Snow Golem
- [~] Squid (legacy PocketMine entity exists; vanilla parity audit required)
- [ ] Strider
- [ ] Trader Llama
- [ ] Turtle
- [~] Villager (legacy PocketMine entity exists; modern Bedrock trading/AI parity required)
- [ ] Wandering Trader
- [ ] Wolf
- [ ] Zombie Horse

## Aquatic mobs

- [ ] Cod
- [ ] Glow Squid
- [ ] Pufferfish
- [ ] Salmon
- [ ] Tadpole
- [ ] Tropical Fish

## Spawn/variant work that must accompany the roster

- Common Overworld monster pool: Zombie, Skeleton, Creeper and Spider
- Desert pools: Husk and desert-specific replacements
- Snow/frozen pools: Stray and appropriate skeleton replacement rates
- Swamp/mangrove/trial rules: Bogged and Witch
- Nether biome pools: Ghast, Blaze, Magma Cube, Wither Skeleton, Piglin family, Hoglin/Zoglin and Strider
- End pools: Enderman; Endermites remain event-driven
- Ocean/river pools: aquatic mobs, Drowned, Guardians and Elder Guardians
- Structure-driven mobs: Cave Spider, Silverfish, Shulker, Pillager/Vindicator/Evoker/Ravager, Breeze and other structure-only mobs
- Slime chunks/swamps and dimension-specific slime-family spawning
- Patrols, raids, phantom insomnia, wandering traders and village population systems
- Boss spawning/summoning and boss fight state

## Current implementation notes

Cave Spiders are intentionally **not** in the ordinary natural monster pool; vanilla spawns them from mineshaft monster spawners. Endermites are also excluded from ordinary natural spawning and have the vanilla-style ender-pearl spawn chance plus timed despawn behaviour. Wither Skeletons are registered but will only become naturally obtainable once Nether-specific spawning is added.

Stray, Bogged and Parched are now registered with their Bedrock network identifiers and core ranged combat. Their arrows apply the Bedrock-style Slowness, Poison and Weakness durations respectively, and Parched rejects Weakness effects. These remain partial until biome replacement/natural-spawn rules, equipment/tipped-arrow drops and their remaining special interactions or transformations are restored.

This file should be updated as each mob moves from `[ ]` to `[~]` and finally `[x]` so the project never loses track of vanilla parity gaps.
