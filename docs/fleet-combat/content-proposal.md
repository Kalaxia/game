# Weapon patterns and officer traits: proposal

**Status:**
- Validated by the maintainer on 2026-10-08.
- **Weapon patterns are implemented**, with free turning (§1).
- **Officer traits are implemented** in the simulator (§2). Random traits, acquisition and spying come with the game integration (Phase 7).

Values are first guesses, to be tuned with the balance harness.

**Maintainer's answers (2026-10-08):**
1. The five pattern kinds are fine. Turning stays free for now.
2. The Corvette lourde gets range 2: done.
3. 1 to 3 traits per officer. Traits of enemy officers can be discovered by spying.
4. In the simulator, the player picks the traits of each officer; they are random in the game.
5. Weapon patterns first, then traits.

Both features reuse the existing engine levers (range, targeting preferences, initiative, dodge, morale, command capacity), so that they stay readable (P5). Ship types will be identified by pictograms; the shape of a weapon pattern could also be shown when hovering over a squadron.

## 1. Weapon patterns (Q12)

Today every squadron fires at any enemy within its range, in every direction. The proposal gives each ship type a **pattern**: the cells it can hit, relative to its position and to the direction its fleet faces.

### Pattern kinds

| Pattern | Cells it can hit | Intent |
|---|---|---|
| **Close** | Adjacent cells only (range 1), any direction | Dogfights: fighters, bombers |
| **Frontal cone** | Cells within range in the 3 directions facing the enemy (a 120° cone) | Line ships must face the enemy; flanks and rear are weak spots |
| **Broadside** | Cells within range on the 2 sides of the fleet's facing (left and right) | Escorts protect the flanks of the line |
| **Ring** | Cells between a minimum and a maximum range, any direction, over allied squadrons | Artillery fires above its own line but cannot defend itself at close range |
| **Area** | Like a ring; the shot also deals 50% damage to squadrons adjacent to the target, allies included | Missile destroyer: strong against packed formations, risky near allies |

### Proposed patterns by ship type

| Ship | Role | Pattern | Range |
|---|---|---|---|
| Chasseur léger | Interceptor | Close | 1 |
| Chasseur lourd | Interceptor | Close | 1 |
| Chasseur multi-tourelles | Interceptor | Close, any direction, can split its shots between two adjacent targets | 1 |
| Bombardier | Bomber | Close, frontal only (torpedo run) | 1 |
| Corvette légère | Escort | Broadside | 1–2 |
| Corvette lourde | Line | Frontal cone | 1–2 (instead of 1, so it stops being outranged) |
| Corvette multi-tourelles | Escort | Broadside + close | 1–2 |
| Frégate d'attaque | Line | Frontal cone | 1–2 |
| Frégate ionique | Artillery | Ring | 2–3 |
| Destroyer | Line | Frontal cone | 1–2 |
| Destroyer lance-missiles | Artillery | Area | 2–4 |
| Croiseur | Capital | Frontal cone + close (point defence) | 1–3 |
| Croiseur lourd | Capital | Frontal cone + broadside | 1–3 |

### Consequences

- **Formation matters more:**
  - line ships at the front;
  - escorts on the flanks;
  - artillery behind the line, where it is safe from close attacks;
  - fighters free to flank.
  This rewards the player's composition (D9).
- **Free turning (maintainer's decision):** squadrons always turn toward their target, so the frontal cone and the broadside do not restrict fire yet. What changes combat today:
  - minimum ranges: the Frégate ionique (2–3) and the Destroyer lance-missiles (2–4) cannot fire at contact, and step back to fire;
  - area damage: the missile destroyer deals 50% of each hit to the squadrons next to the target, allies included, but the shooter is not credited with damage on allies;
  - split fire: the Chasseur multi-tourelles shares its shots between its two best targets in range.
  Cone and broadside are kept in the data (`patterns` in `battle_ship_types.yaml`) for when turning gets a cost.
- **Positioning AI** *(superseded by free turning)*: squadrons need to turn their facing toward their target. Proposal: a squadron faces the direction it last moved, or its target when it does not move. Turning costs nothing in the first version.
- **Readability:** the replay could briefly show the pattern of the squadron firing, and the report could count the shots lost because no target was in the pattern.

## 2. Officer traits (Q13)

In the style of Total War Saga: each officer has **1 to 3 traits**, positive or negative, which colour their fleet's behaviour.

- **Acquisition:**
  - one trait at recruitment, drawn at random;
  - one more at levels 5 and 10 (maximum 3);
  - some traits are earned through events, e.g. surviving a flight gives *Prudent*, destroying 3 fleets gives *Butcher*.
- A trait can never be removed in the first version.

### Proposed traits

| Trait | Kind | Effect |
|---|---|---|
| **Agressif** | mixed | +10% initiative. Withdraws at 20 morale instead of 30. Squadrons move toward the closest enemy, but fire on their preferred targets in range. |
| **Prudent** | mixed | Withdraws at 40 morale instead of 30. +15% dodge while withdrawn or fleeing. Exposed for 1 turn instead of 2 when fleeing. |
| **Tenace** | positive | Morale losses −25%. |
| **Charismatique** | positive | Allied fleets next to it recover +2 morale per turn. |
| **Logisticien** | positive | Command capacity +20% (D10). |
| **Tacticien** | positive | Its fleet is served first when actions are handed out among fleets. |
| **Chasseur de capitaux** | positive | In range, bombers, artillery and capital ships fire first on destroyers and cruisers. +10% damage against them for the whole fleet. |
| **Défenseur** | positive | +10% defence when the fleet defends (side in orbit, D11). |
| **Imprudent** | negative | +10% attack, −10% dodge. |
| **Indécis** | negative | −15% initiative. Remobilises at 60 morale instead of 50. |
| **Boucher** | mixed | +10% damage against fleets already engaged by allies. Morale gains near allies halved (feared by his own). |
| **Vétéran** | positive (earned at level 10) | Morale starts at 100 and never drops below 20 from outnumbering alone. |

### Display

- Traits appear on the officer card, in the simulator fleet editor and in the battle report ("Trait *Tenace* : moral préservé de 12 points").
- In line with the art direction, traits use text and later icons, not colours.

## 3. Questions for the maintainer

1. **Weapon patterns:** do these five pattern kinds suit you? Should squadrons turn freely (first version) or should turning cost movement?
2. **Patterns and existing ranges:** should the Corvette lourde get range 2 now? Today it is outranged and rarely fires.
3. **Traits:** how many per officer (proposal: 1 to 3)? Should they be visible to enemies, for instance through spying?
4. **Traits:** in the simulator, should the player pick the traits of each officer (useful to test) while they are random in the game?
5. **Order:** weapon patterns first (formation impact, D9), or traits first (officer depth, Q13)?
