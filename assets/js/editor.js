(() => {
    'use strict';

    const root = document.querySelector('.editor-page[data-source-url]');
    if (!root) return;

    const sourceUrl = root.dataset.sourceUrl;
    const status = document.getElementById('editor-status');
    const loadingState = document.getElementById('loading-state');
    const pageContainer = document.getElementById('page-container');
    const canvasScroll = document.getElementById('canvas-scroll');
    const canvas = document.getElementById('page-canvas');
    const markLayer = document.getElementById('mark-layer');
    const pageNumberInput = document.getElementById('current-page');
    const pageCountLabel = document.getElementById('page-count');
    const selectedPageLabel = document.getElementById('selected-page-label');
    const previousPageButton = document.getElementById('previous-page');
    const nextPageButton = document.getElementById('next-page');
    const moveUpButton = document.getElementById('move-page-up');
    const moveDownButton = document.getElementById('move-page-down');
    const rotateButton = document.getElementById('rotate-page');
    const deleteButton = document.getElementById('delete-page');
    const saveButton = document.getElementById('save-pdf');
    const zoomLabel = document.getElementById('zoom-level');
    const textField = document.getElementById('text-field');
    const textInput = document.getElementById('overlay-text');
    const colorInput = document.getElementById('overlay-color');
    const undoButton = document.getElementById('undo-mark');
    const markupButtons = [...document.querySelectorAll('[data-tool]')];

    let pdfjs = null;
    let viewerDocument = null;
    let sourceDocument = null;
    let originalBuffer = null;
    let pageOrder = [];
    let currentIndex = 0;
    let zoomFactor = 1;
    let currentViewport = null;
    let currentPdfPage = null;
    let renderTask = null;
    let renderSequence = 0;
    let activeTool = 'text';
    let isDirty = false;
    const pageRotations = new Map();
    const pageMarks = new Map();
    const markHistory = [];

    function setStatus(message, type = '') {
        status.textContent = message;
        status.className = `editor-status${type ? ` status-${type}` : ''}`;
    }

    function getCurrentSourcePage() {
        return pageOrder[currentIndex];
    }

    function getPageGeometry(sourcePageNumber) {
        const page = sourceDocument.getPages()[sourcePageNumber - 1];
        const mediaBox = page.getMediaBox();
        return {
            x: mediaBox.x,
            y: mediaBox.y,
            width: mediaBox.width,
            height: mediaBox.height,
        };
    }

    function getMarks(sourcePageNumber) {
        if (!pageMarks.has(sourcePageNumber)) pageMarks.set(sourcePageNumber, []);
        return pageMarks.get(sourcePageNumber);
    }

    function updateControls() {
        const count = pageOrder.length;
        pageNumberInput.max = String(count);
        pageNumberInput.value = String(currentIndex + 1);
        pageCountLabel.textContent = String(count);
        selectedPageLabel.textContent = String(currentIndex + 1);
        previousPageButton.disabled = currentIndex <= 0;
        nextPageButton.disabled = currentIndex >= count - 1;
        moveUpButton.disabled = currentIndex <= 0;
        moveDownButton.disabled = currentIndex >= count - 1;
        rotateButton.disabled = count === 0;
        deleteButton.disabled = count <= 1;
        zoomLabel.textContent = zoomFactor === 1 ? 'Fit' : `${Math.round(zoomFactor * 100)}%`;
        undoButton.disabled = markHistory.length === 0;
    }

    function hexToRgb(hex) {
        const value = hex.replace('#', '');
        return {
            red: Number.parseInt(value.slice(0, 2), 16),
            green: Number.parseInt(value.slice(2, 4), 16),
            blue: Number.parseInt(value.slice(4, 6), 16),
        };
    }

    function colorToCss(hex, alpha = 1) {
        const { red, green, blue } = hexToRgb(hex);
        return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
    }

    function appendMarkPreview(mark, geometry) {
        const node = document.createElement('span');
        if (mark.type === 'text') {
            const point = currentViewport.convertToViewportPoint(
                geometry.x + mark.x * geometry.width,
                geometry.y + mark.y * geometry.height,
            );
            node.className = 'mark-text';
            node.textContent = mark.text;
            node.style.left = `${point[0]}px`;
            node.style.top = `${point[1] - mark.size * currentViewport.scale}px`;
            node.style.fontSize = `${mark.size * currentViewport.scale}px`;
            node.style.color = mark.color;
            node.style.transformOrigin = 'left bottom';
            node.style.transform = `rotate(${currentViewport.rotation}deg)`;
        } else {
            const first = currentViewport.convertToViewportPoint(
                geometry.x + mark.x * geometry.width,
                geometry.y + mark.y * geometry.height,
            );
            const second = currentViewport.convertToViewportPoint(
                geometry.x + (mark.x + mark.width) * geometry.width,
                geometry.y + (mark.y + mark.height) * geometry.height,
            );
            const left = Math.min(first[0], second[0]);
            const top = Math.min(first[1], second[1]);
            node.className = `mark-shape mark-${mark.type}`;
            node.style.left = `${left}px`;
            node.style.top = `${top}px`;
            node.style.width = `${Math.abs(second[0] - first[0])}px`;
            node.style.height = `${Math.abs(second[1] - first[1])}px`;
            node.style.color = mark.color;
            if (mark.type === 'highlight') node.style.backgroundColor = colorToCss(mark.color, 0.32);
        }
        markLayer.appendChild(node);
    }

    function renderMarkPreviews() {
        markLayer.replaceChildren();
        if (!currentViewport || !currentPdfPage) return;
        const geometry = getPageGeometry(getCurrentSourcePage());
        getMarks(getCurrentSourcePage()).forEach((mark) => appendMarkPreview(mark, geometry));
    }

    async function renderPage() {
        if (!viewerDocument || pageOrder.length === 0) return;
        const sequence = ++renderSequence;
        if (renderTask) renderTask.cancel();

        const sourcePageNumber = getCurrentSourcePage();
        try {
            const page = await viewerDocument.getPage(sourcePageNumber);
            if (sequence !== renderSequence) return;

            const rotation = (page.rotate + (pageRotations.get(sourcePageNumber) ?? 0) + 360) % 360;
            const unscaledViewport = page.getViewport({ scale: 1, rotation });
            const availableWidth = Math.max(180, canvasScroll.clientWidth - 36);
            const fitScale = Math.min(1.25, availableWidth / unscaledViewport.width);
            const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
            const maxScaleByDimension = 4096 / pixelRatio / Math.max(unscaledViewport.width, unscaledViewport.height);
            const maxScaleByArea = Math.sqrt(16000000 / (pixelRatio ** 2 * unscaledViewport.width * unscaledViewport.height));
            const scale = Math.min(fitScale * zoomFactor, maxScaleByDimension, maxScaleByArea);
            if (!Number.isFinite(scale) || scale <= 0) {
                throw new Error('This PDF page has dimensions the editor cannot preview.');
            }

            const viewport = page.getViewport({ scale, rotation });
            canvas.width = Math.ceil(viewport.width * pixelRatio);
            canvas.height = Math.ceil(viewport.height * pixelRatio);
            canvas.style.width = `${viewport.width}px`;
            canvas.style.height = `${viewport.height}px`;
            pageContainer.style.width = `${viewport.width}px`;
            pageContainer.style.height = `${viewport.height}px`;
            markLayer.style.width = `${viewport.width}px`;
            markLayer.style.height = `${viewport.height}px`;
            pageContainer.hidden = false;
            loadingState.hidden = true;

            const context = canvas.getContext('2d', { alpha: false });
            if (!context) throw new Error('Your browser could not create a PDF preview surface.');
            const task = page.render({
                canvasContext: context,
                viewport,
                transform: pixelRatio === 1 ? null : [pixelRatio, 0, 0, pixelRatio, 0, 0],
            });
            renderTask = task;
            await task.promise;
            if (sequence !== renderSequence) return;

            currentViewport = viewport;
            currentPdfPage = page;
            renderMarkPreviews();
            const turns = Math.round((pageRotations.get(sourcePageNumber) ?? 0) / 90);
            const rotationText = turns ? ` · rotated ${turns * 90}°` : '';
            setStatus(`Page ${currentIndex + 1} of ${pageOrder.length}${rotationText}`);
        } catch (error) {
            if (error?.name === 'RenderingCancelledException') return;
            if (sequence !== renderSequence) return;
            pageContainer.hidden = true;
            loadingState.hidden = false;
            loadingState.textContent = 'This PDF page could not be previewed.';
            setStatus(error instanceof Error ? error.message : 'This PDF page could not be previewed.', 'error');
        } finally {
            if (sequence === renderSequence) renderTask = null;
        }
    }

    function selectTool(tool) {
        activeTool = tool;
        markupButtons.forEach((button) => {
            const isActive = button.dataset.tool === tool;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', String(isActive));
        });
        textField.hidden = tool !== 'text';
        markLayer.setAttribute('aria-label', `Add ${tool} to the selected PDF page`);
    }

    function pointToNormalized(point, geometry) {
        return {
            x: (point[0] - geometry.x) / geometry.width,
            y: (point[1] - geometry.y) / geometry.height,
        };
    }

    function placeMarkAt(clientX, clientY) {
        if (!currentViewport || !currentPdfPage) return;
        const rect = markLayer.getBoundingClientRect();
        const localX = Math.max(0, Math.min(rect.width, clientX - rect.left));
        const localY = Math.max(0, Math.min(rect.height, clientY - rect.top));
        const sourcePageNumber = getCurrentSourcePage();
        const geometry = getPageGeometry(sourcePageNumber);
        const color = colorInput.value;
        let mark;

        if (activeTool === 'text') {
            const text = textInput.value.trim();
            if (!text) {
                setStatus('Enter text before placing an overlay.', 'error');
                textInput.focus();
                return;
            }
            const point = currentViewport.convertToPdfPoint(localX, localY);
            const normalized = pointToNormalized(point, geometry);
            mark = {
                type: 'text',
                text,
                x: normalized.x,
                y: normalized.y,
                size: 18 / currentViewport.scale,
                color,
            };
        } else {
            const width = Math.min(rect.width * 0.28, 220);
            const height = Math.min(rect.height * 0.1, 76);
            const left = Math.max(0, Math.min(rect.width - width, localX));
            const top = Math.max(0, Math.min(rect.height - height, localY));
            const first = currentViewport.convertToPdfPoint(left, top);
            const second = currentViewport.convertToPdfPoint(left + width, top + height);
            const firstNormalized = pointToNormalized(first, geometry);
            const secondNormalized = pointToNormalized(second, geometry);
            const x = Math.min(firstNormalized.x, secondNormalized.x);
            const y = Math.min(firstNormalized.y, secondNormalized.y);
            mark = {
                type: activeTool,
                x,
                y,
                width: Math.abs(secondNormalized.x - firstNormalized.x),
                height: Math.abs(secondNormalized.y - firstNormalized.y),
                color,
            };
        }

        const marks = getMarks(sourcePageNumber);
        marks.push(mark);
        markHistory.push({ sourcePageNumber, mark });
        isDirty = true;
        updateControls();
        renderMarkPreviews();
        setStatus(`${activeTool[0].toUpperCase()}${activeTool.slice(1)} added to page ${currentIndex + 1}.`, 'success');
    }

    async function loadPdf() {
        try {
            const response = await fetch(sourceUrl, { credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok) {
                let message = 'This private PDF could not be opened. Upload it again and try once more.';
                try {
                    const result = await response.json();
                    if (typeof result.error === 'string') message = result.error;
                } catch {
                    // Keep the user-facing fallback for non-JSON server errors.
                }
                throw new Error(message);
            }

            originalBuffer = await response.arrayBuffer();
            const pdfLibrary = window.PDFLib;
            if (!pdfLibrary) throw new Error('The local PDF editing library could not be loaded.');
            const pdfJsModule = await import('../vendor/pdfjs/pdf.mjs');
            pdfjs = pdfJsModule;
            pdfjs.GlobalWorkerOptions.workerSrc = new URL('../vendor/pdfjs/pdf.worker.mjs', import.meta.url).toString();
            viewerDocument = await pdfjs.getDocument({ data: new Uint8Array(originalBuffer.slice(0)) }).promise;
            sourceDocument = await pdfLibrary.PDFDocument.load(originalBuffer.slice(0));
            if (viewerDocument.numPages !== sourceDocument.getPageCount() || viewerDocument.numPages < 1) {
                throw new Error('The PDF page structure could not be read consistently.');
            }

            pageOrder = Array.from({ length: viewerDocument.numPages }, (_, index) => index + 1);
            pageCountLabel.textContent = String(pageOrder.length);
            saveButton.disabled = false;
            updateControls();
            loadingState.hidden = true;
            await renderPage();
        } catch (error) {
            loadingState.hidden = false;
            loadingState.textContent = error instanceof Error ? error.message : 'The PDF could not be opened.';
            setStatus(loadingState.textContent, 'error');
        }
    }

    async function downloadEditedPdf() {
        if (!sourceDocument || pageOrder.length === 0) return;
        saveButton.disabled = true;
        saveButton.textContent = 'Preparing PDF…';
        setStatus('Applying your edits and preparing the download…');
        try {
            const { PDFDocument, StandardFonts, rgb, degrees } = window.PDFLib;
            const outputDocument = await PDFDocument.create();
            const copiedPages = await outputDocument.copyPages(sourceDocument, pageOrder.map((pageNumber) => pageNumber - 1));
            const font = await outputDocument.embedFont(StandardFonts.Helvetica);

            for (let index = 0; index < copiedPages.length; index += 1) {
                const outputPage = outputDocument.addPage(copiedPages[index]);
                const sourcePageNumber = pageOrder[index];
                const geometry = getPageGeometry(sourcePageNumber);
                const delta = pageRotations.get(sourcePageNumber) ?? 0;

                for (const mark of getMarks(sourcePageNumber)) {
                    const { red, green, blue } = hexToRgb(mark.color);
                    const color = rgb(red / 255, green / 255, blue / 255);
                    const x = geometry.x + mark.x * geometry.width;
                    const y = geometry.y + mark.y * geometry.height;

                    if (mark.type === 'text') {
                        outputPage.drawText(mark.text, {
                            x,
                            y,
                            size: mark.size,
                            font,
                            color,
                            maxWidth: Math.max(1, geometry.width - mark.x * geometry.width),
                        });
                    } else {
                        const width = mark.width * geometry.width;
                        const height = mark.height * geometry.height;
                        if (mark.type === 'highlight') {
                            outputPage.drawRectangle({ x, y, width, height, color, opacity: 0.32 });
                        } else if (mark.type === 'rectangle') {
                            outputPage.drawRectangle({ x, y, width, height, borderColor: color, borderWidth: 2 });
                        } else if (mark.type === 'ellipse') {
                            outputPage.drawEllipse({
                                x: x + width / 2,
                                y: y + height / 2,
                                xScale: width / 2,
                                yScale: height / 2,
                                borderColor: color,
                                borderWidth: 2,
                            });
                        }
                    }
                }

                const originalRotation = outputPage.getRotation().angle;
                outputPage.setRotation(degrees((originalRotation + delta) % 360));
            }

            const editedBytes = await outputDocument.save();
            const blob = new Blob([editedBytes], { type: 'application/pdf' });
            const downloadUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = 'pdf-toolkit-edited.pdf';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
            isDirty = false;
            setStatus('Your edited PDF was downloaded. The original private upload is unchanged.', 'success');
        } catch (error) {
            const detail = error instanceof Error ? error.message : '';
            setStatus(
                detail.includes('WinAnsi') || detail.includes('encode')
                    ? 'A text overlay uses a character unsupported by the built-in PDF font. Try basic Latin characters.'
                    : `Could not create the edited PDF${detail ? `: ${detail}` : '.'}`,
                'error',
            );
        } finally {
            saveButton.disabled = false;
            saveButton.innerHTML = '<svg aria-hidden="true"><use href="#icon-download"></use></svg> Download PDF';
        }
    }

    function changePage(nextIndex) {
        if (nextIndex < 0 || nextIndex >= pageOrder.length || nextIndex === currentIndex) return;
        currentIndex = nextIndex;
        updateControls();
        renderPage();
    }

    markupButtons.forEach((button) => {
        button.addEventListener('click', () => selectTool(button.dataset.tool));
    });
    markLayer.addEventListener('click', (event) => placeMarkAt(event.clientX, event.clientY));
    markLayer.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        const rect = markLayer.getBoundingClientRect();
        placeMarkAt(rect.left + rect.width / 2, rect.top + rect.height / 2);
    });
    undoButton.addEventListener('click', () => {
        const last = markHistory.pop();
        if (!last) return;
        const marks = getMarks(last.sourcePageNumber);
        const index = marks.lastIndexOf(last.mark);
        if (index >= 0) marks.splice(index, 1);
        isDirty = true;
        updateControls();
        renderMarkPreviews();
        setStatus('Last markup removed.', 'success');
    });
    previousPageButton.addEventListener('click', () => changePage(currentIndex - 1));
    nextPageButton.addEventListener('click', () => changePage(currentIndex + 1));
    pageNumberInput.addEventListener('change', () => {
        const requested = Number.parseInt(pageNumberInput.value, 10);
        if (!Number.isInteger(requested)) {
            pageNumberInput.value = String(currentIndex + 1);
            return;
        }
        changePage(Math.max(0, Math.min(pageOrder.length - 1, requested - 1)));
        pageNumberInput.value = String(currentIndex + 1);
    });
    document.getElementById('zoom-out').addEventListener('click', () => {
        zoomFactor = Math.max(0.55, Math.round((zoomFactor - 0.15) * 100) / 100);
        updateControls();
        renderPage();
    });
    document.getElementById('zoom-in').addEventListener('click', () => {
        zoomFactor = Math.min(2.5, Math.round((zoomFactor + 0.15) * 100) / 100);
        updateControls();
        renderPage();
    });
    moveUpButton.addEventListener('click', () => {
        if (currentIndex <= 0) return;
        [pageOrder[currentIndex - 1], pageOrder[currentIndex]] = [pageOrder[currentIndex], pageOrder[currentIndex - 1]];
        currentIndex -= 1;
        isDirty = true;
        updateControls();
        renderPage();
        setStatus(`Page moved to position ${currentIndex + 1}.`, 'success');
    });
    moveDownButton.addEventListener('click', () => {
        if (currentIndex >= pageOrder.length - 1) return;
        [pageOrder[currentIndex + 1], pageOrder[currentIndex]] = [pageOrder[currentIndex], pageOrder[currentIndex + 1]];
        currentIndex += 1;
        isDirty = true;
        updateControls();
        renderPage();
        setStatus(`Page moved to position ${currentIndex + 1}.`, 'success');
    });
    rotateButton.addEventListener('click', () => {
        const sourcePageNumber = getCurrentSourcePage();
        pageRotations.set(sourcePageNumber, ((pageRotations.get(sourcePageNumber) ?? 0) + 90) % 360);
        isDirty = true;
        renderPage();
    });
    deleteButton.addEventListener('click', () => {
        if (pageOrder.length <= 1) return;
        if (!window.confirm(`Remove page ${currentIndex + 1} from the downloaded copy?`)) return;
        const [removedPage] = pageOrder.splice(currentIndex, 1);
        pageMarks.delete(removedPage);
        pageRotations.delete(removedPage);
        for (let index = markHistory.length - 1; index >= 0; index -= 1) {
            if (markHistory[index].sourcePageNumber === removedPage) markHistory.splice(index, 1);
        }
        currentIndex = Math.min(currentIndex, pageOrder.length - 1);
        isDirty = true;
        updateControls();
        renderPage();
        setStatus('Page removed from the downloaded copy.', 'success');
    });
    saveButton.addEventListener('click', downloadEditedPdf);

    let resizeTimer = 0;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(renderPage, 120);
    });
    window.addEventListener('beforeunload', (event) => {
        if (!isDirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    selectTool(activeTool);
    updateControls();
    loadPdf();
})();
