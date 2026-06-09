(function () {
    'use strict';

    function normalizeAmount(value) {
        var amount = Math.round(Number(value) * 100) / 100;
        return Number.isFinite(amount) ? amount : 0;
    }

    function trimDecimals(formatted) {
        if (formatted.endsWith('.00')) {
            return formatted.slice(0, -3);
        }
        if (formatted.indexOf('.') !== -1) {
            return formatted.replace(/0+$/, '').replace(/\.$/, '');
        }
        return formatted;
    }

    function formatMoneyAmount(value) {
        return trimDecimals(normalizeAmount(value).toFixed(2));
    }

    function formatMoneyDisplay(value) {
        var parts = formatMoneyAmount(value).split('.');
        parts[0] = Number(parts[0]).toLocaleString('en-IN');
        return parts.length > 1 ? parts[0] + '.' + parts[1] : parts[0];
    }

    window.moneyFormatAmount = formatMoneyAmount;
    window.moneyFormatDisplay = formatMoneyDisplay;
})();
