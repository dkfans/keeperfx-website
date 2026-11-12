/**
 * Crash report viewer.
 *
 * Reads the crash dump (or, without one, the stack trace from the game log) in the browser
 * and explains the crash in plain words. Function names and source lines come from the
 * per-build symbol tables in dkfans/keeperfx-symbols.
 *
 * This only adds to the page: #crash-analysis stays hidden when the script can't run, and
 * everything else on the report works without it.
 */
(function () {
    'use strict';

    const root = document.getElementById('crash-analysis');
    const dataElement = document.getElementById('crash-analysis-data');
    if (!root || !dataElement || typeof DecompressionStream === 'undefined' || typeof BigInt === 'undefined') {
        return;
    }
    const config = JSON.parse(dataElement.textContent);
    const SOURCE_REPO = 'https://github.com/dkfans/keeperfx';

    // ---------------------------------------------------------------- helpers

    const hex = (value, width = 8) => '0x' + (value >>> 0).toString(16).toUpperCase().padStart(width, '0');

    function el(tag, attributes, ...children) {
        const node = document.createElement(tag);
        for (const [key, value] of Object.entries(attributes || {})) {
            if (value === null || value === undefined || value === false) continue;
            if (key === 'style') node.style.cssText = value;
            else node.setAttribute(key, value === true ? '' : value);
        }
        for (const child of children.flat(Infinity)) {
            if (child === null || child === undefined || child === false) continue;
            node.append(child instanceof Node ? child : document.createTextNode(String(child)));
        }
        return node;
    }

    async function gunzip(response) {
        return new Response(response.body.pipeThrough(new DecompressionStream('gzip')));
    }

    /** Index of the last element <= value in a sorted typed array, or -1. */
    function lastAtOrBelow(sorted, value) {
        let low = 0, high = sorted.length - 1, found = -1;
        while (low <= high) {
            const mid = (low + high) >> 1;
            if (sorted[mid] <= value) { found = mid; low = mid + 1; } else { high = mid - 1; }
        }
        return found;
    }

    // ------------------------------------------------------- plain-word texts

    const EXCEPTIONS = {
        0xC0000005: ['Access violation', 'The game tried to use memory it had no access to.'],
        0xC0000006: ['In-page error', 'Windows could not read part of the game from disk (failing disk, network drive or removed USB stick).'],
        0xC0000017: ['Out of memory', 'The game ran out of memory.'],
        0xC000001D: ['Illegal instruction', 'The processor was told to run something that is not valid code. Usually a jump to a bad address.'],
        0xC000008E: ['Float divide by zero', 'The game divided a number by zero.'],
        0xC0000094: ['Integer divide by zero', 'The game divided a number by zero.'],
        0xC0000096: ['Privileged instruction', 'The processor was told to run an instruction games may not use. Usually a jump to a bad address.'],
        0xC00000FD: ['Stack overflow', 'A function kept calling itself (or used too much stack space) until the game ran out. Usually endless recursion: look for the same function repeated in the stack.'],
        0xC0000135: ['DLL not found', 'Windows could not start the game because a DLL it needs is missing. Not a game bug: the install is incomplete.'],
        0xC0000142: ['DLL failed to start', 'A DLL the game needs failed to initialise. Often a broken install or security software.'],
        0xC000013A: ['Closed from the console', 'The game was closed with Ctrl+C or by closing its console window. Not a crash.'],
        0xC0000374: ['Heap corruption', 'The game\'s memory bookkeeping was damaged, usually by writing past the end of a buffer or freeing something twice. The real bug is often earlier than where it crashed.'],
        0xC0000409: ['Fail-fast', 'The game stopped itself on purpose after detecting a serious problem (corrupted memory, or a failed check).'],
        0xC0000420: ['Assertion failure', 'A check in the code failed and the game stopped itself.'],
        0x80000003: ['Breakpoint', 'The game hit a debug breakpoint or assertion.'],
        0xE06D7363: ['Unhandled C++ exception', 'C++ code threw an exception that nothing caught.'],
        0x00000003: ['Aborted', 'The game stopped itself after an internal error (abort() was called).'],
        0x00000001: ['Exited with an error', 'The game closed itself after an error. The end of the log usually says why.'],
    };

    function describeException(code) {
        const known = EXCEPTIONS[code >>> 0];
        return known ? { name: known[0], text: known[1] } : { name: 'Unknown error ' + hex(code), text: 'The game ended with a code this page doesn\'t know.' };
    }

    function describeAccessViolation(operation, address, eip) {
        if (operation === 8 || address === eip) {
            return 'It jumped to ' + hex(address) + ', which is not code. This usually means it called a function through a bad or empty function pointer.';
        }
        const verb = operation === 1 ? 'write to' : 'read from';
        if (address < 0x10000) {
            return 'It tried to ' + verb + ' address ' + hex(address) + '. That is close to zero, so it used an empty (NULL) pointer: '
                + 'something the code expected to exist (a creature, thing, room, player, config entry...) was missing.'
                + (address !== 0 ? ' The small offset means it was reading a field inside that missing object.' : '');
        }
        return 'It tried to ' + verb + ' address ' + hex(address) + ', which isn\'t valid memory. Usually a pointer to something already freed, or a corrupted value.';
    }

    const MODULE_KINDS = [
        ['game', /^keeperfx/],
        ['injected', /(overlay|discord|rtss|fraps|graphics-hook|nvspcap|bdhook|avghook|aswhook|sbiedll|easyhook|reshade|mangohud|bandicam|action_x86|medal|xsplit|dxtory)/],
        ['library', /^(sdl\d?(_\w+)?|openal32|soft_oal|libstdc\+\+-\d+|libgcc\w*|libwinpthread-\d+|zlib\d*|libz\w*|libpng\w*|libogg\w*|libvorbis\w*|libcurl\w*|enet\w*|lua\w*|libspng\w*)\.dll$/],
        ['driver', /^(nvoglv32|nvd3dum|nvwgf2um|nvldumd|nvumdshim|nvgpucomp32|atioglxx|atiumdag|atidxx32|amdxc32|aticfx32|amdihk32|ig\w*icd32|igd\w*32|igxe\w*|opengl32|d3d9|d3d11|dxgi|vulkan-1|amdvlk32)\.dll$/],
        ['windows', /^(ntdll|kernel32|kernelbase|ucrtbase|msvcrt|msvcp\w*|vcruntime\w*|user32|gdi32|gdi32full|win32u|combase|ole32|rpcrt4|ws2_32|winmm|dsound|mmdevapi|audioses|dinput8|xinput\w*|shell32|advapi32|sechost|bcrypt\w*|cfgmgr32|setupapi|imm32|uxtheme|dwmapi|hid|wintrust|crypt32|msctf|dbghelp|dbgcore|version|shlwapi|wow64\w*)\.dll$/],
    ];
    const KIND_LABELS = {
        game: 'KeeperFX',
        library: 'a library bundled with KeeperFX',
        driver: 'the graphics driver',
        windows: 'Windows',
        injected: 'a program that hooks into games',
        other: 'a third-party DLL',
    };

    function moduleKind(name, path) {
        const lower = (name || '').toLowerCase();
        for (const [kind, pattern] of MODULE_KINDS) {
            if (pattern.test(lower)) return kind;
        }
        // Dumps carry full paths: anything else straight from the Windows folders is Windows
        if (/^[a-z]:\\windows\\(system32|syswow64)\\[^\\]+$/i.test(path || '')) return 'windows';
        return 'other';
    }

    function verdict(kind, moduleName) {
        switch (kind) {
            case 'game': return 'This is most likely a bug in KeeperFX.';
            case 'library': return 'It crashed inside ' + moduleName + ', a library KeeperFX uses. The cause is usually how the game called it, so look at the first KeeperFX function below.';
            case 'driver': return 'It crashed inside the graphics driver (' + moduleName + '). Updating the graphics driver or trying another renderer often fixes this. If it happens to many players, look at the first KeeperFX function below.';
            case 'windows': return 'It crashed inside Windows (' + moduleName + '), but the cause is nearly always the code that called it: look at the first KeeperFX function below.';
            case 'injected': return 'It crashed inside ' + moduleName + ', which is not part of KeeperFX or Windows. Overlays (Discord, Steam, RivaTuner, OBS...), screen recorders and some antivirus programs load DLLs like this into games. Ask the player to turn it off and try again.';
            default: return 'It crashed inside ' + moduleName + ', which is not part of KeeperFX or Windows. Ask the player what it belongs to.';
        }
    }

    // ------------------------------------------------------------- minidump

    function parseMinidump(buffer) {
        const view = new DataView(buffer);
        const u8 = (offset) => view.getUint8(offset);
        const u16 = (offset) => view.getUint16(offset, true);
        const u32 = (offset) => view.getUint32(offset, true);
        const u64 = (offset) => Number(view.getBigUint64(offset, true));
        const utf16 = new TextDecoder('utf-16le');
        const string = (rva) => utf16.decode(new Uint8Array(buffer, rva + 4, u32(rva)));

        if (buffer.byteLength < 32 || u32(0) !== 0x504D444D) {
            throw new Error('this is not a minidump file');
        }

        const streams = {};
        const streamCount = u32(8), directory = u32(12);
        for (let i = 0; i < streamCount; i++) {
            const entry = directory + i * 12;
            streams[u32(entry)] = { size: u32(entry + 4), rva: u32(entry + 8) };
        }

        const dump = { time: new Date(u32(20) * 1000), modules: [], threads: [], memory: [], threadNames: {}, exception: null, system: null };

        if (streams[7]) { // SystemInfoStream
            const o = streams[7].rva;
            dump.system = { arch: u16(o), cpus: u8(o + 6), major: u32(o + 8), minor: u32(o + 12), build: u32(o + 16) };
        }

        if (streams[4]) { // ModuleListStream
            let o = streams[4].rva;
            const count = u32(o);
            o += 4;
            for (let i = 0; i < count; i++, o += 108) {
                const path = string(u32(o + 20));
                const fixedInfo = o + 24;
                let version = null;
                if (u32(fixedInfo) === 0xFEEF04BD) {
                    const ms = u32(fixedInfo + 8), ls = u32(fixedInfo + 12);
                    version = [ms >>> 16, ms & 0xFFFF, ls >>> 16, ls & 0xFFFF].join('.');
                }
                const name = path.split(/[\\/]/).pop();
                dump.modules.push({ base: u64(o), size: u32(o + 8), path, name, version, kind: moduleKind(name, path) });
            }
            dump.modules.sort((a, b) => a.base - b.base);
        }

        if (streams[5]) { // MemoryListStream
            let o = streams[5].rva;
            const count = u32(o);
            o += 4;
            for (let i = 0; i < count; i++, o += 16) {
                dump.memory.push({ start: u64(o), size: u32(o + 8), rva: u32(o + 12) });
            }
        }

        if (streams[9]) { // Memory64ListStream (full-memory dumps)
            let o = streams[9].rva;
            const count = u64(o);
            let rva = u64(o + 8);
            o += 16;
            for (let i = 0; i < count; i++, o += 16) {
                const size = u64(o + 8);
                dump.memory.push({ start: u64(o), size, rva });
                rva += size;
            }
        }

        if (streams[3]) { // ThreadListStream
            let o = streams[3].rva;
            const count = u32(o);
            o += 4;
            for (let i = 0; i < count; i++, o += 48) {
                const thread = { id: u32(o), stackStart: u64(o + 24), stackSize: u32(o + 32), stackRva: u32(o + 36), contextRva: u32(o + 44) };
                dump.threads.push(thread);
                if (thread.stackRva !== 0) { // the crashing thread's stack is often only in the memory list
                    dump.memory.push({ start: thread.stackStart, size: thread.stackSize, rva: thread.stackRva });
                }
            }
        }

        if (streams[24]) { // ThreadNamesStream
            let o = streams[24].rva;
            const count = u32(o);
            o += 4;
            for (let i = 0; i < count; i++, o += 12) {
                const name = string(u64(o + 4));
                if (name) dump.threadNames[u32(o)] = name;
            }
        }

        if (streams[17]) { // ThreadInfoListStream: start time, CPU time and start address per thread
            const o = streams[17].rva, headerSize = u32(o), entrySize = u32(o + 4), count = u32(o + 8);
            const fileTimeToMs = (offset) => u64(offset) / 10000 - 11644473600000; // FILETIME (100ns since 1601) -> Unix ms
            dump.threadInfo = {};
            for (let i = 0; i < count; i++) {
                const e = o + headerSize + i * entrySize;
                dump.threadInfo[u32(e)] = {
                    created: fileTimeToMs(e + 16),
                    cpuSeconds: (u64(e + 32) + u64(e + 40)) / 1e7,
                    startAddress: u64(e + 48),
                };
            }
        }

        if (streams[6]) { // ExceptionStream
            const o = streams[6].rva, record = o + 8;
            const parameterCount = Math.min(u32(record + 24), 15);
            const parameters = [];
            for (let i = 0; i < parameterCount; i++) parameters.push(u64(record + 32 + i * 8));
            dump.exception = { threadId: u32(o), code: u32(record), address: u64(record + 16), parameters, contextRva: u32(record + 156) };
        }

        dump.memory.sort((a, b) => a.start - b.start);
        dump.memoryStarts = Float64Array.from(dump.memory, (m) => m.start);
        dump.moduleStarts = Float64Array.from(dump.modules, (m) => m.base);

        /** Bytes at [address, address + length), or null if the dump didn't capture them. */
        dump.read = (address, length) => {
            const i = lastAtOrBelow(dump.memoryStarts, address);
            for (let j = i; j >= 0 && j > i - 256; j--) { // ranges overlap: small captured regions sit inside thread stacks
                const range = dump.memory[j];
                if (address + length <= range.start + range.size) {
                    return new Uint8Array(buffer, range.rva + (address - range.start), length);
                }
            }
            return null;
        };

        dump.moduleAt = (address) => {
            const i = lastAtOrBelow(dump.moduleStarts, address);
            const module = i >= 0 ? dump.modules[i] : null;
            return module && address < module.base + module.size ? module : null;
        };

        dump.context = (rva) => {
            if (!rva || (dump.system && dump.system.arch !== 0)) return null; // x86 CONTEXT only
            return {
                edi: u32(rva + 156), esi: u32(rva + 160), ebx: u32(rva + 164), edx: u32(rva + 168),
                ecx: u32(rva + 172), eax: u32(rva + 176), ebp: u32(rva + 180), eip: u32(rva + 184),
                eflags: u32(rva + 192), esp: u32(rva + 196),
            };
        };

        return dump;
    }

    /** Whether the bytes before a return address are a call instruction: true, false, or null if unknown. */
    function precededByCall(dump, returnAddress) {
        const bytes = dump.read(returnAddress - 7, 7);
        if (!bytes) return null;
        const at = (back) => bytes[7 - back];
        const isCallModrm = (modrm) => ((modrm >> 3) & 7) === 2;
        if (at(5) === 0xE8) return true; // call rel32
        if (at(2) === 0xFF && isCallModrm(at(1)) && ((at(1) >> 6) === 3 || ((at(1) >> 6) === 0 && (at(1) & 7) !== 4 && (at(1) & 7) !== 5))) return true; // call reg / [reg]
        if (at(3) === 0xFF && isCallModrm(at(2)) && (((at(2) >> 6) === 1 && (at(2) & 7) !== 4) || ((at(2) >> 6) === 0 && (at(2) & 7) === 4))) return true; // [reg+d8] / [sib]
        if (at(4) === 0xFF && isCallModrm(at(3)) && (at(3) >> 6) === 1 && (at(3) & 7) === 4) return true; // [sib+d8]
        if (at(6) === 0xFF && isCallModrm(at(5)) && (((at(5) >> 6) === 2 && (at(5) & 7) !== 4) || ((at(5) >> 6) === 0 && (at(5) & 7) === 5))) return true; // [reg+d32] / [abs]
        if (at(7) === 0xFF && isCallModrm(at(6)) && (at(6) >> 6) === 2 && (at(6) & 7) === 4) return true; // [sib+d32]
        return false;
    }

    // -------------------------------------------------------------- symbols

    function parseSymbolTable(text) {
        const lines = text.split('\n');
        let i = 0;
        if (lines[i++] !== 'KFXSYM 1') throw new Error('unknown symbol table format');
        const header = {};
        while (i < lines.length && !lines[i].startsWith('files ')) {
            const space = lines[i].indexOf(' ');
            header[lines[i].slice(0, space)] = lines[i].slice(space + 1);
            i++;
        }
        const fileCount = parseInt(lines[i++].slice(6), 10);
        const files = lines.slice(i, i + fileCount);
        i += fileCount;

        const functionCount = parseInt(lines[i++].slice(6), 10);
        const functionAddresses = new Uint32Array(functionCount), functionSizes = new Uint32Array(functionCount), functionNames = new Array(functionCount);
        for (let f = 0; f < functionCount; f++) {
            const line = lines[i++], a = line.indexOf(' '), b = line.indexOf(' ', a + 1);
            functionAddresses[f] = parseInt(line.slice(0, a), 16);
            functionSizes[f] = parseInt(line.slice(a + 1, b), 16);
            functionNames[f] = line.slice(b + 1);
        }

        const rowCount = parseInt(lines[i++].slice(6), 10);
        const rowAddresses = new Uint32Array(rowCount), rowLines = new Uint32Array(rowCount), rowFiles = new Uint16Array(rowCount);
        let address = 0, file = 0;
        for (let r = 0; r < rowCount; r++) {
            const parts = lines[i++].split(' ');
            address += parseInt(parts[0], 36);
            rowAddresses[r] = address;
            rowLines[r] = parseInt(parts[1], 36);
            if (parts.length > 2) file = parseInt(parts[2], 36);
            rowFiles[r] = file;
        }

        return {
            build: header.build,
            commit: header.commit,
            imageBase: parseInt(header.image_base || '400000', 16),
            /** Function and source line at an address relative to the image base the table was made for. */
            lookup(at) {
                const result = { name: null, offset: 0, file: null, line: 0 };
                const f = lastAtOrBelow(functionAddresses, at);
                if (f >= 0 && (functionSizes[f] === 0 || at < functionAddresses[f] + functionSizes[f])) {
                    result.name = functionNames[f];
                    result.offset = at - functionAddresses[f];
                }
                const r = lastAtOrBelow(rowAddresses, at);
                if (r >= 0 && rowLines[r] > 0) {
                    result.file = files[rowFiles[r]];
                    result.line = rowLines[r];
                }
                return result;
            },
        };
    }

    function buildNameFromVersion(version) {
        // "1.4.0.5601 Alpha" -> "keeperfx-1_4_0_5601-Alpha"
        const match = /^\s*(\d+(?:\.\d+){3})\s+(\S+)/.exec(version || '');
        return match ? 'keeperfx-' + match[1].replace(/\./g, '_') + '-' + match[2] : null;
    }

    async function loadSymbols(build) {
        if (!build || !config.symbolsUrl) return { symbols: null, note: 'The game version on this report doesn\'t name a build, so no symbols were looked up.' };
        const url = config.symbolsUrl.replace(/\/?$/, '/') + encodeURIComponent(build) + '.sym.gz';
        try {
            const response = await fetch(url, { credentials: 'omit' });
            if (!response.ok) {
                return { symbols: null, note: 'No symbols found for ' + build + '. Only alpha and release builds are kept (prototype and self-built exes have none), so function names and source lines below are missing or come from the game log.' };
            }
            return { symbols: parseSymbolTable(await (await gunzip(response)).text()), note: null };
        } catch (error) {
            return { symbols: null, note: 'Could not load symbols for ' + build + ': ' + error.message };
        }
    }

    // ---------------------------------------------------------------- frames

    /** Fills in function and source line for a frame in the game's module. */
    function symbolize(frame, symbols) {
        if (!symbols || frame.kind !== 'game' || frame.moduleBase === undefined) return frame;
        // Return addresses point after the call, so look up the byte before for the right line
        const at = frame.address - frame.moduleBase + symbols.imageBase - (frame.isReturnAddress ? 1 : 0);
        const found = symbols.lookup(at);
        if (found.name) {
            frame.functionName = found.name;
            frame.functionOffset = found.offset + (frame.isReturnAddress ? 1 : 0);
        }
        if (found.file) {
            frame.file = found.file;
            frame.line = found.line;
        }
        return frame;
    }

    function stackFromDump(dump, context, symbols, confirmed) {
        const frames = [];
        const addFrame = (address, extra) => {
            const module = dump.moduleAt(address);
            frames.push(symbolize(Object.assign({
                address,
                module: module ? module.name : null,
                moduleBase: module ? module.base : undefined,
                kind: module ? module.kind : 'unknown',
            }, extra), symbols));
        };

        addFrame(context.eip, { isReturnAddress: false, certain: true });

        // No frame pointers in release builds, so scan the stack for values that look like
        // return addresses: inside a loaded module and right after a call instruction.
        const thread = dump.threads.find((t) => t.id === dump.exception.threadId);
        const stackEnd = thread ? thread.stackStart + thread.stackSize : context.esp + 0x10000;
        for (let at = context.esp; at + 4 <= stackEnd && frames.length < 32; at += 4) {
            const bytes = dump.read(at, 4);
            if (!bytes) break;
            const value = (bytes[0] | bytes[1] << 8 | bytes[2] << 16 | bytes[3] << 24) >>> 0;
            const module = dump.moduleAt(value);
            if (!module || value === module.base) continue;
            const call = precededByCall(dump, value);
            if (call === false) continue;
            if (call === null && module.kind !== 'game') continue; // can't check: only trust game code with symbols
            if (call === null) {
                // Without code bytes, only keep addresses inside a known function and not at its
                // start (a return address never is; function pointers on the stack often are)
                const direct = symbols ? symbols.lookup(value - module.base + symbols.imageBase) : null;
                if (!direct || !direct.name || direct.offset === 0) continue;
            }
            if (frames.length > 0 && frames[frames.length - 1].address === value) continue;
            addFrame(value, { isReturnAddress: true, certain: call === true || confirmed.has(value), stackAddress: at });
        }
        return frames;
    }

    /** Addresses the game's own stack walk wrote to the log, used to confirm scanned frames. */
    function logAddresses(trace) {
        const addresses = new Set();
        for (const text of Object.values(trace || {})) {
            const match = /[0-9a-f]{4}:([0-9a-f]{8})/i.exec(text);
            if (match) addresses.add(parseInt(match[1], 16));
        }
        return addresses;
    }

    function stackFromLog(trace, symbols) {
        const entries = Object.entries(trace || {}).sort((a, b) => Number(b[0]) - Number(a[0])); // highest number is the crash point
        const patterns = [
            /^(\S+)\s*:\s*(.*?)\s*\[0x[0-9a-f]+\+0x[0-9a-f]+\]\s*map lookup for: [0-9a-f]+:([0-9a-f]+), base: ([0-9a-f]+)/i,
            /^(\S+)\s*:\s*(\S+)\s*\[[0-9a-f]+:([0-9a-f]+)\+0x[0-9a-f]+, base ([0-9a-f]+)\]/i,
            /^(\[unknown module\]|\S+)\s*:\s*at [0-9a-f]+:([0-9a-f]+), base ([0-9a-f]+)/i,
        ];
        return entries.map(([, text], index) => {
            for (let p = 0; p < patterns.length; p++) {
                const match = patterns[p].exec(text);
                if (!match) continue;
                const [moduleText, name, address, base] = p === 2 ? [match[1], null, match[2], match[3]] : [match[1], match[2], match[3], match[4]];
                const module = moduleText === '[unknown module]' ? null : moduleText;
                const frame = {
                    address: parseInt(address, 16),
                    module,
                    moduleBase: module ? parseInt(base, 16) : undefined,
                    kind: module ? moduleKind(module) : 'unknown',
                    isReturnAddress: index > 0,
                    certain: true,
                    functionName: name ? name.replace(/^->\s*/, '') : null,
                };
                return symbolize(frame, symbols);
            }
            return { address: 0, module: null, kind: 'unknown', rawText: text, certain: true };
        });
    }

    // -------------------------------------------------------------- render

    function sourceLink(frame, commit) {
        if (!frame.file) return null;
        const label = frame.file.split('/').slice(-2).join('/') + ':' + frame.line;
        if (!commit || frame.file.startsWith('/')) return el('span', { title: frame.file }, label);
        return el('a', { href: SOURCE_REPO + '/blob/' + commit + '/' + frame.file + '#L' + frame.line, target: '_blank', rel: 'noopener', title: frame.file }, label);
    }

    function frameLabel(frame) {
        if (frame.rawText) return frame.rawText;
        if (frame.functionName) return frame.functionName;
        if (frame.module && frame.moduleBase !== undefined) return frame.module + ' + ' + hex(frame.address - frame.moduleBase, 1);
        return hex(frame.address);
    }

    function renderStack(frames, commit) {
        const firstGame = frames.findIndex((f) => f.kind === 'game');
        const body = frames.map((frame, index) => el('tr', { style: index === firstGame ? 'font-weight: 600; color: white;' : null },
            el('td', null, '#' + index),
            el('td', null, frame.module || '?'),
            el('td', { style: 'word-break: break-all;' }, frameLabel(frame), frame.certain ? null : el('span', { class: 'text-muted', title: 'Found by scanning the stack; may not be a real call' }, ' (probable)')),
            el('td', null, sourceLink(frame, commit) || el('span', { class: 'text-muted' }, '-')),
            el('td', { class: 'text-muted', style: 'font-family: monospace;' }, frame.address ? hex(frame.address) : '')));
        return el('table', { class: 'table table-sm', style: 'font-size: 13px;' },
            el('thead', null, el('tr', null, el('th', null, ''), el('th', null, 'Module'), el('th', null, 'Function'), el('th', null, 'Source'), el('th', null, 'Address'))),
            el('tbody', null, body));
    }

    function renderSummary(facts) {
        const where = facts.crashFrame;
        const firstGame = facts.frames.find((f) => f.kind === 'game' && f.functionName);
        const items = [];

        items.push(el('p', { style: 'font-size: 1.1em; margin-bottom: 6px;' },
            el('strong', { style: 'color: white;' }, facts.exception.name), ' ', el('span', { class: 'text-muted' }, '(' + hex(facts.exception.code) + ')')));
        items.push(el('p', { style: 'margin-bottom: 6px;' }, facts.exception.text, facts.detail ? ' ' + facts.detail : ''));

        if (where && where.kind !== 'unknown') {
            const place = where.kind === 'game' && where.functionName
                ? ['It crashed in ', el('code', null, where.functionName), where.file ? [' (', sourceLink(where, facts.commit), ')'] : '', '.']
                : ['It crashed in ', el('code', null, where.module || 'unknown code'), ', part of ', KIND_LABELS[where.kind] || 'an unknown module', '.'];
            items.push(el('p', { style: 'margin-bottom: 6px;' }, place));
            items.push(el('p', { style: 'margin-bottom: 6px;' }, verdict(where.kind, where.module)));
            if (where.kind !== 'game' && firstGame) {
                items.push(el('p', { style: 'margin-bottom: 6px;' }, 'The last KeeperFX function before that was ', el('code', null, firstGame.functionName),
                    firstGame.file ? [' (', sourceLink(firstGame, facts.commit), ')'] : '', '.'));
            }
        }

        const repeated = facts.frames.filter((f) => f.functionName).map((f) => f.functionName);
        if (facts.exception.code === 0xC00000FD && repeated.length > 4) {
            const counts = {};
            repeated.forEach((name) => { counts[name] = (counts[name] || 0) + 1; });
            const [name, count] = Object.entries(counts).sort((a, b) => b[1] - a[1])[0];
            if (count > 2) items.push(el('p', null, el('code', null, name), ' appears ' + count + ' times in the stack, which points to endless recursion there.'));
        }

        if (Number.isInteger(config.replayTurn)) {
            // 20 game turns a second at normal speed
            const seconds = config.replayTurn / 20;
            const when = seconds < 10 ? 'right at the start of the level'
                : seconds < 90 ? 'about ' + Math.round(seconds / 10) * 10 + ' seconds into the level'
                : 'about ' + Math.round(seconds / 60) + ' minutes into the level';
            items.push(el('p', { style: 'margin-bottom: 6px;' }, 'It happened on game turn ' + config.replayTurn + ', ' + when + '.'
                + (config.hasReplay ? ' The replay attached to this report plays the game up to that point.' : '')));
        }
        if (facts.threadName) items.push(el('p', { class: 'text-muted', style: 'margin-bottom: 6px;' }, 'Crashed on the "' + facts.threadName + '" thread.'));
        return items;
    }

    const OWNER_ORDER = ['game', 'library', 'driver', 'injected', 'other', 'windows', 'unknown'];
    const OWNER_TITLES = {
        game: 'KeeperFX', library: 'Bundled libraries (SDL, OpenAL...)', driver: 'Graphics driver',
        injected: 'Programs hooked into the game', other: 'Third-party DLLs', windows: 'Windows', unknown: 'Unknown',
    };
    const OWNER_COLOURS = {
        game: '#c9a227', library: '#4a90d9', driver: '#9b59b6', injected: '#e67e22', other: '#999', windows: '#666', unknown: '#555',
    };
    const byOwner = (a, b) => OWNER_ORDER.indexOf(a.kind) - OWNER_ORDER.indexOf(b.kind);

    /** One bar per thread from when it started to the crash, grouped by the code it started in. */
    function renderThreadTimeline(dump, symbols) {
        const crashedId = dump.exception.threadId;
        if (!dump.threadInfo) {
            return el('ul', null, dump.threads.map((t) => el('li', { style: t.id === crashedId ? 'color: #ff6b6b;' : null },
                (dump.threadNames[t.id] || 'Thread ' + t.id) + (t.id === crashedId ? ' (crashed)' : ''))));
        }

        const threads = dump.threads.map((t) => {
            const info = dump.threadInfo[t.id] || { created: 0, cpuSeconds: 0, startAddress: 0 };
            const module = info.startAddress ? dump.moduleAt(info.startAddress) : null;
            let startFunction = module && module.kind === 'game' && symbols
                ? symbols.lookup(info.startAddress - module.base + symbols.imageBase).name : null;
            if (startFunction && startFunction.startsWith('_')) {
                startFunction = 'Game worker thread'; // std::thread starts in a runtime routine with no useful name
            }
            const name = dump.threadNames[t.id] || null;
            // SDL names its threads but starts them through the C runtime, so go by the name
            const kind = name && /^SDL|hotplug/i.test(name) ? 'library' : module ? module.kind : 'unknown';
            return {
                ids: [t.id], created: info.created, cpuSeconds: info.cpuSeconds, count: 1,
                kind, module, name, startFunction,
                crashed: t.id === crashedId,
            };
        }).sort((a, b) => a.created - b.created);

        const main = threads.find((t) => t.kind === 'game');
        if (main) main.isMain = true;
        const start = Math.min(...threads.map((t) => t.created));
        const end = Math.max(dump.time.getTime(), ...threads.map((t) => t.created + 100));
        const span = Math.max(end - start, 1);

        // Unnamed threads that started in the same place (e.g. Windows worker pools) share one row
        const rows = [];
        const shared = {};
        for (const t of threads) {
            const key = t.name || t.crashed || t.isMain ? null : t.kind + '|' + (t.module ? t.module.name : '') + '|' + (t.startFunction || '');
            if (key && shared[key]) {
                shared[key].count++;
                shared[key].ids.push(...t.ids);
                shared[key].cpuSeconds += t.cpuSeconds;
                continue;
            }
            if (key) shared[key] = t;
            rows.push(t);
        }

        const label = (t) => t.isMain ? 'Main game thread'
            : t.name || t.startFunction || (t.module ? t.module.name + (t.count > 1 ? ' workers' : ' thread') : 'Unknown thread');
        const seconds = (ms) => (ms / 1000).toFixed(ms < 10000 ? 1 : 0) + ' s';

        const groups = OWNER_ORDER.filter((kind) => rows.some((r) => r.kind === kind)).map((kind) => el('div', { style: 'margin-bottom: 10px;' },
            el('div', { class: 'text-muted', style: 'font-size: 12px; margin-bottom: 3px;' }, OWNER_TITLES[kind]),
            rows.filter((r) => r.kind === kind).map((r) => {
                const left = ((r.created - start) / span) * 100;
                const colour = r.crashed ? '#d9534f' : OWNER_COLOURS[kind];
                return el('div', {
                    style: 'display: grid; grid-template-columns: 240px 1fr 80px; gap: 8px; align-items: center; font-size: 12px; margin-bottom: 2px;',
                    title: (r.count > 1 ? r.count + ' threads' : 'Thread ' + r.ids[0]) + ', started ' + seconds(r.created - start) + ' after the game, '
                        + r.cpuSeconds.toFixed(2) + ' s of CPU time' + (r.module ? ', in ' + r.module.name : ''),
                },
                el('span', { style: 'overflow: hidden; text-overflow: ellipsis; white-space: nowrap;' + (r.crashed ? ' color: #ff6b6b; font-weight: 600;' : '') },
                    label(r), r.count > 1 ? el('span', { class: 'text-muted' }, ' ×' + r.count) : null),
                el('span', { style: 'position: relative; height: 12px; background: #1a1a1a;' },
                    el('span', { style: 'position: absolute; top: 0; bottom: 0; right: 0; left: ' + left.toFixed(2) + '%; background: ' + colour + ';' + (r.crashed ? '' : ' opacity: 0.8;') })),
                el('span', { class: 'text-muted', style: 'text-align: right;' }, r.crashed ? 'crashed' : r.cpuSeconds >= 0.01 ? r.cpuSeconds.toFixed(2) + ' s CPU' : ''));
            })));

        return el('div', null,
            el('p', { class: 'text-muted', style: 'font-size: 12px;' },
                'Each bar runs from when the thread started to the crash. Windows doesn\'t record which thread started which, so threads are grouped by the code they started in. Hover a row for details.'),
            groups,
            el('div', { class: 'text-muted', style: 'display: grid; grid-template-columns: 240px 1fr 80px; gap: 8px; font-size: 11px;' },
                el('span', null, ''),
                el('span', { style: 'display: flex; justify-content: space-between; border-top: 1px solid #444; padding-top: 2px;' },
                    el('span', null, 'game started'), el('span', null, 'crash (' + seconds(end - start) + ' later)')),
                el('span', null, '')));
    }

    function renderDetails(dump, symbols) {
        const sections = [];
        const crashedName = dump.threadNames[dump.exception.threadId];
        sections.push(el('details', null,
            el('summary', null, 'Threads (' + dump.threads.length + ')' + (crashedName ? ': crashed on "' + crashedName + '"' : '')),
            el('div', { style: 'padding: 8px 0;' }, renderThreadTimeline(dump, symbols))));

        const modules = [...dump.modules].sort((a, b) => byOwner(a, b) || a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }));
        sections.push(el('details', null, el('summary', null, 'Loaded modules (' + dump.modules.length + ')'),
            el('table', { class: 'table table-sm', style: 'font-size: 12px; margin-top: 6px;' },
                el('thead', null, el('tr', null, el('th', null, 'Module'), el('th', null, 'Version'), el('th', null, 'Belongs to'))),
                el('tbody', null, modules.map((m) => el('tr', null,
                    el('td', { title: m.path }, el('span', { style: 'display: inline-block; width: 8px; height: 8px; margin-right: 6px; background: ' + OWNER_COLOURS[m.kind] + ';' }), m.name),
                    el('td', null, m.version || el('span', { class: 'text-muted' }, '-')),
                    el('td', null, OWNER_TITLES[m.kind])))))));

        if (dump.system) {
            sections.push(el('p', { class: 'text-muted', style: 'font-size: 12px; margin-top: 8px;' },
                'Windows ' + dump.system.major + '.' + dump.system.minor + ' build ' + dump.system.build + ', ' + dump.system.cpus + ' CPU threads. Dump written ' + dump.time.toISOString().replace('T', ' ').slice(0, 19) + ' UTC.'));
        }
        return sections;
    }

    function show(...children) {
        root.replaceChildren(
            el('h5', { style: 'margin-top: 30px; border-bottom: 0;' }, 'What happened'),
            el('div', { style: 'color: #ccc; background-color: #0D0D0D; border: 2px solid black; padding: 12px;' }, children));
        root.hidden = false;
    }

    // ----------------------------------------------------------------- main

    async function loadDump() {
        if (!config.dumpUrl) return { dump: null, note: null };
        const name = (config.dumpName || '').toLowerCase();
        if (!name.endsWith('.gz') && !name.endsWith('.dmp')) {
            return { dump: null, note: 'The crash dump is a .' + name.split('.').pop() + ' archive, which the browser can\'t open. Download it and open it in WinDbg or Visual Studio.' };
        }
        try {
            let response = await fetch(config.dumpUrl, { credentials: 'same-origin' });
            if (!response.ok) throw new Error('download failed (' + response.status + ')');
            if (name.endsWith('.gz')) response = await gunzip(response);
            return { dump: parseMinidump(await response.arrayBuffer()), note: null };
        } catch (error) {
            return { dump: null, note: 'Could not read the crash dump: ' + error.message };
        }
    }

    async function main() {
        const build = buildNameFromVersion(config.gameVersion);
        const [dumpResult, symbolResult] = await Promise.all([loadDump(), loadSymbols(build)]);
        const { dump } = dumpResult;
        const { symbols } = symbolResult;
        const prototypeCommit = /Prototype_([0-9a-f]{7,40})/i.exec(config.gameVersion || '');
        const commit = symbols ? symbols.commit : (prototypeCommit ? prototypeCommit[1] : null);
        const notes = [dumpResult.note, symbolResult.note].filter(Boolean);

        let facts = null;
        let details = [];
        if (dump && dump.exception) {
            const context = dump.context(dump.exception.contextRva);
            const exception = Object.assign({ code: dump.exception.code }, describeException(dump.exception.code));
            let detail = null;
            if (dump.exception.code === 0xC0000005 && dump.exception.parameters.length >= 2) {
                detail = describeAccessViolation(dump.exception.parameters[0], dump.exception.parameters[1], context ? context.eip : -1);
            }
            // Without symbols the stack scan can't tell return addresses from other numbers, but the
            // game's own stack walk (named from keeperfx.map) is in the log
            const hasLogTrace = config.logTrace && Object.keys(config.logTrace).length > 0;
            const frames = !context ? []
                : (symbols || !hasLogTrace) ? stackFromDump(dump, context, symbols, logAddresses(config.logTrace))
                : stackFromLog(config.logTrace, null);
            if (!context) notes.push('The dump has no x86 register state, so no stack could be worked out.');
            facts = { exception, detail, frames, crashFrame: frames[0], commit, threadName: dump.threadNames[dump.exception.threadId] };
            details = renderDetails(dump, symbols);
        } else if (config.logError || (config.logTrace && Object.keys(config.logTrace).length)) {
            const code = config.logError ? parseInt(config.logError.exception_code, 16) : (config.exitCode ? parseInt(config.exitCode, 16) : 0);
            const exception = Object.assign({ code }, describeException(code));
            const frames = stackFromLog(config.logTrace, symbols);
            facts = { exception, detail: config.logError ? config.logError.error_message : null, frames, crashFrame: frames[0], commit };
            notes.push(config.dumpUrl
                ? 'So this is based on the stack trace in the game log instead.'
                : 'No crash dump was sent, so this is based on the stack trace in the game log.');
        } else if (config.exitCode) {
            const code = parseInt(config.exitCode, 16);
            const exception = describeException(code);
            show(el('p', null, el('strong', { style: 'color: white;' }, exception.name), ' ', el('span', { class: 'text-muted' }, '(exit code ' + config.exitCode + ')')),
                el('p', null, exception.text),
                el('p', { class: 'text-muted' }, 'There is no crash dump or stack trace, so that is all that can be said. Check the end of the log below.'));
            return;
        } else {
            return; // nothing to analyse: leave the panel hidden
        }

        show(
            renderSummary(facts),
            notes.map((note) => el('p', { class: 'text-muted', style: 'font-size: 13px; margin-bottom: 6px;' }, note)),
            facts.frames.length ? el('h6', { style: 'margin-top: 14px;' }, 'Call stack (most recent first)') : null,
            facts.frames.length ? renderStack(facts.frames, commit) : null,
            details,
        );

        // The panel covers everything in the server-rendered Error section, so keep one view
        const logError = document.getElementById('crash-log-error');
        if (logError) logError.hidden = true;
    }

    main().catch((error) => {
        show(el('p', { class: 'text-muted' }, 'The crash viewer failed: ' + error.message));
        console.error(error);
    });
})();
