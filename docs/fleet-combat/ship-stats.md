# Ship stats for the new combat system: proposal

**Status:** validated on 2026-10-04 and implemented in [`config/game/battle_ship_types.yaml`](../../config/game/battle_ship_types.yaml). That file is separate from `ship_categories.yaml` while the new system only runs in the simulator. Adding a ship category would break the live game, which still relies on exactly 12 ship columns.

The first values are **derived from the current stats**, so that today's balance is the starting point. The balance harness will tune them afterwards.

## New stats

| Stat | Meaning | Derived from |
|---|---|---|
| `role` | Behaviour of the squadron: target preferences and position in the automatic formation (D4). Every squadron may enter another fleet's area since 2026-10-05. | Ship design, current attack profile. |
| `squadronSize` | Maximum number of ships in a squadron of this type (R2). | `floor(100 / pev)`, which keeps today's 100-PEV squadron. |
| `movement` | Fine cells a squadron can move per turn (R4). | `speed`: ≥ 180 → 3, 140–179 → 2, ≤ 100 → 1. |
| `initiative` | Order of action within a round (R3); higher acts first. | `speed`, so the same order as today's dodge ranking. |
| `range` | Maximum distance in fine cells at which the squadron can fire (Q12, simple version: no pattern yet). | Class and weapon profile. |
| `targets` | Ship classes the squadron prefers to target, in order (R4). | Role. |

## Proposed roles

| Role | Behaviour |
|---|---|
| **Interceptor** | Hunts fighters and bombers. Screens its fleet. |
| **Bomber** | Goes for heavy ships and avoids fighters. |
| **Escort** | Anti-fighter screen with many small shots. Stays close to the heavy ships. |
| **Line** | Durable brawler of the front line. Engages whatever is in front of it. |
| **Artillery** | Long range, stays behind the line, targets big ships. |
| **Capital** | Heavy all-rounder. Center of the formation. |

## Proposed values

Class: 0 fighter, 1 corvette, 2 frigate, 3 destroyer/cruiser.

| ID | Ship | Class | PEV | Speed | Role | Squadron size | Movement | Initiative | Range | Preferred targets |
|---|---|---|---|---|---|---|---|---|---|---|
| 0 | Chasseur léger | 0 | 2 | 195 | Interceptor | 50 | 3 | 195 | 1 | fighters → corvettes |
| 1 | Chasseur lourd | 0 | 3 | 185 | Interceptor | 33 | 3 | 185 | 1 | fighters → corvettes |
| 2 | Chasseur multi-tourelles | 0 | 3 | 195 | Interceptor | 33 | 3 | 195 | 1 | fighters → corvettes |
| – | **Bombardier** (new) | 0 | 4 | 170 | Bomber | 25 | 2 | 170 | 1 | destroyers → frigates |
| 3 | Corvette légère | 1 | 5 | 190 | Escort | 20 | 3 | 190 | 2 | fighters → corvettes |
| 4 | Corvette lourde | 1 | 7 | 165 | Line | 14 | 2 | 165 | 2 *(1 until 2026-10-08)* | corvettes → frigates |
| 5 | Corvette multi-tourelles | 1 | 9 | 145 | Escort | 11 | 2 | 145 | 2 | fighters → corvettes |
| 6 | Frégate d'attaque | 2 | 23 | 100 | Line | 4 | 1 | 100 | 2 | corvettes → frigates |
| 7 | Frégate ionique | 2 | 45 | 90 | Artillery | 2 | 1 | 90 | 3 | destroyers → frigates |
| 8 | Destroyer | 3 | 75 | 88 | Line | 1 | 1 | 88 | 2 | frigates → destroyers |
| 9 | Destroyer lance-missiles | 3 | 86 | 75 | Artillery | 1 | 1 | 75 | 4 | corvettes → fighters |
| 10 | Croiseur | 3 | 82 | 80 | Capital | 1 | 1 | 80 | 3 | destroyers → frigates |
| 11 | Croiseur lourd | 3 | 84 | 75 | Capital | 1 | 1 | 75 | 3 | destroyers → frigates |

## Bomber (new ship type)

Base stats are defined in `battle_ship_types.yaml`, since the bomber has no ship category yet:

| PEV | Life | Defense | Speed | Attack | Build cost, upkeep, tech |
|---|---|---|---|---|---|
| 4 | 40 | 4 | 170 | one 60-damage torpedo | to define in Phase 7, when it joins the game |

One torpedo deals about 98 damage to a destroyer (defence 120), against 1 for a light fighter shot. Light fighters kill a bomber in about 3 hits.

## Decisions (2026-10-04)

1. **Bombers:** a new ship type, rather than changing the *Chasseur multi-tourelles*, which stays an interceptor.
2. **Big ships alone in their squadron:** accepted. It gives these ships more intrinsic value.
3. **Fleet size:** composition grid radius 2 kept for now (19 squadrons at most), to see how it plays.
4. **Range in fine cells:** confirmed.
