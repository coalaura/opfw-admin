Number.prototype.toFixed = function(digits) {
    const pow = Math.pow(10, digits),
        fixed = (Math.round(this * pow) / pow).toString();

    if (!fixed.includes('.')) return fixed;

    return fixed.replace(/\.?0+$/, '');
};