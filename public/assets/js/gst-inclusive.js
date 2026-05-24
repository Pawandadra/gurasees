(function () {
    'use strict';

    /**
     * @param {number} inclusiveTotal Net price entered (GST inclusive).
     * @param {number} percent GST rate (e.g. 5 for 5%).
     * @returns {{base: number, gst: number, total: number}}
     */
    function splitInclusiveTotal(inclusiveTotal, percent) {
        var total = Math.max(0, Math.round(inclusiveTotal * 100) / 100);
        if (total <= 0 || percent <= 0) {
            return { base: total, gst: 0, total: total };
        }

        var base = Math.round((total / (1 + percent / 100)) * 100) / 100;
        var gst = Math.round((total - base) * 100) / 100;

        return { base: base, gst: gst, total: total };
    }

    window.gstSplitInclusive = splitInclusiveTotal;
})();
