/**
 * SVG view of a battle on the hex grid, with two zoom levels:
 * - battlefield: the 5×5 fleet areas (clusters) and the fleets standing on them;
 * - fleet: the cells of one fleet area, where squadrons stand.
 * Clicking a fleet zooms on it; the view can be dragged to move around and zoomed with the mouse wheel.
 *
 * Cells use the engine's axial coordinates, drawn with pointy-top hexagons.
 *
 * Art direction: the faction colour is the dominant colour and identifies who owns an element.
 * Squadrons, shots and fleet names use the faction colours (--color-medium-N, --color-light-N);
 * ship types are identified by pictograms, which turn toward the closest enemy squadron.
 */
import { CLASS_PICTOGRAMS, SHIP_PICTOGRAMS } from './ship_pictograms.js';

const SVG_NS = 'http://www.w3.org/2000/svg';
const CELL_SIZE = 10;
const SQRT3 = Math.sqrt(3);

export const factionColor = (faction, shade) => `var(--color-${shade}-${faction ?? 0})`;
// Lighter variant of the faction colour, readable on the dark battlefield even for dark factions
const highlightColor = (faction, whiteRatio) => `color-mix(in srgb, ${factionColor(faction, 'light')}, white ${whiteRatio}%)`;

export class BattlefieldView
{
	constructor(container, battle, { onZoomChange = () => {}, describeSquadron = () => '' } = {})
	{
		this.container = container;
		this.battle = battle;
		this.radius = battle.grid.cluster_radius;
		this.size = battle.grid.battlefield_size;
		this.onZoomChange = onZoomChange;
		this.describeSquadron = describeSquadron;
		this.timeouts = [];
		this.squadronNodes = {};
		this.clusterNodes = {};
		this.isZoomed = false;
		this.drag = null;

		this._build();
		this._listenToPointer();
	}

	destroy()
	{
		this._clearEffects();
		this.container.innerHTML = '';
	}

	/**
	 * Displays the state at the end of a turn, without animation.
	 */
	render(snapshot, turnEvents = [])
	{
		this._clearEffects();
		this._applySnapshot(snapshot, false);
		this._drawStaticFire(snapshot, turnEvents);
	}

	/**
	 * Animates a turn: arriving fleets appear in their formation, then squadrons move,
	 * then each shot is played in firing order, with damage and losses.
	 */
	animateTurn(previous, snapshot, turnEvents, duration)
	{
		this._clearEffects();

		// Squadrons arriving this turn start on their formation cell, as composed by the player
		const deploymentCells = {};
		for (const event of turnEvents.filter(event => event.type === 'fleet_joined')) {
			for (const squadron of event.squadrons) {
				deploymentCells[squadron.squadron] = squadron.cell;
			}
		}

		const start = structuredClone(previous);
		for (const [id, squadron] of Object.entries(snapshot.squadrons)) {
			start.squadrons[id] ??= { ...squadron, ships: squadron.initialShips, cell: deploymentCells[id] ?? squadron.cell, joining: true };
		}
		this._applySnapshot(start, false);

		// Arrivals and movements take a bounded time, the rest of the turn is spread over the shots
		const holdDuration = Object.keys(deploymentCells).length > 0 ? Math.round(Math.min(duration * 0.3, 1000)) : 0;
		const moveDuration = Math.round(Math.min(duration * 0.25, 800));
		const firingWindow = duration - holdDuration - moveDuration - duration * 0.05;
		// Area damage and destructions are shown along with the shot that caused them
		const fireEvents = turnEvents.filter(event => event.type === 'squadron_fired');
		const followUps = new Map();
		let lastShot = null;
		for (const event of turnEvents) {
			if (event.type === 'squadron_fired') {
				lastShot = event;
			} else if (['squadron_splashed', 'squadron_destroyed'].includes(event.type) && lastShot !== null) {
				followUps.set(lastShot, [...(followUps.get(lastShot) ?? []), event]);
			}
		}
		const step = fireEvents.length > 0 ? firingWindow / fireEvents.length : 0;
		const ships = Object.fromEntries(Object.entries(start.squadrons).map(([id, squadron]) => [id, squadron.ships]));

		if (holdDuration > 0) {
			// Arriving squadrons fade in on their formation cells
			const deployed = structuredClone(start);
			Object.values(deployed.squadrons).forEach(squadron => delete squadron.joining);
			this._later(() => this._applySnapshot(deployed, true, Math.min(300, holdDuration)), 16);
		}

		this._later(() => this._applySnapshot({ ...snapshot, squadrons: this._withShips(snapshot.squadrons, ships) }, true, moveDuration), holdDuration + 16);

		fireEvents.forEach((event, index) => {
			this._later(() => {
				ships[event.target] = Math.max(0, ships[event.target] - event.kills);
				// Brief shots: each one overlaps the next without lingering
				const shotDuration = Math.min(Math.max(step * 2, 250), 700);
				this._drawShot(snapshot, event, shotDuration);
				this._setShips(event.target, ships[event.target]);

				for (const followUp of followUps.get(event) ?? []) {
					if (followUp.type === 'squadron_splashed') {
						ships[followUp.target] = Math.max(0, ships[followUp.target] - followUp.kills);
						this._drawDamage(snapshot, followUp.target, followUp.damage, shotDuration, '#fb923c');
						this._setShips(followUp.target, ships[followUp.target]);
					} else {
						this._fadeOut(followUp.squadron);
					}
				}
			}, holdDuration + moveDuration + index * step);
		});

		this._later(() => this._applySnapshot(snapshot, true, 150), holdDuration + moveDuration + firingWindow + 50);
	}

	zoomTo(cluster)
	{
		this._animateViewBox(cluster === null ? this.battlefieldBox : this._clusterBox(cluster));
	}

	// Geometry

	_pixel(hex)
	{
		return { x: CELL_SIZE * SQRT3 * (hex.q + hex.r / 2), y: CELL_SIZE * 1.5 * hex.r };
	}

	_clusterCenter(cluster)
	{
		const n = this.radius;

		return { q: (2 * n + 1) * cluster.q + n * cluster.r, r: -n * cluster.q + (n + 1) * cluster.r };
	}

	_range(center, radius)
	{
		const cells = [];
		for (let q = -radius; q <= radius; q++) {
			for (let r = Math.max(-radius, -q - radius); r <= Math.min(radius, -q + radius); r++) {
				cells.push({ q: center.q + q, r: center.r + r });
			}
		}

		return cells;
	}

	_hexPoints(x, y, radius)
	{
		return Array.from({ length: 6 }, (_, i) => {
			const angle = Math.PI / 180 * (60 * i - 30);

			return `${(x + radius * Math.cos(angle)).toFixed(2)},${(y + radius * Math.sin(angle)).toFixed(2)}`;
		}).join(' ');
	}

	_clusterBox(cluster)
	{
		const center = this._pixel(this._clusterCenter(cluster));
		const halfWidth = CELL_SIZE * SQRT3 * (this.radius + 1);
		const halfHeight = CELL_SIZE * 1.5 * (this.radius + 1);

		return { x: center.x - halfWidth, y: center.y - halfHeight, width: halfWidth * 2, height: halfHeight * 2 };
	}

	// Construction

	_build()
	{
		this.container.innerHTML = '';
		this.svg = this._element('svg', {
			class: 'w-full h-[32rem] select-none bg-black/30 rounded-md',
			'data-zoom': 'battlefield',
			role: 'img',
			'aria-label': 'Champ de bataille',
		});
		this.svg.innerHTML = `
			<style>
				.squadron { transition-property: transform, opacity; transition-timing-function: ease-in-out; }
				[data-zoom="battlefield"] .squadron { cursor: zoom-in; }
				.squadron text { font-size: 3.6px; font-weight: 700; fill: white; paint-order: stroke; stroke: rgba(0, 0, 0, .75); stroke-width: 1px; pointer-events: none; }
				.squadron .pictogram { transform-origin: 0 0; transition: transform 300ms ease-out; fill: white; paint-order: stroke; stroke: rgba(0, 0, 0, .7); stroke-width: 6px; stroke-linejoin: round; pointer-events: none; }
				[data-zoom="battlefield"] .squadron text { display: none; }
				.fleet-label { font-size: 14px; fill: white; paint-order: stroke; stroke: rgba(0, 0, 0, .8); stroke-width: 3px; pointer-events: none; }
				[data-zoom="fleet"] .fleet-label { font-size: 6px; stroke-width: 1.5px; }
				svg { touch-action: none; }
				.cluster { cursor: zoom-in; }
				[data-zoom="fleet"], [data-zoom="fleet"] .cluster, [data-zoom="fleet"] .squadron { cursor: grab; }
				[data-dragging], [data-dragging] * { cursor: grabbing !important; }
				.shot { stroke-width: 1.2; stroke-linecap: round; }
				.damage { font-size: 7px; font-weight: 700; paint-order: stroke; stroke: rgba(0, 0, 0, .9); stroke-width: 2px; }
				[data-zoom="battlefield"] .damage { font-size: 14px; stroke-width: 3px; }
			</style>
		`;

		const clusters = this._element('g');
		const bounds = { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity };

		for (let q = 0; q < this.size; q++) {
			for (let r = 0; r < this.size; r++) {
				const cluster = { q, r };
				const group = this._element('g', { class: 'cluster' });
				const cells = [];

				for (const cell of this._range(this._clusterCenter(cluster), this.radius)) {
					const { x, y } = this._pixel(cell);
					const polygon = this._element('polygon', {
						points: this._hexPoints(x, y, CELL_SIZE * 0.96),
						fill: (q + r) % 2 === 0 ? '#1f2937' : '#1a2230',
						stroke: '#111827',
						'stroke-width': 0.6,
					});
					group.appendChild(polygon);
					cells.push(polygon);
					bounds.minX = Math.min(bounds.minX, x);
					bounds.minY = Math.min(bounds.minY, y);
					bounds.maxX = Math.max(bounds.maxX, x);
					bounds.maxY = Math.max(bounds.maxY, y);
				}

				group.addEventListener('click', () => {
					if (!this.isZoomed) {
						this.zoomTo(cluster);
					}
				});
				clusters.appendChild(group);
				this.clusterNodes[`${q},${r}`] = { cells, defaultFills: cells.map(cell => cell.getAttribute('fill')) };
			}
		}

		const margin = CELL_SIZE * 2;
		this.battlefieldBox = {
			x: bounds.minX - margin,
			y: bounds.minY - margin,
			width: bounds.maxX - bounds.minX + margin * 2,
			height: bounds.maxY - bounds.minY + margin * 2,
		};
		this.viewBox = { ...this.battlefieldBox };
		this._setViewBox(this.viewBox);

		this.squadronLayer = this._element('g');
		this.labelLayer = this._element('g');
		this.effectLayer = this._element('g');
		this.svg.append(clusters, this.squadronLayer, this.labelLayer, this.effectLayer);
		this.container.appendChild(this.svg);
	}

	_squadronNode(id, squadron)
	{
		if (this.squadronNodes[id]) {
			return this.squadronNodes[id];
		}

		const fleet = this.battle.fleets.find(f => f.id === squadron.fleet);
		const group = this._element('g', { class: 'squadron' });
		const hex = this._element('polygon', {
			points: this._hexPoints(0, 0, CELL_SIZE * 0.82),
			fill: factionColor(fleet.faction, 'medium'),
			stroke: factionColor(fleet.faction, 'light'),
			'stroke-width': 1.4,
		});
		// Ship type pictogram above, number of ships below
		const shipType = this.battle.ship_types[squadron.type];
		const pictogram = this._pictogram(shipType?.pictogram ?? CLASS_PICTOGRAMS[shipType?.class]);
		const text = this._element('text', { y: CELL_SIZE * 0.5, 'text-anchor': 'middle', 'dominant-baseline': 'central' });
		const title = this._element('title');

		group.append(hex, ...(pictogram ? [pictogram] : []), text, title);
		// From the battlefield view, clicking a squadron zooms on the area of its fleet
		group.addEventListener('click', () => {
			const cluster = this.currentSnapshot?.fleets[squadron.fleet]?.cluster;
			if (!this.isZoomed && cluster) {
				this.zoomTo(cluster);
			}
		});
		this.squadronLayer.appendChild(group);
		this.squadronNodes[id] = { group, hex, pictogram, text, title };

		return this.squadronNodes[id];
	}

	/**
	 * Pictogram of the ship class, scaled into the upper part of the squadron hexagon.
	 */
	_pictogram(name)
	{
		const shape = SHIP_PICTOGRAMS[name];
		if (!shape) {
			return null;
		}

		const wrapper = this._element('g', { class: 'pictogram' });
		wrapper.appendChild(this._element(shape.tag, { [shape.attribute]: shape.value }));
		this._orientPictogram(wrapper, 0);

		return wrapper;
	}

	/**
	 * Turns the pictogram around its center, in the upper part of the hexagon; 0° is nose up.
	 */
	_orientPictogram(pictogram, angle)
	{
		const size = CELL_SIZE * 0.85;
		pictogram.style.transform = `translate(0px, ${-size * 0.22}px) rotate(${angle}deg) scale(${size / 64}) translate(-32px, -32px)`;
	}

	/**
	 * Angle from a squadron to the closest enemy squadron still fighting, null when there is none.
	 */
	_angleToClosestEnemy(snapshot, id, squadron)
	{
		const side = this.battle.fleets.find(fleet => fleet.id === squadron.fleet)?.side;
		const from = this._pixel(squadron.cell);
		let closest = null;

		for (const [otherId, other] of Object.entries(snapshot.squadrons)) {
			if (otherId === id || other.ships <= 0 || other.fled || this.battle.fleets.find(fleet => fleet.id === other.fleet)?.side === side) {
				continue;
			}

			const to = this._pixel(other.cell);
			const distance = (to.x - from.x) ** 2 + (to.y - from.y) ** 2;
			if (null === closest || distance < closest.distance) {
				closest = { distance, angle: Math.atan2(to.y - from.y, to.x - from.x) * 180 / Math.PI + 90 };
			}
		}

		return closest?.angle ?? null;
	}

	// State

	_applySnapshot(snapshot, animated, duration = 0)
	{
		this.currentSnapshot = snapshot;
		for (const [id, squadron] of Object.entries(snapshot.squadrons)) {
			const node = this._squadronNode(id, squadron);
			const { x, y } = this._pixel(squadron.cell);

			node.group.style.transitionDuration = animated ? `${duration}ms` : '0ms';
			node.group.style.transform = `translate(${x}px, ${y}px)`;
			node.group.style.opacity = squadron.ships > 0 && !squadron.joining && !squadron.fled ? this._squadronOpacity(snapshot, squadron) : '0';
			node.text.textContent = squadron.ships;
			node.title.textContent = this.describeSquadron(id, squadron);

			const angle = node.pictogram ? this._angleToClosestEnemy(snapshot, id, squadron) : null;
			if (null !== angle) {
				// Shortest turn, so that the transition does not spin around
				const previous = node.angle ?? angle;
				node.angle = previous + ((((angle - previous) % 360) + 540) % 360 - 180);
				this._orientPictogram(node.pictogram, node.angle);
			}
		}

		// Squadrons not in the snapshot have not joined yet
		for (const [id, node] of Object.entries(this.squadronNodes)) {
			if (!snapshot.squadrons[id]) {
				node.group.style.opacity = '0';
			}
		}

		this._renderFleets(snapshot);
	}

	/**
	 * Withdrawn and fleeing fleets are dimmed: they no longer fire.
	 */
	_squadronOpacity(snapshot, squadron)
	{
		return ['withdrawn', 'fleeing'].includes(snapshot.fleets[squadron.fleet]?.status) ? '0.55' : '1';
	}

	_renderFleets(snapshot)
	{
		for (const node of Object.values(this.clusterNodes)) {
			node.cells.forEach((cell, index) => cell.setAttribute('fill', node.defaultFills[index]));
		}
		this.labelLayer.innerHTML = '';

		for (const [fleetId, fleetState] of Object.entries(snapshot.fleets)) {
			const fleet = this.battle.fleets.find(f => f.id === fleetId);
			const alive = fleetState.status !== 'fled' && Object.values(snapshot.squadrons).some(s => s.fleet === fleetId && s.ships > 0);
			const node = this.clusterNodes[`${fleetState.cluster.q},${fleetState.cluster.r}`];

			if (!node || !alive) {
				continue;
			}

			node.cells.forEach(cell => cell.setAttribute('fill', `color-mix(in srgb, var(--color-medium-${fleet.faction}) 35%, #111827)`));

			const center = this._pixel(this._clusterCenter(fleetState.cluster));
			const offset = CELL_SIZE * 1.5 * (this.radius + 0.6);
			const label = this._element('text', { class: 'fleet-label', x: center.x, y: center.y - offset, 'text-anchor': 'middle' });
			const behaviour = { withdrawn: ' · repli', fleeing: ' · fuite' }[fleetState.status] ?? '';
			label.textContent = `${fleet.name} (${fleet.side === 0 ? 'A' : 'B'})${behaviour}`;
			label.style.fill = highlightColor(fleet.faction, 45);
			this.labelLayer.appendChild(label);
		}
	}

	_withShips(squadrons, ships)
	{
		return Object.fromEntries(Object.entries(squadrons).map(([id, squadron]) => [id, { ...squadron, ships: ships[id] ?? squadron.ships }]));
	}

	_setShips(id, ships)
	{
		const node = this.squadronNodes[id];
		if (!node) {
			return;
		}

		node.text.textContent = ships;
		node.hex.animate([{ filter: 'brightness(2.2)' }, { filter: 'brightness(1)' }], { duration: 300 });
	}

	_fadeOut(id)
	{
		const node = this.squadronNodes[id];
		if (node) {
			node.group.style.transitionDuration = '400ms';
			node.group.style.opacity = '0';
		}
	}

	// Effects

	_drawShot(snapshot, event, duration)
	{
		const attacker = snapshot.squadrons[event.attacker];
		const target = snapshot.squadrons[event.target];
		if (!attacker || !target) {
			return;
		}

		const fleet = this.battle.fleets.find(f => f.id === attacker.fleet);
		const from = this._pixel(attacker.cell);
		const to = this._pixel(target.cell);
		const color = highlightColor(fleet.faction, 35);
		const line = this._element('line', { class: 'shot', x1: from.x, y1: from.y, x2: to.x, y2: to.y, stroke: color });
		const length = Math.hypot(to.x - from.x, to.y - from.y);

		line.style.strokeDasharray = `${length}`;
		this.effectLayer.appendChild(line);
		line.animate(
			[{ strokeDashoffset: length, opacity: 1 }, { strokeDashoffset: 0, opacity: 1, offset: 0.4 }, { strokeDashoffset: 0, opacity: 0 }],
			{ duration, fill: 'forwards' },
		).finished.then(() => line.remove()).catch(() => {});

		const label = this._element('text', {
			class: 'damage',
			x: to.x,
			y: to.y - CELL_SIZE,
			'text-anchor': 'middle',
			fill: event.hits === 0 ? '#9ca3af' : '#fde047',
		});
		label.textContent = event.hits === 0 ? 'raté' : `-${event.damage.toLocaleString('fr-FR')}`;
		this.effectLayer.appendChild(label);
		label.animate(
			[{ transform: 'translateY(0)', opacity: 1 }, { transform: `translateY(-${CELL_SIZE}px)`, opacity: 0 }],
			{ duration: duration * 1.5, fill: 'forwards' },
		).finished.then(() => label.remove()).catch(() => {});
	}

	/**
	 * Floating damage number over a squadron, e.g. for area damage.
	 */
	_drawDamage(snapshot, squadronId, damage, duration, color)
	{
		const squadron = snapshot.squadrons[squadronId];
		if (!squadron) {
			return;
		}

		const { x, y } = this._pixel(squadron.cell);
		const label = this._element('text', { class: 'damage', x, y: y - CELL_SIZE, 'text-anchor': 'middle', fill: color });
		label.textContent = `-${damage.toLocaleString('fr-FR')}`;
		this.effectLayer.appendChild(label);
		label.animate(
			[{ transform: 'translateY(0)', opacity: 1 }, { transform: `translateY(-${CELL_SIZE}px)`, opacity: 0 }],
			{ duration: duration * 1.5, fill: 'forwards' },
		).finished.then(() => label.remove()).catch(() => {});
	}

	_drawStaticFire(snapshot, turnEvents)
	{
		for (const event of turnEvents) {
			if (event.type !== 'squadron_fired') {
				continue;
			}

			const attacker = snapshot.squadrons[event.attacker];
			const target = snapshot.squadrons[event.target];
			if (!attacker || !target) {
				continue;
			}

			const fleet = this.battle.fleets.find(f => f.id === attacker.fleet);
			const from = this._pixel(attacker.cell);
			const to = this._pixel(target.cell);
			this.effectLayer.appendChild(this._element('line', {
				class: 'shot',
				x1: from.x,
				y1: from.y,
				x2: to.x,
				y2: to.y,
				stroke: highlightColor(fleet.faction, 35),
				opacity: 0.45,
			}));
		}
	}

	_clearEffects()
	{
		this.timeouts.forEach(timeout => clearTimeout(timeout));
		this.timeouts = [];
		this.effectLayer.getAnimations?.({ subtree: true }).forEach(animation => animation.cancel());
		this.effectLayer.innerHTML = '';
	}

	_later(callback, delay)
	{
		this.timeouts.push(setTimeout(callback, delay));
	}

	// View box

	_animateViewBox(target)
	{
		const start = { ...this.viewBox };
		const startTime = performance.now();
		const duration = 450;

		cancelAnimationFrame(this.viewBoxFrame);
		const tick = now => {
			const progress = Math.min(1, (now - startTime) / duration);
			const eased = 1 - Math.pow(1 - progress, 3);

			this.viewBox = Object.fromEntries(Object.keys(target).map(key => [key, start[key] + (target[key] - start[key]) * eased]));
			this._setViewBox(this.viewBox);

			if (progress < 1) {
				this.viewBoxFrame = requestAnimationFrame(tick);
			}
		};
		this.viewBoxFrame = requestAnimationFrame(tick);
	}

	_setViewBox(box)
	{
		this.svg.setAttribute('viewBox', `${box.x} ${box.y} ${box.width} ${box.height}`);

		// Zoomed in when less than half of the battlefield is visible: squadron details are shown
		const isZoomed = box.width < this.battlefieldBox.width * 0.5;
		if (isZoomed !== this.isZoomed) {
			this.isZoomed = isZoomed;
			this.svg.dataset.zoom = isZoomed ? 'fleet' : 'battlefield';
			this.onZoomChange(isZoomed);
		}
	}

	// Pointer: drag to move, wheel to zoom

	_listenToPointer()
	{
		this.svg.addEventListener('pointerdown', event => {
			if (event.button !== 0) {
				return;
			}
			this.drag = { pointerId: event.pointerId, x: event.clientX, y: event.clientY, viewBox: { ...this.viewBox }, moved: false };
		});

		this.svg.addEventListener('pointermove', event => {
			if (this.drag === null || event.pointerId !== this.drag.pointerId) {
				return;
			}

			const dx = event.clientX - this.drag.x;
			const dy = event.clientY - this.drag.y;

			if (!this.drag.moved && Math.hypot(dx, dy) < 4) {
				return;
			}
			if (!this.drag.moved) {
				this.drag.moved = true;
				this.svg.setPointerCapture(event.pointerId);
				this.svg.dataset.dragging = '';
				cancelAnimationFrame(this.viewBoxFrame);
			}

			const scale = this._unitsPerPixel(this.drag.viewBox);
			this.viewBox = this._clamp({ ...this.drag.viewBox, x: this.drag.viewBox.x - dx * scale, y: this.drag.viewBox.y - dy * scale });
			this._setViewBox(this.viewBox);
		});

		const endDrag = event => {
			if (this.drag === null || event.pointerId !== this.drag.pointerId) {
				return;
			}
			if (this.drag.moved) {
				// The click following a drag must not zoom on a fleet
				this.svg.addEventListener('click', clickEvent => clickEvent.stopPropagation(), { capture: true, once: true });
				this.svg.releasePointerCapture?.(event.pointerId);
			}
			delete this.svg.dataset.dragging;
			this.drag = null;
		};
		this.svg.addEventListener('pointerup', endDrag);
		this.svg.addEventListener('pointercancel', endDrag);

		this.svg.addEventListener('wheel', event => {
			event.preventDefault();
			cancelAnimationFrame(this.viewBoxFrame);

			const factor = Math.exp(event.deltaY * 0.0015);
			const rect = this.svg.getBoundingClientRect();
			const scale = this._unitsPerPixel(this.viewBox);
			// Point under the cursor, which must stay in place
			const anchorX = this.viewBox.x + (event.clientX - rect.left - (rect.width - this.viewBox.width / scale) / 2) * scale;
			const anchorY = this.viewBox.y + (event.clientY - rect.top - (rect.height - this.viewBox.height / scale) / 2) * scale;
			const minWidth = this._clusterBox({ q: 0, r: 0 }).width * 0.6;
			const width = Math.min(this.battlefieldBox.width, Math.max(minWidth, this.viewBox.width * factor));
			const ratio = width / this.viewBox.width;

			this.viewBox = this._clamp({
				x: anchorX - (anchorX - this.viewBox.x) * ratio,
				y: anchorY - (anchorY - this.viewBox.y) * ratio,
				width,
				height: this.viewBox.height * ratio,
			});
			this._setViewBox(this.viewBox);
		}, { passive: false });
	}

	/**
	 * Size of a screen pixel in SVG units, the view box being scaled to fit the element.
	 */
	_unitsPerPixel(box)
	{
		const rect = this.svg.getBoundingClientRect();

		return Math.max(box.width / rect.width, box.height / rect.height);
	}

	/**
	 * Keeps the view over the battlefield.
	 */
	_clamp(box)
	{
		const bounds = this.battlefieldBox;
		const x = Math.min(Math.max(box.x, bounds.x - box.width / 2), bounds.x + bounds.width - box.width / 2);
		const y = Math.min(Math.max(box.y, bounds.y - box.height / 2), bounds.y + bounds.height - box.height / 2);

		return { ...box, x, y };
	}

	_element(name, attributes = {})
	{
		const element = document.createElementNS(SVG_NS, name);
		for (const [key, value] of Object.entries(attributes)) {
			element.setAttribute(key, value);
		}

		return element;
	}
}
