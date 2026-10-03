// Reproduce the local asset without a compiler or external dependencies.
// stdout emits the bundle; --check compares it with the checked-in asset.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const root = path.resolve(__dirname, '..');
const directory = path.join(root, 'SymconJSLive/js/canvas-gauges');
const source = fs.readFileSync(path.join(directory, 'gauge.min.js'), 'utf8').replace(/\r\n/g, '\n');
assert.equal(crypto.createHash('sha256').update(source).digest('hex'),
    '44b0a4ac54e0b980371e8788f7ce8215dab5a2181cda460fc344276b50385904');
function once(text, before, after) {
    assert.equal(text.split(before).length, 2, 'Patch anchor must occur exactly once.');
    return text.replace(before, after);
}
const start = source.indexOf('set:function(e){var t=this;e=n.ensureValue(e,this.options.minValue);');
const end = source.indexOf(',get:function(){return void 0===this._value?', start);
assert.ok(start > 0 && end > start, 'Locate the verified BaseGauge value setter.');
// Keep the patch readable inside the otherwise byte-preserved distribution.
const setter = `set:function(value) {
    var gauge = this;
    value = n.ensureValue(value, this.options.minValue);
    var fromValue = this.options.value;
    // RadialGauge can interpolate across north to an equivalent angle, but
    // its getter and final display must retain the original requested value.
    var target = this._jsliveTargetValue === undefined ? value : this._jsliveTargetValue;
    delete this._jsliveTargetValue;
    if (this.options.animation && this.animation.frame && this._value === target) return;
    this.animation.cancel();
    delete this._value;
    if (!this.options.animation || value === fromValue) {
        this.emit('value', target, this.value);
        this.options.value = target;
        this.draw();
        return;
    }
    this._value = target;
    this.emit('animationStart');
    this.animation.animate(function(percent) {
        var nextValue = fromValue + (value - fromValue) * percent;
        if (gauge.options.animatedValue) gauge.emit('value', nextValue, gauge.value);
        gauge.options.value = nextValue;
        gauge.draw();
        gauge.emit('animate', percent, gauge.options.value);
    }, function() {
        if (gauge._value !== undefined) {
            gauge.emit('value', gauge._value, gauge.value);
            gauge.options.value = gauge._value;
            delete gauge._value;
        }
        gauge.draw();
        gauge.emit('animationEnd');
    });
}`;
let patched = source.slice(0, start) + setter + source.slice(end);
patched = once(patched, '(this._value=e,e=this.options.value+',
    '(this._jsliveTargetValue=e,e=this.options.value+');
patched = '/*! JSLive local asset revision 2.1.7-jslive.1; see SOURCES.md. */\n' + patched.trimEnd() + '\n';
if (process.argv.includes('--check')) {
    assert.equal(fs.readFileSync(path.join(directory, '2.1.7-jslive.1/gauge.min.js'), 'utf8').replace(/\r\n/g, '\n'), patched);
    console.log('Canvas Gauges local patch reproduces exactly from the verified upstream bundle.');
} else {
    process.stdout.write(patched);
}
