(function () {
    'use strict';

    var container = document.getElementById('stockPdfViewer');
    if (!container) {
        return;
    }

    var url = container.getAttribute('data-url');
    if (!url) {
        return;
    }

    var workerSrc =
        'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.0.379/build/pdf.worker.min.mjs';
    var pdfSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.0.379/build/pdf.min.mjs';

    container.classList.add('stock-pdf-viewer--loading');
    container.setAttribute('aria-busy', 'true');

    import(pdfSrc)
        .then(function (pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

            return pdfjsLib.getDocument({ url: url, withCredentials: true }).promise;
        })
        .then(function (pdf) {
            container.textContent = '';
            container.classList.remove('stock-pdf-viewer--loading');
            container.removeAttribute('aria-busy');

            var renderNext = function (pageNum) {
                if (pageNum > pdf.numPages) {
                    return Promise.resolve();
                }

                return pdf.getPage(pageNum).then(function (page) {
                    var wrap = document.createElement('div');
                    wrap.className = 'stock-pdf-page';
                    var canvas = document.createElement('canvas');
                    wrap.appendChild(canvas);
                    container.appendChild(wrap);

                    var parentWidth = container.clientWidth || 480;
                    var viewport = page.getViewport({ scale: 1 });
                    var scale = Math.min(2, Math.max(0.75, parentWidth / viewport.width));
                    var scaled = page.getViewport({ scale: scale });

                    canvas.width = scaled.width;
                    canvas.height = scaled.height;

                    return page
                        .render({
                            canvasContext: canvas.getContext('2d'),
                            viewport: scaled,
                        })
                        .promise.then(function () {
                            return renderNext(pageNum + 1);
                        });
                });
            };

            return renderNext(1);
        })
        .catch(function () {
            container.classList.remove('stock-pdf-viewer--loading');
            container.removeAttribute('aria-busy');
            container.innerHTML =
                '<p class="text-muted small mb-0">Could not display the PDF. Use Download to open the file.</p>';
        });
})();
