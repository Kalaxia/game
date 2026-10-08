import { factionColor } from './battlefield_view.js';

/**
 * Timeline of a replay (D5): the remaining PEV of every fleet over the turns, side A above the axis
 * and side B below, stacked by fleet in faction colours. The key moments of the report are markers,
 * and the current turn is a cursor. Clicking or dragging on the timeline seeks the replay.
 *
 * The chart shows at a glance who leads, when reinforcements arrive and when a side collapses,
 * so that a long battle can be skimmed.
 */
export class BattleTimelineView
{
	/**
	 * @param {HTMLElement} container
	 * @param {object} battle the simulation response
	 * @param {object[]} snapshots the state after each turn, snapshots[0] being the deployment
	 * @param {object} options { onSeek(turn), describeHighlight(highlight), escape(text) }
	 */
	constructor(container, battle, snapshots, options)
	{
		this.container = container;
		this.battle = battle;
		this.snapshots = snapshots;
		this.options = options;
		this.lastTurn = snapshots.length - 1;
		this.dragging = false;

		this._render();
	}

	setTurn(turn)
	{
		this.container.querySelector('[data-timeline-cursor]').style.left = `${this._position(turn)}%`;
		this.container.querySelector('[data-timeline-chart]').setAttribute('aria-valuenow', turn);
	}

	destroy()
	{
		this.container.innerHTML = '';
	}

	_render()
	{
		const { escape } = this.options;

		this.container.innerHTML = `
			<div class="relative h-24 cursor-pointer select-none rounded-md bg-black/20 outline-none focus-visible:ring-2 focus-visible:ring-white/40"
				data-timeline-chart tabindex="0" role="slider" aria-label="Chronologie de la bataille"
				aria-valuemin="0" aria-valuemax="${this.lastTurn}" aria-valuenow="0">
				<svg class="absolute inset-0 size-full" viewBox="0 -1 ${Math.max(1, this.lastTurn)} 2" preserveAspectRatio="none" aria-hidden="true">
					${this._renderAreas()}
					<line x1="0" y1="0" x2="${Math.max(1, this.lastTurn)}" y2="0" stroke="white" stroke-opacity="0.3" vector-effect="non-scaling-stroke" />
				</svg>
				<span class="pointer-events-none absolute left-2 top-1 text-xs text-gray-400">Camp A</span>
				<span class="pointer-events-none absolute bottom-1 left-2 text-xs text-gray-400">Camp B</span>
				<div class="pointer-events-none absolute inset-y-0 w-0.5 -translate-x-1/2 bg-white shadow-[0_0_4px_black]" data-timeline-cursor style="left: 0%"></div>
			</div>
			<div class="relative mt-1 h-5" aria-label="Moments clés">
				${this._groupHighlights().map(([turn, highlights]) => {
					const title = highlights.map(highlight => this.options.describeHighlight(highlight)).join('\n');

					return `
						<button type="button"
							class="absolute top-0 -translate-x-1/2 px-0.5 text-sm leading-none text-gray-300 hover:text-white"
							style="left: ${this._position(turn)}%"
							title="Tour ${turn} : ${escape(title)}"
							data-timeline-turn="${turn}">◆</button>
					`;
				}).join('')}
			</div>
		`;

		const chart = this.container.querySelector('[data-timeline-chart]');

		chart.addEventListener('pointerdown', event => {
			this.dragging = true;
			chart.setPointerCapture(event.pointerId);
			this._seekAt(event.clientX);
		});
		chart.addEventListener('pointermove', event => {
			if (this.dragging) {
				this._seekAt(event.clientX);
			}
		});
		chart.addEventListener('pointerup', () => { this.dragging = false; });
		chart.addEventListener('pointercancel', () => { this.dragging = false; });
		chart.addEventListener('keydown', event => {
			const current = Number(chart.getAttribute('aria-valuenow'));
			const turn = { ArrowLeft: current - 1, ArrowRight: current + 1, Home: 0, End: this.lastTurn }[event.key];

			if (turn !== undefined) {
				event.preventDefault();
				this.options.onSeek(Math.max(0, Math.min(this.lastTurn, turn)));
			}
		});

		for (const marker of this.container.querySelectorAll('[data-timeline-turn]')) {
			marker.addEventListener('click', () => this.options.onSeek(Number(marker.dataset.timelineTurn)));
		}
	}

	/**
	 * One stacked area per fleet, its height being the fleet's PEV still in the battle
	 * (fleets that fled leave the chart), relative to the strongest side at any turn.
	 */
	_renderAreas()
	{
		const series = this.battle.fleets.map(fleet => this.snapshots.map(snapshot => this._fleetPev(snapshot, fleet.id)));
		const sideTotals = this.snapshots.map((snapshot, turn) => [0, 1].map(side => this.battle.fleets
			.reduce((total, fleet, index) => total + (fleet.side === side ? series[index][turn] : 0), 0)));
		const max = Math.max(1, ...sideTotals.flat());
		const stacks = this.snapshots.map(() => [0, 0]);

		return this.battle.fleets.map((fleet, index) => {
			const direction = fleet.side === 0 ? -1 : 1;
			const lower = stacks.map(stack => stack[fleet.side]);
			const upper = lower.map((base, turn) => base + series[index][turn] / max);
			upper.forEach((value, turn) => { stacks[turn][fleet.side] = value; });

			if (series[index].every(pev => pev === 0)) {
				return '';
			}

			const points = [
				...upper.map((value, turn) => `${turn},${direction * value}`),
				...lower.map((value, turn) => `${turn},${direction * value}`).reverse(),
			];

			return `<polygon points="${points.join(' ')}"
				fill="${factionColor(fleet.faction, 'medium')}" fill-opacity="0.85"
				stroke="${factionColor(fleet.faction, 'light')}" stroke-width="1" vector-effect="non-scaling-stroke" />`;
		}).join('');
	}

	_fleetPev(snapshot, fleetId)
	{
		if (snapshot.fleets[fleetId]?.status === 'fled') {
			return 0;
		}

		return Object.values(snapshot.squadrons)
			.filter(squadron => squadron.fleet === fleetId && !squadron.fled)
			.reduce((total, squadron) => total + squadron.ships * this.battle.ship_types[squadron.type].pev, 0);
	}

	/**
	 * @returns {Array<[number, object[]]>} the highlights grouped by turn
	 */
	_groupHighlights()
	{
		const groups = new Map();

		for (const highlight of this.battle.report?.highlights ?? []) {
			groups.set(highlight.turn, [...(groups.get(highlight.turn) ?? []), highlight]);
		}

		return [...groups.entries()];
	}

	_seekAt(clientX)
	{
		const bounds = this.container.querySelector('[data-timeline-chart]').getBoundingClientRect();
		const ratio = Math.max(0, Math.min(1, (clientX - bounds.left) / bounds.width));

		this.options.onSeek(Math.round(ratio * this.lastTurn));
	}

	_position(turn)
	{
		return this.lastTurn === 0 ? 0 : turn / this.lastTurn * 100;
	}
}
