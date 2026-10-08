# AGENTS.md

Guide for AI agents working on **Kalaxia**. It is a multiplayer, browser-based space strategy game. The codebase is a long-running fork of the open-source game *Asylamba*, which is why `asylamba` still shows up in package names, DB names and URLs. Players belong to factions, manage planets (buildings, ships, research, trade), command fleets, fight other players and take part in faction politics.

## Gameplay in brief

**Full reference: [`docs/gameplay.md`](docs/gameplay.md).** Read the relevant section before touching any game rule. Its last section lists known bugs and inconsistencies.

- **Cycles:** the game runs on cycles called *relèves*, 1 h in standard mode and 10 min in fast mode. Each cycle handles resource production, population tax, route income, wages, upkeep, commander training and university research.
- **Currencies:** credits belong to the player, and resources are a single generic stock per planet.
- **Planets:** a planet has 9 active buildings (generator, refinery, two shipyards, technosphere, commercial platform, storage, recycling, spatioport). Its base type (colony, commercial, military, capital) caps building levels. A player can hold at most 15 planets.
- **Research:** two layers. University sciences are funded with credits every cycle, and technologies are researched in the technosphere queue.
- **Military:** there are 12 ship types. Commanders have one squadron per level, each up to 100 PEV, arranged on battle lines. Combat is automatic and round-based between commanders, with line activation, dodging and log-scaled damage. There is no planetary defence.
- **Missions:** move, loot, colonise, conquer. Spying is paid in credits and countered by anti-spy investment.
- **Factions:** 5 factions with bonuses, each with a senate, a government, laws, and elections, divine designation or coups depending on its regime. Diplomacy is done through laws. Sector control gives tax income and victory points; the first faction to 2000 points wins.

## Ongoing work

- **Fleet combat redesign** (branch `feature/fleet-composition`): see [`docs/fleet-combat/plan.md`](docs/fleet-combat/plan.md). It holds the vision, the open questions, the roadmap and the decision log. Keep it updated as decisions are made.

## Game design references

Give game-design feedback **only when the maintainer asks for it**, to save tokens. When asked, use these references:

- **Jesse Schell, *The Art of Game Design: A Book of Lenses*.** This is the primary reference. Use its lenses: balance, meaningful choice, economy, goals, risk, interest curve, competition and cooperation, the elemental tetrad.
- **Raph Koster, *A Theory of Fun for Game Design*.** Fun as learning and mastering patterns.
- **Katie Salen & Eric Zimmerman, *Rules of Play*.** Formal systems, meaningful play, emergence.
- **Ernest Adams & Joris Dormans, *Game Mechanics: Advanced Game Design*.** Economies, feedback loops and internal economy modelling (Machinations). Directly relevant to Kalaxia's resources, credits and upkeep.
- **Hunicke, LeBlanc & Zubek, "MDA: A Formal Approach to Game Design and Game Research".** Mechanics, Dynamics, Aesthetics.
- **Richard Bartle, "Hearts, Clubs, Diamonds, Spades: Players Who Suit MUDs"** and **Quantic Foundry's gamer motivation model.** Player types in a persistent multiplayer world.
- **Sid Meier, "interesting decisions",** and **Soren Johnson's Designer Notes** (4X and strategy design).
- **Daniel Cook, "The Chemistry of Game Design"** (skill atoms and loops) and **Lost Garden essays** on game economies.
- **Elinor Ostrom-style thinking and EVE Online postmortems** for player-driven economies, politics and faction governance.

## Tech stack

- **PHP 8.4 / Symfony 8**: full-stack app, server-rendered.
- **Doctrine ORM 3** on **MariaDB 10**. Mappings live in `config/doctrine/<module>/*.orm.xml` (XML, not attributes).
- **Symfony Messenger** over **RabbitMQ** (async jobs) + **Symfony Scheduler** (recurring game ticks).
- **Redis** for cache and locks. **Mercure** for real-time push.
- **Front**: Twig + **Symfony UX** (Twig Components, Live Components, Turbo, Stimulus), **Tailwind CSS 4** via `symfonycasts/tailwind-bundle`, **AssetMapper/importmap** (no Webpack/Node build). Chart.js for charts.
- **Tooling**: PHPUnit 13, Zenstruck Foundry (fixtures/factories), PHPStan (level 0 + baseline), PHP-CS-Fixer, Rector.
- Docker Compose services: `phpfpm` (container `kalaxia_app`), `mariadb`, `redis`, `rabbitmq`, `caddy`, `mercure`, and a `supervisord` worker (`kalaxia_worker`) that consumes Messenger queues.

## Architecture: modules named after Greek gods

All game code lives in `src/Modules/<Module>/` under the namespace `App\Modules\<Module>\`:

| Module | Domain |
|---|---|
| **Ares** | War: commanders, fleets/squadrons, ships, combat (fight handlers, reports), movements, conquest, loot. The combat simulator uses `VirtualCommander`. |
| **Artemis** | Espionage: spy reports. |
| **Athena** | Planet economy: buildings & building queues, ship queues, commercial routes, trade/transactions, recycling, taxes. |
| **Atlas** | Rankings (player and faction). |
| **Demeter** | Factions (`Color` entity): laws, senate, government, elections, forum, political mandates (Symfony Workflow). See `docs/factions/politics.md`. |
| **Galaxy** | The map: sectors, systems, places, planets (incl. asteroids, gas planets, ruins…), galaxy generation. |
| **Hephaistos** | Infrastructure "forge": DB population command (`app:hephaistos:populate-database`), game schedule (`GameScheduleProvider`), daily routine. |
| **Hermes** | Communication: notifications, conversations, news, roadmap. |
| **Portal** | Out-of-game portal: `User` accounts, security, combat simulator page. |
| **Promethee** | Research and technologies, technology queues. |
| **Travel** | Travel types (shared movement concepts). |
| **Zeus** | Players: `Player`, bonuses, credits, financial reports, wages. |
| **Shared** | Cross-module kernel: shared Twig components (Atoms/Molecules/Organisms), Doctrine helpers, server time mode, services. |

Other places:
- `src/Shared/`: lower-level shared code: `AsyncMessage` / `AsyncHighPriorityMessage` marker interfaces, the Specification pattern, `DurationHandler`, base events, `DurationInterface` / `QueueableInterface` / `TravellerInterface`.
- `src/Classes/`, `src/action/`, `src/script/`: **legacy** Asylamba code (`Game`, `Chronos`, `Format`, `Utils`…). Leave it alone unless asked. Move logic into modules when you refactor.
- `config/game/`: game data as YAML (factions & regimes, faction bonuses, ship categories).
- `config/services.yaml` imports each module's `Resource/config/config.yml`, which holds its parameters (game coefficients) and service wiring.

### Module internals

Modules are halfway through a migration from a legacy layout to a DDD/hexagonal one, so both coexist:

- **Target layout (use this for new code):**
  - `Domain/`: entities (`Domain/Entity` or `Domain/Model`), enums, repository **interfaces**, specifications, domain events and services.
  - `Application/`: message handlers, factories, event listeners, workflows, use-case services.
  - `Infrastructure/`: controllers (one invokable class per action, routes declared as attributes), Doctrine repositories, Forms, Validators/DTOs, Twig components, console commands, DataFixtures (Foundry factories and stories).
  - `Ui/`: only some modules have it (Galaxy).
- **Legacy layout (still widespread):** `Model/`, `Manager/` (fat services such as `CommanderManager`), `Handler/`, `Message/`, `Repository/`, `Helper/`, `Resource/` (static data + config).

### Game-loop mechanics

- **Recurring ticks**: `Hephaistos/Infrastructure/Schedule/GameScheduleProvider.php` (schedule `game`) sends hourly messages (credits, commander XP, planet updates) and nightly ones (daily routine, rankings). The frequency depends on `SERVER_TIME_MODE` (`standard` = hourly / 3am, `fast` = every 10 min / hourly).
- **Timed events** (travel arrival, end of a building/ship/tech queue…) are Messenger messages dispatched with a delay. The handler (e.g. `Ares/Handler/CommanderTravelHandler`) resolves the event when it arrives.
- A message goes to the `async` or `async_high_priority` transport depending on whether it implements `AsyncMessage` or `AsyncHighPriorityMessage`. In tests, both transports are `in-memory://`.
- Server time settings come from env vars: `SERVER_START_TIME`, `SERVER_SEGMENT_SHIFT`, `SERVER_TIME_MODE`.
- The project loads a compiled `.env.local.php`, which takes precedence over `.env`. Give every new env var a default with an `env(NAME)` parameter, or run `composer dump-env dev`.

## Front-end conventions

- Use **Atomic design** for Twig components: `templates/components/{Atoms,Molecules,Organisms}/<Domain>/…`. The PHP classes sit in `src/Modules/<Module>/Infrastructure/Twig/Components/{Atoms,Molecules,Organisms}/`.
  - Default namespace mapping (`config/packages/twig_component.yaml`): `App\Modules\Shared\Infrastructure\Twig\Components\` → `components/`, and Demeter → `components/Faction/`. Components in other modules set `name:` and `template:` explicitly in `#[AsTwigComponent]` / `#[AsLiveComponent]`.
  - Anonymous components (template only) are also resolved from `templates/components/`.
- Interactive UI should use **Live Components** (`#[LiveProp]`, `#[LiveAction]`, `#[LiveListener]` + `emit`) rather than custom JS. Only add Stimulus controllers (`assets/controllers/`) for purely client-side behaviour.
- Pages are under `templates/pages/`, layouts under `templates/layouts/`. Older partials remain in `templates/{atoms,molecules,blocks}` and lowercase `templates/components/<feature>/`.
- Styling is Tailwind utility classes. Custom CSS goes in `assets/styles/app.css`.
- **Art direction: the faction colour dominates.**
  - The colour of the player's faction is the dominant colour of the interface.
  - On screens showing several factions (battles, maps, rankings), the faction colour is what tells the player who owns an element: squadrons, shots, fleet areas, labels.
  - Use the faction colour variables from `assets/styles/faction_colors/all.css` (`--color-light-N`, `--color-medium-N`, `--color-dark-N`, and the matching `text-/bg-/border-` classes), not ad-hoc palettes.
  - Do not introduce other colour codes for categories (e.g. ship roles): ship types will be identified by **pictograms**. Until they exist, use text (labels, tooltips).
  - Keep text readable on light faction colours (e.g. Falkirr yellow) with a dark outline or shadow.
- Translations: `translations/messages+intl-icu.fr.yaml`. The game UI is in **French**.
- **Compiled assets:** the local setup keeps compiled assets in `public/assets` (gitignored). When that directory exists, Symfony serves these frozen files even in dev, so changes to `assets/` (Stimulus controllers, CSS) and new Tailwind classes in templates are invisible until a rebuild. After any front-end change, run in the container:
  ```bash
  php bin/console tailwind:build
  php bin/console asset-map:compile
  ```
  Symptom of stale assets: a new Stimulus controller does nothing (missing from the compiled `controllers-*.js`), or new classes have no effect.

## Code conventions

- **Code, comments and commit messages in English.** UI text is in French.
- **Tabs for indentation** (PHP-CS-Fixer: `@Symfony` + `@PSR2`, tab indent). Run the fixer before committing.
- `declare(strict_types=1);` everywhere. Use constructor property promotion, `readonly` classes for services and handlers, enums over constants for new code, and PHP 8 attributes (`#[AsMessageHandler]`, `#[Route]`, `#[AsLiveComponent]`…).
- Entities use public typed properties (e.g. `$commander->travelType`) and `Uuid` ids (`symfony/uid`).
- Depend on repository **interfaces** from `Domain/Repository`, not on Doctrine implementations.
- Every change should come with tests (CONTRIBUTING.md). Game-feature changes are discussed with the maintainer first.

## Tests

- `tests/Modules/…` and `tests/Unit`: unit tests. `tests/Integration/Modules/…`: kernel/DB tests with Foundry stories from `tests/Support/DataFixtures`. `tests/Functional/…`: HTTP and workflow tests (e.g. elections).
- Run them with `make test` (= `php vendor/bin/phpunit`), inside the container if needed.

## Useful commands

```bash
docker compose up -d
make cli                                              # shell into kalaxia_app
symfony composer install
symfony console importmap:install
symfony console doctrine:schema:update --force --complete
symfony console app:hephaistos:populate-database      # generate a new game (galaxy, factions, players)
symfony console tailwind:build --watch                # when working on UI
make consume_async                                    # messenger:consume async
make consume_game_scheduler                           # messenger:consume scheduler_game
make test
symfony console app:ares:simulate-battles --a=LightFighter:50,Corvette:14 --b=Bomber:25 --runs=100   # balance harness of the new battle system
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse
```

Inside the container, replace `symfony console` with `php bin/console`.

## Git workflow

- **Agents never commit or push.** The maintainer commits all work themselves. Leave changes in the working tree.

- Main branch: `master`. Feature branches are named `feature/<topic>`, refactors `refacto/<topic>`. PRs go to `Kalaxia/…` on GitHub.
- Commit messages: imperative, English, describing one change (e.g. "Implement Combat Simulator page with…", "Hotfix …").
