Number.prototype.toFixed = function(digits) {
    const pow = Math.pow(10, digits),
        fixed = Math.round(this * pow) / pow;

    return fixed.toString().replace(/\.?0+$/, '');
};
