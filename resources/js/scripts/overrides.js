Number.prototype.toFixed = function (digits) {
    const pow = 10 ** digits;
    const fixed = (Math.round(this * pow) / pow).toString();

    if (!fixed.includes('.')) return fixed;

    return fixed.replace(/\.?0+$/, '');
};