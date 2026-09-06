# Bedrock 1.26.45 port

This branch updates the archived PMMP codebase to Minecraft Bedrock 1.26.45
(display version v26.45), protocol 2169.

The protocol/runtime implementation is based on the public
SyntaxStudiosRE/NetherGamesMC continuation of PocketMine-MP and is pinned to
SyntaxStudiosRE/PocketMine-MP commit
`70d6ca99be6fc29f10af9e7ce4b2ca865e139108` for reproducibility.

Its 2169 implementation is a protocol-number/version bump over the 2168
serializer implementation. This branch additionally promotes 2169 to
CURRENT_PROTOCOL and advertises v26.45 / network version 1.26.45.

PocketMine-MP remains LGPL-3.0 licensed. Original PMMP, NetherGamesMC and
SyntaxStudiosRE attribution and copyright notices are preserved.
