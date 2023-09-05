const toFixed = Number.prototype.toFixed;

Number.prototype.toFixed = digits => {
    const fixed = toFixed.call(this, digits);

    return fixed.replace(/\.?0+$/, '');
};
