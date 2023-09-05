Number.prototype.toFixed = function(digits) {
    const pow = Math.pow(10, digits),
        fixed = (Math.round(this * pow) / pow).toString();

    if (!fixed.includes('.')) return fixed;

    return fixed.replace(/\.?0+$/, '');
};

Object.prototype.clean = function() {
    const clean = {};

    for (const key in this) {
        const value = this[key];

        if (value !== undefined && value !== null && value !== '' && value !== NaN) {
            clean[key] = value;
        }
    }

    return clean;
};