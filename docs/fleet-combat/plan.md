# Fleet combat redesign: work plan

**Branch:** `feature/fleet-composition`
**Status:** Phase 3 done (2026-10-05), pending the maintainer's check. Phase 4 (rich report and replay) next. The design described here is a target, not the current behaviour. For current rules, see [`docs/gameplay.md` §9–11](../gameplay.md#9-military-ships-commanders-fleets).

This file is the single source of truth for the fleet and combat redesign. Update it whenever a decision is made: answer the open question, add an entry to the decision log, and move the work items forward.

---

## 1. Why: problems with the Asylamba system

The current system (inherited from Asylamba):

1. A player creates a fleet by assigning an officer (commander) in orbit of one of their bases.
2. The player puts ships into the fleet's squadrons. The number of squadrons is the officer's level.
3. The fleet is sent somewhere with an order: loot, colonise, conquer or move. If the order is hostile and defending fleets are in orbit, a battle happens. Only **one** defending fleet fights the attacker, even when several are present.
4. The battle is resolved **automatically and instantly**. Squadrons fight in rounds until one fleet has no ships left. Ship type, composition and squadron line strongly affect the outcome.
5. Afterwards the player can read a combat report: a table of losses per fleet.

What is wrong with it:

| # | Problem | Consequence |
|---|---|---|
| P1 | Instant resolution leaves no room for reinforcements. | Little cooperation between players. Players synchronised attacks to arrive in the same second, which distorts the game. |
| P2 | Battles are always one fleet against one fleet. | Synchronised attacks and multiple defending fleets are pointless. |
| P3 | Fleet size depends on officer level. | Players loot NPCs over and over just to level up officers. |
| P4 | An instant result and a table of losses are a poor reward. | The time spent composing the fleet and levelling officers goes unrewarded. |
| P5 | Composition is hard to read. | Players can't tell which compositions work, and the report doesn't show what effect their choices had. |

## 2. Target vision

- **Multi-fleet battles that involve several players.** They are turn-based and run in real time, with each turn lasting several seconds.
- **Live visualisation on a hexagonal grid** with two zoom levels:
  - **Battlefield level:** how the fleets are positioned relative to each other.
  - **Fleet level:** the squadrons inside a fleet.
  - Each turn plays an animation showing which squadrons fire and what is lost.
- **New fleets can join a battle while it runs,** so the biggest battles last longer.

**Strategy:** build everything first as a **combat simulator in the portal** (`/combat-simulator`). This has no effect on the live game. The real game only switches over once the engine and the visualisation are proven.

## 3. Design goals (from P1–P5)

These goals are the acceptance criteria for design decisions.

- **G1 (P1, P2):** Arriving at the same second gives no advantage. Being present and timing your arrival over minutes is what counts.
- **G2 (P2):** Several attacking and defending fleets take part in the same battle, and they interact.
- **G3 (P3):** Fleet power does not depend on repetitive grinding. Fleet size is limited by maluses, the economy and strategy, not by officer level (Q13).
- **G4 (P4):** A battle is a **show** worth watching, and a replay of it can be kept.
- **G5 (P5):** The player can see, during the battle and afterwards, **why** things happened: who fired at whom, and the effect of lines, positioning and ship types.

## 4. Current state of the code

### 4.1 Reusable as is or with adaptation

| Piece | Location | Reuse |
|---|---|---|
| Ship stats (life, attack list, defence, speed, PEV) | `config/game/ship_categories.yaml`, `ShipCategory` | Keep as the stat source. New stats (range, role…) may be added. |
| Damage formula and dodge | `Ares/Application/Handler/Battle/ShipFightHandler.php` | Starting point for turn resolution. |
| Bonuses (techs, faction) | `ShipStatsHandler`, `PlayerBonusManager` | Keep, and fix the known bugs ([gameplay §14](../gameplay.md#14-known-issues), items 3, 8). |
| `VirtualCommander` + `CommanderInterface` | `Ares/Domain/Model/VirtualCommander.php` | The simulator's fleet model. |
| Simulator page, commander form, list (session storage) | `Portal/Infrastructure/Controller/CombatSimulator/`, `Portal/.../Twig/Components/Organisms/CombatSimulator/` | Keep and extend. |
| Fleet grid prototype (5 rows of 20→16 cells, `gridPositionSelected`) | `Ares/.../Organisms/FleetGrid.php`, `Atoms/FleetGridPosition.php`, `templates/components/{Organisms,Atoms}/Fleet/` | Starting point for the formation editor. Its shape will change to a hexagon of hexes (R2, R10). |
| Ship transfer tiles | `ShipTransfer*`, `assets/controllers/fleet/ship_transfer_controller.js` | Shared by the real fleet screen and the simulator. |
| Delayed Messenger messages, Redis locks, Mercure + Turbo Streams | Already in the stack; Turbo Streams are used in `templates/components/planet/trade/market.html.twig` | Turn ticking and live push. |

### 4.2 To be replaced

- `FightManager`, which keeps static state and resolves a battle in one go.
- `LiveReport`, which is a static global.
- `Report` (begin and end armies only).
- `CommanderManager::startFight` and the defender selection in `LootManager` and `ConquestManager`. Defender selection is broken anyway: [gameplay §14](../gameplay.md#14-known-issues) item 1.

### 4.3 Unfinished work in the working tree (fix first)

- `ShipTransfer.html.twig` still emits `data-fleet--ship-transfer-base-value`, but the JS now expects `source`. As a result `base_id` is sent empty.
- `targetTileQuantityElement.dataset.has('infinite')` is not valid: a `DOMStringMap` has no `.has()`. Use `'infinite' in dataset`.
- `ShipTransferTile.html.twig` contains a leftover `{% dump('ok') %}`.
- `isInfinite` is passed to `ShipTransferTile`, but the PHP component doesn't declare it.

## 5. Design decisions

These are the maintainer's answers. Each item keeps its original question number (Q1–Q17) for traceability.

### 5.1 The battle as an object

- **Q1. Location.** At most **one battle per planet** at any time. The only exception is in Q2. Interceptions in open space may come later and are out of scope for now.
- **Q2. Sides.** Faction relations decide who fights whom:
  - A faction arriving that is **enemy of one belligerent** joins the **opposite side**, whether it is allied with or neutral toward that side.
  - A faction that is **enemy of every belligerent** fights everyone, as its own side.
  - A faction that is **allied with or neutral toward every belligerent** does **not** join.
  - **Exception to Q1** (*replaced by D7: the second battle waits for the first to end*): factions neutral toward or allied with the current belligerents, but at war with each other, start a **second, separate battle** at the same planet.
  - So the model has to support N sides, and possibly several battles at one planet.
- **Q3. Who joins.** Any faction with fleets in the same orbit as an enemy fleet takes part, not only the planet owner's faction. A fleet that arrives while at least one of its enemies is in the battle **joins it, whatever its original order** (move, loot, colonise, conquer).
- **Q4. Planetary defence.** None for now. It will come later, outside the fleet battle system.
- **Q5. NPCs.** NPC planets and NPC fleets follow exactly the same rules as players.

### 5.2 Turns and duration

- **Q6. Turn length.** A **new environment variable**, independent of `SERVER_TIME_MODE`. Default: **10 seconds**. Suggested name: `BATTLE_TURN_DURATION`.
- **Q7. End and retreat.**
  - Long term, fleets have **morale**. When morale is too low the fleet retreats, which avoids losing a whole fleet at once.
  - A retreat is **punitive**: for example, during several turns the enemy can fire at it with no return fire.
  - Two kinds of retreat:
    - **Flight:** the fleet leaves the battle and heads back to its origin planet. If that planet is no longer under allied control, it goes to an allied planet.
    - **Withdrawal:** the fleet moves behind allied fleets on the battlefield, and may re-engage later in the battle.
  - The battle ends when no two hostile sides have fleets left on the battlefield.
- **Q8. Player actions during a battle.** **None.** Connected players must not have an advantage over disconnected ones. Retreat is automatic, driven by morale.
- **Q9. Reinforcements.** A fleet joins at the **next turn after its travel ends**. It is placed in contact with an enemy fleet if possible, otherwise at the nearest free position.

### 5.3 Grid and positioning

- **Q10. Battlefield** (zoom 1).
  - A **5×5 hex grid** for now; the size is arbitrary and should stay configurable.
  - Fleets are placed **by order of arrival**.
  - A fleet **moves** if it is not in contact with an enemy fleet and can get closer to one. Otherwise it waits.
- **Q11. Fleet formation** (zoom 2). *Confirmed by D9 after the community poll: the player draws the formation.*
  - The formation is prepared **on the planet** while composing the fleet.
  - Composition and formation are **locked once the fleet leaves on a mission**.
  - During combat, squadrons may **reposition automatically** according to their weapon range and capabilities.
- **Q12. Range.** Long term, ship types get different weapons that hit **different cells relative to their position**. Squadrons position themselves to fire effectively, especially against the ship types they counter.

### 5.4 Fleet size and progression (P3)

- **Q13. Fleet size.**
  - Fleet size is **no longer capped by officer level**.
  - The bigger the fleet, the bigger its **maluses**: initiative, dodge, travel speed on the galaxy map, and so on.
  - Officer level reduces these maluses.
  - In practice fleet size is limited by the **economy and strategy**: cost, and how military forces are spread across the map. A giant fleet is possible but quickly becomes a disadvantage.
  - Officers gain **traits** (in the style of Total War Saga) through experience, which can help or hinder their fleet.
- **Q14. Experience.** For now, officer XP comes from **damage dealt to enemy fleets plus damage taken by the officer's own fleet**.

### 5.5 Rewards and readability (P4, P5)

- **Q15. Replay.** **Yes.** It is needed for players who were offline during the battle, for later analysis, and for sharing.
- **Q16. Report.** Approved: damage dealt and taken per squadron, kills by ship type, effect of bonuses, highlights.
- **Q17. Spectators.** At first, only **direct participants** can watch live. Sharing reports comes later.

### 5.6 Clarifications (R1–R8)

- **R1. Enemies.** Hostility is decided at **faction** level, from the `relations` map of each faction (`Color::relations`).
  - NPCs are enemies of everyone for now.
  - *(Provisional detail: relations can be one-sided, so a faction counts as an enemy if **either** faction has set ENEMY. In practice a declaration of war sets ENEMY on both sides.)*
- **R2. Grids, squadrons and fronts.**
  - **One ship type per squadron.** This replaces the Asylamba rule, where a squadron could mix ship types up to 100 PEV.
  - **Squadron size is set by the ship type:** a new ship stat gives the maximum number of ships per squadron.
  - **Fleet grid shape.** It is a **hexagon made of smaller hexes**, matching the battlefield hex it sits in. Zooming into a battlefield hex shows that fleet's grid.
  - **The battle fleet grid is larger than the composition grid,** so squadrons have empty cells to move into.
  - **Squadrons move on fleet grids,** including **onto another fleet's grid**.
  - **Range depends on the weapons** of the squadron's ship type.
  - **Front line.** In composition the fleet is laid out top to bottom, and the **top row is the front line**. When two fleets meet, their top rows face each other.
  - **Multiple fronts.** A fleet surrounded by several enemies fights on several fronts, so an enemy's front line can face the **rear** of a fleet that is already engaged.
- **R3. Turn order.**
  - **Initiative is per squadron.**
  - Squadrons fire **one after another** within a round, so damage from one squadron changes its target before that target acts.
  - The number of squadrons firing per round is **limited**, to keep rounds short. How to choose them is still open (R9).
- **R4. Movement and targeting.**
  - **Movement capacity depends on the ship type:** fighters move more easily than cruisers.
  - **Cell occupancy:** the first squadron (in initiative order) to reach an empty cell holds it. Another squadron can only take that cell by destroying the holder.
  - **Target choice**, both fleet against fleet and squadron against squadron, depends on **officer traits** (aggressiveness, courage…) and **ship stats**. Example: bombers prefer heavy ships and avoid fighters.
- **R5. Morale model.** To be calibrated during the balancing phase.
- **R6. Fleet "weight"** (for size maluses). *Superseded by D2: PEV for now.*
  - Mainly the **number of squadrons**: spreading forces out causes disorganisation, so 10 squadrons of 1 fighter are worse than 1 squadron of 10.
  - **PEV** probably counts as well, so 10 squadrons of 20 fighters weigh more than 10 squadrons of 1.
  - Exact formula: later.
- **R7. Missions.** For now, a fleet that survives a battle **carries on with its original mission**.
- **R8.** A fleet is engaged in **at most one battle** at a time.

### 5.7 Outcomes of the game-design review (2026-10-04)

These decisions **override** the earlier answers where they conflict.

- **D1. Battle duration scales with the forces involved.**
  - A small or medium one-against-one battle lasts **a few minutes**.
  - Battles with more or bigger fleets last **much longer**, so other players have time to intervene. This is what really solves P1: if battles were short, players would simply synchronise to the minute instead of to the second.
  - **Engagement delay:** a fleet arriving outnumbered in an orbit waits several minutes before combat starts. This gives a placement phase and time for reinforcements.
  - Duration targets must be written down and checked by the balance harness.
- **D2. Fleet weight for size maluses is measured in PEV for now.** This overrides R6. Counting squadrons would penalise diverse fleets, which conflicts with single-type squadrons.
- **D3. Combat stays idle, as in Asylamba.** The player cannot act during a battle.
  - ~~The officer composes the formation.~~ **Superseded by D9** (community poll): players compose their formations themselves.
  - The officer-built formation (`BuildFleetFormation`) is kept as a **suggestion**: a "fill automatically" action in the editor. It also builds the formations of NPC fleets.
- **D4. Each ship type has one or two clear roles.** ~~Only fighters and bombers can move onto another fleet's grid.~~ **Amended on 2026-10-05: every squadron may enter another fleet's area**, so that heavy ships of a fleet arriving behind an engaged enemy can reach it. Roles still drive targeting and formation.
- **D5. Interest curve: to design later.** Battles need highlights (opening, reinforcements, routs, turning point), and replays need to be skimmable (timeline, summary, MVP). Not designed yet; noted as a risk.
- **D6. Experience.**
  - Damage dealt **and taken** both give XP (Q14 stands). Losing ships is already punishment enough against farming through defeat.
  - XP is weighted by the **importance formula** (relative strength of the opponent), as today.
  - Other penalties may come later: persistent fleet morale, public opinion in the faction or on the player's planets.
- **D7. Second battle at the same planet:** instead of running two battles in parallel, fleets that would start a second battle **wait** until the first one ends. This replaces the Q2 exception, so there is still at most **one battle per planet**.
- **D8. Faction spectators: to test.** Members of a participant's faction may watch live, as a social draw (cf. EVE Online's large battles). Q17 stays "participants only" until this is tested.

- **D9. Players compose their formations themselves** (community poll, 2026-10-04). This supersedes the officer-built formation of D3.
  - The player builds single-type squadrons from the planet's ships and places them on the composition grid, top row being the front line.
  - `BuildFleetFormation` stays as a suggestion ("fill automatically") and for NPC fleets.
  - The engine is unchanged: it already takes a list of formation slots, whatever their source.
  - Readability risk (§5.8) is reduced: the player's choices map directly to positions on the battlefield.

### 5.8 Known design risks

- **Readability (P5) of the idle combat.** Many automatic systems pile up (squadron movement, cell control, initiative, trait-driven targeting, range patterns, morale, size maluses, random dodge). Players may no longer connect their choices to the result.
  - **Mitigations:**
    - clear ship roles (D4);
    - every automatic decision is explained in the replay (e.g. "squadron X charges: Aggressive trait");
    - the balance harness checks that no composition dominates and that counters are visible;
    - simplify the rules before investing in visuals if the harness shows otherwise.
- **Interest curve** (D5): a long battle with no player action must still be worth watching, or at least skimming.

## 6. Remaining open questions

- **R9. How to limit the number of squadrons firing per round** (R3). Options:
  - **(a)** A fixed cap per round, with **accumulated initiative**: squadrons that did not fire gain priority in the next round. The maintainer's worry is that powerful ships could be destroyed before firing once.
  - **(b)** Every squadron fires every round, ordered by initiative. Rounds are longer, but bounded by the number of squadrons.
  - **(c)** A cap per fleet rather than per battle, so every fleet acts each round.
  - **(d)** Guarantee each squadron at least one shot within N rounds after it engages.
  - *(Provisional for Phase 1: option (b), every squadron fires in initiative order. The engine computes all of it server-side; only the animation needs to stay readable. The cap is tested later with the balance harness.)*
- **R10. Grid sizes.**
  - What is the radius of the composition grid, and of the battle fleet grid?
  - *(Provisional: composition radius 2, i.e. 19 cells; battle radius 4, i.e. 61 cells.)*
  - The current prototype (5 rows of 20→16 cells) will be replaced by a hex-shaped grid.
- **R11. Turn vs round.** Is one turn (`BATTLE_TURN_DURATION`, 10 s) exactly one round, i.e. movement, then firing in initiative order? *(Provisional: yes.)*
- **R12. New ship stats needed:**
  - max ships per squadron
  - movement
  - initiative
  - weapon range and pattern
  - target preferences (e.g. heavy vs light)
  - Values are to be designed for the 12 existing ships before Phase 1 tests can mean anything. *(Provisional: derive first values from the current stats. Speed gives movement and initiative; class gives range; a squadron capacity of about 100 PEV ÷ ship PEV keeps today's balance.)*

## 7. Technical principles

These are proposals that can be changed.

- **A pure domain engine.**
  - `BattleEngine::resolveTurn(BattleState, seed): TurnResult`, with no Doctrine, no static state and no I/O.
  - It is deterministic for a given RNG seed, which allows unit tests, a fast simulator and exact replays.
  - It lives in `Ares/Domain/Service/Battle/`.
- **Event log.**
  - Each turn produces a list of events, e.g. `FleetJoined`, `FleetMoved`, `SquadronMoved`, `SquadronFired{from, to, shots, hits, damage, kills}`, `SquadronDestroyed`, `FleetRetreated`, `BattleEnded`.
  - The same log drives the animation, the report and the replay.
- **A single fine-grained hex grid.**
  - Since squadrons can move from one fleet's grid onto another's (R2), the engine works on **one fine hex grid** in axial coordinates.
  - Each battlefield hex is a **hexagonal cluster** ("super-hex") of radius R, which has `3R(R+1)+1` cells. Hexagonal clusters tile the plane exactly.
  - Zoom 1 (battlefield) is an aggregated view of the super-hexes. Zoom 2 (fleet) shows the fine cells.
  - A fleet is anchored to a super-hex. Its formation is the composition grid, rotated (6 possible orientations) so that its front row faces the enemy it engages.
- **Ship stats in config.** New per-type stats (R12) go in `config/game/ship_categories.yaml` next to the existing ones, so balancing never requires code changes.
- **Side resolution is its own service.** `ResolveBattleSides(factions, relations)` implements Q2 and Q3 (join the opposite side, fight everyone, stay out, or start a second battle). It is pure and heavily unit-tested.
- **Config.** `BATTLE_TURN_DURATION` (default 10 s) and the battlefield size (5×5) are container parameters.
- **The simulator runs the engine synchronously.** All turns are computed in one request, then replayed on the client at the chosen speed. Reinforcements are simulated with "joins at turn N". This needs no worker or Mercure.
- **The live game runs on a turn ticker.**
  - Each `Battle` entity schedules a delayed `BattleTurnMessage` (high-priority transport) every `BATTLE_TURN_DURATION`.
  - A Redis lock per battle (`symfony/lock`) prevents concurrent turns.
  - Each turn's events are published through Mercure on `/battles/{id}`; access is restricted to participants (Q17).
  - On arrival, `CommanderTravelHandler` asks a `BattleJoiner` service whether the fleet joins a battle, opens one, or carries on with its mission.
- **Front-end.**
  - The hex battlefield and its animations are client-side rendering, so they belong in a **Stimulus controller** (SVG, or Canvas if performance requires it). This is an accepted exception to the "prefer Live Components" rule.
  - Forms and editors (fleet composition, formation, simulator setup) stay as Live Components.

## 8. Roadmap

Each phase ends in a usable, tested state. Game-facing phases (6+) need explicit approval.

### Phase 0: stabilise the branch

- [x] Fix the items in §4.3 (ShipTransfer `base` → `source`, `dataset.has`, `dump`, `isInfinite`). Done 2026-10-04; static checks only, not yet tested in a browser.
- [ ] Note: the simulator's ship tiles are not attached to any `fleet--ship-transfer` controller yet. They will be replaced by the formation editor (Phase 2, D9).

### Phase 1: battle domain model and engine (simulator only)

**Scope (MVP):**
- **Sides:** N sides, resolved by `ResolveBattleSides`.
- **Squadrons:** one ship type each, capped by a per-type size.
- **Grid:**
  - a 5×5 battlefield of super-hexes on a single fine hex grid;
  - fleets placed by arrival order, with their formation rotated to face the enemy;
  - multiple fronts allowed.
- **Movement:** squadrons move by their per-type movement capacity. The first one to reach a cell holds it. Squadrons may enter other fleets' grids.
- **Firing:** sequential in per-squadron initiative order (R9, provisional option b), with simple per-type range and the current damage and dodge rules.
- **Targeting:** a simple rule based on ship stats (preferences). Traits come later.
- **Reinforcements:** joining at turn N, placed in contact with an enemy.
- **Formation:** built automatically from the ships provided (D3), with a simple first algorithm based on ship roles. Only fighters and bombers may leave their fleet's area (D4).
- **End condition:** no two hostile sides left. Surviving fleets resume their mission (R7).
- **XP:** from damage dealt and taken.

**Not in scope yet:** morale and retreat, complex weapon patterns, size maluses, traits.

**Tasks:**
- [x] Draft first values for the new ship stats (R12), including **roles** (D4): see [`ship-stats.md`](ship-stats.md). Validated, and implemented in `config/game/battle_ship_types.yaml` (`BattleShipTypeRegistry`, `BattleShipTypeConfiguration`).
- [x] Hex grid primitives: axial coordinates, distance, neighbours, super-hex clusters, rotation. Pure PHP, unit-tested (`Ares/Domain/Model/Battle/Grid/`).
- [x] Model `BattleFleet` (formation + cluster anchor + orientation), `BattleSquadron` (single ship type, cell), `BattleState`, `TurnResult` and `BattleEvent` (`Ares/Domain/Model/Battle/`). Sides are an integer per fleet.
- [x] `ResolveBattleSides` service (Q2, Q3, R1) and its unit tests (`Ares/Domain/Service/Battle/`), with `ColorFactionHostility` (`Ares/Application/Battle/`). When a faction is friendly to several sides, it joins the oldest one (provisional).
- [x] `BattleEngine::resolveTurn` with a seeded RNG (`Ares/Domain/Service/Battle/`, steps in `Engine/`): placement of joining fleets (Q9), fleet and squadron movement with cell occupancy (Q10, R4), targeting, sequential fire by initiative (R2, R3, R9 option b).
- [x] Unit tests: determinism, side resolution, end conditions, reinforcements, fleet area, cell occupancy, mirror fairness, bonuses, experience.
- [x] Officer XP from damage dealt and taken, weighted by the importance of the battle (D6): `ComputeBattleExperience`.
- [x] Combat bonuses per fleet (`BattleBonuses`: attack, defense, speed percentages by ship class); faction bonuses from `factions.yaml` (`GetFactionBattleBonuses`).
- [x] `BuildFleetFormation` service: the officer turns the provided ships into single-type squadrons and a formation (D3).
- [x] **Balance harness, as early as possible** (validate the fun before investing in visuals): a CLI command that runs N simulations of composition A against composition B. It reports win rates, **battle duration in turns and minutes** (D1), and dominant compositions.

**First harness results** (2026-10-04, 40 battles each; `app:ares:simulate-battles`):

| Matchup | Result | Median duration |
|---|---|---|
| 100 light fighters vs 50 bombers | fighters 90% | 50 s |
| 50 bombers vs 4 destroyers | bombers 100% | 40 s |
| 4 destroyers vs 150 light fighters | destroyers 100%, no losses | 2 min 20 |
| Mixed mid-size fleet, mirror | 55 / 45 (fair within noise) | 3 min |
| Two big mixed fleets | the fleet with bombers wins 95% | 1 min 30 |

Takeaways:
- **Durations are far below the D1 target.** Big battles last 1–5 minutes, which leaves no time for reinforcements. The damage scale (or life) needs to go down a lot.
- **Counters are very hard.** Light fighters do about 1 damage to a destroyer per shot (logarithmic damage formula), and bombers dominate heavy ships. This is readable, but may be too binary.
- **A movement bias was found and fixed:** a fixed neighbour order gave the first deployed fleet 65% wins in mirror battles. Ties are now broken at random from the turn seed.

**Duration tuning** (2026-10-04, provisional values in `src/Modules/Ares/Resource/config/config.yml`):

- **Finding:** without a limit, durations do not grow with fleet size (1–2 min from 100 to 1,500 PEV), because firepower and life grow together. A global factor alone cannot meet D1.
- **Two levers added:**
  - `ares.battle.durability_factor`: multiplies the life of every ship. It sets the base duration.
  - `ares.battle.max_firing_squadrons_per_fleet (replaced by ares.battle.action_duration on 2026-10-05)`: at most N squadrons of a **fleet** fire per round. Squadrons left out accumulate their initiative, so heavy ships still fire (checked: cruisers and destroyers fire as often as without the cap). The firepower of big fleets saturates, so **big battles last longer**.
  - The cap is per fleet rather than per side, so that each reinforcement still adds firepower (G2). It could later depend on the officer level, as one of the size maluses officers mitigate (Q13).
- **Values chosen: durability 3, cap 2** (median durations over 30 battles):

| Battle | Without levers | Durability 3, cap 2 |
|---|---|---|
| Small (~100 PEV per side) | 2.0 min | 1.7 min |
| Small mixed (~200 PEV) | 1.2 min | 2.2 min |
| Medium (~600 PEV) | 1.5 min | 5.3 min |
| Big (~1,500 PEV) | 1.5 min | 7.3 min |
| Big, line ships (~1,650 PEV) | 2.3 min | 11.2 min |
| Medium mirror, 2 fleets per side | – | 9–11 min; a reinforcement arriving at turn 30 (5 min) still wins 100% |

- **Still to address:**
  - Small battles cannot go below about 1.5 min: the approach phase (squadrons moving to contact) sets a floor.
  - Range-1 line ships (*Corvette lourde*) rarely fire against escorts and artillery: they are outranged.
  - Damage and life values remain the legacy ones; durability only scales them.

**Experience and bonuses** (2026-10-04):

- **Experience formula:** `(damage dealt + damage taken) / durability × experience_per_damage (2) × importance`.
  - Importance = enemy strength ÷ own strength, where strength = PEV × (officer level + 1), summed over every enemy fleet, and capped at 3.
  - Dividing by durability means tuning battle durations does not change experience.
  - Order of magnitude: about 31,000 XP for a medium battle (~600 PEV per side), close to the legacy formula (630 PEV × 50).
  - ⚠️ The legacy importance formula, `enemyPEV × (ownLevel + 1) / (enemyLevel + 1)`, gives *less* experience against a higher-level officer. The new one follows the stated intent instead: a stronger opponent earns more.
  - ⚠️ Consequence of D6 to keep in mind: in a battle between two fleets, A's damage dealt is B's damage taken, so **both officers earn the same experience whatever the outcome**. Only the importance differs. Winning is not rewarded as such.
- **Bonuses:** attack uses the shooting fleet's bonuses, defense and dodge use the target fleet's bonuses. Cruisers get the destroyer class bonuses, and the faction defense bonus is applied once. These were three legacy bugs.
  - Harness check: a mirror battle between Kalankar (defense +5%) and Valkar (speed +10%, heavy ships' defense −5%) ends 62 / 38.
  - Technology bonuses for the live game will be mapped from `PlayerBonus` in Phase 7. The simulator accepts explicit per-class bonuses.
- **Officer level** only weighs in the experience for now. Its effect on combat (size maluses, traits) comes in Phase 5.

### Phase 2: simulator v2

- [x] **Formation editor** (D9), the `SimulatorFleetEditor` Live Component:
  - the player places single-type squadrons on the composition grid (hexagon of radius 2, 19 cells), front line at the top, with a "place / remove" tool;
  - the squadron size is chosen per placement (full squadron by default);
  - "Réorganiser automatiquement" uses `BuildFleetFormation` as a suggestion;
  - errors (fields and `ValidateFormation` violations) are displayed in French.
  - Display convention: the editor shows the front at the top. The engine stores formations with the front facing east, so the editor rotates the view; the stored offsets keep the engine convention.
- [x] Simulator setup: several fleets per side, each with a name, a faction (combat bonuses), an officer level and an arrival turn. Fleets are listed by side (`SimulatorFleetsList`: edit, duplicate, remove) and kept in the session as plain arrays (`SimulatedFleetStorage`).
- [x] Per fleet: arrival turn, to simulate reinforcements.
- [x] "Lancer la simulation" (`POST /combat-simulator/run`): computes the whole battle on the server and returns the event log as JSON, with ship type names and officer experience. An optional seed replays the same battle.
- [x] A basic viewer (Stimulus `combat-simulator--replay`):
  - a summary: winner, duration, seed, remaining PEV and XP per fleet;
  - a turn player with first / previous / play-pause / next / last, a slider and four speeds (up to real time);
  - the state of each fleet at the chosen turn;
  - the events of the turn in French.
- Verified end to end over HTTP against the dev server, the viewer logic in Node on a real battle, then in the browser by the maintainer (after recompiling the stale `public/assets`, see AGENTS.md).
- Also fixed:
  - Live components under `/_components` required authentication, so the public simulator could not work for anonymous visitors. Access is now public for `Simulator*` components only (`config/packages/security.yaml`).
  - `BATTLE_TURN_DURATION` has a default value, because the project loads a compiled `.env.local.php`.
- Left in place, unused: the previous `SimulatorCommanderForm` / `SimulatorCommandersList` components, `VirtualCommanderType` and the `FleetGrid` prototype. They contain uncommitted work of the maintainer, who will remove them.

### Phase 3: hex visualisation

- [x] Battlefield view (zoom 1): the 5×5 fleet areas drawn as hexagonal clusters of fine cells, tinted with the faction colour of the fleet standing there, with fleet names coloured by side. `BattlefieldView` (`assets/controllers/combat_simulator/battlefield_view.js`), SVG.
- [x] Fleet view (zoom 2): click a fleet area or one of its squadrons to zoom on it (animated view box), "Vue d'ensemble" to zoom out. Squadrons are hexagons in their **faction colours** (medium fill, light outline), showing their ship count. *(Role colours replaced by faction colours on 2026-10-05, see the art direction in AGENTS.md; ship pictograms to come.)*
- [x] Per-turn animations, when playing or stepping forward one turn:
  - arrivals fade in, squadrons glide to their new cell;
  - each shot is drawn in firing order as a line from shooter to target, in the shooter's side colour;
  - floating damage numbers ("raté" for misses), the target flashes and its ship count drops;
  - destroyed squadrons fade out.
  - Jumping to a turn shows its final state with that turn's shots as faint lines.
- [x] Readability layer:
  - squadron tooltips: type, ships left / initial, fleet, side, role, life per ship;
  - a per-turn summary: damage dealt and ships lost by each side;
  - a legend of side and role colours.
- `fleet_moved` events now carry the translation and the squadrons moved, and the run response includes the grid geometry and ship life.
- Verified in the browser pane with a local harness loading the real modules, compiled CSS and a real battle. The pane cannot open `https://kalaxia.local` because of its self-signed certificate.
- Known limits:
  - a squadron moving onto another fleet's area is shown on that area, while its fleet's tint stays on its own area;
  - shots between adjacent squadrons are short and can be hard to see at the battlefield zoom;
  - no fleet ever moved in the test battles, because fleets deploy in contact, so the `fleet_moved` animation is only covered by its unit test.

**Movement fixes after the maintainer's multi-fleet test** (2026-10-05):

- **Symptom:** a fleet that had beaten its opponent waited for its allies to finish their fight instead of joining it, although free fronts existed.
- **Causes:**
  - Being next to an enemy fleet counted as "in contact", even when every enemy squadron faced another front, out of reach of the fleet's line squadrons, which cannot leave their area (D4).
  - Squadrons moved greedily toward their target and got stuck against the edge of their area.
  - Destroyed fleets kept holding their area, so the free front was not free.
- **New rules:**
  - A fleet is engaged when an enemy squadron is within its squadrons' range from the edge of its area. Otherwise it searches (breadth-first, around the other fleets) for the free area closest to the enemy squadrons and moves one area toward it each turn (`MoveFleets`).
  - Squadrons explore the cells they may enter and head for the closest cell in range of their preferred target, else in range of any enemy, else as close as possible to their target (`MoveSquadrons`). Ties are broken at random from the turn seed.
  - Destroyed fleets free their area. A fleet destroyed on its arrival turn is now reported in the event log.
- **Viewer navigation:** the battlefield can be dragged to move around and zoomed with the mouse wheel around the cursor. A click right after a drag does not zoom on a fleet.

**Second round of fixes** (2026-10-05):

- **D4 amended: every squadron may enter another fleet's area.** Before, the heavy ships of a fleet arriving behind an engaged enemy stayed out of reach. A fleet still moves its whole area toward the closest free front when none of its squadrons can reach an enemy from its area, which keeps the formation together and brings slow ships forward.
- **Formations are kept as composed at deployment.** Fleets arriving on the same turn are all placed first, then oriented toward each other and deployed. Before, the first one faced the battlefield centre and turned around within the turn.
- **The viewer shows the formation as composed:**
  - a "Déploiement" step 0 shows the fleets present from the start, before any movement;
  - on the arrival turn of a reinforcement, its squadrons appear on their formation cells before moving.

**Actions per turn derived from the real turn duration** (2026-10-05, maintainer's real-time test):

- **Goal:** during real-time playback (one turn = `BATTLE_TURN_DURATION`, 10 s), animations must be continuous. The playback speed chosen in the viewer does not change the rules.
- **Rule:** a turn holds `turn_duration / action_duration` actions, i.e. 10 actions with `ares.battle.action_duration: 1.0`. One action is one squadron firing.
  - Actions are handed out in turn to the fleets able to fire. Within a fleet, the squadron with the highest priority (initiative + accumulated initiative) acts. Squadrons left out accumulate initiative (R9).
  - This replaces the cap of 2 firing squadrons per fleet. With a fixed budget per turn, battles with more fleets last longer (D1), and a reinforcement still gives its side more actions (G2).
- **Viewer:** the animation of a turn fills the whole turn (95%), arrivals and movements take at most 1.5 s each, and the shots are spread over the rest. Checked in real time: 10 shots, one every 0.75 s, each line lasting 1.5 s.
- **Retuning:** `ares.battle.durability_factor` goes from 3 to 5, to keep balanced battles at the durations validated before. Median durations over 20 battles:

| Battle | Durability 5, 10 actions/turn |
|---|---|
| Small (~100 PEV per side) | 1.5 min |
| Small mixed (~200 PEV) | 2.7 min |
| Medium, unbalanced (~600 PEV) | 3.5 min |
| Big (~1,500 PEV) | 4.7 min |
| Medium mirror, 1 v 1 | 10.3 min |
| Medium mirror, 2 v 2 | 12.7 min |
| Medium mirror 1 v 1, B reinforced at turn 30 | 6.3 min, B wins 100% |

**Faster pace** (2026-10-05, the maintainer found the animation too slow):

- `ares.battle.action_duration` goes from 1.0 to **0.4 s**, i.e. **25 actions per 10-second turn**. `ares.battle.durability_factor` goes from 5 to **6**.
- Viewer: movements take at most 0.8 s, arrivals at most 1 s, and each shot line at most 0.7 s.
- Checked in real time: 15 actions in a turn, one every 0.55 s, the first one 0.8 s into the turn.
- ⚠️ **Trade-off for D1:** with 25 actions, the budget only binds in very big battles, because small and medium ones rarely have 25 squadrons in range. Big battles no longer last longer than medium ones, and 2 v 2 lasts about as long as 1 v 1. The fleet-size maluses of Phase 5 are expected to restore this scaling.

| Battle (durability 6, 25 actions/turn) | Median duration |
|---|---|
| Small (~100 PEV per side) | 1.5 min |
| Small mixed (~200 PEV) | 2.5 min |
| Medium, unbalanced (~600 PEV) | 3.2 min |
| Big (~1,500 PEV) | 3.2 min |
| Medium mirror, 1 v 1 | 11.5 min |
| Medium mirror, 2 v 2 | 10.3 min |
| Medium mirror 1 v 1, B reinforced at turn 30 | 6.7 min, B wins 100% |

### Phase 4: rich report and replay

- [ ] A post-battle report built from the event log (Q16).
- [ ] The same viewer used for replays.

### Phase 5: advanced combat rules (simulator first)

- [ ] Morale and the two kinds of retreat (Q7, R5).
- [ ] Weapon patterns and range, squadron auto-positioning (Q11, Q12).
- [ ] Fleet-size maluses, measured in PEV and mitigated by officer level (Q13, D2).
- [ ] Engagement delay when a fleet arrives outnumbered (D1).
- [ ] Battle highlights and a skimmable replay (D5).
- [ ] Officer traits (Q13).

### Phase 6: real-time engine (still off the live game)

- [ ] Persist `Battle` (Doctrine) plus the turn log.
- [ ] `BattleTurnMessage` ticker driven by `BATTLE_TURN_DURATION`, a lock per battle, Mercure publishing restricted to participants.
- [ ] A live viewer in the simulator: a "real-time" mode with timed reinforcements.

### Phase 7: integration in the game (requires approval)

- [ ] Fleet screen on the planet: the player composes the fleet with the formation editor (D9), using the ships of the planet's hangar. Composition is locked once the fleet leaves.
- [ ] NPC (rebel) fleets get their formation from `BuildFleetFormation`.
- [ ] Hook travel arrival into battle creation and joining (Q3, Q9, R7), including NPCs (Q5). Remove `FightManager`, `LiveReport` and the legacy `Report`.
- [ ] Remove "squadrons = officer level" (Q13); new XP rules (Q14).
- [ ] Data migration: existing mixed squadrons (`ship0`…`ship11`) are split into single-type squadrons, and lines become formations.
- [ ] Battle access for participants and the report and replay pages (Q15, Q17).
- [ ] Battle queueing at a planet (D7).
- [ ] Optional: live access for members of participant factions (D8).
- [ ] Balancing pass using the Phase 1 harness, including duration targets (D1). Update `docs/gameplay.md`.

## 9. Decision log

| Date | Decision | Reason |
|---|---|---|
| 2026-10-04 | Build the new combat system first as a portal simulator, then integrate it into the game. | Limits the impact on the live game while engine and visualisation mature. |
| 2026-10-04 | Target: multi-fleet, real-time turn-based battles on a hex grid with two zoom levels, and reinforcements during the battle. | Addresses P1, P2, P4, P5. |
| 2026-10-04 | One battle per planet, except hostile third parties that start a separate battle. Sides are computed from faction relations, for N sides (Q1–Q3). | Multi-player cooperation (G2). |
| 2026-10-04 | Any fleet arriving where an enemy fights joins the battle, whatever its order. NPCs follow the same rules. No planetary defence for now (Q3–Q5). | Consistency and simplicity. |
| 2026-10-04 | Turn length is a dedicated env var, default 10 s (Q6). | Independent of game speed. |
| 2026-10-04 | No player action during battles; retreat is automatic through morale (later) (Q7, Q8). | Connected players must not have an advantage. |
| 2026-10-04 | 5×5 battlefield; placement by arrival order; fleets move until in contact; reinforcements join at the next turn, placed in contact (Q9, Q10). | |
| 2026-10-04 | Formation is set on the planet and locked once the fleet leaves; squadrons reposition automatically in combat; weapons will have range patterns (Q11, Q12). | Strategy happens before the battle (G5). |
| 2026-10-04 | Fleet size is no longer capped by officer level. Size maluses are mitigated by officer level, plus traits. XP comes from damage dealt and taken (Q13, Q14). | Removes NPC farming (P3). |
| 2026-10-04 | Battles can be replayed. The report follows Q16. Only participants can watch live (Q15–Q17). | Reward and readability (P4, P5). |
| 2026-10-04 | Hostility comes from faction relations; NPCs are enemies of everyone (R1). | |
| 2026-10-04 | A squadron holds one ship type, and its maximum size depends on that type. The fleet grid is a hexagon of hexes, larger in battle than in composition. Squadrons may move onto other fleets' grids. The top row is the front line, and a fleet can fight on several fronts (R2). | Readability (P5) and richer positioning. |
| 2026-10-04 | Initiative is per squadron and firing is sequential within a round. The number of squadrons firing per round is to be limited, method still open (R3, R9). | |
| 2026-10-04 | Movement depends on ship type, the first squadron to reach a cell holds it, and targeting depends on officer traits and ship stats (R4). | |
| 2026-10-04 | Fleet weight is measured by squadron count, probably also PEV. Morale is calibrated later. Surviving fleets resume their mission. A fleet is in at most one battle (R5–R8). | |
| 2026-10-04 | Battle duration scales with the size of the forces; an outnumbered arriving fleet waits before engaging (D1). | Really solves P1 (cooperation). |
| 2026-10-04 | Fleet weight is measured in PEV (D2). | Squadron count would penalise diverse fleets. |
| 2026-10-04 | Combat stays idle; the officer builds the formation from the ships provided. Clear roles per ship type; only fighters and bombers move onto other fleets' grids (D3, D4). | Asylamba spirit; readability. |
| 2026-10-04 | XP from damage dealt and taken, weighted by the importance formula (D6). | Ship losses already deter farming through defeat. |
| 2026-10-04 | A second battle at the same planet waits for the first to end (D7). Faction spectators to be tested (D8). Interest curve to be designed later (D5). | Simplicity; social value. |
| 2026-10-04 | Method: validate the fun with the balance harness before investing in visualisation. | Prototype the riskiest part first (Schell). |
| 2026-10-04 | Bombers are a new ship type. While the system is simulator-only, battle ship types live in `battle_ship_types.yaml`, separate from `ShipCategory`, so the live game is untouched. | Adding a 13th ShipCategory would break the 12-column squadrons and many loops. |
| 2026-10-04 | Big ships stay alone in their squadron. Composition grid radius 2 (19 squadrons max) for now. Range counted in fine cells. | Intrinsic value of big ships; test small fleets first. |
| 2026-10-04 | Battle durations are tuned with two levers: a ship durability factor (3) and a cap on squadrons firing per round **per fleet** (2), with accumulated initiative. Provisional values. | Durations must grow with fleet size (D1); per fleet keeps reinforcements meaningful (G2). |
| 2026-10-04 | Officer XP = (damage dealt + taken) ÷ durability × 2 × importance (relative strength, capped at 3). Bonuses apply per fleet: attacker's for attack, target's for defense and dodge; cruisers included; faction defense bonus applied once. | D6; fixes three legacy combat bugs. |
| 2026-10-04 | Players compose their formations themselves (D9, community poll). `BuildFleetFormation` is kept as a suggestion and for NPC fleets. | Player preference; makes composition choices readable. |
| 2026-10-05 | Art direction: the faction colour is the dominant colour and identifies ownership; squadrons, shots, fleet areas and labels use faction colours. Ship roles lose their colour code; ship types will get pictograms. | Visual feeling of "forces of a faction"; recorded in AGENTS.md. |
| 2026-10-05 | A fleet is engaged when enemy squadrons are within reach of its area, not merely when an enemy fleet is adjacent; otherwise it heads for the closest free front. Squadrons use pathfinding to reach firing positions. Destroyed fleets free their area. | Victorious fleets now help their allies (maintainer's multi-fleet test). |
| 2026-10-05 | D4 amended: every squadron may enter another fleet's area. Fleets arriving on the same turn are oriented toward each other before deploying; the replay starts with a deployment step showing the formations as composed. | Maintainer's multi-fleet tests. |
| 2026-10-05 | A turn holds turn_duration / action_duration actions (10 by default), shared in turn among the fleets, replacing the per-fleet firing cap. Durability retuned to 5. | Continuous animation in real-time playback; durations kept. |
| 2026-10-05 | Faster pace: action duration 0.4 s (25 actions per turn), durability 6, shorter animations. Battle durations no longer grow with fleet size; to be restored by the Phase 5 size maluses. | The animation felt slow. |
