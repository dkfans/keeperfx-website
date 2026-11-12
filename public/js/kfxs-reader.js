/**
 * Reader for KeeperFX KFXS files (saves, replay headers, campaign progress).
 *
 * The format is self-describing: every file carries a schema (SCHM) with the name, type and
 * array size of every field, so this reads any version without knowing the game's structs.
 * See src/kfx/save/FORMAT.txt in dkfans/keeperfx for the format.
 *
 * Records can be large (a save's GAME record is ~18 MB unpacked) and unions make some of
 * them variable length, so values are decoded lazily: a Node points at a struct in the
 * data, and its fields are found by walking only as far as needed.
 */
(function (root) {
    'use strict';

    const TYPE_NAMES = ['?', 'u8', 'i8', 'u16', 'i16', 'u32', 'i32', 'u64', 'i64', 'f32', 'f64', 'bool', 'str', 'struct', 'union'];
    const TYPE_SIZES = [0, 1, 1, 2, 2, 4, 4, 8, 8, 4, 8, 1, 1, 0, 0];
    const T_STR = 12, T_STRUCT = 13, T_UNION = 14;

    const CHUNK_ZLIB = 0x01;

    /** NAMS domain ids (FORMAT.txt section 9) */
    const DOMAINS = {
        1: 'creature', 2: 'room', 3: 'trap', 4: 'door', 5: 'object', 6: 'spell', 7: 'power',
        8: 'shot', 9: 'effect', 10: 'effect element', 11: 'effect generator', 12: 'instance',
    };

    /**
     * Fields that hold an index into a config table, from the game's schema tables
     * (".name_domain" in src/kfx/save/core/schema). The file doesn't record this.
     */
    const FIELD_DOMAINS = {
        'CastedSpellData.spkind': 6,
        'CreatureControl.active_disease_spell': 6,
        'CreatureControl.active_instance_id': 12,
        'CreatureControl.active_teleport_spell': 6,
        'CreatureControl.active_timebomb_spell': 6,
        'CreatureControl.instance_id': 12,
        'CreatureControl.summon_spl_idx': 6,
        'Dungeon.manufacture_kind': 'byManufactureClass',
        'Room.kind': 2,
        'Thing.model': 'byThingClass',
        'UserState.chosen_power_kind': 7,
    };

    /** Thing.class_id values (enum ThingClassIndex) and the table Thing.model indexes for each. */
    const THING_CLASSES = ['Empty', 'Object', 'Shot', 'Effect element', 'Dead creature', 'Creature', 'Effect',
        'Effect generator', 'Trap', 'Door', 'Unused 10', 'Unused 11', 'Ambient sound', 'Cave-in'];
    const THING_CLASS_DOMAIN = { 1: 5, 2: 8, 3: 10, 4: 1, 5: 1, 6: 9, 7: 11, 8: 3, 9: 4 };

    class KfxsError extends Error {}

    async function inflate(bytes) {
        const stream = new Blob([bytes]).stream().pipeThrough(new DecompressionStream('deflate'));
        return new Uint8Array(await new Response(stream).arrayBuffer());
    }

    const latin1 = new TextDecoder('windows-1252');

    // ------------------------------------------------------------------ schema

    function parseSchema(bytes) {
        const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
        let p = 0;
        const need = (n) => { if (p + n > bytes.length) throw new KfxsError('schema is damaged'); };
        const u8 = () => { need(1); return view.getUint8(p++); };
        const u16 = () => { need(2); const v = view.getUint16(p, true); p += 2; return v; };
        const u32 = () => { need(4); const v = view.getUint32(p, true); p += 4; return v; };
        const str = () => { const n = u16(); need(n); const s = latin1.decode(bytes.subarray(p, p + n)); p += n; return s; };

        const structs = new Map();
        const structCount = u32();
        for (let s = 0; s < structCount; s++) {
            const name = str();
            const fields = [];
            const fieldCount = u16();
            for (let f = 0; f < fieldCount; f++) {
                const field = { name: str(), type: u8(), flags: u8(), dims: [] };
                const dimCount = u8();
                for (let d = 0; d < dimCount; d++) field.dims.push(u32());
                field.subType = str();
                if (field.type === T_UNION) {
                    field.members = [];
                    const memberCount = u16();
                    for (let m = 0; m < memberCount; m++) field.members.push({ name: str(), subType: str(), rawSize: u32() });
                }
                if (field.type === 0 || field.type > 14 || dimCount > 3) throw new KfxsError('schema is damaged');
                field.count = field.dims.reduce((a, b) => a * b, 1);
                fields.push(field);
            }
            structs.set(name, { name, fields });
        }
        if (p !== bytes.length) throw new KfxsError('schema is damaged');

        // Fixed size of each struct, or null when it contains a union somewhere
        const sizes = new Map();
        const sizeOfStruct = (name, depth = 0) => {
            if (sizes.has(name)) return sizes.get(name);
            const struct = structs.get(name);
            if (!struct || depth > 16) throw new KfxsError('schema refers to unknown struct ' + name);
            let total = 0;
            for (const field of struct.fields) {
                const one = field.type === T_UNION ? null
                    : field.type === T_STRUCT ? sizeOfStruct(field.subType, depth + 1)
                    : TYPE_SIZES[field.type];
                field.fixedSize = one === null ? null : one * field.count;
                if (total !== null) total = field.fixedSize === null ? null : total + field.fixedSize;
            }
            sizes.set(name, total);
            return total;
        };
        for (const name of structs.keys()) sizeOfStruct(name);
        return { structs, sizeOfStruct };
    }

    // ------------------------------------------------------------------- file

    async function open(buffer) {
        const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
        const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
        const magic = [0x4B, 0x46, 0x58, 0x53, 0x00, 0xFF, 0x1A, 0x0A];
        if (bytes.length < 20 || magic.some((b, i) => bytes[i] !== b)) {
            throw new KfxsError('not a KFXS file (older saves and replays used a different format)');
        }
        const file = {
            major: view.getUint16(8, true),
            minor: view.getUint16(10, true),
            kind: latin1.decode(bytes.subarray(12, 16)),
            chunks: new Map(),
            size: bytes.length,
        };
        if (file.major !== 1) throw new KfxsError('format version ' + file.major + ' is not supported');

        let offset = 20;
        while (offset + 20 <= bytes.length) {
            const id = latin1.decode(bytes.subarray(offset, offset + 4));
            const flags = view.getUint32(offset + 4, true);
            const storedLength = view.getUint32(offset + 8, true);
            const rawLength = view.getUint32(offset + 12, true);
            if (offset + 20 + storedLength > bytes.length) throw new KfxsError('file is cut short in chunk ' + id);
            const stored = bytes.subarray(offset + 20, offset + 20 + storedLength);
            file.chunks.set(id, { id, flags, storedLength, rawLength, stored, data: null });
            offset += 20 + storedLength;
            if (id === 'TURN') break; // replay packets follow, in their own codec
        }
        file.replayDataLength = bytes.length - offset;

        for (const chunk of file.chunks.values()) {
            chunk.data = (chunk.flags & CHUNK_ZLIB) ? await inflate(chunk.stored) : chunk.stored;
        }

        if (!file.chunks.has('SCHM')) throw new KfxsError('file has no schema');
        file.schema = parseSchema(file.chunks.get('SCHM').data);
        file.names = file.chunks.has('NAMS') ? parseNames(file.chunks.get('NAMS').data) : new Map();
        file.configChanges = file.chunks.has('CONF') ? parseConfigChanges(file.chunks.get('CONF').data) : [];

        /** Root node of a chunk's record, e.g. root('GAME', 'Game'). */
        file.root = (chunkId, structName) => {
            const chunk = file.chunks.get(chunkId);
            return chunk && file.schema.structs.has(structName) ? new Node(file, chunk.data, structName, 0, chunkId) : null;
        };
        /** Every record of a RECS chunk. */
        file.records = (structName) => {
            const chunk = file.chunks.get('RECS');
            if (!chunk) return [];
            const nodes = [];
            for (let at = 0; at < chunk.data.length;) {
                const node = new Node(file, chunk.data, structName, at, 'RECS');
                const size = node.size();
                if (size === 0) break;
                nodes.push(node);
                at += size;
            }
            return nodes;
        };
        /** Name of an entry of a config table, or null. */
        file.nameOf = (domain, index) => {
            const table = file.names.get(domain);
            return table && index >= 0 && index < table.length ? table[index] : null;
        };
        return file;
    }

    function parseNames(bytes) {
        const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
        const names = new Map();
        let p = 0;
        const domainCount = view.getUint16(p, true); p += 2;
        for (let d = 0; d < domainCount && p + 6 <= bytes.length; d++) {
            const id = view.getUint16(p, true);
            const count = view.getUint32(p + 2, true);
            p += 6;
            const list = [];
            for (let i = 0; i < count && p + 2 <= bytes.length; i++) {
                const n = view.getUint16(p, true);
                list.push(latin1.decode(bytes.subarray(p + 2, p + 2 + n)));
                p += 2 + n;
            }
            names.set(id, list);
        }
        return names;
    }

    function parseConfigChanges(bytes) {
        const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
        const changes = [];
        let p = 4;
        const count = view.getUint32(0, true);
        for (let i = 0; i < count && p + 3 <= bytes.length; i++) {
            const pathLength = view.getUint16(p, true);
            const path = latin1.decode(bytes.subarray(p + 2, p + 2 + pathLength));
            p += 2 + pathLength;
            const type = view.getUint8(p++);
            let value;
            if (type === T_STR) {
                const n = view.getUint16(p, true);
                value = latin1.decode(bytes.subarray(p + 2, p + 2 + n));
                p += 2 + n;
            } else {
                value = readScalar(view, p, type);
                p += TYPE_SIZES[type];
            }
            changes.push({ path, type: TYPE_NAMES[type], value });
        }
        return changes;
    }

    function readScalar(view, at, type) {
        switch (type) {
            case 1: return view.getUint8(at);
            case 2: return view.getInt8(at);
            case 3: return view.getUint16(at, true);
            case 4: return view.getInt16(at, true);
            case 5: return view.getUint32(at, true);
            case 6: return view.getInt32(at, true);
            case 7: { const v = view.getBigUint64(at, true); return v <= BigInt(Number.MAX_SAFE_INTEGER) ? Number(v) : v; }
            case 8: { const v = view.getBigInt64(at, true); return (v <= BigInt(Number.MAX_SAFE_INTEGER) && v >= BigInt(Number.MIN_SAFE_INTEGER)) ? Number(v) : v; }
            case 9: return view.getFloat32(at, true);
            case 10: return view.getFloat64(at, true);
            case 11: return view.getUint8(at) !== 0;
            default: throw new KfxsError('unknown stored type ' + type);
        }
    }

    // ------------------------------------------------------------------- node

    /** A struct in a record. Fields are located on first use and cached. */
    class Node {
        constructor(file, data, structName, offset, chunkId) {
            this.file = file;
            this.data = data;
            this.view = new DataView(data.buffer, data.byteOffset, data.byteLength);
            this.struct = file.schema.structs.get(structName);
            this.structName = structName;
            this.offset = offset;
            this.chunkId = chunkId;
            this._layout = null;
        }

        /** [{field, offset, size}] for every field, walking unions as needed. */
        layout() {
            if (this._layout) return this._layout;
            const layout = [];
            let at = this.offset;
            for (const field of this.struct.fields) {
                const size = field.fixedSize !== null ? field.fixedSize : this._variableSize(field, at);
                layout.push({ field, offset: at, size });
                at += size;
            }
            if (at > this.data.length) throw new KfxsError(this.structName + ' runs past the end of its chunk');
            this._layout = layout;
            return layout;
        }

        size() {
            const fixed = this.file.schema.sizeOfStruct(this.structName);
            if (fixed !== null) return fixed;
            const layout = this.layout();
            const last = layout[layout.length - 1];
            return last ? last.offset + last.size - this.offset : 0;
        }

        _variableSize(field, at) {
            let total = 0;
            for (let i = 0; i < field.count; i++) {
                const one = field.type === T_UNION ? this._unionSize(field, at + total)
                    : new Node(this.file, this.data, field.subType, at + total, this.chunkId).size();
                total += one;
            }
            return total;
        }

        _unionSize(field, at) {
            const code = this.view.getUint8(at);
            if (code === 0) return 1;
            const member = field.members[code - 1];
            if (!member) throw new KfxsError('union ' + field.name + ' has an unknown member ' + code);
            if (!member.subType) return 1 + member.rawSize;
            const tableSize = new Node(this.file, this.data, member.subType, at + 1, this.chunkId).size();
            const tailLength = this.view.getUint16(at + 1 + tableSize, true);
            return 1 + tableSize + 2 + (tailLength > 0 ? 2 + tailLength : 0);
        }

        /** Layout entry of a field by name. */
        entry(name) {
            return this.layout().find((e) => e.field.name === name) || null;
        }

        /**
         * Value of a field: a number/boolean/string, a Node for a struct, an array
         * (nested for several dimensions) of those, or {member, node|raw} for a union.
         */
        get(name) {
            const e = this.entry(name);
            return e ? this.valueOf(e) : undefined;
        }

        /** One element of an array field, without decoding the rest. */
        at(name, index) {
            const e = this.entry(name);
            if (!e || index < 0 || index >= e.field.count) return undefined;
            return this._element(e, index);
        }

        valueOf(e) {
            const { field } = e;
            if (field.type === T_STR) return this._strings(e);
            if (field.dims.length === 0) return this._element(e, 0);
            const flat = [];
            for (let i = 0; i < field.count; i++) flat.push(this._element(e, i));
            return reshape(flat, field.dims);
        }

        _element(e, index) {
            const { field } = e;
            if (field.type === T_STR) {
                const capacity = field.dims[field.dims.length - 1] || 1;
                return this._string(e.offset + index * capacity, capacity);
            }
            if (field.type === T_STRUCT) {
                const elementSize = this.file.schema.sizeOfStruct(field.subType);
                if (elementSize !== null) return new Node(this.file, this.data, field.subType, e.offset + index * elementSize, this.chunkId);
                return new Node(this.file, this.data, field.subType, this._elementOffset(e, index), this.chunkId);
            }
            if (field.type === T_UNION) return this._union(field, e.offset);
            const size = TYPE_SIZES[field.type];
            return readScalar(this.view, e.offset + index * size, field.type);
        }

        /** Offset of element i of an array of variable-size structs (cached walk). */
        _elementOffset(e, index) {
            if (!e.elementOffsets) {
                e.elementOffsets = new Float64Array(e.field.count);
                let at = e.offset;
                for (let i = 0; i < e.field.count; i++) {
                    e.elementOffsets[i] = at;
                    at += new Node(this.file, this.data, e.field.subType, at, this.chunkId).size();
                }
            }
            return e.elementOffsets[index];
        }

        _union(field, at) {
            const code = this.view.getUint8(at);
            if (code === 0) return { member: null };
            const member = field.members[code - 1];
            if (!member.subType) return { member: member.name, raw: this.data.subarray(at + 1, at + 1 + member.rawSize) };
            const node = new Node(this.file, this.data, member.subType, at + 1, this.chunkId);
            return { member: member.name, node };
        }

        _string(at, capacity) {
            const bytes = this.data.subarray(at, at + capacity);
            const end = bytes.indexOf(0);
            return latin1.decode(end >= 0 ? bytes.subarray(0, end) : bytes);
        }

        _strings(e) {
            const { field } = e;
            const capacity = field.dims[field.dims.length - 1] || 1;
            const count = field.count / capacity;
            if (field.dims.length <= 1) return this._string(e.offset, capacity);
            const flat = [];
            for (let i = 0; i < count; i++) flat.push(this._string(e.offset + i * capacity, capacity));
            return reshape(flat, field.dims.slice(0, -1));
        }

        /** Config table name for a field's value, when the field indexes one. */
        nameFor(fieldName, value) {
            let domain = FIELD_DOMAINS[this.structName + '.' + fieldName];
            if (domain === 'byThingClass') domain = THING_CLASS_DOMAIN[this.get('class_id')];
            if (domain === 'byManufactureClass') domain = { 8: 3, 9: 4 }[this.get('manufacture_class')];
            return domain && typeof value === 'number' ? this.file.nameOf(domain, value) : null;
        }

        /** Plain object of the scalar and string fields (structs and unions left out). */
        scalars() {
            const out = {};
            for (const e of this.layout()) {
                if (e.field.type !== T_STRUCT && e.field.type !== T_UNION) out[e.field.name] = this.valueOf(e);
            }
            return out;
        }
    }

    function reshape(flat, dims) {
        if (dims.length <= 1) return flat;
        const inner = dims.slice(1).reduce((a, b) => a * b, 1);
        const out = [];
        for (let i = 0; i < dims[0]; i++) out.push(reshape(flat.slice(i * inner, (i + 1) * inner), dims.slice(1)));
        return out;
    }

    const api = { open, KfxsError, TYPE_NAMES, DOMAINS, THING_CLASSES, THING_CLASS_DOMAIN, FIELD_DOMAINS, T_STR, T_STRUCT, T_UNION };
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
    else root.KFXS = api;
})(typeof window !== 'undefined' ? window : globalThis);
