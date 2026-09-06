# GravityG PocketMine-MP

A community-maintained continuation of the archived **PocketMine-MP** server software for Minecraft: Bedrock Edition.

## Current Bedrock target

- **Minecraft display version:** v26.45
- **Network version:** 1.26.45
- **Protocol:** 2169
- **PocketMine-MP base/API line:** 5.44.3 / API 5 compatible
- **PHP:** PMMP-compatible PHP 8.1+

This fork keeps the multi-protocol work inherited from NetherGamesMC and SyntaxStudiosRE, including the protocol changes required by Bedrock 1.26.40+ and the 1.26.45 protocol 2169 target.

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
