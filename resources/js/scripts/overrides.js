Number.prototype.toFixed = digits => {
    const fixed = Math.round(this * Math.pow(10, digits)) / Math.pow(10, digits);

    return fixed.toString().replace(/\.?0+$/, '');
};
