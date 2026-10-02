/* JSLive realtime experiment. Not loaded by production templates. GPL-3.0. */
(function (root) {
    'use strict';

    const owners = new WeakMap();

    // Lower/upper bound on the validated, sorted point array; duplicate x is allowed.
    function bound(data, value, upper = false) {
        let low = 0;
        let high = data.length;
        while (low < high) {
            const mid = low + Math.floor((high - low) / 2);
            if (data[mid].x < value || (upper && data[mid].x === value)) low = mid + 1;
            else high = mid;
        }
        return low;
    }

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
                now: () => Date.now(), frameNow: () => root.performance.now(), document: root.document,
                requestFrame: fn => root.requestAnimationFrame(fn), cancelFrame: id => root.cancelAnimationFrame(id),
                setInterval: (fn, ms) => root.setInterval(fn, ms), clearInterval: id => root.clearInterval(id)
            };
            this.running = false;
            this.destroyed = false;
            this.pausedAt = null;
            this.frame = null;
            this.timer = null;
            this.nextDraw = -Infinity;
            this.lastFrame = -Infinity;
            this.refreshTooltip = false;
            this.selectionPlugin = {
                id: 'jslive-realtime-selection',
                // Active indices are remapped during maintenance, but Chart.js
                // parses the replacement array only during the next update.
                afterUpdate: () => this.guard(() => this.reconcileTooltip())
            };
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
                this.chart.config.plugins.push(this.selectionPlugin);
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
                const liveStart = Math.max(0, bound(data, liveMin) - 2);
                let start = liveStart;
                let gapStart = liveStart;
                if (this.pausedAt !== null) {
                    const frozenStart = Math.max(0, bound(data, this.pausedAt - this.settings.duration) - 2);
                    const frozenEnd = bound(data, this.pausedAt, true);
                    if (frozenStart < frozenEnd) {
                        start = Math.min(liveStart, frozenStart);
                        gapStart = Math.min(liveStart, frozenEnd);
                    }
                }
                const gap = liveStart - gapStart;
                if (start || gap) {
                    // Allocate only when something expires. Keep Chart.js's existing
                    // replacement/update lifecycle: splicing can shift element objects
                    // before public selection remapping has been reconciled by update().
                    dataset.data = gap ? data.slice(start, gapStart).concat(data.slice(liveStart)) : data.slice(start);
                    mappings.set(datasetIndex, { start, gapStart, liveStart, gap, length: data.length });
                }
            });
            if (mappings.size) {
                const remap = elements => elements.flatMap(element => {
                    const map = mappings.get(element.datasetIndex);
                    if (!map) return [{ datasetIndex: element.datasetIndex, index: element.index }];
                    const index = element.index;
                    if (index < map.start || index >= map.length || (index >= map.gapStart && index < map.liveStart)) return [];
                    return [{ datasetIndex: element.datasetIndex,
                        index: index - map.start - (index >= map.liveStart ? map.gap : 0) }];
                });
                this.chart.setActiveElements(remap(this.chart.getActiveElements()));
                const tooltip = this.chart.tooltip;
                if (tooltip) tooltip.setActiveElements(remap(tooltip.getActiveElements()), { x: tooltip.caretX || 0, y: tooltip.caretY || 0 });
                this.refreshTooltip = true;
            }
        }

        reconcileTooltip() {
            if (!this.refreshTooltip) return;
            const tooltip = this.chart.tooltip;
            if (!tooltip || (this.chart.options.plugins && this.chart.options.plugins.tooltip === false)) return;
            this.refreshTooltip = false;
            // Read the current selection, not a saved one: a mouse event or a
            // caller may have selected another point since maintenance.
            const active = tooltip.getActiveElements().map(({ datasetIndex, index }) => ({ datasetIndex, index }));
            if (!active.length) return;
            const position = { x: tooltip.caretX || 0, y: tooltip.caretY || 0 };
            // Chart.js otherwise caches the same active indices, including the
            // old parsed/formatted values. Public APIs refresh that cache after parsing.
            tooltip.setActiveElements([], position);
            tooltip.setActiveElements(active, position);
        }

        draw() {
            const frameTime = this.frameTime();
            const now = this.env.now();
            const axis = this.chart.options.scales[this.settings.axisId];
            axis.min = now - this.settings.duration;
            axis.max = now;
            this.chart.update('none');
            this.lastFrame = frameTime;
            this.nextDraw = frameTime + 1000 / this.settings.frameRate;
        }

        frameTime() {
            return this.env.frameNow ? this.env.frameNow() : this.env.now();
        }

        schedule() {
            if (!this.running || this.pausedAt !== null || this.env.document.hidden || this.frame !== null) return;
            this.frame = this.env.requestFrame(() => {
                this.frame = null;
                this.guard(() => {
                    if (this.pausedAt === null && !this.env.document.hidden) {
                        const now = this.frameTime();
                        const deadline = this.nextDraw;
                        if (now < this.lastFrame || now >= deadline) {
                            this.draw();
                            // Carry fractional frame time forward without rendering a
                            // catch-up burst. Wall-clock jumps do not affect browser cadence.
                            if (now >= deadline) {
                                const interval = 1000 / this.settings.frameRate;
                                this.nextDraw = deadline + (Math.floor((now - deadline) / interval) + 1) * interval;
                            }
                        }
                        this.lastFrame = now;
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
            const plugins = this.chart.config && this.chart.config.plugins;
            const pluginIndex = plugins ? plugins.indexOf(this.selectionPlugin) : -1;
            if (pluginIndex >= 0) plugins.splice(pluginIndex, 1);
            this.refreshTooltip = false;
            if (owners.get(this.chart) === this) owners.delete(this.chart);
        }
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = RealtimeWindow;
    else root.JSLiveRealtimeWindow = RealtimeWindow;
})(typeof globalThis !== 'undefined' ? globalThis : this);
