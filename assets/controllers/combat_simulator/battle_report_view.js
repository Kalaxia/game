import { factionColor } from './battlefield_view.js';

/**
 * HTML rendering of the post-battle report built by the server (BuildBattleReport):
 * an overview by side with the key moments, and the details by fleet and squadron.
 */
const CLASS_NAMES = ['Chasseurs', 'Corvettes', 'Frégates', 'Destroyers et croiseurs'];
const STAT_NAMES = { attack: 'attaque', defense: 'défense', speed: 'vitesse' };

export class BattleReportView
{
	/**
	 * @param {object} battle the simulation response
	 * @param {object} helpers { escape, sideName, fleetName, squadronName, typeName }
	 */
	constructor(battle, helpers)
	{
		this.battle = battle;
		this.report = battle.report;
		this.helpers = helpers;
	}

	renderOverview()
	{
		const { escape, sideName } = this.helpers;

		return `
			<div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
				${Object.entries(this.report.sides).map(([side, figures]) => `
					<div class="rounded-md bg-black/20 p-3 text-sm">
						<p class="font-semibold">Camp ${sideName(Number(side))}${this.report.winning_side === Number(side) ? ' · vainqueur' : ''}</p>
						<dl class="mt-2 grid grid-cols-3 gap-2 text-xs text-gray-400">
							<div><dt>PEV perdus</dt><dd class="text-base text-white">${this._number(figures.lost_pev)} / ${this._number(figures.initial_pev)}</dd></div>
							<div><dt>Vaisseaux perdus</dt><dd class="text-base text-white">${this._number(figures.ships_lost)}</dd></div>
							<div><dt>Dégâts infligés</dt><dd class="text-base text-white">${this._number(figures.damage_dealt)}</dd></div>
						</dl>
					</div>
				`).join('')}
			</div>

			<h3 class="mt-5 text-sm font-semibold text-gray-300">Moments clés</h3>
			<ol class="mt-2 space-y-1 text-sm">
				${this.report.highlights.map(highlight => `
					<li class="flex items-baseline gap-3">
						<button type="button"
							class="shrink-0 rounded px-1.5 py-0.5 text-xs text-gray-300 ring-1 ring-white/20 hover:bg-white/10"
							data-action="combat-simulator--replay#showTurn"
							data-combat-simulator--replay-turn-param="${highlight.turn}"
							title="Revoir ce tour">Tour ${highlight.turn}</button>
						<span>${escape(this._describeHighlight(highlight))}</span>
					</li>
				`).join('') || '<li class="text-gray-500">Aucun moment marquant.</li>'}
			</ol>
		`;
	}

	renderDetails()
	{
		return `
			<div class="mt-6 rounded-lg bg-gray-800 p-4">
				<h3 class="text-sm font-semibold text-gray-300">Détail par flotte</h3>
				<div class="mt-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
					${this.report.fleets.map(fleet => this._renderFleet(fleet)).join('')}
				</div>

				<h3 class="mt-6 text-sm font-semibold text-gray-300">Escadrilles les plus efficaces</h3>
				${this._renderTopSquadrons()}
			</div>
		`;
	}

	_renderFleet(fleet)
	{
		const { escape, sideName, fleetName, typeName } = this.helpers;
		const info = this.battle.fleets.find(f => f.id === fleet.fleet);
		const status = fleet.joined_at_turn === null
			? 'n’est jamais arrivée'
			: (fleet.destroyed_at_turn !== null ? `détruite au tour ${fleet.destroyed_at_turn}` : `${this._number(fleet.remaining_pev)} / ${this._number(fleet.initial_pev)} PEV restants`);
		const kills = Object.entries(fleet.kills_by_type).filter(([, count]) => count > 0);

		return `
			<details class="rounded-md bg-black/20 border-t-4" style="border-color: ${factionColor(fleet.faction, 'medium')}" open>
				<summary class="cursor-pointer p-3 text-sm">
					<span class="font-medium">${escape(fleetName(fleet.fleet))}</span>
					<span class="text-xs text-gray-400">· camp ${sideName(fleet.side)} · ${escape(info?.faction_name ?? '')} · ${status}</span>
				</summary>
				<div class="px-3 pb-3 text-xs">
					<table class="w-full text-left">
						<thead class="text-gray-400"><tr><th class="py-1">Vaisseaux</th><th>Engagés</th><th>Restants</th><th>Pertes</th></tr></thead>
						<tbody>
							${Object.entries(fleet.ships).map(([type, ships]) => `
								<tr class="border-t border-white/10">
									<td class="py-1">${escape(typeName(type))}</td>
									<td>${ships.initial}</td>
									<td>${ships.remaining}</td>
									<td class="${ships.initial > ships.remaining ? 'text-red-300' : ''}">${ships.initial - ships.remaining}</td>
								</tr>
							`).join('')}
						</tbody>
					</table>
					<dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1">
						<dt class="text-gray-400">Dégâts infligés</dt><dd>${this._number(fleet.damage_dealt)}</dd>
						<dt class="text-gray-400">Dégâts subis</dt><dd>${this._number(fleet.damage_taken)}</dd>
						<dt class="text-gray-400">Vaisseaux détruits</dt>
						<dd>${kills.length === 0 ? 'aucun' : kills.map(([type, count]) => `${count} ${escape(typeName(type))}`).join(', ')}</dd>
						<dt class="text-gray-400">Expérience de l’officier</dt><dd>${this._number(fleet.experience)} XP</dd>
						<dt class="text-gray-400">Bonus de combat</dt><dd>${escape(this._describeBonuses(fleet.bonuses))}</dd>
					</dl>
				</div>
			</details>
		`;
	}

	_renderTopSquadrons()
	{
		const { escape, squadronName } = this.helpers;
		const squadrons = [...this.report.squadrons]
			.filter(squadron => squadron.damage_dealt > 0)
			.sort((a, b) => b.damage_dealt - a.damage_dealt)
			.slice(0, 5);

		if (squadrons.length === 0) {
			return '<p class="mt-2 text-sm text-gray-500">Aucune escadrille n’a fait de dégâts.</p>';
		}

		return `
			<table class="mt-2 w-full text-left text-xs">
				<thead class="text-gray-400"><tr><th class="py-1">Escadrille</th><th>Dégâts</th><th>Destructions</th><th>Précision</th><th>Survivants</th></tr></thead>
				<tbody>
					${squadrons.map(squadron => `
						<tr class="border-t border-white/10">
							<td class="py-1">${escape(squadronName(squadron.squadron))}</td>
							<td>${this._number(squadron.damage_dealt)}</td>
							<td>${squadron.kills}</td>
							<td>${squadron.shots > 0 ? Math.round(squadron.hits / squadron.shots * 100) : 0} %</td>
							<td>${squadron.remaining_ships} / ${squadron.initial_ships}</td>
						</tr>
					`).join('')}
				</tbody>
			</table>
		`;
	}

	_describeHighlight(highlight)
	{
		const { sideName, fleetName, squadronName } = this.helpers;
		const ships = count => `${count} vaisseau${count > 1 ? 'x' : ''}`;

		switch (highlight.type) {
			case 'first_blood':
				return `Premier sang : ${squadronName(highlight.attacker)} détruit ${ships(highlight.kills)} de ${squadronName(highlight.target)}.`;
			case 'deadliest_action':
				return `Tir le plus dévastateur : ${squadronName(highlight.attacker)} inflige ${this._number(highlight.damage)} dégâts à ${squadronName(highlight.target)}${highlight.kills > 0 ? ` et détruit ${ships(highlight.kills)}` : ''}.`;
			case 'reinforcement':
				return `${fleetName(highlight.fleet)} arrive en renfort.`;
			case 'fleet_destroyed':
				return `La flotte ${fleetName(highlight.fleet)} est détruite.`;
			case 'turning_point':
				return `Tournant de la bataille : le camp ${sideName(highlight.side)} prend l’avantage pour de bon.`;
			case 'most_valuable_squadron':
				return `Escadrille la plus efficace : ${squadronName(highlight.squadron)}, avec ${this._number(highlight.damage)} dégâts et ${ships(highlight.kills)} détruits.`;
			default:
				return highlight.type;
		}
	}

	_describeBonuses(bonuses)
	{
		const lines = Object.entries(bonuses ?? {})
			.map(([shipClass, stats]) => {
				const values = Object.entries(stats)
					.filter(([, value]) => value !== 0)
					.map(([stat, value]) => `${STAT_NAMES[stat]} ${value > 0 ? '+' : ''}${value} %`);

				return values.length > 0 ? `${CLASS_NAMES[shipClass] ?? shipClass} : ${values.join(', ')}` : null;
			})
			.filter(line => line !== null);

		return lines.length > 0 ? lines.join(' · ') : 'aucun';
	}

	_number(value)
	{
		return Number(value).toLocaleString('fr-FR');
	}
}
