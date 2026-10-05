import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
	static outlets = ['fleet--squadron'];
	static targets = ['source', 'squadron'];
	static values = {
		squadronUpdateUrl: String,
		source: String,
		manufactureMaxStorableShipPoints: Number,
		shipyardMaxStorableShipPoints: Number,
		shipPevs: Array,
	};

	initialize() {
		super.initialize();

		this.timer = null;
		this.onSquadronSelection = () => this._updateAllTiles();
	}

	connect()
	{
		this.application.element.addEventListener('fleet:squadron-selection', this.onSquadronSelection);
	}

	disconnect()
	{
		this.application.element.removeEventListener('fleet:squadron-selection', this.onSquadronSelection);
	}

	transfer(event) {
		const squadron = this.fleetSquadronOutlet.squadron;
		if (squadron === null) {
			console.warn('No squadron selected');

			return;
		}

		const originTile = event.currentTarget;
		const originTileQuantityElement = originTile.querySelector('.quantity');
		let originTileQuantity = this._getQuantity(originTileQuantityElement);

		if (originTileQuantity === 0) {
			console.warn('The desired tile has no ships left to transfer');

			return;
		}

		const shipIdentifier = originTile.dataset.shipId;
		const targetSide = (originTile.dataset.side === 'source') ? 'squadron' : 'source';
		const targetTile = this._getTileByIdentifierAndSide(targetSide, shipIdentifier);
		const targetTileQuantityElement = targetTile.querySelector('.quantity');
		let targetTileQuantity = this._getQuantity(targetTileQuantityElement);

		const shipPev = this.shipPevsValue[shipIdentifier];
		const availableSpace = targetSide === 'source' ? this._getHangarAvailableSpace(shipIdentifier) : this._getSquadronAvailableSpace();
		// An infinite source can only fill the space available in the target
		const maxQuantity = Math.min(originTileQuantity, Math.floor(availableSpace / shipPev));
		const desiredQuantity = this._getDesiredQuantity(event, maxQuantity);

		if (desiredQuantity === 0) {
			console.warn('Not enough space to transfer the ships. Remaining space : %d', availableSpace);

			return;
		}

		originTileQuantity -= desiredQuantity;
		targetTileQuantity += desiredQuantity;

		this.fleetSquadronOutlet.updateSquadron(shipIdentifier, targetSide === 'squadron' ? targetTileQuantity : originTileQuantity);

		this._updateTile(originTile, originTileQuantityElement, originTileQuantity);
		this._updateTile(targetTile, targetTileQuantityElement, targetTileQuantity);

		const squadronSnapshot = Object.assign({}, this.fleetSquadronOutlet.squadron);

		clearTimeout(this.timer);
		this.timer = setTimeout(() => {
			this._sendRequest(squadronSnapshot);
		}, 1000);
	}

	_getDesiredQuantity(event, maxQuantity)
	{
		let desiredQuantity = 1;

		if (event.ctrlKey) {
			desiredQuantity = maxQuantity;
		}

		if (event.shiftKey) {
			desiredQuantity = 5;
		}

		return Math.min(desiredQuantity, maxQuantity);
	}

	_getSquadronAvailableSpace()
	{
		return 100 - this.fleetSquadronOutlet.squadron.pev;
	}
	
	_getHangarAvailableSpace(shipIdentifier)
	{
		return (shipIdentifier < 6)
			? this._getManufactureAvailableSpace()
			: this._getShipyardAvailableSpace();
	}

	_getManufactureAvailableSpace()
	{
		return this._getSourceAvailableSpace(this.manufactureMaxStorableShipPointsValue, shipId => shipId < 6);
	}

	_getShipyardAvailableSpace()
	{
		return this._getSourceAvailableSpace(this.shipyardMaxStorableShipPointsValue, shipId => shipId >= 6);
	}

	_getSourceAvailableSpace(maxStorableShipPoints, isInDock)
	{
		const tiles = this.sourceTargets.filter(element => isInDock(parseInt(element.dataset.shipId)));

		if (tiles.some(element => 'infinite' in element.querySelector('.quantity').dataset)) {
			return Infinity;
		}

		return maxStorableShipPoints - tiles.reduce(
			(acc, element) => acc + parseInt(element.querySelector('.quantity').innerText) * this.shipPevsValue[element.dataset.shipId],
			0,
		);
	}

	_getQuantity(quantityElement)
	{
		return ('infinite' in quantityElement.dataset)
			? Infinity
			: parseInt(quantityElement.innerText);
	}

	_updateTile(tile, quantityElement, quantity)
	{
		if (quantity === Infinity) {
			return;
		}

		quantityElement.innerText = quantity;

		if (quantity === 0) {
			tile.dataset.empty = '';
		} else {
			delete tile.dataset['empty'];
		}
	}

	_updateAllTiles()
	{
		this.squadronTargets.forEach(element => {
			this._updateTile(
				element,
				element.querySelector('.quantity'),
				this.fleetSquadronOutlet.squadron.ships[element.dataset.shipId],
			);
		});
	}

	_getTileByIdentifierAndSide(side, identifier)
	{
		return this[side + 'Targets'].find(element => element.dataset.shipId === identifier);
	}

	_sendRequest(squadron)
	{
		fetch(decodeURI(this.squadronUpdateUrlValue).replace('{squadronId}', squadron.id), {
			method: 'PATCH',
			headers: {
				'Content-Type': 'application/json',
			},
			credentials: 'include',
			body: JSON.stringify({
				base_id: this.sourceValue,
				army: squadron.ships,
			}),
		});
	}
}
