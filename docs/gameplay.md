# Kalaxia: gameplay reference

This document describes the game rules **as they are implemented in the code** (October 2026). It was produced by reading the source and has not been checked by play-testing. Every section cites the files that hold the rules, so check those files before changing anything.

- **⚠️** marks suspected bugs or inconsistencies. They are all collected in [§14 Known issues](#14-known-issues).
- **(legacy)** marks code that is dead, disabled, or a leftover from Asylamba.

UI text and the names of in-game concepts are in French. The French term is given in parentheses where it helps.

---

## Table of contents

1. [Game overview](#1-game-overview)
2. [Time and scheduling](#2-time-and-scheduling)
3. [Player lifecycle](#3-player-lifecycle)
4. [Galaxy](#4-galaxy)
5. [Planets](#5-planets)
6. [Buildings](#6-buildings)
7. [Economy](#7-economy)
8. [Research and technologies](#8-research-and-technologies)
9. [Military: ships, commanders, fleets](#9-military-ships-commanders-fleets)
10. [Combat](#10-combat)
11. [Missions and travel](#11-missions-and-travel)
12. [Espionage](#12-espionage)
13. [Factions, politics, diplomacy, victory](#13-factions-politics-diplomacy-victory)
14. [Known issues](#14-known-issues)

---

## 1. Game overview

Kalaxia is a persistent, multiplayer, browser-based space strategy game. It is a fork of *Asylamba*.

- Each player joins one of **5 factions**: Kalankar, Valkar, Falkirr, Adranite and Malgar.
- A player starts with one planet in a sector their faction owns. From there they:
  - develop the planet with **buildings**, which cost resources;
  - earn **credits**, mostly from population taxes and trade routes;
  - research **technologies**, which unlock things and give bonuses;
  - recruit **commanders**, who lead fleets of up to 17 squadrons.
- Players expand by **colonising** neutral planets and **conquering** enemy ones. They can also **loot**, **spy**, **trade** on a market and build **commercial routes**.
- Each faction has an internal political life: a **senate**, a **government** of ministers, **laws**, and **elections**, **divine designation** or **coups (putsch)** depending on its regime.
- Factions compete for **territory**, i.e. control of sectors. Every night a ranking awards **victory points**, and the first faction to reach **2000 points** wins the server (but see §13.7).

The game runs on **cycles** called *relèves*: 1 hour in standard mode and 10 minutes in fast mode. Production, taxes, wages and commander training all happen once per cycle. Everything with a duration (building, research, travel, elections) is a **delayed Messenger message** that resolves when it is due.

### Module map

| Domain | Module |
|---|---|
| War | Ares |
| Espionage | Artemis |
| Planet economy and trade | Athena |
| Rankings | Atlas |
| Factions and politics | Demeter |
| Map | Galaxy |
| Setup and scheduling | Hephaistos |
| Communication | Hermes |
| Accounts and simulator | Portal |
| Research | Promethee |
| Travel time | Travel |
| Players, credits and bonuses | Zeus |

---

## 2. Time and scheduling

### 2.1 Server clock

Configured in `.env` and `config/services.yaml`:

- `SERVER_START_TIME`: the server start date. Several durations are counted from it (ranking start, first elections).
- `SERVER_SEGMENT_SHIFT`: an offset added to the displayed game date.
- `SERVER_TIME_MODE`: `standard` or `fast`, read into `App\Modules\Shared\Domain\Server\TimeMode`.

**Game calendar** (`Shared/Domain/Service/GameTimeConverter.php`; the older `src/Classes/Library/Chronos.php` is legacy):

| Unit | Length |
|---|---|
| Relève (REL), one cycle | 3600 s (standard) or 600 s (fast) |
| Segment (SEG) | 24 relèves |
| Strate (STR) | 2400 relèves |

The displayed date is `SEG<n> REL<m>`. It is computed as `floor((now − start) / cycle) + SEGMENT_SHIFT × 24` relèves.

### 2.2 Recurring ticks

Defined in `Hephaistos/Infrastructure/Schedule/GameScheduleProvider.php`, schedule `game`.

| Tick | Standard | Fast | Messages |
|---|---|---|---|
| Cycle | `0 * * * *` | `*/10 * * * *` | `PlayersCreditsUpdateMessage`, `CommandersSchoolExperienceMessage`, `PlanetsUpdateMessage` |
| Nightly | `0 3 * * *` | `0 * * * *` | `DailyRoutineMessage`, `RankingCreationMessage` |

- **Catch-up:** if workers were down, handlers process the missed cycles (`Shared/Application/Service/CountMissingSystemUpdates.php`).
  - Planets: in batches of 10.
  - Credits: in batches of 5. Anything beyond 24 missed cycles is merged into one report.
- **Rescheduling:** `app:hephaistos:schedule-actions` re-dispatches every pending timed action (queues, travels, political events). It covers every service tagged `app.scheduler` (`SchedulerInterface`) and is useful after a restart.

### 2.3 Daily routine

`Hephaistos/Handler/DailyRoutineHandler.php`:

1. Deletes read notifications older than 3 days and unread ones older than 10 days.
2. Deletes player financial reports older than 60 days.
3. Handles inactive players:
   - after 360 h (15 days) without logging in, Active players become **Inactive**;
   - the 600 h "kill" is **commented out** (disabled).

Logging in always sets the player back to Active.

### 2.4 What fast mode changes

| Item | Standard | Fast |
|---|---|---|
| Cycle length | 3600 s | 600 s |
| Building time | table value | 10 s × level |
| Technology time | formula | 10 s × level |
| Ship build time | config `time` × qty | (id² + 5) × qty s |
| Fleet travel | distance-based | 300 s fixed |
| Commercial shipping | distance-based | 120 s fixed |
| Recycler travel | distance-based | 600 s fixed |
| Recycling cycle | 2 × travel + 8 h | 2 × travel + 1 h |
| Planet-leave cooldown | 12 h | 2 h |
| Law vote | 24 h | 1 h |
| Mandate / campaign / election / mandate end / putsch | faction config / 4 d / 2 d / 1 h / 7 h | 420 s / 120 s / 120 s / 30 s / 1200 s |

---

## 3. Player lifecycle

### 3.1 Registration

Controllers are in `Zeus/Infrastructure/Controller/Registration/`. The player needs a Portal `User` account.

1. **Choose a faction.** Factions are listed by ascending number of active players. Closed factions (`Color::isClosed`) are refused.
2. **Create a character.**
   - Pseudo: unique, 2–15 characters, letters and digits only, at least one letter (`Zeus/Helper/CheckName.php`).
   - Avatar: chosen from the faction's set.
3. **Choose a place.**
   - The sector must currently be owned by the player's faction.
   - The starting planet is picked at random among the 30 lowest-population free planets of that sector.
4. `PlayerFactory::create` builds the player.
   - A sponsor (godfather) receives +1000 credits right away, and +1,000,000 when the new player reaches level 3.

### 3.2 Starting state

Defined in `Zeus/Application/Factory/PlayerFactory.php`.

| | Normal | High mode (`HIGH_LEVEL_MODE`, testing) |
|---|---|---|
| Credits | 5,000 | 10,000,000 |
| XP / level | 630 / 1 | 18,000 / 5 |
| Resources on planet | 1,000 | 3,000,000 |
| Buildings | Generator, Refinery and Storage at 1; others at 0 | Main buildings at 35, Dock 2 / Commercial platform / Spatioport at 10, Recycling at 15 |
| Research levels | all 0 | all 15 |
| Technologies | none | all unlocks except the two cruisers; Base quantity 4 |
| Ships | none | a starter fleet |
| University investment | 1,000 per cycle, split 25/25/25/25 | same |

The starting planet always gets "spawn" stats (`PlaceManager::turnAsSpawnPlace`):

- `coefResources = 60`
- `coefHistory = 20`
- `population = 50`
- `iSchool = 500`
- `iAntiSpy = 500`

### 3.3 Levels and experience

Defined in `Zeus/Manager/PlayerManager::increaseExperience`.

- Level up when `experience ≥ 1250 × 2^(level−1)`. Experience is cumulative, and at most one level is gained per call.
- **Sources of experience:**
  - completed buildings (the `points` column);
  - ships built (`points` × quantity);
  - finished technologies (points × level);
  - battles (commander experience ÷ 100);
  - market sales (`1 + price / 15000`);
  - accepted trade routes (10% of the route income);
  - tutorial steps.
- **Protection by level:**
  - Level 1 players cannot be looted.
  - Players at level 3 or below cannot be conquered.

### 3.4 Statuses

There are two separate enums:

- **`PlayerStatement`** (`Zeus/Domain/Enum/PlayerStatement.php`), the account state:

  | Value | State | Notes |
  |---|---|---|
  | 1 | Active | |
  | 2 | Inactive | |
  | 3 | Holiday | never set (legacy) |
  | 4 | Banned | never set (legacy) |
  | 5 | Deleted | |
  | 6 | Dead | |

  - `canAccess` is true for Active, Inactive and Holiday.
  - System accounts are Dead: the rebel player (id 1), "Jean-Mi" (id 2) and one account per faction.
- **`PlayerStatus`** (`Zeus/Domain/Enum/PlayerStatus.php`), the political role inside the faction:

  | Value | Role |
  |---|---|
  | 1 | Standard |
  | 2 | Parliament (senator) |
  | 3 | Treasurer |
  | 4 | Warlord |
  | 5 | Minister |
  | 6 | Chief |

  Each faction gives these roles its own titles; see §13.2.

### 3.5 Losing every planet

Handled by `Athena/Application/EventListener/PlayerLossListener.php`, which calls `PlayerManager::reborn`.

- The player respawns on a random free planet in one of their faction's sectors:
  - the planet is named "Colonie", with 1,000 resources and spawn stats;
  - university investment is reset to 1,000;
  - `factionPoint` is reset to 0;
  - the *Base quantity* technology is reset to 0.
- If no spawn planet is available, the player is **killed**: statement Dead, and the name becomes `† Name I`.

### 3.6 Tutorial

Defined in `Zeus/Resource/TutorialResource.php`.

- There are 28 steps, numbered 1–29 (step 14 does not exist).
- Each step is validated by a `TutorialEvent`.
- Each step rewards XP, credits, resources and/or ships. Examples: build a generator, train an officer, spy a planet, loot a rebel planet, post on the faction forum.

---

## 4. Galaxy

### 4.1 Generation

Run by `app:hephaistos:populate-database` (`Hephaistos/Cli/PopulateDatabase.php`), with generation done asynchronously by `Galaxy/Helper/GalaxyGenerator.php`.

1. **Factions:** creates the rebel faction (id 0) and the factions `[1, 2, 3, 4, 7]`. All relations start at NEUTRAL.
2. **System players:** creates the rebel player, Jean-Mi, and one Dead account per faction.
3. **Market:** sets the initial market rates (resource 1.26, commander 12, ship 1.875) and 5% import and export taxes between every pair of factions.
4. **Mandates:** creates the first mandates for factions that are not royalistic.
5. **Galaxy:** generates sectors, then systems, then places.

Configuration lives in `Galaxy/Galaxy/GalaxyConfigurationV6.php` (`GalaxyConfigurationTestEnvironment` in tests):

- The map is 250×250 and has 50 polygonal sectors.
- **Sector danger:** 10 sectors at danger 1, 16 at 2, 12 at 3, 7 at 4, 5 at 5.
- **Sector points** (used for faction victory), 86 in total:
  - 33 sectors worth 1, 9 worth 2, 5 worth 3;
  - 2 worth 5 (sectors 42 and 46);
  - 1 worth 10 (sector 50, danger 5).
- **Starting sectors** ("prime" sectors, all danger 1):

  | Faction | Sectors |
  |---|---|
  | Kalankar | 8, 11 |
  | Valkar | 10, 12 |
  | Falkirr | 5, 9 |
  | Adranite | 7, 13 |
  | Malgar | 6, 16 |

- **Systems per sector:** `round(25 × (1 + danger / 10))`, which gives 28 to 38.
- **System types and their place mix:**

  | System | Share | Places | Planet | Gas planet | Ruin | Gas pocket | Asteroid | Empty |
  |---|---|---|---|---|---|---|---|---|
  | Graveyard | 3% | 2–6 | 0 | 0 | 85 | 10 | 0 | 5 |
  | Nebula | 8% | 2–8 | 0 | 0 | 5 | 90 | 0 | 5 |
  | Blue giant | 9% | 8–12 | 60 | 20 | 2 | 0 | 15 | 3 |
  | Yellow dwarf | 25% | 6–10 | 65 | 15 | 3 | 0 | 15 | 2 |
  | Red dwarf | 55% | 3–6 | 75 | 10 | 3 | 0 | 10 | 2 |

### 4.2 System and sector ownership

**Base-type scores** (`galaxy.scores`): colony 1, commercial 3, military 5, capital 10.

**System owner** (`Galaxy/EventListener/SystemListener.php`): the faction with the highest score among the planets in that system.

**Sector owner** (`Galaxy/Manager/SectorOwnershipCalculator.php`):

- A faction's score in a sector is the sum of its planets' base scores **plus 2 for each system it controls** there.
- Taking a sector requires **at least 10 points**, and a challenger needs a strictly higher score than the current owner.
- If the owner drops below 10, a normal sector becomes neutral, but a prime sector stays owned. A prime sector loses its prime status once it is taken.
- Every change of owner dispatches `SectorOwnerChangeEvent`, which produces galactic news.

**Owning a sector gives:**

- **Sector tax:** the owner receives `sector.tax`% of the population tax of *every* planet in the sector, including planets of enemy players. The default is 5%, and a law can set it to 2–15%.
- **No range limit:** fleets can target places in own and allied sectors whatever the distance.
- **Victory points:** sector points feed the territorial ranking.
- **Spawning:** new players and reborn players can only spawn in sectors their faction owns.

The parameter `galaxy.limit_conquest_sector` (4) is unused.

---

## 5. Planets

### 5.1 Place types

`Galaxy/Domain/Enum/PlaceType.php`; Doctrine joined inheritance on `galaxy__places`.

| Type | Habitable | Notes |
|---|---|---|
| Planet | yes | Can be colonised and has buildings. |
| Gas planet, Ruin, Gas pocket, Asteroid | no | `UninhabitedPlace` with a resource stock of 2M–20M. Can be recycled (§7.6). |
| Empty | no | What a recycled place becomes once its stock runs out. |

Recycling coefficients (resources / credits / history):

| Type | Resources | Credits | History |
|---|---|---|---|
| Gas planet | 38 | 52 | 10 |
| Ruin | 5 | 0 | 95 |
| Gas pocket | 0 | 96 | 4 |
| Asteroid | 98 | 0 | 2 |

### 5.2 Planet stats

`Galaxy/Domain/Entity/Planet.php`, generated by `Galaxy/Application/Handler/PlaceGenerationHandler.php`.

- **Generation:** about 100 points are split at random between population, history and resources.
- **`population`** (up to about 250) is **static**: it never grows. It drives the population tax.
- **`coefResources`** multiplies refinery production.
- **`coefHistory`** speeds up research on that planet:
  - 0 if below 10;
  - 40% if 100 or more;
  - otherwise `ceil(0.004·c² − 0.01·c + 0.7)`. Example: 20 gives 3%.
- **`danger` / `maxDanger`** set the strength of the rebel defender on an unowned planet. The range depends on sector danger (danger 1 gives 0–10, danger 5 gives 12–100). After a fight, danger regrows by +2 per cycle.
- **`planetType`** (desert, ice, ocean…) is **cosmetic only**.
- **Unowned planets** accumulate resources: `+2 × population` per cycle, up to `ceil(pop / 50) × 600 × (maxDanger + 1)`.
- **Planet `points`:** the sum of `resourcePrice / 1000` over every built building level.
- **Investments per planet:**
  - `iSchool`: up to 50,000 credits per cycle; trains commanders.
  - `iAntiSpy`: up to 100,000 credits per cycle.

### 5.3 Base types

Data in `Galaxy/Resource/PlaceResource.php`; switching handled by `Athena/Infrastructure/Controller/Planet/ChangePlanetType.php`.

| Type | Name | Switch cost | Requirement | Tax × | School size | Score |
|---|---|---|---|---|---|---|
| 0 | Colonie | – | – | 1 | 3 | 1 |
| 1 | Centre Industriel (commercial) | 250,000 | Generator 20 | 1.25 | 3 | 3 |
| 2 | Base Militaire | 250,000 | Generator 20 | 0.5 | 8 | 5 |
| 3 | Capitale | 1,000,000 | Generator 35 | 1.25 | 8 | 10 |

Rules:

- The base type caps building levels (§6.2) and limits which technology columns can be researched there (§8.3).
- A player can **create** only one capital, but conquered capitals can stack.
- A capital cannot change type.
- Switching between commercial and military:
  - lowers any building above the new type's max level;
  - deletes the building queue without refund;
  - switching to commercial also deletes recycling missions.
  - ⚠️ This is expected to crash (§14).

### 5.4 Number of planets

- Maximum bases = **level of the *Administration étendue* technology (Base quantity, max level 14) + 1**, so 15 planets at most.
- Colonisation missions currently in flight count toward the limit.

### 5.5 Abandoning a planet

Handled by `Athena/.../Planet/LeavePlanet.php`.

- You cannot abandon your last planet, or a planet whose fleets are moving.
- A planet can only be abandoned once a cooldown since its acquisition has passed: 12 h in standard mode, 2 h in fast mode.
- The building queue is lost.
- A capital is first downgraded at random to commercial or military.
- Then `PlanetManager::changeOwner(planet, null)` runs.

**On any change of owner** (`Galaxy/Manager/PlanetManager::changeOwner`):

- Market offers move to the new owner.
- Commercial routes, technology queues and recycling missions are deleted.
- Investments are reset to 0.
- Commanders on the base:
  - INSCHOOL, ONSALE and RESERVE commanders go to the new owner;
  - MOVING commanders become RETIRED;
  - all others die.

---

## 6. Buildings

Data in `Galaxy/Resource/PlanetResource.php`, read through `Galaxy/Helper/PlanetHelper::getBuildingInfo` and `Athena/Domain/Service/Base/Building/*`.

### 6.1 Building list

| ID | Building | Role | Required technology | Min. generator |
|---|---|---|---|---|
| 0 | Générateur | Building queue slots; caps other buildings | – | – |
| 1 | Raffinerie | Resource production per cycle | – | – |
| 2 | Chantier Alpha (Dock 1) | Builds ships 0–5; hangar capacity | – | – |
| 3 | Chantier de Ligne (Dock 2) | Builds ships 6–11; hangar capacity | Grue magnétique (1) | 20 |
| 4 | Colonne d'Assemblage (Dock 3) | Motherships (**disabled**, cannot be built) | Champ magnétique (2, inactive) | 30 |
| 5 | Technosphère | Technology queue slots and required level | – | – |
| 6 | Plateforme Commerciale | Commercial ships (market and gift transport) | Long courrier (0) | 10 |
| 7 | Stockage | Resource storage | – | – |
| 8 | Centre de Recyclage | Recyclers | Recycleur autonome (3) | 10 |
| 9 | Spatioport | Max commercial routes | Vaisseaux de transport (4) | 20 |

**Level rule:** a building's level cannot exceed `generator level − minimum generator`. The generator level counted here includes generator levels already queued.

### 6.2 Max level by base type

Order: colony / commercial / military / capital.

| Building | Max level |
|---|---|
| Générateur | 30 / 40 / 40 / 40 |
| Raffinerie | 30 / 40 / 30 / 40 |
| Chantier Alpha | 30 / 30 / 40 / 40 |
| Chantier de Ligne | 0 / 10 / 20 / 20 |
| Technosphère | 30 / 40 / 40 / 40 |
| Plateforme Commerciale | 20 / 30 / 20 / 30 |
| Stockage | 30 / 40 / 40 / 40 |
| Centre de Recyclage | 20 / 20 / 30 / 30 |
| Spatioport | 0 / 20 / 10 / 20 |

### 6.3 Cost, time and queue

- **Cost:** only resources, the `resourcePrice` of the target level. They are debited when the building is ordered.
- **Time:** the table value (standard mode) minus the `GENERATOR_SPEED` bonus %.
- **On completion:**
  - the level goes up by 1;
  - planet points are recomputed;
  - the player gains the level's `points` as XP.
- **Queue:**
  - entries run one after another;
  - the number of slots comes from the generator level: 2 (levels 1–5), 3 (6–15), 4 (16–25), 5 (26–35), 6 (36–40).
- **Cancelling** removes the last queued level of that building and refunds 50% of its resources.

### 6.4 Selected values

| Level | Générateur price / time | Raffinerie coef. | Stockage capacity | Chantier Alpha hangar (PEV) | Technosphère price |
|---|---|---|---|---|---|
| 1 | 100 / 20 s | 8 | 3,700 | 56 | 100 |
| 10 | 1,600 / 413 s | 24 | 12,800 | 104 | 2,110 |
| 20 | 25,000 / 2.7 h | 81.6 | 79,400 | 308 | 35,000 |
| 30 | 152,000 / 11 h | 173.5 | 492,400 | 638 | 217,000 |
| 40 | 616,000 / 44.6 h | 267.3 | 6,589,900 | 1,071 | 705,000 |

Other buildings:

- **Chantier de Ligne:** hangar from 100 PEV (level 1) to 1,265 PEV (level 20). Level 20 costs 849,000 and takes 170 h.
- **Plateforme Commerciale:** 5 commercial ships at level 1, 800 at level 10, 3,000 at level 20, 3,700 at level 30.
- **Centre de Recyclage:** 1 recycler at level 1, 46 at level 10, 191 at level 20, 436 at level 30.
- **Spatioport:** 1 route at level 1, 4 at level 10, 7 at level 15, 10 at level 20.

**Ships unlocked by dock level** (`releasedShip`):

- **Chantier Alpha:** at levels 1, 9, 17, 25, 33 and 40 it unlocks ships 0, 1, 2, 3, 4 and 5 respectively.
- **Chantier de Ligne:** at levels 1, 4, 8, 12, 16 and 20 it unlocks ships 6, 7, 8, 9, 10 and 11 respectively.

**Storage cap:** storage capacity + `REFINERY_STORAGE`%. Incoming flows (production, deliveries, refunds) may overfill it by up to **25,000** (`Planet::EXTRA_STOCK`).

---

## 7. Economy

There are only **two currencies**:

- **credits**, held by the player (`Player::$credit`);
- **resources**, one generic stock per planet (`Planet::$resourcesStorage`).

Ships are stored per planet in `Planet::$shipStorage`, the hangar.

### 7.1 Resource production (each cycle)

Computed in `Galaxy/Application/Handler/PlanetUpdateHandler.php`:

```
produced = refiningCoefficient(levelRefinery) × coefResources × (1 + REFINERY_REFINING% / 100)
```

Example: a spawn planet with refinery level 1 produces 8 × 60 = 480 resources per cycle.

### 7.2 Hourly credit update

`Zeus/Application/Handler/PlayerCreditUpdateHandler.php` creates one `PlayerFinancialReport` per cycle and per **Active** player. In order, it processes:

1. **For each planet:**
   - population tax;
   - commercial route income;
   - sector tax paid to the faction that owns the sector;
   - anti-spy investment.
2. **Flows since the last report:** market sales and purchases, route construction costs, credit transfers, technology costs, and credits from recycling.
3. **University investment** (§8.1).
4. **Commander wages:** `200 × level` per cycle for AFFECTED and MOVING commanders.
5. **Ship upkeep:** ships on sale, then fleets, then hangars.

**Population tax** (`Athena/Application/Handler/Tax/PopulationTaxHandler.php`):

```
tax = (180 × population + 1500) × baseTypeTaxCoef × (1 + POPULATION_TAX% / 100)
```

Example: a spawn colony with population 50 earns 10,500 credits per cycle.

**When the player cannot pay:**

- **Commander wage:** the commander's ships go back to the hangar, and the commander deserts to the rebel player as ONSALE. It can be bought back on the market.
- **Fleet upkeep:** the commander deserts **with its ships** (`UnpaidFleetEvent`).
- **Hangar upkeep:** ships are destroyed, starting with the most expensive type, until the rest can be paid for (`UnmaintainedHangarShipsEvent`).
- **University:** the investment is reduced to the current wallet, or to 0, and the player gets a "Caisses vides" notification.

**Ship upkeep** is the `cost` field of each ship category (§9.1), charged per cycle:

- full price for ships in squadrons;
- × 0.8 (`game.ship_cost_reduction`) for ships in the hangar or on sale.

### 7.3 Commercial routes

Code: `Athena/Model/CommercialRoute.php`, `Athena/Application/Handler/CommercialRoute/*`, `Athena/.../Trade/Route/*`.

- **Requirements:**
  - both planets have a Spatioport;
  - the player has a free route slot;
  - there is no existing route between the two planets;
  - neither faction considers the other an ENEMY.
- **Distance:** `max(1, floor(Euclidean distance between the two systems))`.
- **Price:** `distance × 8000`, paid by **both** the proposer (when proposing) and the acceptor (when accepting). Falkirr gets −3%.
- **Income per cycle**, paid in full to **each** endpoint planet:

  ```
  17 × √(min(distance, 100) × 300)
     × 1.1 if the planets are in different sectors
     × 1.5 if the players are in different factions
     × (1 + COMMERCIAL_INCOME% / 100)
  ```

  The maximum is about 4,858 per cycle before bonuses.
- **When accepted,** both players gain XP equal to 10% of the income.
- **Refunds:** cancelling before acceptance refunds 80%; a refusal refunds 100%; deleting an active route refunds nothing.
- **War:** routes between enemy factions are frozen (STANDBY).

### 7.4 Market

Code: `Athena/Model/Transaction.php`, `Athena/.../Trade/Offer/*`, `TransactionManager`.

- **What can be sold:** resources, ships from the hangar, or a commander (who becomes ONSALE).
- **When an offer is posted,** the goods leave the seller immediately.
- **Accepting an offer** requires a Plateforme Commerciale.
- **Transport capacity** is counted in commercial ships:
  - 1 per 1,000 resources;
  - 1 per PEV of ships;
  - 1 per commander.
- **Price bounds** per unit:

  | Goods | Unit | Range (credits) |
  |---|---|---|
  | Resources | per resource | 0.2–100 |
  | Ships | per resource-equivalent | 1–100 |
  | Commanders | per XP | 1–100 |

- **Taxes:** the buyer pays `price + export tax + import tax`.
  - The export rate comes from the seller's sector faction toward the buyer's; the import rate from the buyer's sector faction toward the seller's.
  - Both default to 5% and are set by faction laws.
  - The seller receives exactly `price`.
- **Market rate** after each sale:

  ```
  new = (qty × thisRate + 50000 × 0.99 × currentRate) / (50000 + qty)
  ```

- **Cancelling an offer** costs 5% of its price.
- **Delivery** is done by commercial shipping:
  - travel takes 0.2 × the fleet travel time, and the convoy then takes the same time to come back;
  - a commander arrives in RESERVE.
- **Gifts:** resources and ships can be sent to any planet using the same shipping rules (`Trade/GiveResources.php`, `GiveShips.php`).

### 7.5 Credit transfers

- Player to player: `Financial/SendCreditsToPlayer.php`.
- Player to faction: `SendCreditsToFaction.php`.
- Faction to player, done by the Treasurer: `Demeter/.../Government/SendCredits.php`.

### 7.6 Recycling

Code: `Athena/Model/RecyclingMission.php`, `Athena/Handler/RecyclingMissionHandler.php`.

- **Targets:** gas planets, ruins, gas pockets and asteroids in neutral sectors or the player's own faction's sectors.
  - The mission stops if the sector changes hands or the place runs out.
- **Cycle length:** `2 × travel + 8 h`.
- **Per cycle:**
  - `points = min(stock, recyclers × 400)`, taken out of the place's stock;
  - this yields resources, credits (`points × creditCoef / 100 × 10`) and random ships (`points × history / 100 × 1.6` ship-points), each with ±5% randomness.
  - ⚠️ The resource yield formula is wrong (§14).
- **Recyclers:** extra recyclers can be added for the next cycle, and a mission can be cancelled; it stops at the end of the current cycle.

### 7.7 Bonuses (`PlayerBonus`)

Code: `Zeus/Manager/PlayerBonusManager.php`, `Zeus/Model/PlayerBonusId.php`.

A bonus is a percentage that the code reading it applies as `value × bonus / 100`. Bonuses come from three sources:

- **technologies:** the sum of the per-level bonuses;
- **faction bonuses** (§13.1);
- **laws in force** (§13.4).

| ID | Bonus | Effect |
|---|---|---|
| 0 | GENERATOR_SPEED | Building time |
| 1 | REFINERY_REFINING | Resource production |
| 2 | REFINERY_STORAGE | Storage capacity |
| 3, 4 | DOCK1_SPEED, DOCK2_SPEED | Ship build time |
| 5 | TECHNOSPHERE_SPEED | Research time |
| 6 | COMMERCIAL_INCOME | Route income |
| 9 | POPULATION_TAX | Population tax |
| 10 | COMMANDER_INVEST | School XP |
| 11 | UNI_INVEST | University efficiency |
| 13 | SHIP_SPEED | Fleet travel speed |
| 14 | SHIP_CONTAINER | Loot capacity |
| 16–27 | SPEED / ATTACK / DEFENSE per ship class | Fighter, corvette, frigate, destroyer |
| 7, 8, 12 | GRAVIT_MODULE, DOCK3_SPEED, ANTISPY_INVEST | Computed but never read |
| 15 | BASE_QUANTITY | The raw technology level is read instead of the bonus |

---

## 8. Research and technologies

Research comes in two separate systems.

### 8.1 University research (Promethee `Research`)

Code: `Promethee/Model/Research.php`, `Promethee/Manager/ResearchManager.php`, `Zeus/Application/Handler/UniversityInvestmentHandler.php`.

- **10 sciences in 4 domains:**

  | Domain | Sciences |
  |---|---|
  | Natural | Mathématiques, Physique, Chimie |
  | Political | Droit, Communication |
  | Social / economic | Économie, Psychologie |
  | Computer engineering | Réseaux, Algorithmique, Statistiques |

- **Funding:**
  - the player invests `iUniversity` credits per cycle (max 500,000);
  - the investment is split between domains with 4 sliders;
  - each share is increased by `UNI_INVEST`%.
- **Progress:**
  - each domain has one target science that receives the credits;
  - when it is paid off, it gains a level and the next target is picked **at random** within the domain;
  - the gap between sciences of the same domain is kept to 3 levels or less.
- **Level cost** (level 2 and up) follows a 5th-degree polynomial:
  - 12.6k (level 2), 142k (level 10), 1.09M (level 20), 2.98M (level 30);
  - level 1 costs 100–9,000 depending on the science.

### 8.2 Technologies (Promethee `Technology`)

Code: `Promethee/Model/TechnologyId.php`, `Promethee/Resource/TechnologyResource.php`, `Promethee/Helper/TechnologyHelper.php`.

There are 47 IDs. IDs 2, 26 and 27 are inactive.

**Unlock technologies** (one level, fixed cost):

| ID | Name | Unlocks | Technosphère |
|---|---|---|---|
| 0 | Long courrier | Plateforme Commerciale | 9 |
| 1 | Grue magnétique | Chantier de Ligne | 16 |
| 3 | Recycleur autonome | Centre de Recyclage | 9 |
| 4 | Vaisseaux de transport | Spatioport | 16 |
| 5–16 | Châssis … | Ship types 0–11 | 1 → 40 |
| 17 | Colonisation | Colonising neutral planets (15k resources, 20k credits) | 14 |
| 18 | Conquête | Conquering player planets (30k resources, 40k credits) | 25 |

**Level technologies:**

| ID | Name | Effect | Max level |
|---|---|---|---|
| 19 | Ingénierie du bâtiment | GENERATOR_SPEED | 20 |
| 20 | Craquage catalytique | REFINERY_REFINING | 25 |
| 21 | Silo-compresseur | REFINERY_STORAGE | 30 |
| 22, 23 | Chaîne d'assemblage (lourde) | DOCK1 / DOCK2 speed | 25 |
| 24 | Intelligence artificielle | TECHNOSPHERE_SPEED | 15 |
| 25 | Mercatique | COMMERCIAL_INCOME | 30 |
| 28 | Économie sociale de marché | POPULATION_TAX | 30 |
| 29 | Formation continue | COMMANDER_INVEST | 30 |
| 30 | Lobbying universitaire | UNI_INVEST | 30 |
| 31 | Surveillance réseau | ANTISPY_INVEST (**no effect**) | 30 |
| 32 | Propulsion hyperspatiale | SHIP_SPEED | 30 |
| 33 | Silo de transport | SHIP_CONTAINER | 30 |
| 34 | Administration étendue | +1 base per level | 14 |
| 35–46 | Turbo-propulsion / armement / bouclier | Speed / attack / defence for fighters, corvettes, frigates, destroyers | 30 |

**Formulas** (`TechnologyHelper::getInfo`):

- **Cost:** `base × m^(L−1)`, where m is 1.5, 1.3 or 1.2 depending on the technology's category.
- **Time:** `base × L`, reduced by `TECHNOSPHERE_SPEED`% and by the planet's `coefHistory` bonus.
- **XP:** `points × L`.
- **Bonus of level i:** `base% + floor((i−1) / 5)`. The total bonus is the sum over all levels. Examples: about 135% for a base-2 technology at level 30, about 165% for base 3.

### 8.3 Research conditions and queue

Code: `Promethee/.../SearchTechnology.php`.

- **Resources and credits** are paid upfront.
- **Technosphère:**
  - its level must be at least the technology's requirement;
  - its level sets the number of queue slots, 2 to 6.
- **Science levels:** each required science must be at least `required + targetLevel − 1`.
- **Base type limits which technology columns can be researched:**

  | Base type | Columns |
  |---|---|
  | Colony | 1–3 |
  | Commercial | 1–5 |
  | Military | 1–3, 6, 7 |
  | Capital | all |

- **One queue entry per technology per player.** The queue is per planet and runs sequentially.
- **Cancelling** refunds 50% of both resources and credits.

---

## 9. Military: ships, commanders, fleets

### 9.1 Ships

Data in `config/game/ship_categories.yaml`; enum `Ares/Domain/Model/ShipCategory.php`.

| ID | Name | PEV | Life | Def | Speed | Shots (damage) | Upkeep / cycle | Build time | Resources | Dock / level |
|---|---|---|---|---|---|---|---|---|---|---|
| 0 | Chasseur Léger | 2 | 26 | 1 | 195 | 5 | 7 | 500 s | 2,800 | Alpha 1 |
| 1 | Chasseur lourd | 3 | 30 | 5 | 185 | 7 | 5 | 570 s | 3,500 | Alpha 9 |
| 2 | Chasseur multi-tourelles | 3 | 26 | 3 | 195 | 6, 6 | 25 | 850 s | 4,420 | Alpha 17 |
| 3 | Corvette légère | 5 | 65 | 6 | 190 | 20, 2, 2 | 100 | 480 s | 4,000 | Alpha 25 |
| 4 | Corvette lourde | 7 | 90 | 23 | 165 | 30 | 58 | 1,880 s | 8,350 | Alpha 33 |
| 5 | Corvette multi-tourelles | 9 | 75 | 8 | 145 | 20, 12×4, 7×3 | 80 | 2,500 s | 15,300 | Alpha 40 |
| 6 | Frégate d'attaque | 23 | 250 | 40 | 100 | 15×4 | 68 | 1 h | 40,000 | Ligne 1 |
| 7 | Frégate ionique | 45 | 320 | 40 | 90 | 225 | 500 | 2 h | 80,000 | Ligne 4 |
| 8 | Destroyer | 75 | 1,200 | 120 | 88 | 35×4, 25, 10, 10 | 250 | 3.2 h | 112,000 | Ligne 8 |
| 9 | Destroyer lance-missiles | 86 | 1,050 | 100 | 75 | 21×20 | 600 | 4.8 h | 150,000 | Ligne 12 |
| 10 | Croiseur | 82 | 1,220 | 135 | 80 | 175, 50, 50, 25×4, 6×3 | 700 | 4.4 h | 208,000 | Ligne 16 |
| 11 | Croiseur lourd | 84 | 1,350 | 150 | 75 | 200, 50×4, 25×4, 6×3 | 950 | 5 h | 300,000 | Ligne 20 |

Field meanings:

- **PEV:** fleet space. A squadron holds at most 100 PEV, and hangar capacity is also counted in PEV.
- **Speed:** only used for **dodging** in combat. It does **not** affect travel time.
- **`cost`:** upkeep per cycle, not a build price.

**Building ships:**

- Each ship type also requires its *Châssis* technology (5–16). ⚠️ This check never blocks (§14).
- Chantier de Ligne orders always have quantity 1.
- The UI limits an order to the smallest of: affordable quantity, free hangar PEV, and 99.
- When ships are completed, they go to the hangar and the player gains `points × qty` XP.
- Scrapping ships from the hangar refunds 50% of their resources.

### 9.2 Commanders

Code: `Ares/Model/Commander.php`.

**Statuses:**

| Value | Status | Meaning |
|---|---|---|
| 0 | INSCHOOL | Trains and earns XP every cycle |
| 1 | AFFECTED | In orbit at its base; holds ships and defends |
| 2 | MOVING | Travelling |
| 3 | DEAD | Killed |
| 4 | DESERT | Fired |
| 5 | RETIRED | Was moving when its base was lost |
| 6 | ONSALE | On the market, or deserted after unpaid wages |
| 7 | RESERVE | In the mess hall; no XP |

**Recruitment** (`Ares/.../CreateCommander.php`):

- Costs 2,500 credits and starts with 100–210 XP.
- School capacity is 3 on colonies and commercial bases, 8 on military bases and capitals.
- The reserve holds at most 20 commanders.

**Levels:**

- A commander levels up when `xp ≥ 100 × 2^level`, so level L needs `100 × 2^(L−1)` XP.
- There are 20 grade names, from "3ème Classe" up to "Grand Amiral".

**School XP per cycle** (`Ares/Handler/CommanderSchoolExperienceHandler.php`):

```
iSchool × (1 + COMMANDER_INVEST% / 100) / 100, ± 5%
```

**Battle XP:** `importance × 50` (§10.4).

**Wages:** `200 × level` per cycle, while the commander is AFFECTED or MOVING.

**Actions:**

| Action | Effect |
|---|---|
| Affect | Moves the commander from school or reserve to AFFECTED. There is no cap on affected commanders per planet. |
| Unaffect | Sends the commander back to school, or to reserve if the school is full. |
| Disband | Empties the squadrons into the hangar. |
| Fire | Status becomes DESERT. |
| Sell | Lists the commander on the market. |

### 9.3 Fleet composition

- **Squadrons:** a commander has **as many squadrons as its level**. The practical cap is 17.
- **Squadron content:** each squadron (`Ares/Model/Squadron.php`) stores a count for each of the 12 ship types, up to **100 PEV**.
- **Battle lines:** each squadron position belongs to a line, given by `Commander::$LINECOORD`:

  ```
  [1,1,1,2,2, 1,2,3,3, 1,2,3,4,4, 2,3,4,5,5, ...]
  ```

  - Levels 1–3: every squadron is on line 1.
  - Line 2 appears at level 4, line 3 at level 8, line 4 at level 13, line 5 at level 18.
- **Hangar transfers** (`Ares/.../Fleet/UpdateSquadron.php`, `assets/controllers/fleet/ship_transfer_controller.js`):
  - only for an AFFECTED commander at its base;
  - click moves 1 ship, Shift+click 5, Ctrl+click all.
- **In progress** (branch `feature/fleet-composition`):
  - a 5-row hexagonal **fleet grid** with 20 to 16 cells per row (`FleetGrid`, `FleetGridPosition`);
  - it is currently used only in the combat simulator and does not affect combat yet.

---

## 10. Combat

Code: `Ares/Manager/FightManager.php`, `Ares/Application/Handler/Battle/{SquadronFightHandler,ShipFightHandler,ShipStatsHandler}.php`, `Ares/Application/Handler/{CommanderArmyHandler,FightImportanceHandler,CommanderExperienceHandler}.php`.

### 10.1 Who fights

- **Only commanders fight.** There is no planetary or orbital defence.
- **Defending a player planet:** its AFFECTED commanders defend.
  - Loot: one defender picked at random.
  - Conquest: every defender in turn.
- **Unowned planet:** a **rebel virtual commander** is generated from the planet's danger and population (`Ares/Application/Handler/VirtualCommanderHandler.php`, squadron templates in `Galaxy/Resource/SquadronResource.php`).
  - Level = `ceil(((maxDanger/5)×9 + population/12.5) / 10)`.
  - The number of filled squadrons scales with `danger / maxDanger`.
  - After the fight, danger is reduced in proportion to the PEV the rebel lost.

### 10.2 Rounds and lines

- Each round has two halves: the **defender acts first**, then the attacker.
- **Lines join over time:** a squadron fights (and can be targeted) only once `lineCoord × 3 ≤ currentLine`. `currentLine` starts at 3 and goes up by 1 each round.
  - Line 1 fights from the first round.
  - Line 2 joins at round 3, line 3 at round 6, and so on every 3 rounds.
- The battle ends when one side has no ships left, or after 1000 rounds.

### 10.3 Squadron and ship resolution

- **Choosing a target:** a squadron keeps its target while that target has ships. Otherwise it picks a random enemy squadron on an active line.
- **Exchanges are mutual:** A fires at B, then B immediately fires back at A.
- **Each shot** in a ship's attack list:
  1. **Picks a target:** a random living ship in the target squadron.
  2. **Dodge:** the shot misses if `random_int(0, speed) > 80`. That is about 59% for fighters and 0% for ships with speed ≤ 80.
  3. **Damage:** `ceil(ln(A/D + 1) × 4 × A)`, where A is the shot's attack and D the target's defence.
  4. **Destruction:** a ship at 0 life or less is destroyed.
- **Bonuses:** `stat × (1 + bonus%)` for attack, defence and speed, by ship class. Cruisers (10 and 11) get no class bonus. Ship life never gets a bonus.

### 10.4 Outcome

- **The loser's commander dies**, since all of its ships are gone.
  - The winner's `victory` and the loser's `defeat` counters go up, and the winner's `palmares` goes up.
- **Importance:** `floor(enemyPEV × (ownLevel + 1) / (enemyLevel + 1))`, using PEV at the start of the battle.
  - Commander XP = `importance × 50`.
  - Player XP = commander XP ÷ 100. The losing player also gets this.
- **Report:** a `Report` is saved, built from `LiveReport`.
  - `isLegal` is false when the two factions are at PEACE or ALLY. It is recorded only; nothing punishes an illegal attack.
- **Combat simulator** (`/combat-simulator`, Portal) is **in progress**:
  - virtual commanders are stored in the session;
  - no fight is run yet.

---

## 11. Missions and travel

Code: `Ares/Infrastructure/Controller/Fleet/*`, `Ares/Handler/CommanderTravelHandler.php`, `Ares/Application/Handler/Movement/MoveFleet.php`.

### 11.1 Common rules

- **Range:** at most **30** distance units, unless the target is in a sector owned by your own faction or an allied one.
- **Missions** (`CommanderMission`): Move, Loot, Colo (colonisation or conquest), Back.
- **Arrival:** each mission is resolved when a delayed `CommanderTravelMessage` fires.
- **Returning (Back):** the commander becomes AFFECTED again, and any loot it carries is added to its base.
- **Cancelling:** the commander turns around.
- **Incoming attacks** (loot or colonisation) become visible to the defender when the fleet enters the defender's anti-spy radius (§12.2).

### 11.2 Travel time

Code: `Travel/Domain/Service/CalculateTravelTime.php`.

- **Same system:** `round(584 × d × (40 − d) / 50 + 180)` s, where d is the difference in position within the system.
- **Between systems:** `600 + round(50000 × dist / fleetSpeed)` s.
  - `fleetSpeed = 35 + round(105 × SHIP_SPEED% / 100)`.
  - Example: distance 10 with no bonus takes about 4 h 08.
- **Fleet composition does not matter.**
- **Commercial shipping** takes × 0.2 of these times.

### 11.3 Move

- **Allowed:** an AFFECTED commander can move toward a planet of its own faction.
- **On arrival:**
  - if the planet belongs to the same player, the commander changes base;
  - otherwise it bounces back.

### 11.4 Loot

- **Allowed targets:** not your own faction or an ally, and not an active level-1 player.
- **On arrival:**
  - **Rebel planet:** fight the rebels; if the attacker wins, it loots.
  - **Enemy player planet:** fight one random defender, or none if there is no defender; if the attacker wins, it loots.
- **Capacity:** `remaining PEV × 275 × (1 + 2 × SHIP_CONTAINER% / 100)`.
- **Amount taken:**
  - from a player: up to `stock − 5000` (the last 5,000 resources are protected);
  - from a rebel planet: up to the whole stock.

### 11.5 Colonisation

- **Requires:**
  - the Colonisation technology;
  - a free base slot (in-flight colonisations count);
  - an AFFECTED commander with ships.
- **Price:** `current bases × 80,000` credits, paid when the fleet leaves and not refunded. Adranite gets −10%.
- **On arrival:**
  - fight the rebels;
  - if the attacker wins, the planet becomes a colony named "colonie", with 2,000 resources, school and anti-spy investments of 500, and danger 0;
  - the commander is based there.

### 11.6 Conquest

- **Validated by** `Ares/Infrastructure/Validator/Commander/CanConquer.php`:
  - the Conquête technology;
  - the commander is in orbit, in range, and has ships;
  - the target belongs to a player of another, non-allied faction;
  - there is a free base slot;
  - the player can afford the price.
- **Price:** `current planets × 150,000` credits. Adranite gets −10%.
- **On arrival** (the target player must be above level 3):
  - fight **every** AFFECTED defender in turn;
  - if the attacker survives all of them, it takes the planet (`changeOwner`) and is based there.

---

## 12. Espionage

Code: `Artemis/*`, `Athena/.../Financial/UpdatePlanetInvestments.php`, `AntiSpyHandler`.

### 12.1 Spying

- **Target:** any planet not owned by your own faction.
- **Price:** chosen by the spy, from 1 to 1,000,000 credits. UI presets are 1,000, 2,500 and 5,000.
- **Success rate:** `min(100, round(price / antiSpy × 33))`.
  - antiSpy = the target planet's `antiSpyAverage`, or `maxDanger × 40` for rebel planets.
- **Information revealed** grows with the success rate:

  | Success rate above | Reveals |
  |---|---|
  | 10 | Resources |
  | 20 | Hangar |
  | 30 | Anti-spy |
  | 40 | Commanders |
  | 50 | Route income |
  | 60 | PEV |
  | 70 | Planet points |
  | 80 | Movements |
  | 95 | Army composition |
  | 99 | Docks |

- **Getting caught** (only if the target's anti-spy is 1,000 or more, and never for rebel planets):

  | Success rate | Not caught | Caught anonymously | Caught, identity revealed |
  |---|---|---|---|
  | < 40 | 5% | 45% | 50% |
  | 40–79 | 30% | 30% | 40% |
  | 80–99 | 50% | 30% | 20% |
  | 100 | 70% | 20% | 10% |

### 12.2 Anti-spy

- `antiSpyAverage` is a 24-cycle moving average of the planet's `iAntiSpy` investment.
- **Detection radius:** `sqrt(investment / 3.14)` map units. It is drawn on the map as 3 circles.
- An incoming attack becomes visible once the fleet enters the outer circle. Attacks within the same system are always visible.

---

## 13. Factions, politics, diplomacy, victory

Code: `Demeter/*`, `config/game/factions.yaml`, `config/game/faction_bonuses.yaml`, `docs/factions/politics.md`, `config/packages/workflow.php`. Faction names, lore and titles are in `translations/messages+intl-icu.fr.yaml`.

### 13.1 Factions

| ID | Faction | Regime | Mandate | Bonuses |
|---|---|---|---|---|
| 1 | Fédération Kalankare | Democratic | 7 days | +5% small-ship defence; −5% price on cruisers |
| 2 | Ascendance Valkar | Royalistic | 7 days | +10% fighter and corvette speed; −5% frigate and destroyer defence |
| 3 | République de Falkirr | Royalistic | 7 days | +5% route income; −3% route price |
| 4 | Ruche Adranite | Royalistic | 14 days | +3% population tax; −10% colonisation and conquest price; −5% loot capacity |
| 7 | Marche de Malgar | Theocratic | 16 days | +5% small-ship defence; +6% commander school; +2% university |

Faction 0 is the rebel faction ("Sans Faction"). It is closed and only owns the rebel player.

Lore in brief:

- **Kalankar:** survivors of an empire that fell in the "Grand Cataclysme"; their culture is built around Memory.
- **Valkar:** an exiled military house, meritocratic.
- **Falkirr:** a former colony that became a republic and values freedom.
- **Adranite:** telepathic hives.
- **Malgar:** a prophetic theocracy built around the "Spirit of Malgar".

Bonuses 9–12 (refinery, storage, big academic, technology) exist in the code but no faction has them.

### 13.2 Members, senate, government

**Role titles per faction:**

| Role | Kalankar | Valkar | Falkirr | Adranite | Malgar |
|---|---|---|---|---|---|
| Standard | Membre | Guerrier | Citoyen | Monarque | Fidèle |
| Parliament | Conseiller | Dignitaire | Sénateur | Adelphe | Prêtre |
| Treasurer | Gardien du Trésor | Trésorier | Trésorier | Économe | La Raison |
| Warlord | Gardien des Armes | Maréchal | Stratège | Dévoreur | Le Poing |
| Minister | Gardien du Domaine | Chambellan | Premier Consul | Coordinateur | La Voix |
| Chief | Premier Conseiller | Empereur | Président | Haut Monarque | La Vision |

**Senate** (`Demeter/Domain/Service/UpdateSenate.php`):

- The top `round(members / 4)` players by `factionPoint` become senators. This is clamped between 10 and 40 seats.
- Government members keep their role.
- The senate is updated at each campaign (democratic and theocratic factions), or periodically (royalistic factions).

**Government:**

- The Chief appoints and dismisses the Treasurer, the Warlord and the Minister, choosing among parliament members.
- Ministers can resign.
- In non-democratic factions the Chief can abdicate in favour of an heir.
- When a mandate expires, every minister goes back to parliament.

**Powers:**

| Role | Can |
|---|---|
| Chief | Propose Chief laws, which apply instantly without a vote; appoint and dismiss ministers |
| Treasurer | Propose tax laws; send faction credits to players |
| Warlord | Propose "Effort de guerre" |
| Minister | Propose "Transfert technologique" and "Amende" |
| Any government member | Write official faction messages; edit the faction description; manage faction news; access the Council forum |

### 13.3 Political cycle

The cycle is the `faction_mandate` state machine on `Color::mandateState`:

- **States:** `active`, `democratic_campaign`, `democratic_vote`, `theocratic_campaign`, `putsch`.
- Timings are listed in §2.4.

**Democratic regime (Kalankar)**

- Every mandate duration, a **campaign** starts and lasts 4 days.
- During the campaign, senators and government members can stand as candidates (or withdraw). Each candidacy gets a topic in the campaign forum.
- Then comes a **vote** of 2 days, where every member votes once. The candidate with the most votes wins, and ties are broken at random.
- **Special cases:**
  - one candidate: elected directly;
  - no candidate: the current leader stays.
- The new mandate starts when the previous one expires.

**Theocratic regime (Malgar)**

- There is a campaign of 4 days, as in the democratic regime, but no vote.
- The leader is picked **at random** among the candidates ("divine designation").

**Royalistic regimes (Valkar, Falkirr, Adranite)**

- There are no mandates and no elections.
- Any parliament member who is not the Chief can launch a **coup**:
  - it lasts 7 hours, and only one coup can run at a time;
  - the coup should succeed with **at least 15%** support from members.
- ⚠️ Coups are currently broken (§14).

### 13.4 Laws

Code: `Demeter/Resource/LawResources.php`.

| ID | Law | Proposed by | Price | Effect |
|---|---|---|---|---|
| 1 | Imposition sectorielle | Treasurer | 50,000 | Sets the tax (2–15%) of a sector the faction owns |
| 2 | Décret de revendication | Chief | 250,000 | Renames a sector the faction owns |
| 3 | Taxes de vente | Treasurer | 50,000 | Export tax toward a faction (2–15%) |
| 4 | Taxes d'achat | Treasurer | 50,000 | Import tax from a faction (2–15%) |
| 5 | Effort de guerre | Warlord | 1,000 / cycle / active member | +20% dock speed, −10% refinery |
| 6 | Transfert technologique | Minister | 1,250 / cycle / active member | +10% research speed |
| 7 | Traité de paix | Chief | 10,000 | Relation → PEACE (max 2) |
| 8 | Déclaration de guerre | Chief | 10,000 | ENEMY on **both** sides; freezes routes |
| 9 | Alliance totale | Chief | 10,000 | Relation → ALLY (max 1) |
| 10 | Neutralité | Chief | 10,000 | Relation → NEUTRAL |
| 11 | Amende | Minister | 2,000 | Fines a member; the credits go to the faction treasury |

**Procedure:**

- Only the matching role can propose a law. The faction treasury pays when it is proposed.
- Duration laws (5 and 6) last up to 2400 cycles, and only one of each type can be active or under vote at a time.
- Chief laws apply immediately.
- **Other laws go to a 24 h senate vote:**
  - only senators vote;
  - yes counts +1 and no −1; the law passes if the total is 0 or more (no quorum);
  - a rejected law refunds 90% of its price.

### 13.5 Treasury

Faction credits are stored in `Color::credits`.

| Income | Spending |
|---|---|
| Sector taxes (§4.2) | Law prices |
| Market taxes (§7.4) | Treasurer transfers to players |
| Donations from members | |
| Fines | |
| Refunds of rejected laws | |

### 13.6 Diplomacy

`Color::relations` maps each other faction to NEUTRAL, PEACE, ALLY or ENEMY. Relations change only through laws 7–10 and are one-sided, except war, which applies to both sides.

| Relation | Effect |
|---|---|
| ALLY | Cannot be looted or conquered. Allied sectors count as friendly for range. |
| PEACE / ALLY | Attacks are flagged "illegal" in reports, with no further consequence. |
| ENEMY | No new commercial routes between the two factions; existing routes are frozen. |

### 13.7 Rankings and victory

Code: `Atlas/*`, run nightly.

**Player ranking**, one score per category:

| Category | Score |
|---|---|
| General | Planet points + 0.1% of resources + 0.1% of ship value |
| Resources | Production |
| Experience | Player XP |
| Fight | Victories − defeats |
| Armies | Total PEV |
| Butcher | PEV destroyed − PEV lost |
| Trader | Route income |

**Faction ranking:**

- **General:** the sum of the members' general scores.
- **Wealth:** route income.
- **Territorial:** the sum of the points of owned sectors.

**Victory points:**

- Points are awarded only after 480 h (20 days) from server start.
- Each ranking awards points by rank in each category, with weights:

  | Category | Weight | 1st / 2nd / 3rd / 4th |
  |---|---|---|
  | General | × 0.1 | 4 / 3 / 2 / 1 |
  | Wealth | × 0.4 | 16 / 12 / 8 / 4 |
  | Territorial | × 0.5 | 20 / 15 / 10 / 5 |

  That is at most 40 points per ranking.
- **The first faction to reach 2000 points wins.** Rankings then stop changing.
- ⚠️ Points do not add up from one ranking to the next, so victory cannot be reached today (§14).

### 13.8 Communication

The Hermes module covers:

- **Notifications.**
- **Conversations:** player conversations of up to 25 people, plus system and faction conversations.
- **Galactic news:** putsch attempts, candidates, new leaders, ministers, sector changes.

Demeter covers the **faction newspaper** (`FactionNews`) and the **faction forums**:

| Forum | Access |
|---|---|
| 1–5 (battle plans, trade, politics, chat, special operations) | Members |
| 10 (Council) | Government |
| 20 (Embassy) | Chiefs of all factions |
| 30 | Campaign topics |

---

## 14. Known issues

These issues were found by reading the code and have not been checked at runtime. **(✔)** means I re-checked it directly in the code.

### Combat and military

1. **(✔) Defenders are never found.** `CommanderRepository::getPlanetCommanders($planet)` defaults to `statements = []`, so `findBy(['statement' => []])` matches nothing. Calls that pass no filter (`LootManager`, `ConquestManager`, `PlanetManager::changeOwner`, `LeavePlanet`) therefore get no commanders. Consequences:
   - loot and conquest never fight player defenders;
   - commanders of a lost base are neither killed nor transferred.
2. **The line counter is never reset.** `FightManager::$currentLine` is static and only reset in the constructor. Several fights in the same process (a worker, or the conquest loop) start with rear lines already active.
3. **Defence and dodge use the wrong player's bonuses.** The target's defence and dodge speed are computed with the **attacker's** `PlayerBonus`.
4. **Rebel squadrons are positioned wrongly.** They have no `lineCoord` or `position`, and `chooseEnemy` shuffles the Doctrine collection of the virtual commander, which is empty. Player squadrons may therefore never pick rebels as a target.
5. **Cancel makes the return instant.** It computes the return arrival in the past, and does not unschedule the original message.
6. **Missing checks:** `Loot` does not check that the commander is AFFECTED, and `AffectCommander` has no ownership check.
7. **The squadron cap is inconsistent.** `Commander::getSquadron()` throws above index 16, while `setArmy` allows 25 squadrons. Cruisers get no class bonus.

### Bonuses and prices

8. **(✔) A faction bonus is applied twice.** `DEFENSELITTLESHIPBONUS` appears twice in `PlayerBonusManager::addFactionBonus` (lines 99 and 152), so Kalankar and Malgar get +10% defence instead of +5%.
9. **A law malus is applied twice.** "Effort de guerre" applies the refinery −10% twice, giving −20%.
10. **The Kalankar cruiser discount is not charged.** `CountShipResourceCost` and `CountAffordableShips` use `in_array(int, [enum])`, which never matches.
11. **The Falkirr route discount is applied twice** in `Route/Accept.php`. The route slot check there uses `>` instead of `>=`, which allows one extra route.
12. **The Châssis technology check never blocks.** `ShipHelper::haveRights('techno')` returns a string, which is treated as a boolean, so ships can be built without their technology.
13. **`HasUnlockedBuildingValidator::checkGeneratorRequirement` never fires** because of an operator-precedence bug. The real check is done by `IsValidTargetLevel`.

### Economy

14. **Faction market taxes credit the rate instead of the amount.** `Offer/Accept.php` gives factions the tax *rate* rather than the amount collected.
15. **School investment is never paid.** `iSchool` is recorded in the financial report but excluded from `getTotalLosses()`.
16. **Some credit flows may be counted twice.** Flows already applied to the wallet as they happen (sales, purchases, transfers, technologies, recycling) are added again by the hourly report, which then overwrites `player.credit`.
17. **The recycling resource yield is huge.** It multiplies by the place's multi-million resource *stock* instead of a percentage coefficient.
18. **`PlayerManager::decreaseCredit` takes nothing** when the player cannot afford the amount. Negative transfer amounts are turned positive by `abs()`.
19. **Two actions crash.** Dock 3 is missing from `PlanetResource::BUILDINGS`, so code that loops over every building throws: `ChangePlanetType` (commercial ↔ military) and abandoning a capital.
20. **The university slider can exceed 100%.** The cap is computed but never applied.
21. **Planet-leave cooldown quirk:** a planet between 1 day and 1 day + cooldown old cannot be abandoned.

### Politics and victory

22. **(✔) `Player::factionPoint` is never increased**, so the senate is chosen in an arbitrary order.
23. **(✔) `Color::rankingPoints` is never written.** Victory points do not add up and 2000 is unreachable. The victory message after `return;` is dead code.
24. **Coups cannot succeed:**
    - `VoteForCandidate` only accepts votes in the `democratic_vote` state;
    - `GetPutschSupportPercentage` does not compute a percentage;
    - on success, nobody is made Chief.
25. **Rejected instantaneous laws still apply.** Their result handlers do not check the law's state. The refund formula for duration laws also differs from the price formula.
26. **The democratic leader is promoted too early.** With a unique candidate, the new leader becomes Chief right away while the old one keeps the title until the mandate expires. Abdicating in a democracy does nothing.
27. **Missing permission checks:** `FireMinister` lets the Chief fire themselves, `News/Pin` has no permission check, and the Treasurer's `SendCredits` does not check the recipient's faction.
28. **Laws with no effect:** the length check on sector names uses `and`, so it never applies. `CancelLaw` is not implemented.

### Legacy and unused

29. **Config parameters overridden by constants:** `ares.coeff.move_*`, `ares.loot_limit`, `ares.coeff.loot`, `ares.commander.income_level`. Note the travel coefficient: 1000 in config vs 50000 in the code.
30. **Unused constants and parameters:** `Planet::MAXCOMMANDERSTANDARD` / `MAXCOMMANDERMILITARY`, `Commander::MAXTRAVELTIME`, `galaxy.limit_conquest_sector`, `pam_coeftax`, the prestige constants in `Color`, `Report::fromLiveReport`.
31. **Unused statuses and bonuses:** `PlayerStatement::Holiday` and `Banned` are never set. The bonuses `ANTISPY_INVEST`, `GRAVIT_MODULE` and `DOCK3_SPEED` are never read.
32. **Dead legacy code:** `src/action/`, `src/script/`, `Hermes RoadMap`.
33. **Stale delayed messages.** Cancelling a queue or market action does not reschedule the delayed Messenger messages (TODOs), so old messages may still fire.
34. **The faction texts do not match the regimes.** The campaign descriptions (`factions.*.descriptions.campaign`) describe other regimes, inherited from Asylamba.
