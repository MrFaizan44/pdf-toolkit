<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/bootstrap.php';

$csrfToken = $_SESSION['csrf_token'];
$uploadId = $_GET['id'] ?? '';
$uploads = $_SESSION['uploads'] ?? [];
$record = is_string($uploadId) && preg_match('/\A[a-f0-9]{32}\z/', $uploadId) && is_array($uploads)
    ? ($uploads[$uploadId] ?? null)
    : null;
$storedName = is_array($record) ? ($record['stored_name'] ?? '') : '';
$createdAt = is_array($record) ? ($record['created_at'] ?? null) : null;
$canOpenEditor = is_string($storedName)
    && $storedName === $uploadId . '.pdf'
    && is_numeric($createdAt)
    && (int) $createdAt >= time() - UPLOAD_RETENTION_SECONDS;

if (!$canOpenEditor) {
    http_response_code(404);
}
header('Cache-Control: no-store, private');

$assetPrefix = '../';
$extraStylesheet = '../assets/css/editor.css';
$pageTitle = 'Edit PDF — PDF Toolkit';
$pageDescription = 'Add text, highlights, shapes, and page changes to a PDF in your browser.';
$documentName = is_array($record) && isset($record['name']) && is_string($record['name'])
    ? $record['name']
    : 'PDF document';
$sourceUrl = $canOpenEditor
    ? '../api/source.php?id=' . rawurlencode($uploadId)
    : '';

require __DIR__ . '/../includes/header.php';
?>
<main class="editor-page section-wrap"<?= $canOpenEditor ? ' data-source-url="' . htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
    <?php if (!$canOpenEditor): ?>
        <section class="editor-unavailable">
            <span class="editor-empty-icon"><svg aria-hidden="true"><use href="#icon-file"></use></svg></span>
            <h1>This PDF is no longer available</h1>
            <p>Upload a PDF again from the homepage to open it in the editor.</p>
            <a class="button button-primary" href="../#upload">Go to upload</a>
        </section>
    <?php else: ?>
        <div class="editor-heading">
            <div>
                <a class="editor-back-link" href="../#tools"><svg aria-hidden="true"><use href="#icon-arrow"></use></svg> All tools</a>
                <h1>Edit your PDF</h1>
                <p><?= htmlspecialchars($documentName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="editor-privacy"><svg aria-hidden="true"><use href="#icon-shield"></use></svg> Markup and export stay in your browser</span>
        </div>
        <div class="editor-layout">
            <aside class="editor-panel markup-panel" aria-label="Markup tools">
                <div class="panel-heading">
                    <span class="panel-heading-icon"><svg aria-hidden="true"><use href="#icon-edit"></use></svg></span>
                    <div><h2>Add to page</h2><p>Choose a tool, then click the document.</p></div>
                </div>
                <div class="markup-tools" role="group" aria-label="Choose a markup tool">
                    <button class="markup-tool is-active" type="button" data-tool="text" aria-pressed="true"><svg aria-hidden="true"><use href="#icon-word"></use></svg><span>Text</span></button>
                    <button class="markup-tool" type="button" data-tool="highlight" aria-pressed="false"><svg aria-hidden="true"><use href="#icon-spark"></use></svg><span>Highlight</span></button>
                    <button class="markup-tool" type="button" data-tool="rectangle" aria-pressed="false"><svg aria-hidden="true"><use href="#icon-edit"></use></svg><span>Rectangle</span></button>
                    <button class="markup-tool" type="button" data-tool="ellipse" aria-pressed="false"><svg aria-hidden="true"><use href="#icon-image"></use></svg><span>Ellipse</span></button>
                </div>
                <label class="editor-field" id="text-field">Text overlay
                    <input id="overlay-text" type="text" maxlength="160" placeholder="Type your text" autocomplete="off">
                </label>
                <label class="editor-field">Color
                    <input id="overlay-color" type="color" value="#6757d9">
                </label>
                <button class="button button-secondary undo-button" id="undo-mark" type="button" disabled>Undo last mark</button>
                <p class="editor-disclaimer">This editor adds a new layer; existing PDF text is unchanged. Encrypted PDFs and editable form fields are not supported. Saving a signed PDF invalidates its digital signature.</p>
            </aside>

            <section class="editor-workspace" aria-label="PDF preview and controls">
                <div class="editor-toolbar">
                    <div class="page-navigation" aria-label="Page navigation">
                        <button class="icon-button" id="previous-page" type="button" aria-label="Previous page" disabled><svg aria-hidden="true"><use href="#icon-arrow"></use></svg></button>
                        <label for="current-page">Page</label>
                        <input id="current-page" type="number" min="1" value="1" aria-label="Current page">
                        <span>of <strong id="page-count">—</strong></span>
                        <button class="icon-button next-page-button" id="next-page" type="button" aria-label="Next page" disabled><svg aria-hidden="true"><use href="#icon-arrow"></use></svg></button>
                    </div>
                    <div class="zoom-controls" aria-label="Zoom controls">
                        <button class="icon-button" id="zoom-out" type="button" aria-label="Zoom out">−</button>
                        <span id="zoom-level">Fit</span>
                        <button class="icon-button" id="zoom-in" type="button" aria-label="Zoom in">+</button>
                    </div>
                    <button class="button button-primary save-button" id="save-pdf" type="button" disabled><svg aria-hidden="true"><use href="#icon-download"></use></svg> Download PDF</button>
                </div>
                <div class="canvas-scroll" id="canvas-scroll">
                    <div class="loading-state" id="loading-state"><span class="loading-spinner" aria-hidden="true"></span><span>Opening your PDF…</span></div>
                    <div class="page-container" id="page-container" hidden>
                        <canvas id="page-canvas" aria-label="PDF page preview"></canvas>
                        <div class="mark-layer" id="mark-layer" tabindex="0" aria-label="Add markup to this page"></div>
                    </div>
                </div>
                <p class="editor-status" id="editor-status" role="status" aria-live="polite">Loading your private PDF…</p>
            </section>

            <aside class="editor-panel page-panel" aria-label="Page organization">
                <div class="panel-heading">
                    <span class="panel-heading-icon panel-heading-blue"><svg aria-hidden="true"><use href="#icon-merge"></use></svg></span>
                    <div><h2>Organize pages</h2><p>Reorder, rotate, or remove pages.</p></div>
                </div>
                <div class="current-page-card">
                    <span class="current-page-number" id="selected-page-label">—</span>
                    <span>Selected page</span>
                </div>
                <div class="page-actions">
                    <button class="page-action" id="move-page-up" type="button" disabled><svg aria-hidden="true"><use href="#icon-arrow"></use></svg> Move earlier</button>
                    <button class="page-action" id="move-page-down" type="button" disabled><svg aria-hidden="true"><use href="#icon-arrow"></use></svg> Move later</button>
                    <button class="page-action" id="rotate-page" type="button" disabled><svg aria-hidden="true"><use href="#icon-rotate"></use></svg> Rotate 90°</button>
                    <button class="page-action page-action-danger" id="delete-page" type="button" disabled><svg aria-hidden="true"><use href="#icon-close"></use></svg> Remove page</button>
                </div>
                <p class="editor-disclaimer">The downloaded PDF is a new copy. Your private source upload remains unchanged.</p>
            </aside>
        </div>
    <?php endif; ?>
</main>
<?php if ($canOpenEditor): ?>
    <script src="../assets/vendor/pdf-lib/pdf-lib.min.js"></script>
    <script type="module" src="../assets/js/editor.js"></script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
