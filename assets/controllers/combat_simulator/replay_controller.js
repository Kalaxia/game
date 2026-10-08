import { Controller } from '@hotwired/stimulus';
import { BattlefieldView, factionColor } from './battlefield_view.js';
import { BattleReportView } from './battle_report_view.js';
import { BattleTimelineView } from './battle_timeline_view.js';

/**
 * Runs a combat simulation on the server and replays its event log turn by turn,
 * on the hex battlefield (BattlefieldView) and as a list of events.
 *
 * The state of every squadron and fleet is rebuilt from the events, so that any turn can be displayed.
 * Moving forward by one turn is animated; jumping to a turn displays its final state.
 * Step 0 is the deployment: the fleets present from the start, in their formation as composed.
 *
 * To skim a long battle (D5): a timeline of the forces with the key moments, buttons to jump between
 * key moments, and an option to play calm turns (no ship lost, no fleet event) four times faster.
 *
 * In real-time mode (Phase 6), the server resolves a turn every turn duration and pushes it through
 * Mercure: the viewer follows the last turn unless the player went back in the replay, and the player
 * can send reinforcements. After (re)connecting, the battle so far is fetched to catch up.
 */
export default class extends Controller
{
	static targets = ['seed', 'runButton', 'liveButton', 'error', 'output'];
	static values = {
		runUrl: String,
		liveUrl: String,
	};

	static SPEEDS = [
		{ label: '1 tour / s', delay: 1000 },
		{ label: '2 tours / s', delay: 500 },
		{ label: '5 tours / s', delay: 200 },
		{ label: 'Temps réel', delay: null },
	];

	initialize()
	{
		this.battle = null;
		this.snapshots = [];
		this.currentTurn = 0;
		this.timer = null;
		this.speed = 0;
		this.view = null;
		this.timeline = null;
		this.fastCalmTurns = true;
		this.live = null;
		this.eventSource = null;
	}

	disconnect()
	{
		this.pause();
		this._closeLive();
		this.view?.destroy();
		this.timeline?.destroy();
	}

	async run()
	{
		await this._start(this.runUrlValue);
	}

	async runLive()
	{
		const data = await this._start(this.liveUrlValue);

		if (data?.live) {
			this._subscribe();
		}
	}

	/**
	 * Sends the selected simulator fleet into the running battle.
	 */
	async sendReinforcement()
	{
		const fleet = this.outputTarget.querySelector('[data-live-reinforcement-fleet]').value;
		const travelTurns = parseInt(this.outputTarget.querySelector('[data-live-reinforcement-turns]').value, 10) || 0;
		const feedback = this.outputTarget.querySelector('[data-live-reinforcement-feedback]');
		const response = await fetch(`${this.liveUrlValue}/${this.live.id}/reinforcements`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
			credentials: 'include',
			body: JSON.stringify({ fleet, travel_turns: travelTurns }),
		});
		const data = await response.json();
		const name = this.live.reinforcements.find(candidate => candidate.id === fleet)?.name ?? '';

		feedback.textContent = response.ok
			? `${name} arrive ${data.travel_turns === 0 ? 'au prochain tour' : `dans ${data.travel_turns + 1} tours`}.`
			: (data.error ?? 'Le renfort n’a pas pu être envoyé.');
	}

	togglePlay()
	{
		this.timer === null ? this.play() : this.pause();
	}

	play()
	{
		if (this.currentTurn >= this.battle.turns.length) {
			this.currentTurn = 0;
		}

		const step = () => {
			if (this.currentTurn >= this.battle.turns.length) {
				this.pause();

				return;
			}
			this.currentTurn++;
			this._renderTurn(true);
			this.timer = setTimeout(step, this._turnDelay(this.currentTurn));
		};
		this.timer = setTimeout(step, this._turnDelay(this.currentTurn + 1));
		this._renderControls();
	}

	pause()
	{
		clearTimeout(this.timer);
		this.timer = null;

		if (this.battle !== null) {
			this._renderControls();
		}
	}

	first() { this._goTo(0); }
	previous() { this._goTo(this.currentTurn - 1); }
	next() { this._goTo(this.currentTurn + 1, true); }
	last() { this._goTo(this.battle.turns.length); }

	previousMoment()
	{
		const turn = this._momentTurns().filter(turn => turn < this.currentTurn).pop();
		this._goTo(turn ?? 0);
	}

	nextMoment()
	{
		const turn = this._momentTurns().find(turn => turn > this.currentTurn);
		this._goTo(turn ?? this.battle.turns.length);
	}

	toggleFastCalmTurns(event)
	{
		this.fastCalmTurns = event.currentTarget.checked;

		if (this.timer !== null) {
			this.pause();
			this.play();
		}
	}

	/**
	 * Jumps the replay to a turn mentioned in the report, and brings the battlefield into view.
	 */
	showTurn(event)
	{
		this.pause();
		this._goTo(event.params.turn);
		this.outputTarget.querySelector('[data-replay-battlefield]').scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	zoomOut()
	{
		this.view.zoomTo(null);
	}

	changeSpeed(event)
	{
		this.speed = parseInt(event.currentTarget.value, 10);

		if (this.timer !== null) {
			this.pause();
			this.play();
		}
	}

	reuseSeed()
	{
		this.seedTarget.value = this.battle.seed;
	}

	async _start(url)
	{
		this.pause();
		this._closeLive();
		this.errorTarget.classList.add('hidden');
		this.runButtonTarget.disabled = true;
		this.liveButtonTarget.disabled = true;

		try {
			const seed = parseInt(this.seedTarget.value, 10);
			const response = await fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
				credentials: 'include',
				body: JSON.stringify(Number.isInteger(seed) ? { seed } : {}),
			});
			const data = await response.json();

			if (!response.ok) {
				throw new Error(data.error ?? 'La simulation a échoué.');
			}

			this._load(data, 0);

			return data;
		} catch (error) {
			this.errorTarget.textContent = error.message;
			this.errorTarget.classList.remove('hidden');

			return null;
		} finally {
			this.runButtonTarget.disabled = false;
			this.liveButtonTarget.disabled = false;
		}
	}

	_load(data, turn)
	{
		this.battle = data;
		this.live = data.live ?? null;
		this.snapshots = this._buildSnapshots(data);
		this.currentTurn = Math.min(turn, data.turns.length);
		this._render();
	}

	/**
	 * Follows the live updates of the battle. The battle so far is fetched on every (re)connection,
	 * in case turns were resolved meanwhile.
	 */
	_subscribe()
	{
		const url = new URL(this.live.hub_url);
		url.searchParams.append('topic', this.live.topic);

		this.eventSource = new EventSource(url, { withCredentials: true });
		this.eventSource.onopen = () => this._catchUp();
		this.eventSource.onmessage = event => this._receive(JSON.parse(event.data));
	}

	_closeLive()
	{
		this.eventSource?.close();
		this.eventSource = null;
	}

	async _catchUp()
	{
		const response = await fetch(`${this.liveUrlValue}/${this.live.id}`, { headers: { 'Accept': 'application/json' }, credentials: 'include' });

		if (!response.ok || this.battle?.live?.id !== this.live.id) {
			return;
		}

		const data = await response.json();
		const following = this._isFollowing();

		if (data.turns.length > this.battle.turns.length || data.result) {
			this._load(data, following ? data.turns.length : this.currentTurn);
		}
		if (data.live.is_over) {
			this._closeLive();
		}
	}

	/**
	 * A turn pushed by the server: the fleets that joined during it, and the result once the battle is over.
	 */
	_receive(update)
	{
		const expected = this.battle.turns.length + 1;

		if (update.turn.turn < expected) {
			return;
		}
		if (update.turn.turn > expected) {
			this._catchUp();

			return;
		}

		const following = this._isFollowing();

		for (const fleet of update.fleets) {
			if (!this.battle.fleets.some(known => known.id === fleet.id)) {
				this.battle.fleets.push(fleet);
			}
		}
		Object.assign(this.battle.squadron_life, update.squadron_life);
		this.battle.turns.push(update.turn);
		this.snapshots = this._buildSnapshots(this.battle);

		if (update.result) {
			this.battle.result = update.result;
			this.battle.report = update.report;
			this.live.is_over = true;
			this._closeLive();
			if (following) {
				this.currentTurn = this.battle.turns.length - 1;
			}
			this._render();
			if (following) {
				this.next();
			}

			return;
		}

		this._renderLiveStatus();
		this._renderTimeline();
		if (following) {
			this.next();
		} else {
			this._renderControls();
		}
	}

	/**
	 * The viewer follows the battle when it shows the last turn received and is not replaying.
	 */
	_isFollowing()
	{
		return this.timer === null && this.currentTurn >= this.battle.turns.length;
	}

	_goTo(turn, animated = false)
	{
		const target = Math.max(0, Math.min(turn, this.battle.turns.length));
		const isNextTurn = target === this.currentTurn + 1;

		this.currentTurn = target;
		this._renderTurn(animated && isNextTurn);
	}

	/**
	 * Playback delay of a turn; calm turns go four times faster when the option is on.
	 */
	_turnDelay(turn = this.currentTurn)
	{
		const delay = this.constructor.SPEEDS[this.speed].delay ?? this.battle.turn_duration * 1000;

		return this.fastCalmTurns && this._isCalm(turn) ? delay / 4 : delay;
	}

	/**
	 * A calm turn has no ship lost and no fleet event: only moves and missed or harmless shots.
	 */
	_isCalm(turn)
	{
		const events = this.battle.turns[turn - 1]?.events;

		if (events === undefined) {
			return false;
		}

		return events.every(event => ['squadron_moved', 'fleet_moved', 'morale_updated'].includes(event.type)
			|| (['squadron_fired', 'squadron_splashed'].includes(event.type) && event.kills === 0));
	}

	_momentTurns()
	{
		return [...new Set((this.battle.report?.highlights ?? []).map(highlight => highlight.turn))].sort((a, b) => a - b);
	}

	/**
	 * State after each turn: snapshots[n] is the state at the end of turn n.
	 * snapshots[0] is the deployment of the fleets present from the start, before any movement.
	 * Each snapshot holds the squadrons (fleet, type, ships, cell) and the fleets (cluster).
	 */
	_buildSnapshots(data)
	{
		const state = { squadrons: {}, fleets: {} };
		const firstTurnEvents = data.turns[0]?.events ?? [];
		const deployment = { squadrons: {}, fleets: {} };

		for (const event of firstTurnEvents.filter(event => event.type === 'fleet_joined')) {
			this._applyEvent(deployment, event);
		}

		const snapshots = [structuredClone(deployment)];

		for (const turn of data.turns) {
			for (const event of turn.events) {
				this._applyEvent(state, event);
			}
			snapshots.push(structuredClone(state));
		}

		return snapshots;
	}

	_applyEvent(state, event)
	{
		switch (event.type) {

			case 'fleet_joined':
				state.fleets[event.fleet] = { cluster: event.cluster };
				for (const squadron of event.squadrons) {
					state.squadrons[squadron.squadron] = {
						fleet: event.fleet,
						type: squadron.type,
						ships: squadron.ships,
						initialShips: squadron.ships,
						cell: squadron.cell,
					};
				}
				break;
			case 'fleet_moved':
				state.fleets[event.fleet].cluster = event.to;
				for (const id of event.squadrons) {
					const cell = state.squadrons[id].cell;
					state.squadrons[id].cell = { q: cell.q + event.translation.q, r: cell.r + event.translation.r };
				}
				break;
			case 'squadron_moved':
				state.squadrons[event.squadron].cell = event.to;
				break;
			case 'squadron_fired':
			case 'squadron_splashed':
				state.squadrons[event.target].ships -= event.kills;
				break;
			case 'squadron_destroyed':
				state.squadrons[event.squadron].ships = 0;
				break;
			case 'fleet_withdrew':
				state.fleets[event.fleet].status = 'withdrawn';
				break;
			case 'fleet_remobilised':
				state.fleets[event.fleet].status = 'engaged';
				break;
			case 'fleet_fleeing':
				state.fleets[event.fleet].status = 'fleeing';
				break;
			case 'fleet_fled':
				state.fleets[event.fleet].status = 'fled';
				for (const id of event.squadrons) {
					state.squadrons[id].fled = true;
				}
				break;
			case 'morale_updated':
				for (const [fleetId, morale] of Object.entries(event.morale)) {
					if (state.fleets[fleetId]) {
						state.fleets[fleetId].morale = morale;
					}
				}
				break;
		}
	}

	_render()
	{
		const battle = this.battle;
		const isRunning = !battle.result;
		const winner = isRunning
			? 'Bataille en cours'
			: (battle.result.winning_side === null ? 'Aucun vainqueur' : `Victoire du camp ${this._sideName(battle.result.winning_side)}`);

		const report = new BattleReportView(battle, {
			escape: text => this._escape(text),
			sideName: side => this._sideName(side),
			fleetName: fleetId => this._fleetName(fleetId),
			squadronName: squadronId => this._squadronName(squadronId),
			typeName: type => this._typeName(type),
		});

		this.outputTarget.innerHTML = `
			<div class="rounded-lg bg-gray-800 p-4">
				<div class="flex flex-wrap items-baseline gap-x-6 gap-y-2">
					<p class="flex items-center gap-2 text-lg font-semibold">
						${isRunning ? '<span class="inline-block size-2.5 animate-pulse rounded-full bg-white" aria-hidden="true"></span>' : ''}
						${this._escape(winner)}
					</p>
					<p class="text-sm text-gray-400" data-replay-live-status></p>
					<p class="ml-auto text-sm text-gray-400">
						Graine ${battle.seed}
						<button type="button" class="ml-2 underline hover:text-white" data-action="combat-simulator--replay#reuseSeed">réutiliser</button>
					</p>
				</div>
				${isRunning ? this._renderReinforcementForm() : report.renderOverview()}
			</div>

			<div class="mt-6 rounded-lg bg-gray-800 p-4">
				<div data-replay-timeline></div>
				<div class="mt-3 flex flex-wrap items-center gap-3" data-replay-controls></div>

				<div class="mt-4">
					<div class="mb-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400">
						<span>Cliquez sur une flotte pour voir ses escadrilles · faites glisser pour vous déplacer · molette pour zoomer</span>
						<button type="button" class="hidden rounded-md px-2 py-1 text-gray-200 ring-1 ring-white/20 hover:bg-white/10" data-replay-zoom-out data-action="combat-simulator--replay#zoomOut">Vue d’ensemble</button>
						<span class="ml-auto flex flex-wrap gap-x-3 gap-y-1">
							${this._factionsInBattle().map(fleet => `<span class="flex items-center gap-1"><span class="inline-block size-2.5 rounded-sm border" style="background: ${factionColor(fleet.faction, 'medium')}; border-color: ${factionColor(fleet.faction, 'light')}"></span>${this._escape(fleet.faction_name)}</span>`).join('')}
						</span>
					</div>
					<div data-replay-battlefield></div>
				</div>

				<div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
					<div data-replay-fleets></div>
					<div>
						<h3 class="text-sm font-semibold text-gray-300">Tour <span data-replay-turn-number></span></h3>
						<p class="mt-1 text-xs text-gray-400" data-replay-summary></p>
						<ol class="mt-2 max-h-96 space-y-1 overflow-y-auto text-sm" data-replay-events></ol>
					</div>
				</div>
			</div>

			${isRunning ? '' : report.renderDetails()}
		`;

		this.view?.destroy();
		this.view = new BattlefieldView(this.outputTarget.querySelector('[data-replay-battlefield]'), battle, {
			onZoomChange: isZoomed => {
				this.outputTarget.querySelector('[data-replay-zoom-out]').classList.toggle('hidden', !isZoomed);
			},
			describeSquadron: (id, squadron) => this._describeSquadron(id, squadron),
		});

		this.report = report;
		this._renderLiveStatus();
		this._renderTimeline();

		this._renderTurn();
	}

	_renderTimeline()
	{
		this.timeline?.destroy();
		this.timeline = new BattleTimelineView(this.outputTarget.querySelector('[data-replay-timeline]'), this.battle, this.snapshots, {
			onSeek: turn => {
				this.pause();
				this._goTo(turn);
			},
			describeHighlight: highlight => this.report.describeHighlight(highlight),
			escape: text => this._escape(text),
		});
		this.timeline.setTurn(this.currentTurn);
	}

	_renderLiveStatus()
	{
		const turns = this.battle.result?.turns ?? this.battle.turns.length;
		const status = `${turns} tour${turns > 1 ? 's' : ''} · ${this._formatDuration(turns * this.battle.turn_duration)} de bataille`;

		this.outputTarget.querySelector('[data-replay-live-status]').textContent = this.battle.result
			? status
			: `${status} · un tour toutes les ${this.battle.turn_duration} s`;
	}

	_renderReinforcementForm()
	{
		const fleets = this.live?.reinforcements ?? [];

		if (fleets.length === 0) {
			return '';
		}

		return `
			<div class="mt-4 flex flex-wrap items-end gap-3 text-sm">
				<label class="flex flex-col gap-1 text-xs text-gray-400">
					Envoyer en renfort
					<select class="rounded-md bg-white/5 px-2 py-1 text-sm text-white" data-live-reinforcement-fleet>
						${fleets.map(fleet => `<option value="${this._escape(fleet.id)}">${this._escape(fleet.name)} (camp ${this._sideName(fleet.side)})</option>`).join('')}
					</select>
				</label>
				<label class="flex flex-col gap-1 text-xs text-gray-400">
					Trajet (tours)
					<input type="number" min="0" max="30" value="0" class="w-20 rounded-md bg-white/5 px-2 py-1 text-sm text-white" data-live-reinforcement-turns />
				</label>
				<button type="button" class="rounded-md px-3 py-1.5 ring-1 ring-white/30 hover:bg-white/10" data-action="combat-simulator--replay#sendReinforcement">Envoyer</button>
				<p class="text-xs text-gray-300" data-live-reinforcement-feedback aria-live="polite"></p>
			</div>
		`;
	}

	_renderTurn(animated = false)
	{
		this._renderControls();
		this._renderFleets();
		this._renderEvents();
		this._renderSummary();
		this.timeline.setTurn(this.currentTurn);

		const events = this.battle.turns[this.currentTurn - 1]?.events ?? [];
		if (animated) {
			// The animation fills the whole turn, so that real-time playback is continuous
			this.view.animateTurn(this.snapshots[this.currentTurn - 1], this.snapshots[this.currentTurn], events, this._turnDelay() * 0.95);
		} else {
			this.view.render(this.snapshots[this.currentTurn], events);
		}
	}

	_renderSummary()
	{
		const events = this.battle.turns[this.currentTurn - 1]?.events ?? [];
		const sideOf = squadronId => {
			const squadron = this.snapshots[this.snapshots.length - 1].squadrons[squadronId];

			return this.battle.fleets.find(fleet => fleet.id === squadron?.fleet)?.side;
		};
		const damage = [0, 0];
		const losses = [0, 0];

		for (const event of events.filter(event => ['squadron_fired', 'squadron_splashed'].includes(event.type))) {
			// Area damage on allies is not counted as damage dealt
			if (sideOf(event.attacker) !== sideOf(event.target)) {
				damage[sideOf(event.attacker)] += event.damage;
			}
			losses[sideOf(event.target)] += event.kills;
		}

		this.outputTarget.querySelector('[data-replay-turn-number]').textContent = this.currentTurn === 0 ? '0 (déploiement)' : this.currentTurn;
		this.outputTarget.querySelector('[data-replay-summary]').textContent = [0, 1]
			.map(side => `Camp ${this._sideName(side)} : ${damage[side].toLocaleString('fr-FR')} dégâts infligés, ${losses[side]} vaisseau${losses[side] > 1 ? 'x' : ''} perdu${losses[side] > 1 ? 's' : ''}`)
			.join(' · ');
	}

	_describeSquadron(id, squadron)
	{
		const fleet = this.battle.fleets.find(f => f.id === squadron.fleet);
		const life = this.battle.squadron_life[id];

		return [
			`${this._typeName(squadron.type)} × ${squadron.ships} / ${squadron.initialShips}`,
			`${fleet.name} (camp ${this._sideName(fleet.side)})`,
			`${this._roleName(this.battle.ship_types[squadron.type].role)}${life ? ` · ${life.toLocaleString('fr-FR')} PV par vaisseau` : ''}`,
		].join('\n');
	}

	_factionsInBattle()
	{
		return this.battle.fleets.filter((fleet, index, fleets) => fleets.findIndex(other => other.faction === fleet.faction) === index);
	}

	_roleName(role)
	{
		return {
			interceptor: 'Intercepteur',
			bomber: 'Bombardier',
			escort: 'Escorte',
			line: 'Ligne',
			artillery: 'Artillerie',
			capital: 'Capital',
		}[role] ?? role;
	}

	_renderControls()
	{
		const container = this.outputTarget.querySelector('[data-replay-controls]');
		const total = this.battle.turns.length;
		const button = (action, label, title) => `
			<button type="button" title="${title}" class="rounded-md px-2 py-1 ring-1 ring-white/20 hover:bg-white/10" data-action="combat-simulator--replay#${action}">${label}</button>
		`;

		container.innerHTML = `
			${button('first', '⏮', 'Premier tour')}
			${button('previous', '◀', 'Tour précédent')}
			${button('togglePlay', this.timer === null ? '▶ Lecture' : '⏸ Pause', 'Lecture / pause')}
			${button('next', '▶', 'Tour suivant')}
			${button('last', '⏭', 'Dernier tour')}
			${button('previousMoment', '◆ ◀', 'Moment clé précédent')}
			${button('nextMoment', '▶ ◆', 'Moment clé suivant')}
			<span class="grow text-sm text-gray-300">${this.currentTurn === 0 ? 'Déploiement' : `Tour ${this.currentTurn} / ${total}`} · ${this._formatDuration(this.currentTurn * this.battle.turn_duration)}</span>
			<select class="rounded-md bg-white/5 px-2 py-1 text-sm" aria-label="Vitesse" data-action="combat-simulator--replay#changeSpeed">
				${this.constructor.SPEEDS.map((speed, index) => `<option value="${index}" ${index === this.speed ? 'selected' : ''}>${speed.label}</option>`).join('')}
			</select>
			<label class="flex items-center gap-1.5 text-sm text-gray-300" title="Les tours sans perte ni événement de flotte défilent quatre fois plus vite">
				<input type="checkbox" ${this.fastCalmTurns ? 'checked' : ''} data-action="combat-simulator--replay#toggleFastCalmTurns" />
				Accélérer les tours calmes
			</label>
		`;
	}

	_renderFleets()
	{
		const snapshot = this.snapshots[this.currentTurn];
		const container = this.outputTarget.querySelector('[data-replay-fleets]');

		container.innerHTML = [0, 1].map(side => `
			<h3 class="text-sm font-semibold text-gray-300 ${side === 1 ? 'mt-4' : ''}">Camp ${this._sideName(side)}</h3>
			<ul class="mt-2 space-y-3">
				${this.battle.fleets.filter(fleet => fleet.side === side).map(fleet => {
					const squadrons = Object.values(snapshot.squadrons).filter(squadron => squadron.fleet === fleet.id);
					const pev = this._fleetPev(fleet.id, this.currentTurn);
					const initialPev = this._fleetPev(fleet.id, this.currentTurn, true);
					const ratio = initialPev > 0 ? Math.round(pev / initialPev * 100) : 0;
					const fleetState = snapshot.fleets[fleet.id] ?? {};
					const behaviour = { withdrawn: ' · en repli', fleeing: ' · en fuite', fled: ' · a quitté la bataille' }[fleetState.status] ?? '';
					const morale = fleetState.morale !== undefined && pev > 0 && fleetState.status !== 'fled' ? ` · moral ${fleetState.morale}` : '';
					const status = squadrons.length === 0
						? `arrive au tour ${fleet.arrival_turn}`
						: (pev === 0 ? 'détruite' : `${pev} / ${initialPev} PEV${morale}${behaviour}`);

					return `
						<li class="rounded-md bg-black/20 p-3">
							<div class="flex items-baseline justify-between gap-4 text-sm">
								<span class="font-medium">${this._escape(fleet.name)}</span>
								<span class="text-xs text-gray-400">${status}</span>
							</div>
							${(fleet.traits ?? []).length > 0 ? `<p class="mt-1 text-xs text-gray-400">${fleet.traits.map(trait => `<span title="${this._escape(trait.description)}">${this._escape(trait.name)}</span>`).join(', ')}</p>` : ''}
							<div class="mt-2 h-1.5 rounded bg-white/10"><div class="h-1.5 rounded bg-medium${fleet.faction || 1}" style="width: ${ratio}%"></div></div>
							<ul class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-300">
								${this._shipsByType(squadrons).map(([type, ships, initial]) => `
									<li class="${ships === 0 ? 'text-gray-500 line-through' : ''}">${ships}/${initial} ${this._escape(this._typeName(type))}</li>
								`).join('')}
							</ul>
						</li>
					`;
				}).join('')}
			</ul>
		`).join('');
	}

	_renderEvents()
	{
		const turn = this.battle.turns[this.currentTurn - 1];
		const container = this.outputTarget.querySelector('[data-replay-events]');
		const lines = [];
		let squadronMoves = 0;

		for (const event of turn?.events ?? []) {
			switch (event.type) {
				case 'fleet_joined':
					lines.push(['text-sky-300', `${this._fleetName(event.fleet)} entre dans la bataille (camp ${this._sideName(event.side)}).`]);
					break;
				case 'fleet_moved':
					lines.push(['text-gray-300', `${this._fleetName(event.fleet)} se rapproche de l’ennemi.`]);
					break;
				case 'squadron_moved':
					squadronMoves++;
					break;
				case 'squadron_fired':
					lines.push(['text-gray-200', `${this._squadronName(event.attacker)} tire sur ${this._squadronName(event.target)} : ${event.hits}/${event.shots} tirs au but, ${event.damage.toLocaleString('fr-FR')} dégâts${event.kills > 0 ? `, ${event.kills} vaisseau${event.kills > 1 ? 'x' : ''} détruit${event.kills > 1 ? 's' : ''}` : ''}.`]);
					break;
				case 'squadron_splashed':
					lines.push(['text-gray-400', `Les explosions de ${this._squadronName(event.attacker)} touchent ${this._squadronName(event.target)} : ${event.damage.toLocaleString('fr-FR')} dégâts${event.kills > 0 ? `, ${event.kills} vaisseau${event.kills > 1 ? 'x' : ''} détruit${event.kills > 1 ? 's' : ''}` : ''}.`]);
					break;
				case 'squadron_destroyed':
					lines.push(['text-red-300', `${this._squadronName(event.squadron)} est anéantie.`]);
					break;
				case 'fleet_destroyed':
					lines.push(['text-red-400 font-semibold', `La flotte ${this._fleetName(event.fleet)} est détruite.`]);
					break;
				case 'engagement_delayed':
					lines.push(['text-amber-200', `Les attaquants, en infériorité, retardent l’engagement, au plus tard jusqu’au tour ${event.until_turn} : personne ne tire.`]);
					break;
				case 'engagement_started':
					lines.push(['text-amber-200', 'L’engagement commence.']);
					break;
				case 'fleet_withdrew':
					lines.push(['text-orange-300', `${this._fleetName(event.fleet)} se replie derrière ses alliés (moral ${event.morale}).`]);
					break;
				case 'fleet_remobilised':
					lines.push(['text-sky-300', `${this._fleetName(event.fleet)} reprend le combat (moral ${event.morale}).`]);
					break;
				case 'fleet_fleeing':
					lines.push(['text-orange-400 font-semibold', `${this._fleetName(event.fleet)} prend la fuite (moral ${event.morale}) et reste exposée quelques tours.`]);
					break;
				case 'fleet_fled':
					lines.push(['text-orange-400 font-semibold', `${this._fleetName(event.fleet)} quitte la bataille avec ses vaisseaux restants.`]);
					break;
				case 'battle_ended':
					lines.push(['text-amber-300 font-semibold', event.winning_side === null ? 'Fin de la bataille, sans vainqueur.' : `Fin de la bataille : victoire du camp ${this._sideName(event.winning_side)}.`]);
					break;
			}
		}

		if (this.currentTurn === 0) {
			lines.push(['text-gray-400', 'Déploiement des flottes présentes dès le début, dans leur formation. Lancez la lecture pour commencer la bataille.']);
		}

		if (squadronMoves > 0) {
			lines.unshift(['text-gray-400', `${squadronMoves} escadrille${squadronMoves > 1 ? 's se déplacent' : ' se déplace'}.`]);
		}

		container.innerHTML = lines.length === 0
			? '<li class="text-gray-500">Aucun événement.</li>'
			: lines.map(([classes, text]) => `<li class="${classes}">${this._escape(text)}</li>`).join('');
	}

	_shipsByType(squadrons)
	{
		const ships = {};

		for (const squadron of squadrons) {
			ships[squadron.type] ??= [squadron.type, 0, 0];
			ships[squadron.type][1] += squadron.ships;
			ships[squadron.type][2] += squadron.initialShips;
		}

		return Object.values(ships);
	}

	_fleetPev(fleetId, turn, initial = false)
	{
		return Object.values(this.snapshots[turn].squadrons)
			.filter(squadron => squadron.fleet === fleetId)
			.reduce((total, squadron) => total + (initial ? squadron.initialShips : squadron.ships) * this.battle.ship_types[squadron.type].pev, 0);
	}

	_squadronName(squadronId)
	{
		const squadron = this.snapshots[this.snapshots.length - 1].squadrons[squadronId];

		return squadron ? `${this._typeName(squadron.type)} (${this._fleetName(squadron.fleet)})` : squadronId;
	}

	_fleetName(fleetId)
	{
		return this.battle.fleets.find(fleet => fleet.id === fleetId)?.name ?? fleetId;
	}

	_typeName(type)
	{
		return this.battle.ship_types[type]?.name ?? type;
	}

	_sideName(side)
	{
		return side === 0 ? 'A' : 'B';
	}

	_formatDuration(seconds)
	{
		const minutes = Math.floor(seconds / 60);

		return `${minutes} min ${String(seconds % 60).padStart(2, '0')} s`;
	}

	_escape(text)
	{
		const element = document.createElement('span');
		element.textContent = String(text);

		return element.innerHTML;
	}
}
