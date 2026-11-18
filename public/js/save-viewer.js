/**
 * Save and replay viewer, built on kfxs-reader.js.
 *
 * Shows what was going on in a saved game or a replay header in plain words (level, players,
 * their creatures and rooms, how full the game's tables were), then the config the level
 * changed and an explorer for every field in the file.
 *
 * Usage:
 *   <div data-kfxs-url="/path/to/file.sav" data-kfxs-title="Save file"></div>   fetched and shown
 *   <div data-kfxs-picker></div>                                                pick a local file
 * Elements stay empty (and hidden) if the browser can't run this.
 */
(function () {
    'use strict';

    if (typeof KFXS === 'undefined' || typeof DecompressionStream === 'undefined') return;

    const PLAYERS = [
        ['Red', '#c0392b'], ['Blue', '#2e6fd8'], ['Green', '#2e9e45'], ['Yellow', '#d4b106'],
        ['Heroes', '#e8e8e8'], ['Neutral', '#8c8c8c'], ['Purple', '#8e44ad'], ['Black', '#3a3a3a'], ['Orange', '#e67e22'],
    ];
    const TURNS_PER_SECOND = 20;
    const PAGE_SIZE = 50;
    const GAME_KINDS = { 0: 'unset', 1: 'network', 2: 'single player', 3: 'two-player', 4: 'demo', 5: 'multiplayer' };

    // ---------------------------------------------------------------- helpers

    function el(tag, attributes, ...children) {
        const node = document.createElement(tag);
        for (const [key, value] of Object.entries(attributes || {})) {
            if (value === null || value === undefined || value === false) continue;
            if (key === 'style') node.style.cssText = value;
            else if (key.startsWith('on')) node.addEventListener(key.slice(2), value);
            else node.setAttribute(key, value === true ? '' : value);
        }
        for (const child of children.flat(Infinity)) {
            if (child === null || child === undefined || child === false) continue;
            node.append(child instanceof Node ? child : document.createTextNode(String(child)));
        }
        return node;
    }

    const muted = (...text) => el('span', { class: 'text-muted' }, ...text);
    const plural = (n, word) => n + ' ' + word + (n === 1 ? '' : 's');

    function duration(turns) {
        const seconds = Math.round(turns / TURNS_PER_SECOND);
        if (seconds < 60) return plural(seconds, 'second');
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return plural(minutes, 'minute');
        return Math.floor(minutes / 60) + ' h ' + (minutes % 60) + ' min';
    }

    function playerBadge(index, extra) {
        const [name, colour] = PLAYERS[index] || ['Player ' + index, '#666'];
        return el('span', { style: 'display: inline-flex; align-items: center; gap: 6px;' },
            el('span', { style: 'width: 12px; height: 12px; border-radius: 2px; border: 1px solid #000; background: ' + colour + ';' }),
            el('strong', { style: 'color: white;' }, name), extra ? muted(' ', extra) : null);
    }

    function chips(counts) {
        const entries = Object.entries(counts).sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]));
        if (entries.length === 0) return muted('none');
        return el('span', { style: 'display: flex; flex-wrap: wrap; gap: 4px;' }, entries.map(([name, count]) =>
            el('span', { style: 'background: #1f1f1f; border: 1px solid #333; padding: 1px 6px; font-size: 12px;' }, prettyName(name), count > 1 ? muted(' ×' + count) : null)));
    }

    /** "DARK_MISTRESS" -> "Dark mistress" */
    function prettyName(code) {
        if (!code) return '?';
        const words = code.toLowerCase().replace(/_/g, ' ');
        return words.charAt(0).toUpperCase() + words.slice(1);
    }

    function card(title, ...body) {
        return el('div', { style: 'background: #0D0D0D; border: 2px solid black; padding: 12px; margin-bottom: 12px; color: #ccc;' },
            title ? el('div', { style: 'font-weight: 600; color: white; margin-bottom: 8px;' }, title) : null, body);
    }

    function meter(used, total, label) {
        const share = total > 0 ? used / total : 0;
        const colour = share > 0.9 ? '#d9534f' : share > 0.75 ? '#e67e22' : '#4a90d9';
        return el('div', { style: 'display: grid; grid-template-columns: 150px 1fr 120px; gap: 8px; align-items: center; font-size: 12px; margin-bottom: 3px;' },
            el('span', null, label),
            el('span', { style: 'height: 10px; background: #1a1a1a; position: relative;' },
                el('span', { style: 'position: absolute; top: 0; bottom: 0; left: 0; width: ' + Math.min(100, share * 100).toFixed(1) + '%; background: ' + colour + ';' })),
            el('span', { style: share > 0.9 ? 'color: #ff6b6b;' : null }, used + ' / ' + total + (share > 0.9 ? ' (nearly full)' : '')));
    }

    // ------------------------------------------------------------- summary

    /** Everything the summary needs, gathered in one pass over the game record. */
    function analyseGame(file, game) {
        const facts = { players: new Map(), thingsByClass: {}, thingsUsed: 0, roomsUsed: 0 };
        const playerFacts = (index) => {
            if (!facts.players.has(index)) facts.players.set(index, { creatures: {}, rooms: {}, roomTiles: 0, traps: {}, doors: {} });
            return facts.players.get(index);
        };

        const things = game.entry('things_data');
        facts.thingsTotal = things ? things.field.count : 0;
        for (let i = 0; i < facts.thingsTotal; i++) {
            const thing = game.at('things_data', i);
            if (!(thing.get('alloc_flags') & 1)) continue;
            facts.thingsUsed++;
            const classId = thing.get('class_id');
            const className = KFXS.THING_CLASSES[classId] || 'Class ' + classId;
            facts.thingsByClass[className] = (facts.thingsByClass[className] || 0) + 1;
            const owner = thing.get('owner');
            const model = thing.nameFor('model', thing.get('model')) || 'model ' + thing.get('model');
            const bucket = classId === 5 ? 'creatures' : classId === 8 ? 'traps' : classId === 9 ? 'doors' : null;
            if (bucket) {
                const counts = playerFacts(owner)[bucket];
                counts[model] = (counts[model] || 0) + 1;
            }
        }

        const rooms = game.entry('rooms');
        facts.roomsTotal = rooms ? rooms.field.count : 0;
        for (let i = 0; i < facts.roomsTotal; i++) {
            const room = game.at('rooms', i);
            if (!(room.get('alloc_flags') & 1)) continue;
            facts.roomsUsed++;
            const player = playerFacts(room.get('owner'));
            const kind = room.nameFor('kind', room.get('kind')) || 'kind ' + room.get('kind');
            player.rooms[kind] = (player.rooms[kind] || 0) + 1;
            player.roomTiles += room.get('slabs_count') || 0;
        }

        const players = game.entry('players');
        for (let p = 0; p < (players ? players.field.count : 0); p++) {
            const info = game.at('players', p);
            const flags = info.get('allocflags');
            if (!(flags & 1)) continue;
            const player = playerFacts(p);
            player.exists = true;
            player.name = info.get('player_name');
            player.computer = (flags & 0x40) !== 0;
            player.victory = info.get('victory_state');
            const dungeon = game.at('dungeon', p);
            if (dungeon) {
                player.gold = dungeon.get('total_money_owned');
                player.creatureCount = dungeon.get('num_active_creatrs');
                player.diggerCount = dungeon.get('num_active_diggers');
                player.score = dungeon.get('total_score');
                const heart = dungeon.get('dnheart_idx');
                if (heart > 0 && heart < facts.thingsTotal) {
                    const heartThing = game.at('things_data', heart);
                    if (heartThing.get('alloc_flags') & 1) player.heartHealth = heartThing.get('health');
                }
            }
        }
        return facts;
    }

    function renderSummary(file, title, crashGameVersion) {
        const meta = file.root('META', 'CatalogueEntry');
        const header = file.root('PHDR', 'PacketSaveHead');
        const game = file.root('GAME', 'Game');
        const metaValues = meta ? meta.scalars() : {};
        const sections = [];

        // What the file is
        const build = metaValues.game_ver_major !== undefined
            ? [metaValues.game_ver_major, metaValues.game_ver_minor, metaValues.game_ver_release, metaValues.game_ver_build].join('.') : null;
        const levelName = metaValues.campaign_name
            ? metaValues.campaign_name + ', level ' + metaValues.level_num
            : 'Level ' + (metaValues.level_num ?? '?');
        const lines = [];
        lines.push(el('div', { style: 'font-size: 1.1em; color: white; margin-bottom: 4px;' }, levelName,
            metaValues.textname && file.kind === 'SAVE' ? muted(' (saved as "' + metaValues.textname + '")') : null));
        if (game) {
            const turn = game.get('play_gameturn');
            lines.push(el('div', null, 'Game turn ' + turn + ', ' + duration(turn) + ' into the level. ',
                muted(GAME_KINDS[game.get('game_kind')] ? 'A ' + GAME_KINDS[game.get('game_kind')] + ' game.' : '')));
        }
        if (header) {
            const started = header.get('timestamp');
            lines.push(el('div', null, 'Replay recorded from the start of the level',
                typeof started === 'number' && started > 0 ? ' on ' + new Date(started * 1000).toISOString().slice(0, 16).replace('T', ' ') + ' UTC' : '', '.',
                file.replayDataLength ? muted(' ' + Math.round(file.replayDataLength / 1024) + ' KB of recorded input follows the header.') : null));
        }
        if (build) {
            let note = null;
            const crashBuild = /(\d+)\.(\d+)\.(\d+)\.(\d+)/.exec(crashGameVersion || '');
            if (crashBuild && crashBuild.slice(1).join('.') !== build) {
                note = el('span', { style: 'color: #e6a23c;' }, ' The crash happened on ' + crashBuild.slice(1).join('.') + ', a different build.');
            }
            lines.push(el('div', null, muted('Made by KeeperFX ' + build + ', file format ' + file.major + '.' + file.minor + '.'), note));
        }
        sections.push(card(title, lines));

        // Players
        const playerRows = [];
        if (game) {
            const facts = analyseGame(file, game);
            for (const [index, player] of [...facts.players.entries()].sort((a, b) => a[0] - b[0])) {
                if (!player.exists) continue;
                const who = player.computer ? 'computer' : 'human' + (player.name ? ', ' + player.name : '');
                const stats = [];
                if (player.gold !== undefined) stats.push(player.gold.toLocaleString() + ' gold');
                if (player.creatureCount !== undefined) stats.push(plural(player.creatureCount, 'creature'));
                if (player.diggerCount) stats.push(plural(player.diggerCount, 'digger'));
                if (player.heartHealth !== undefined) stats.push('heart health ' + player.heartHealth.toLocaleString());
                playerRows.push(el('div', { style: 'border-top: 1px solid #222; padding: 8px 0;' },
                    el('div', { style: 'margin-bottom: 4px;' }, playerBadge(index, who), muted('  ' + stats.join(' · '))),
                    el('div', { style: 'display: grid; grid-template-columns: 90px 1fr; gap: 4px 8px; font-size: 13px;' },
                        muted('Creatures'), chips(player.creatures),
                        muted('Rooms'), el('span', null, chips(player.rooms), player.roomTiles ? muted(' ' + player.roomTiles + ' tiles') : null),
                        Object.keys(player.traps).length ? [muted('Traps'), chips(player.traps)] : null,
                        Object.keys(player.doors).length ? [muted('Doors'), chips(player.doors)] : null)));
            }
            sections.push(card('Players', playerRows.length ? playerRows : muted('No players in this save.')));

            // How full the game's fixed-size tables were: a full one is a common crash cause
            sections.push(card('How full the game was',
                el('p', { class: 'text-muted', style: 'font-size: 12px; margin-bottom: 8px;' }, 'The game has fixed limits on things and rooms. Running out can cause odd behaviour or crashes.'),
                meter(facts.thingsUsed, facts.thingsTotal, 'Things'),
                meter(facts.roomsUsed, facts.roomsTotal, 'Rooms'),
                el('div', { style: 'font-size: 12px; margin-top: 6px;' }, muted('Things by type: '), chips(facts.thingsByClass))));
        } else if (header) {
            const exist = header.get('players_exist') || 0, computer = header.get('players_comp') || 0;
            for (let p = 0; p < PLAYERS.length; p++) {
                if (exist & (1 << p)) playerRows.push(el('div', { style: 'margin-bottom: 4px;' }, playerBadge(p, (computer & (1 << p)) ? 'computer' : 'human')));
            }
            sections.push(card('Players', playerRows.length ? playerRows : muted('No players recorded.'),
                el('p', { class: 'text-muted', style: 'font-size: 12px; margin: 8px 0 0;' }, 'A replay only holds the start of the level and the players\' input. Play it to see what happened.')));
        }

        // Config the level changed
        if (file.configChanges.length) sections.push(renderConfigChanges(file));
        return sections;
    }

    function renderConfigChanges(file) {
        const list = el('tbody');
        const filter = el('input', { type: 'search', class: 'form-control form-control-sm', placeholder: 'Filter, e.g. creature or room_cfgstats', style: 'max-width: 320px; margin-bottom: 6px;' });
        const fill = () => {
            const term = filter.value.trim().toLowerCase();
            const rows = file.configChanges.filter((c) => !term || c.path.toLowerCase().includes(term));
            list.replaceChildren(...rows.slice(0, 300).map((c) => el('tr', null,
                el('td', { style: 'font-family: monospace; font-size: 12px; word-break: break-all;' }, c.path),
                el('td', { style: 'font-size: 12px;' }, String(c.value)))),
            rows.length > 300 ? el('tr', null, el('td', { colspan: 2, class: 'text-muted' }, (rows.length - 300) + ' more, narrow the filter')) : null);
        };
        filter.addEventListener('input', fill);
        fill();
        return el('details', { style: 'margin-bottom: 12px;' },
            el('summary', null, 'Settings the level changed (' + file.configChanges.length + ')'),
            el('div', { style: 'padding: 8px 0;' },
                el('p', { class: 'text-muted', style: 'font-size: 12px;' }, 'Values the level\'s script or config changed from the normal game config, as saved.'),
                filter,
                el('table', { class: 'table table-sm' }, el('thead', null, el('tr', null, el('th', null, 'Setting'), el('th', null, 'Value'))), list)));
    }

    // ------------------------------------------------------------ explorer

    /** Short description of a struct element, so long arrays can be scanned. */
    function describe(node) {
        try {
            switch (node.structName) {
                case 'Thing': {
                    if (!(node.get('alloc_flags') & 1)) return 'unused';
                    const classId = node.get('class_id');
                    const model = node.nameFor('model', node.get('model'));
                    return (KFXS.THING_CLASSES[classId] || 'class ' + classId) + (model ? ' ' + prettyName(model) : '') + ', ' + (PLAYERS[node.get('owner')] || ['player ' + node.get('owner')])[0];
                }
                case 'Room':
                    if (!(node.get('alloc_flags') & 1)) return 'unused';
                    return prettyName(node.nameFor('kind', node.get('kind'))) + ', ' + (PLAYERS[node.get('owner')] || ['player ' + node.get('owner')])[0];
                case 'PlayerInfo':
                    return (node.get('allocflags') & 1) ? ((node.get('allocflags') & 0x40) ? 'computer' : 'human') : 'not in game';
                default:
                    return null;
            }
        } catch (error) {
            return null;
        }
    }

    function scalarText(node, field, value) {
        if (typeof value === 'bigint') return value.toString();
        if (typeof value === 'string') return JSON.stringify(value);
        const name = node.nameFor(field.name, value);
        return String(value) + (name ? ' (' + name + ')' : '');
    }

    /** Collapsible rows for a struct's fields. */
    function renderStruct(node) {
        const rows = el('div', { style: 'padding-left: 14px; border-left: 1px solid #2a2a2a;' });
        let layout;
        try {
            layout = node.layout();
        } catch (error) {
            return el('div', { style: 'color: #ff6b6b;' }, 'Could not read ' + node.structName + ': ' + error.message);
        }
        for (const entry of layout) rows.append(renderField(node, entry));
        return rows;
    }

    function fieldLabel(field) {
        const type = field.type === KFXS.T_STRUCT ? field.subType : field.type === KFXS.T_UNION ? 'union' : KFXS.TYPE_NAMES[field.type];
        const dims = field.type === KFXS.T_STR ? field.dims.slice(0, -1) : field.dims;
        return [el('span', { style: 'color: #e0c080;' }, field.name), muted(' ' + type + (dims.length ? '[' + dims.join('][') + ']' : ''))];
    }

    function renderField(node, entry) {
        const { field } = entry;
        const isString = field.type === KFXS.T_STR;
        const isArray = isString ? field.dims.length > 1 : field.dims.length > 0;

        // Single scalar or string: one line
        if (!isArray && field.type !== KFXS.T_STRUCT && field.type !== KFXS.T_UNION) {
            return el('div', { style: 'font-size: 12px; padding: 1px 0;' }, fieldLabel(field), ' = ', el('span', { style: 'color: white;' }, scalarText(node, field, node.valueOf(entry))));
        }

        // Struct, union or array: expandable
        const body = el('div');
        const details = el('details', null, el('summary', { style: 'font-size: 12px; cursor: pointer;' }, fieldLabel(field), summaryHint(node, entry)), body);
        details.addEventListener('toggle', () => {
            if (!details.open || body.childElementCount) return;
            if (field.type === KFXS.T_UNION) body.append(renderUnion(node.valueOf(entry)));
            else if (!isArray) body.append(renderStruct(node.valueOf(entry)));
            else body.append(renderArray(node, entry));
        }, { once: false });
        return details;
    }

    function summaryHint(node, entry) {
        const { field } = entry;
        if (field.type === KFXS.T_UNION) {
            const value = node.valueOf(entry);
            return muted(' → ' + (value.member || 'nothing'));
        }
        if (field.type !== KFXS.T_STRUCT && field.type !== KFXS.T_STR && field.count <= 16) {
            const values = [];
            for (let i = 0; i < field.count; i++) values.push(node.at(field.name, i));
            return muted(' = [' + values.join(', ') + ']');
        }
        return null;
    }

    function renderUnion(value) {
        if (!value.member) return muted('nothing stored');
        if (value.raw) return el('div', { style: 'font-family: monospace; font-size: 11px; word-break: break-all;' }, value.member + ': ' + [...value.raw].map((b) => b.toString(16).padStart(2, '0')).join(' '));
        return el('div', null, muted(value.member), renderStruct(value.node));
    }

    /** Array elements, a page at a time, with an "in use only" filter where structs have one. */
    function renderArray(node, entry) {
        const { field } = entry;
        const count = field.type === KFXS.T_STR ? field.count / field.dims[field.dims.length - 1] : field.count;
        const container = el('div', { style: 'padding-left: 14px; border-left: 1px solid #2a2a2a;' });
        const list = el('div');
        let usedOnly = false;
        let shown = 0;
        let indices = null;

        const inUse = (i) => {
            const element = node.at(field.name, i);
            if (!(element instanceof Object) || !element.layout) return true;
            const flag = element.entry('alloc_flags') || element.entry('allocflags');
            return flag ? (element.valueOf(flag) & 1) !== 0 : true;
        };
        const elementRow = (i) => {
            if (field.type === KFXS.T_STR) {
                const strings = node.valueOf(entry);
                const flat = Array.isArray(strings) ? strings.flat(Infinity) : [strings];
                return el('div', { style: 'font-size: 12px;' }, muted('[' + i + '] '), JSON.stringify(flat[i]));
            }
            if (field.type !== KFXS.T_STRUCT) {
                return el('div', { style: 'font-size: 12px;' }, muted('[' + i + '] '), scalarText(node, field, node.at(field.name, i)));
            }
            const element = node.at(field.name, i);
            const body = el('div');
            const details = el('details', null, el('summary', { style: 'font-size: 12px; cursor: pointer;' }, muted('[' + i + '] '), describe(element) || field.subType), body);
            details.addEventListener('toggle', () => { if (details.open && !body.childElementCount) body.append(renderStruct(element)); });
            return details;
        };
        const more = el('button', { type: 'button', class: 'btn btn-sm btn-outline-secondary', style: 'margin: 4px 0; font-size: 11px;' }, 'Show more');
        const showMore = () => {
            const source = indices || null;
            const total = source ? source.length : count;
            const end = Math.min(total, shown + PAGE_SIZE);
            for (; shown < end; shown++) list.append(elementRow(source ? source[shown] : shown));
            more.hidden = shown >= total;
            more.textContent = 'Show more (' + (total - shown) + ' left)';
        };
        more.addEventListener('click', showMore);

        if (field.type === KFXS.T_STRUCT) {
            const sample = node.at(field.name, 0);
            if (sample && (sample.entry('alloc_flags') || sample.entry('allocflags'))) {
                const toggle = el('input', { type: 'checkbox', checked: true });
                usedOnly = true;
                const reset = () => {
                    usedOnly = toggle.checked;
                    indices = null;
                    if (usedOnly) {
                        indices = [];
                        for (let i = 0; i < count; i++) if (inUse(i)) indices.push(i);
                    }
                    shown = 0;
                    list.replaceChildren();
                    showMore();
                };
                toggle.addEventListener('change', reset);
                container.append(el('label', { style: 'font-size: 12px; display: block; margin: 2px 0;' }, toggle, ' in use only'));
                container.append(list, more);
                reset();
                return container;
            }
        }
        container.append(list, more);
        showMore();
        return container;
    }

    /** Resolves "things_data[45].health" against the roots; returns a rendered result. */
    function goToPath(roots, path) {
        const tokens = path.trim().match(/[A-Za-z_:][\w:]*|\[\d+\]/g);
        if (!tokens || tokens[0].startsWith('[')) return muted('Type a path like things_data[45] or dungeon[0].total_money_owned');
        let current = roots[0].node;
        let shown = [];
        for (let t = 0; t < tokens.length; t++) {
            const name = tokens[t];
            const entry = current.entry(name);
            if (!entry) return muted('No field "' + name + '" in ' + current.structName + '.');
            const index = tokens[t + 1] && tokens[t + 1].startsWith('[') ? Number(tokens[++t].slice(1, -1)) : null;
            shown.push(name + (index !== null ? '[' + index + ']' : ''));
            const value = index !== null ? current.at(name, index) : current.valueOf(entry);
            if (value === undefined) return muted(shown.join('.') + ' is out of range.');
            const isLast = t === tokens.length - 1;
            if (value && value.layout) {
                current = value;
                if (isLast) break;
                continue;
            }
            if (!isLast) return muted(shown.join('.') + ' has no fields.');
            if (value && value.member !== undefined) return el('div', null, shown.join('.'), ' → ', renderUnion(value));
            const text = Array.isArray(value)
                ? '[' + value.flat(Infinity).slice(0, 64).join(', ') + (value.flat(Infinity).length > 64 ? ', ...' : '') + ']'
                : scalarText(current, entry.field, value);
            return el('div', null, shown.join('.'), ' = ', el('strong', { style: 'color: white;' }, text));
        }
        return el('div', null, muted(shown.join('.') + ' (' + current.structName + (describe(current) ? ': ' + describe(current) : '') + ')'), renderStruct(current));
    }

    function renderExplorer(file) {
        const roots = [
            ['GAME', 'Game', 'Game state'], ['ILVL', 'IntralevelData', 'Campaign progress'],
            ['PHDR', 'PacketSaveHead', 'Replay header'], ['META', 'CatalogueEntry', 'Title'],
        ].map(([chunk, struct, label]) => ({ node: file.root(chunk, struct), label, chunk }))
            .filter((r) => r.node);
        file.records && ['HighScore', 'IntralevelData'].forEach((struct) => {
            if (file.chunks.has('RECS') && file.schema.structs.has(struct)) {
                file.records(struct).forEach((node, i) => roots.push({ node, label: struct + ' record ' + i, chunk: 'RECS' }));
            }
        });

        const result = el('div', { style: 'margin: 6px 0;' });
        const input = el('input', { type: 'search', class: 'form-control form-control-sm', placeholder: 'Go to, e.g. things_data[45] or dungeon[0].total_money_owned', style: 'max-width: 420px;' });
        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            try { result.replaceChildren(goToPath(roots, input.value)); } catch (error) { result.replaceChildren(muted(error.message)); }
        });

        return el('details', { style: 'margin-bottom: 12px;' },
            el('summary', null, 'Explore every field'),
            el('div', { style: 'padding: 8px 0;' },
                el('p', { class: 'text-muted', style: 'font-size: 12px;' }, 'Everything stored in the file, read using the schema saved inside it. Long lists load ' + PAGE_SIZE + ' at a time. Press Enter in the box to jump to a field.'),
                input, result,
                roots.map((r) => {
                    const body = el('div');
                    const details = el('details', { style: 'margin-top: 4px;' }, el('summary', { style: 'cursor: pointer;' }, r.label, muted(' ' + r.node.structName + ', ' + r.chunk + ' chunk')), body);
                    details.addEventListener('toggle', () => { if (details.open && !body.childElementCount) body.append(renderStruct(r.node)); });
                    return details;
                })));
    }

    // ---------------------------------------------------------------- entry

    async function render(container, buffer, title, crashGameVersion) {
        let file;
        try {
            file = await KFXS.open(buffer);
        } catch (error) {
            container.replaceChildren(card(title, muted('Can\'t show this file here: ' + error.message)));
            return;
        }
        container.replaceChildren(...renderSummary(file, title, crashGameVersion), renderExplorer(file));
    }

    async function fetchFile(url, name) {
        let response = await fetch(url, { credentials: 'same-origin' });
        if (!response.ok) throw new Error('download failed (' + response.status + ')');
        if (/\.gz$/i.test(name || url)) response = new Response(response.body.pipeThrough(new DecompressionStream('gzip')));
        return response.arrayBuffer();
    }

    document.querySelectorAll('[data-kfxs-url]').forEach(async (container) => {
        const title = container.getAttribute('data-kfxs-title') || 'Saved game';
        const section = container.closest('#game-state');
        if (section) section.hidden = false;
        if (/\.(7z|zip)$/i.test(container.getAttribute('data-kfxs-name') || '')) {
            container.replaceChildren(card(title, muted('This file is in a ' + container.getAttribute('data-kfxs-name').split('.').pop() + ' archive, which the browser can\'t open. Download it to look inside.')));
            container.hidden = false;
            return;
        }
        container.hidden = false;
        container.replaceChildren(card(title, muted('Reading...')));
        try {
            await render(container, await fetchFile(container.getAttribute('data-kfxs-url'), container.getAttribute('data-kfxs-name')), title, container.getAttribute('data-kfxs-version'));
        } catch (error) {
            container.replaceChildren(card(title, muted('Could not read the file: ' + error.message)));
        }
    });

    document.querySelectorAll('[data-kfxs-picker]').forEach((container) => {
        const output = el('div', { style: 'margin-top: 12px;' });
        const input = el('input', { type: 'file', class: 'form-control', accept: '.sav,.fxpkt,.pck,.cid,.gz' });
        const open = async (fileObject) => {
            output.replaceChildren(card(fileObject.name, muted('Reading...')));
            const buffer = await fileObject.arrayBuffer();
            const bytes = /\.gz$/i.test(fileObject.name)
                ? await new Response(new Blob([buffer]).stream().pipeThrough(new DecompressionStream('gzip'))).arrayBuffer()
                : buffer;
            await render(output, bytes, fileObject.name);
        };
        input.addEventListener('change', () => { if (input.files[0]) open(input.files[0]); });
        container.addEventListener('dragover', (event) => event.preventDefault());
        container.addEventListener('drop', (event) => {
            event.preventDefault();
            if (event.dataTransfer.files[0]) open(event.dataTransfer.files[0]);
        });
        container.replaceChildren(
            el('p', null, 'Pick or drop a save (.sav), replay (.fxpkt) or campaign progress (.cid) file. It is read in your browser and not uploaded.'),
            input, output);
        container.hidden = false;
    });
})();
