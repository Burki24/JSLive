/* JSLive realtime experiment. Not loaded by production templates. GPL-3.0. */
(function (root) {
    'use strict';

    const owners = new WeakMap();

    class RealtimeWindow {
        constructor(chart, options, environment) {
            const settings = Object.assign({ frameRate: 30, maintenanceInterval: 1000, axisId: 'x' }, options);
            for (const key of ['duration', 'frameRate', 'maintenanceInterval']) {
                if (!Number.isFinite(settings[key]) || settings[key] <= 0) throw new Error(`Invalid ${key}.`);
            }
            if (!chart.options.scales[settings.axisId] || chart.options.scales[settings.axisId].type !== 'time') {
                throw new Error('The prototype requires an ordinary time axis.');
            }
            this.chart = chart;
            this.settings = settings;
            this.env = environment || {
                now: () => Date.now(), document: root.document,
                requestFrame: fn => root.requestAnimationFrame(fn), cancelFrame: id => root.cancelAnimationFrame(id),
                setInterval: (fn, ms) => root.setInterval(fn, ms), clearInterval: id => root.clearInterval(id)
            };
            this.running = false;
            this.destroyed = false;
            this.pausedAt = null;
            this.frame = null;
            this.timer = null;
            this.lastDraw = -Infinity;
            this.visibility = () => this.guard(() => {
                if (this.env.document.hidden) this.cancelFrame();
                else if (this.pausedAt === null) {
                    this.maintain();
                    this.draw();
                    this.schedule();
                }
            });
        }

        start() {
            if (this.destroyed) throw new Error('Realtime window is destroyed.');
            if (this.running) return;
            if (owners.has(this.chart)) throw new Error('Chart already has a realtime owner.');
            this.maintain(); // Validate before acquiring timers/listeners.
            this.running = true;
            owners.set(this.chart, this);
            this.guard(() => {
                this.env.document.addEventListener('visibilitychange', this.visibility);
                this.timer = this.env.setInterval(() => this.guard(() => this.maintain()), this.settings.maintenanceInterval);
                if (!this.env.document.hidden) this.draw();
                this.schedule();
            });
        }

        guard(action) {
            if (!this.running) return;
            try { action(); } catch (error) { this.destroy(); throw error; }
        }

        invalidate() {
            // The next drawing tick coalesces all visible incoming updates.
            // In hidden tabs also trim at ingestion, since browser timers can be throttled.
            this.guard(() => {
                if (this.env.document.hidden) this.maintain();
            });
        }

        maintain() {
            const datasets = this.chart.data.datasets;
            // Prototype contract: sorted numeric x/object points and scalar styles.
            // Validate all datasets before changing any of them.
            for (const dataset of datasets) {
                let previous = -Infinity;
                if (!Array.isArray(dataset.data)) throw new Error('Expected a point array.');
                for (const point of dataset.data) {
                    if (!point || !Number.isFinite(point.x) || point.x < previous) {
                        throw new Error('Expected sorted points with finite numeric x.');
                    }
                    previous = point.x;
                }
                if (Object.keys(dataset).some(key => key !== 'data' && Array.isArray(dataset[key])) ||
                    (dataset.datalabels && Object.values(dataset.datalabels).some(Array.isArray))) {
                    throw new Error('Per-point option arrays are outside this prototype.');
                }
            }
            const liveMin = this.env.now() - this.settings.duration;
            const mappings = new Map();
            datasets.forEach((dataset, datasetIndex) => {
                const data = dataset.data;
                let liveStart = data.findIndex(point => point.x >= liveMin);
                if (liveStart < 0) liveStart = data.length;
                liveStart = Math.max(0, liveStart - 2); // Preserve curve boundary points.
                let frozenStart = data.length;
                if (this.pausedAt !== null) {
                    frozenStart = data.findIndex(point => point.x >= this.pausedAt - this.settings.duration);
                    if (frozenStart < 0) frozenStart = data.length;
                    frozenStart = Math.max(0, frozenStart - 2);
                }
                const mapping = new Map();
                const retained = data.filter((point, index) => {
                    const keep = index >= liveStart ||
                        (this.pausedAt !== null && index >= frozenStart && point.x <= this.pausedAt);
                    if (keep) mapping.set(index, mapping.size);
                    return keep;
                });
                if (retained.length !== data.length) {
                    dataset.data = retained;
                    mappings.set(datasetIndex, mapping);
                }
            });
            if (mappings.size) {
                const remap = elements => elements.flatMap(element => {
                    const map = mappings.get(element.datasetIndex);
                    if (!map) return [{ datasetIndex: element.datasetIndex, index: element.index }];
                    return map.has(element.index) ? [{ datasetIndex: element.datasetIndex, index: map.get(element.index) }] : [];
                });
                this.chart.setActiveElements(remap(this.chart.getActiveElements()));
                const tooltip = this.chart.tooltip;
                if (tooltip) tooltip.setActiveElements(remap(tooltip.getActiveElements()), { x: tooltip.caretX || 0, y: tooltip.caretY || 0 });
            }
        }

        draw() {
            const now = this.env.now();
            const axis = this.chart.options.scales[this.settings.axisId];
            axis.min = now - this.settings.duration;
            axis.max = now;
            this.chart.update('none');
            this.lastDraw = now;
        }

        schedule() {
            if (!this.running || this.pausedAt !== null || this.env.document.hidden || this.frame !== null) return;
            this.frame = this.env.requestFrame(() => {
                this.frame = null;
                this.guard(() => {
                    if (this.pausedAt === null && !this.env.document.hidden) {
                        const elapsed = this.env.now() - this.lastDraw;
                        if (elapsed < 0 || elapsed >= 1000 / this.settings.frameRate) this.draw();
                    }
                    this.schedule();
                });
            });
        }

        cancelFrame() {
            if (this.frame !== null) this.env.cancelFrame(this.frame);
            this.frame = null;
        }

        pause() {
            if (!this.running || this.pausedAt !== null) return;
            const max = this.chart.options.scales[this.settings.axisId].max;
            this.pausedAt = Number.isFinite(max) ? max : this.env.now();
            this.cancelFrame();
        }

        resume() {
            this.guard(() => {
                this.pausedAt = null;
                this.maintain();
                if (!this.env.document.hidden) this.draw();
                this.schedule();
            });
        }

        destroy() {
            if (this.destroyed) return;
            this.running = false;
            this.destroyed = true;
            this.cancelFrame();
            if (this.timer !== null) this.env.clearInterval(this.timer);
            this.timer = null;
            this.env.document.removeEventListener('visibilitychange', this.visibility);
            if (owners.get(this.chart) === this) owners.delete(this.chart);
        }
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = RealtimeWindow;
    else root.JSLiveRealtimeWindow = RealtimeWindow;
})(typeof globalThis !== 'undefined' ? globalThis : this);
