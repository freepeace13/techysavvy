import { parseEnv } from './parse.js';
import { diffEnvs, inspectEnv } from './analyze.js';
import { generateExample } from './example.js';

// Referenced by the page's x-data="envDiff()". Everything runs in memory in the
// visitor's browser: no fetch, no form post, no storage.
window.envDiff = function () {
    return {
        a: '',
        b: '',
        copied: false,
        result: null,

        init() {
            this.$watch('a', () => this.analyze());
            this.$watch('b', () => this.analyze());
        },

        analyze() {
            this.copied = false;

            if (this.a.trim() === '' && this.b.trim() === '') {
                this.result = null;
                return;
            }

            const pa = parseEnv(this.a);
            const pb = parseEnv(this.b);

            this.result = {
                diff: diffEnvs(pa, pb),
                a: { ...inspectEnv(pa), duplicates: pa.duplicates, invalid: pa.invalid, count: pa.map.size },
                b: { ...inspectEnv(pb), duplicates: pb.duplicates, invalid: pb.invalid, count: pb.map.size },
            };
        },

        get example() {
            return this.a.trim() === '' ? '' : generateExample(this.a);
        },

        async copyExample() {
            try {
                await navigator.clipboard.writeText(this.example);
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            } catch {
                this.$refs.example?.select();
            }
        },

        clear() {
            this.a = '';
            this.b = '';
        },
    };
};
