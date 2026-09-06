# GravityG PocketMine-MP 5.46 Vanilla Restoration

This branch is the development line for restoring more vanilla Minecraft Bedrock behaviour while retaining the PocketMine-MP API and server architecture.

## Target

- GravityG-PocketMine-MP: **5.46.0-dev**
- Minecraft Bedrock: **1.26.45 / v26.45**
- Protocol: **2169**
- Base stable line: **5.45.0**

## Development stages

### Stage 1 - Mob AI foundation

- [x] Goal scheduler with MOVE, LOOK, JUMP and TARGET control channels
- [x] Priority/pre-emption support
- [x] Mob base class with separate behaviour and target selectors
- [x] Vanilla-compatible `NoAI` entity NBT support
- [x] Zombie and Villager moved onto the new Mob base
- [x] Unit coverage for selector priority and disabled controls
- [ ] Movement and look controllers
- [ ] Ground navigation and pathfinding
- [ ] Sensing and target acquisition

### Stage 2 - Natural spawning

- [ ] Spawn categories and caps
- [ ] Per-player spawning radius
- [ ] Light, block and biome validation
- [ ] Passive and hostile spawn cycles
- [ ] Natural despawning and persistence rules

### Stage 3 - First vanilla mobs

- [ ] Zombie
- [ ] Skeleton
- [ ] Creeper
- [ ] Spider
- [ ] Cow
- [ ] Pig
- [ ] Sheep
- [ ] Chicken

### Stage 4 - Broader vanilla systems

Breeding, equipment, loot/XP, villagers/trading, redstone, containers, vehicles, portals, dimensions and additional world/gameplay mechanics will be added incrementally after the core mob stack is stable.

## World compatibility

The mob work is designed to operate on existing worlds and existing chunks. A world reset is not required for natural mob spawning. Future terrain/structure generation changes will only affect newly generated chunks unless a separate migration is implemented.

## Research and attribution

The AI architecture is informed by PocketMine community work, including IvanCraft623/MobPlugin (Apache-2.0). GravityG's core implementation is being integrated and maintained directly in this fork rather than requiring an external gameplay plugin.
