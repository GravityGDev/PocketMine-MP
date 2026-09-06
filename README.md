# GravityG PocketMine-MP

A community-maintained continuation of the archived **PocketMine-MP** server software for Minecraft: Bedrock Edition.

## Current release

- **GravityG-PocketMine-MP:** 5.45.0
- **Minecraft display version:** v26.45
- **Network version:** 1.26.45
- **Protocol:** 2169
- **Upstream base:** PocketMine-MP 5.44.3
- **API line:** API 5 compatible
- **PHP:** PMMP-compatible PHP 8.1+

This fork keeps the multi-protocol work inherited from NetherGamesMC and SyntaxStudiosRE, including the protocol changes required by Bedrock 1.26.40+ and the 1.26.45 protocol 2169 target.

## Update log — 5.45.0

Released as the first GravityG-maintained version after the official PMMP 5.44.3 line was archived.

### Bedrock support

- Updated the active Bedrock target to **v26.45 / network 1.26.45**.
- Added **protocol 2169** support.
- Reused the 1.26.40+ serializer path for 1.26.45 where Mojang made no packet serializer changes.
- Retained the inherited multi-protocol compatibility layer for older supported Bedrock protocol versions.

### GravityG continuation changes

- Renamed the runtime branding to **GravityG-PocketMine-MP**.
- Updated Composer package identity to `gravitygdev/pocketmine-mp`.
- Updated repository/source links to `GravityGDev/PocketMine-MP`.
- Fixed startup code that still referenced the old `syntaxstudiosre/pocketmine-mp` Composer package name.

### Build and deployment

- Added automatic PHAR release builds through GitHub Actions.
- Release assets include the PHAR, SHA-256 checksum, build metadata and launcher scripts.
- Fixed the Docker build after the move to `nethergamesmc/bedrock-data` by removing the obsolete `vendor/pocketmine/bedrock-data/.minify_json.php` build step.
- Added a root `docker-compose.yml` for Dokploy/Docker Compose testing.
- Verified the server boots successfully in Dokploy, creates the default world, reaches 100% spawn generation and opens the Bedrock network interface on UDP port 19132.
- Docker PHAR builds now derive their git revision from the checked-out repository automatically instead of pinning stale build metadata.

### Validation status

A real Minecraft Bedrock **v26.45** client has successfully connected to the server using **protocol 2169**, entered the world and executed the server information command. The server reported **GravityG-PocketMine-MP 5.45.0**, compatible Minecraft version **1.26.45**, protocol **2169**, PHP **8.2.30** and Linux at runtime.

Core protocol/login compatibility for Bedrock 1.26.45 is therefore verified. Broader plugin, gameplay and long-running production testing can continue as normal for a maintained PocketMine server release.

## Downloads

Stable releases are built automatically by GitHub Actions. Each release includes:

- `PocketMine-MP.phar` - ready-to-run server PHAR
- `PocketMine-MP.phar.sha256` - SHA-256 checksum
- `release-build-info.json` - exact PocketMine, Bedrock protocol and commit metadata
- `start.sh`, `start.cmd` and `start.ps1` - launcher scripts

Download the latest build from the [GitHub Releases page](https://github.com/GravityGDev/PocketMine-MP/releases/latest).

## Important

PocketMine-MP is **not** a vanilla Bedrock Dedicated Server replacement. It is designed for custom servers and plugin-driven networks. Vanilla features such as full vanilla world generation, redstone and mob AI are not complete in PocketMine-MP.

`PocketMine-MP.phar` also does **not** run on an ordinary stock PHP installation. Use a PMMP-compatible PHP build containing the required extensions such as `pmmpthread`, `chunkutils2`, `leveldb`, `morton`, `encoding` and `crypto`.

## Building

```bash
composer install --no-dev --classmap-authoritative --ignore-platform-reqs
php -dphar.readonly=0 build/server-phar.php
```

## Automated releases

The `.github/workflows/release-phar.yml` workflow builds and verifies the PHAR whenever `src/VersionInfo.php` is changed on `stable`. It creates or updates the matching `v<version>` GitHub release automatically. Publishing a GitHub release manually also causes the workflow to rebuild and attach the release assets.

## Protocol-port lineage

The original `pmmp/PocketMine-MP` project ended official support in July 2026. The Bedrock 1.26.45 continuation in this repository incorporates public multi-protocol/runtime work from **NetherGamesMC** and **SyntaxStudiosRE**. The 1.26.45 implementation uses protocol **2169** and reuses the 1.26.40+ serializer path where Mojang did not change the packet serializers.

See [`BEDROCK_PORT.md`](BEDROCK_PORT.md) for the pinned source provenance used for this port.

Original source copyright and attribution notices are retained. This repository is not an official PMMP, Mojang or Microsoft release.

## Licensing

PocketMine-MP and this continuation are licensed under **LGPL-3.0**. See [`LICENSE`](LICENSE) for details.
