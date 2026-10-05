import { Controller } from '@hotwired/stimulus';

/**
 * Runs a combat simulation on the server and replays its event log turn by turn.
 *
 * The state of every squadron is rebuilt from the events, so that any turn can be displayed.
 */
export default class extends Controller
{
	static targets = ['seed', 'runButton', 'error', 'output'];
	static values = {
		runUrl: String,
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
	}

	disconnect()
	{
		this.pause();
	}

	async run()
	{
		this.pause();
		this.errorTarget.classList.add('hidden');
		this.runButtonTarget.disabled = true;

		try {
			const seed = parseInt(this.seedTarget.value, 10);
			const response = await fetch(this.runUrlValue, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
				credentials: 'include',
				body: JSON.stringify(Number.isInteger(seed) ? { seed } : {}),
			});
			const data = await response.json();

			if (!response.ok) {
				throw new Error(data.error ?? 'La simulation a échoué.');
			}

			this.battle = data;
			this.snapshots = this._buildSnapshots(data);
			this.currentTurn = data.turns.length > 0 ? 1 : 0;
			this._render();
		} catch (error) {
			this.errorTarget.textContent = error.message;
			this.errorTarget.classList.remove('hidden');
		} finally {
			this.runButtonTarget.disabled = false;
		}
	}

	togglePlay()
	{
		this.timer === null ? this.play() : this.pause();
	}

	play()
	{
		if (this.currentTurn >= this.battle.turns.length) {
			this.currentTurn = 1;
		}

		const delay = this.constructor.SPEEDS[this.speed].delay ?? this.battle.turn_duration * 1000;
		this.timer = setInterval(() => {
			if (this.currentTurn >= this.battle.turns.length) {
				this.pause();

				return;
			}
			this.currentTurn++;
			this._renderTurn();
		}, delay);
		this._renderControls();
	}

	pause()
	{
		clearInterval(this.timer);
		this.timer = null;

		if (this.battle !== null) {
			this._renderControls();
		}
	}

	first() { this._goTo(1); }
	previous() { this._goTo(this.currentTurn - 1); }
	next() { this._goTo(this.currentTurn + 1); }
	last() { this._goTo(this.battle.turns.length); }

	seek(event)
	{
		this._goTo(parseInt(event.currentTarget.value, 10));
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

	_goTo(turn)
	{
		this.currentTurn = Math.max(1, Math.min(turn, this.battle.turns.length));
		this._renderTurn();
	}

	/**
	 * State of every squadron after each turn: snapshots[n] is the state at the end of turn n.
	 */
	_buildSnapshots(data)
	{
		const squadrons = {};
		const snapshots = [structuredClone(squadrons)];

		for (const turn of data.turns) {
			for (const event of turn.events) {
				switch (event.type) {
					case 'fleet_joined':
						for (const squadron of event.squadrons) {
							squadrons[squadron.squadron] = {
								fleet: event.fleet,
								type: squadron.type,
								ships: squadron.ships,
								initialShips: squadron.ships,
							};
						}
						break;
					case 'squadron_fired':
						squadrons[event.target].ships -= event.kills;
						break;
					case 'squadron_destroyed':
						squadrons[event.squadron].ships = 0;
						break;
				}
			}
			snapshots.push(structuredClone(squadrons));
		}

		return snapshots;
	}

	_render()
	{
		const battle = this.battle;
		const winner = battle.result.winning_side === null
			? 'Aucun vainqueur'
			: `Victoire du camp ${this._sideName(battle.result.winning_side)}`;

		this.outputTarget.innerHTML = `
			<div class="rounded-lg bg-gray-800 p-4">
				<div class="flex flex-wrap items-baseline gap-x-6 gap-y-2">
					<p class="text-lg font-semibold">${this._escape(winner)}</p>
					<p class="text-sm text-gray-400">
						${battle.result.turns} tours · ${this._formatDuration(battle.result.turns * battle.turn_duration)} de bataille
					</p>
					<p class="ml-auto text-sm text-gray-400">
						Graine ${battle.seed}
						<button type="button" class="ml-2 underline hover:text-white" data-action="combat-simulator--replay#reuseSeed">réutiliser</button>
					</p>
				</div>
				<table class="mt-4 w-full text-left text-sm">
					<thead class="text-xs text-gray-400">
						<tr><th class="py-1">Flotte</th><th>Camp</th><th>PEV restants</th><th>Expérience de l’officier</th></tr>
					</thead>
					<tbody>
						${battle.fleets.map(fleet => `
							<tr class="border-t border-white/10">
								<td class="py-1">${this._escape(fleet.name)}</td>
								<td>${this._sideName(fleet.side)}</td>
								<td>${this._fleetPev(fleet.id, this.snapshots.length - 1)} / ${this._fleetPev(fleet.id, this.snapshots.length - 1, true)}</td>
								<td>${(battle.result.experience[fleet.id] ?? 0).toLocaleString('fr-FR')} XP</td>
							</tr>
						`).join('')}
					</tbody>
				</table>
			</div>

			<div class="mt-6 rounded-lg bg-gray-800 p-4">
				<div class="flex flex-wrap items-center gap-3" data-replay-controls></div>
				<div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
					<div data-replay-fleets></div>
					<div>
						<h3 class="text-sm font-semibold text-gray-300">Événements du tour</h3>
						<ol class="mt-2 max-h-96 space-y-1 overflow-y-auto text-sm" data-replay-events></ol>
					</div>
				</div>
			</div>
		`;

		this._renderTurn();
	}

	_renderTurn()
	{
		this._renderControls();
		this._renderFleets();
		this._renderEvents();
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
			<input type="range" min="1" max="${total}" value="${this.currentTurn}" class="grow" aria-label="Tour" data-action="input->combat-simulator--replay#seek" />
			<span class="w-36 text-sm text-gray-300">Tour ${this.currentTurn} / ${total} · ${this._formatDuration(this.currentTurn * this.battle.turn_duration)}</span>
			<select class="rounded-md bg-white/5 px-2 py-1 text-sm" aria-label="Vitesse" data-action="combat-simulator--replay#changeSpeed">
				${this.constructor.SPEEDS.map((speed, index) => `<option value="${index}" ${index === this.speed ? 'selected' : ''}>${speed.label}</option>`).join('')}
			</select>
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
					const squadrons = Object.values(snapshot).filter(squadron => squadron.fleet === fleet.id);
					const pev = this._fleetPev(fleet.id, this.currentTurn);
					const initialPev = this._fleetPev(fleet.id, this.currentTurn, true);
					const ratio = initialPev > 0 ? Math.round(pev / initialPev * 100) : 0;
					const status = squadrons.length === 0
						? `arrive au tour ${fleet.arrival_turn}`
						: (pev === 0 ? 'détruite' : `${pev} / ${initialPev} PEV`);

					return `
						<li class="rounded-md bg-black/20 p-3">
							<div class="flex items-baseline justify-between gap-4 text-sm">
								<span class="font-medium">${this._escape(fleet.name)}</span>
								<span class="text-xs text-gray-400">${status}</span>
							</div>
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
				case 'squadron_destroyed':
					lines.push(['text-red-300', `${this._squadronName(event.squadron)} est anéantie.`]);
					break;
				case 'fleet_destroyed':
					lines.push(['text-red-400 font-semibold', `La flotte ${this._fleetName(event.fleet)} est détruite.`]);
					break;
				case 'battle_ended':
					lines.push(['text-amber-300 font-semibold', event.winning_side === null ? 'Fin de la bataille, sans vainqueur.' : `Fin de la bataille : victoire du camp ${this._sideName(event.winning_side)}.`]);
					break;
			}
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
		return Object.values(this.snapshots[turn])
			.filter(squadron => squadron.fleet === fleetId)
			.reduce((total, squadron) => total + (initial ? squadron.initialShips : squadron.ships) * this.battle.ship_types[squadron.type].pev, 0);
	}

	_squadronName(squadronId)
	{
		const squadron = this.snapshots[this.snapshots.length - 1][squadronId];

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
