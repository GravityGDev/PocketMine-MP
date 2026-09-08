# GravityG PocketMine-MP 5.46 Vanilla Restoration

This branch is the development line for restoring more vanilla Minecraft Bedrock behaviour while retaining the PocketMine-MP API and server architecture.

## Target

- GravityG-PocketMine-MP: **5.46.0-dev**
- Minecraft Bedrock: **1.26.45 / v26.45**
- Protocol: **2169**
- Base stable line: **5.45.0**
- Development branch: **vanilla-5.46**

## Release status

The branch has enough integrated vanilla behaviour for public testing, but it is **not full vanilla parity yet**. Builds from this line should therefore be published as **Vanilla Preview** prereleases until the major survival systems and mob roster are substantially complete.

### Vanilla Preview 1 baseline

- Goal-based mob AI with MOVE, LOOK, JUMP and TARGET control channels
- Bounded A* ground pathfinding, repathing, stuck detection and line-of-sight sensing
- Amphibious navigation foundation used by Drowned
- Hostile natural spawning, caps, distance checks, darkness checks and natural despawning
- Restored common Overworld hostile mobs and several biome/special variants
- Spawn eggs, Bedrock item mappings and entity persistence for restored mobs
- Waterlogging and extra world block-layer support
- Restored/expanded vanilla behaviour for liquids and several water-sensitive blocks
- Projectile/equipment networking needed by restored mob combat

See `docs/VANILLA_MOB_PARITY.md` for the detailed mob-by-mob parity tracker.

## Development stages

### Stage 1 - Mob AI foundation

- [x] Goal scheduler with MOVE, LOOK, JUMP and TARGET control channels
- [x] Priority/pre-emption support
- [x] Mob base class with separate behaviour and target selectors
- [x] Vanilla-compatible `NoAI` entity NBT support
- [x] Movement, look and jump controllers
- [x] Nearest-player target acquisition
- [x] Line-of-sight sensing
- [x] Bounded terrain-aware A* ground pathfinding
- [x] One-block step-up and safe short-drop path nodes
- [x] Path caching, target-motion repathing and stuck detection
- [x] Loaded-chunk-only path search to avoid AI-triggered terrain generation
- [~] Water/amphibious navigation; Drowned support is present, broader fluid navigation remains
- [ ] Advanced vanilla navigation rules such as doors, more complex smoothing and specialist movement controllers

### Stage 2 - Natural spawning

- [x] Hostile mob cap
- [x] Per-player minimum/maximum spawn distance checks
- [x] Darkness/light validation for restored hostile spawning
- [x] Common Overworld hostile spawn cycle
- [x] Natural hostile persistence and distant despawning
- [~] Spawn pools and variants; common monsters are implemented while biome/dimension/structure pools are still expanding
- [ ] Passive/ambient/water creature natural spawn pools
- [ ] Full biome- and dimension-specific spawn registry
- [ ] Full structure, patrol, raid, insomnia and special-event spawning rules

### Stage 3 - Restored vanilla mobs

Implemented core parity:

- [x] Zombie
- [x] Skeleton
- [x] Creeper
- [x] Spider
- [x] Cave Spider
- [x] Silverfish
- [x] Endermite
- [x] Husk
- [x] Wither Skeleton

Partially restored and still receiving special-case parity work:

- [~] Drowned
- [~] Zombie Villager
- [~] Stray
- [~] Bogged
- [~] Parched

Next major roster work:

- [ ] Passive farm animals: Cow, Pig, Sheep and Chicken
- [ ] Slime family and Enderman
- [ ] Nether hostile/neutral pools
- [ ] Aquatic mobs
- [ ] Illagers, raids and village population systems
- [ ] Bosses

### Stage 4 - Broader vanilla systems

Already restored or expanded in this branch:

- [x] Waterlogging interface/trait and extra world block layers
- [x] Liquid bucket placement support for layered blocks
- [x] Water-sensitive behaviour improvements for coral, campfires, candles and sea pickles
- [x] Mob hand/equipment broadcast support used by restored combat

Still to be restored incrementally:

- [ ] Breeding, age/baby mechanics and passive animal interactions
- [ ] Full equipment pickup and mob loot tables
- [ ] Villager professions, trading, reputation and raids
- [ ] Redstone parity
- [ ] Containers and inventory mechanics not already covered by PocketMine
- [ ] Vehicles, riding and passengers
- [ ] Portals, dimensions and dimension-specific gameplay
- [ ] Remaining terrain, structures and biome gameplay parity

## World compatibility

The mob and spawning work is designed to operate on existing worlds and existing chunks. A world reset is not required for the restored natural mob spawning system. Future terrain/structure generation changes will only affect newly generated chunks unless a separate migration is implemented.

## Preview release policy

Vanilla Preview builds are intended for server owners who want to test the restored vanilla systems early. They may contain incomplete mob behaviour or gameplay gaps listed in the parity tracker. Stable vanilla-labelled releases should only be made once the major survival systems are no longer dependent on large missing parity areas.

## Research and attribution

The AI architecture is informed by PocketMine community work, including IvanCraft623/MobPlugin (Apache-2.0). GravityG's core implementation is integrated and maintained directly in this fork rather than requiring an external gameplay plugin.
