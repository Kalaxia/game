/**
 * Pictograms identifying the ship types on the battlefield, by file name of the legacy pictos
 * (assets/images/legacy/picto/ships/types/*.svg, viewBox 0 0 64 64, nose up). Each battle ship type
 * names its pictogram in config/game/battle_ship_types.yaml.
 *
 * The shapes are inlined so that they can be drawn, styled and rotated inside the battlefield SVG.
 */
export const SHIP_PICTOGRAMS = {
	Intercepteur: { tag: 'polygon', attribute: 'points', value: '64,54.9 51,37.5 44.6,39.4 37.4,31.5 32,1.7 26.6,31.5 19.4,39.4 13,37.5 0,54.9 19.1,48 21,43.1 24.1,43.9 27.4,58.2 32,62.3 36.6,58.2 39.9,43.9 43,43.1 44.9,48' },
	fighter: { tag: 'polygon', attribute: 'points', value: '49.1,33.1 45.4,35.7 37.9,32.7 32,0 26.1,32.7 18.6,35.7 14.9,33.1 5.3,58.4 17.6,45.5 24.3,47.5 27,59.4 32,64 37,59.4 39.7,47.5 46.4,45.5 58.7,58.4' },
	ChasseurSW: { tag: 'polygon', attribute: 'points', value: '44,3.1 42.1,4.3 49.6,32 41.7,33.7 39.4,28.4 33.1,11.1 32,10.2 30.9,11.1 24.6,28.4 22.3,33.7 14.4,32 21.9,4.3 20,3.1 0,43.6 11.7,60.9 14.6,59.9 12.7,44.7 24.2,40.8 32,53.4 39.8,40.8 51.3,44.7 49.4,59.9 52.3,60.9 64,43.6' },
	corvette: { tag: 'polygon', attribute: 'points', value: '20.1,24.1 32,63.7 43.9,24.1 64,33.6 53.4,6.6 42.2,0.3 32,21.1 21.8,0.3 10.6,6.6 0,33.6' },
	frigate: { tag: 'path', attribute: 'd', value: 'M51.7,3.9L48.5,4l1.6,45.4c-1.7-3.3-4.5-8.1-8.8-12.3l4.1-9.5l-7-5.6L34.5,2L32,0l-2.5,2l-3.8,20.1l-7,5.6l4.1,9.5c-4.3,4.2-7.2,9-8.8,12.3L15.5,4l-3.2-0.1L1,55.4L16.1,64l3.7-2.5L17.3,58c1.2-1.2,4.8-4.5,9.5-6.1l2.3,5.4H32h2.8l2.3-5.4c4.7,1.7,8.3,4.9,9.5,6.1l-2.5,3.5l3.7,2.5L63,55.4L51.7,3.9z' },
	cruiser: { tag: 'polygon', attribute: 'points', value: '46.8,43.1 32,3.5 17.2,43.1 0,33.6 10.6,60.5 21.8,55.6 32,60.5 42.2,55.6 53.4,60.5 64,33.6' },
};

/** Fallback by ship class (0 fighters, 1 corvettes, 2 frigates, 3 destroyers and cruisers) */
export const CLASS_PICTOGRAMS = ['fighter', 'corvette', 'frigate', 'cruiser'];
