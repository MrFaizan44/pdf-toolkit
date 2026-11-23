<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/bootstrap.php';

$csrfToken = $_SESSION['csrf_token'];
$zipArchiveAvailable = class_exists('ZipArchive');
$acceptedFormats = $zipArchiveAvailable
    ? 'PDF, Word, PowerPoint, Excel, JPG or PNG'
    : 'PDF, DOC, PPT, XLS, JPG or PNG · DOCX/PPTX/XLSX need PHP ZipArchive';
$tools = [
    ['PDF to Word', 'word', 'convert-pdf'],
    ['Word to PDF', 'file', 'convert-to-pdf'],
    ['PDF to PowerPoint', 'slides', 'convert-pdf'],
    ['PowerPoint to PDF', 'slides', 'convert-to-pdf'],
    ['PDF to Excel', 'sheet', 'convert-pdf'],
    ['Excel to PDF', 'sheet', 'convert-to-pdf'],
    ['Merge PDF', 'merge', 'organize-pdf'],
    ['Split PDF', 'split', 'organize-pdf'],
    ['Compress PDF', 'compress', 'optimize-pdf'],
    ['Edit PDF', 'edit', 'edit-pdf'],
    ['Rotate PDF', 'rotate', 'organize-pdf'],
    ['JPG to PDF', 'image', 'convert-to-pdf'],
    ['PDF to JPG', 'image', 'convert-pdf'],
    ['Protect PDF', 'lock', 'security'],
    ['Unlock PDF', 'unlock', 'security'],
];

require __DIR__ . '/includes/header.php';
?>
<main>
    <section class="hero section-wrap" id="home">
        <div class="hero-copy">
            <p class="eyebrow"><span class="eyebrow-dot"></span> A calmer way to work with documents</p>
            <h1>Everything you need to work with your <span>documents.</span></h1>
            <p class="hero-description">Convert, organize, and refine your PDFs with a clear, straightforward toolkit. Your documents stay yours.</p>
            <div class="hero-proof">
                <span><svg aria-hidden="true"><use href="#icon-shield"></use></svg> Privacy-first by design</span>
                <span><svg aria-hidden="true"><use href="#icon-check"></use></svg> No account required</span>
            </div>
        </div>
        <div class="upload-card" id="upload">
            <div class="upload-topline">
                <div class="upload-icon"><svg aria-hidden="true"><use href="#icon-upload"></use></svg></div>
                <span class="secure-label"><svg aria-hidden="true"><use href="#icon-lock"></use></svg> Private upload</span>
            </div>
            <h2>Start with a document</h2>
            <p class="upload-description">Choose a file to add it to your private workspace.</p>
            <div class="drop-zone" id="drop-zone" role="group" aria-label="Choose or drop a document">
                <input id="file-input" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png" hidden>
                <svg class="drop-icon" aria-hidden="true"><use href="#icon-file"></use></svg>
                <strong>Drop your file here</strong>
                <span>or <button class="text-button" type="button" id="browse-button">browse files</button></span>
                <small><?= htmlspecialchars($acceptedFormats, ENT_QUOTES, 'UTF-8') ?> · Up to 25 MB</small>
            </div>
            <div class="file-panel" id="file-panel" hidden>
                <div class="file-summary">
                    <span class="file-type-icon"><svg aria-hidden="true"><use href="#icon-file"></use></svg></span>
                    <div class="file-details">
                        <strong id="file-name"></strong>
                        <span id="file-size"></span>
                    </div>
                    <button class="icon-button" id="remove-button" type="button" aria-label="Remove selected file" title="Remove file">
                        <svg aria-hidden="true"><use href="#icon-close"></use></svg>
                    </button>
                </div>
                <div class="progress-track" id="progress-track" hidden><span id="progress-bar"></span></div>
                <p class="upload-status" id="upload-status" role="status" aria-live="polite"></p>
                <button class="button button-primary upload-submit" id="upload-button" type="button">Upload securely <svg aria-hidden="true"><use href="#icon-arrow"></use></svg></button>
                <a class="button button-secondary editor-link" id="edit-document-button" href="#" hidden>Edit this PDF <svg aria-hidden="true"><use href="#icon-arrow"></use></svg></a>
            </div>
            <p class="privacy-note" id="privacy-note"><svg aria-hidden="true"><use href="#icon-shield"></use></svg> Uploads are stored outside the public web folder. Expired files are cleaned during later uploads; schedule the included cleanup script for regular cleanup.</p>
        </div>
        <div class="hero-orbit orbit-one" aria-hidden="true"></div>
        <div class="hero-orbit orbit-two" aria-hidden="true"></div>
    </section>

    <section class="section-wrap tools-section" id="tools">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Your everyday document toolkit</p>
                <h2>Popular tools</h2>
                <p>Everything in one place, with a clear path from upload to finished document.</p>
            </div>
            <span class="availability-note"><span></span> Tools are being built in stages</span>
        </div>
        <div class="tool-grid">
            <?php foreach ($tools as [$name, $icon, $category]): ?>
                    <?php
                    $isAvailable = in_array($name, ['Edit PDF', 'Rotate PDF'], true);
                    $toolTag = $isAvailable ? 'a' : 'article';
                    $toolStatus = !$isAvailable ? 'Coming soon' : ($name === 'Rotate PDF' ? 'In editor' : 'Available');
                    ?>
                    <<?= $toolTag ?> class="tool-card<?= $isAvailable ? ' tool-card-link' : '' ?>"<?= $isAvailable ? ' href="#upload" aria-label="Upload a PDF to edit or rotate pages"' : '' ?>>
                    <span class="tool-icon icon-<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>">
                        <svg aria-hidden="true"><use href="#icon-<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></use></svg>
                    </span>
                    <span class="tool-title"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="tool-status<?= $isAvailable ? ' tool-status-available' : '' ?>"><?= htmlspecialchars($toolStatus, ENT_QUOTES, 'UTF-8') ?></span>
                    </<?= $toolTag ?>>
            <?php endforeach; ?>
        </div>
        <p class="tool-disclosure">The PDF editor supports text overlays, annotations, shapes, page rotation, reordering, and removal. Other tools are shown as a roadmap and are not active yet.</p>
    </section>

    <section class="category-section" id="categories">
        <div class="section-wrap">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Find the right workflow</p>
                    <h2>Tools for every task</h2>
                    <p>Explore the ways PDF Toolkit is being shaped around your documents.</p>
                </div>
            </div>
            <div class="category-grid">
                <a class="category-card" href="#tools" id="convert-pdf"><span class="category-icon purple"><svg aria-hidden="true"><use href="#icon-file"></use></svg></span><span><strong>Convert PDF</strong><small>PDF to Word, Excel, PowerPoint and images</small></span><svg class="category-arrow" aria-hidden="true"><use href="#icon-arrow"></use></svg></a>
                <a class="category-card" href="#tools" id="convert-to-pdf"><span class="category-icon blue"><svg aria-hidden="true"><use href="#icon-convert"></use></svg></span><span><strong>Convert to PDF</strong><small>Turn office documents and images into PDF</small></span><svg class="category-arrow" aria-hidden="true"><use href="#icon-arrow"></use></svg></a>
                <a class="category-card" href="#tools" id="edit-pdf"><span class="category-icon pink"><svg aria-hidden="true"><use href="#icon-edit"></use></svg></span><span><strong>Edit PDF</strong><small>Add annotations and organize page content</small></span><svg class="category-arrow" aria-hidden="true"><use href="#icon-arrow"></use></svg></a>
                <a class="category-card" href="#tools" id="organize-pdf"><span class="category-icon amber"><svg aria-hidden="true"><use href="#icon-merge"></use></svg></span><span><strong>Organize PDF</strong><small>Reorder, rotate, and remove pages; merge and split are coming soon</small></span><svg class="category-arrow" aria-hidden="true"><use href="#icon-arrow"></use></svg></a>
                <a class="category-card" href="#tools" id="optimize-pdf"><span class="category-icon green"><svg aria-hidden="true"><use href="#icon-compress"></use></svg></span><span><strong>Optimize PDF</strong><small>Reduce file size and keep documents manageable</small></span><svg class="category-arrow" aria-hidden="true"><use href="#icon-arrow"></use></svg></a>
                <a class="category-card" href="#tools" id="security"><span class="category-icon navy"><svg aria-hidden="true"><use href="#icon-shield"></use></svg></span><span><strong>Security &amp; protection</strong><small>Protect files you own or are authorized to modify</small></span><svg class="category-arrow" aria-hidden="true"><use href="#icon-arrow"></use></svg></a>
            </div>
        </div>
    </section>

    <section class="section-wrap how-section" id="how-it-works">
        <div class="section-heading centered-heading">
            <div>
                <p class="eyebrow">Simple from the start</p>
                <h2>Three steps. No guesswork.</h2>
                <p>Upload a PDF, apply the changes you need, and download a new copy.</p>
            </div>
        </div>
        <div class="steps-grid">
            <article class="step-card"><span class="step-number">01</span><span class="step-icon"><svg aria-hidden="true"><use href="#icon-upload"></use></svg></span><h3>Upload your document</h3><p>Choose a file from your device. It is handled securely and kept private.</p></article>
            <article class="step-card"><span class="step-number">02</span><span class="step-icon"><svg aria-hidden="true"><use href="#icon-sliders"></use></svg></span><h3>Make your changes</h3><p>Add text, highlights, and shapes, or rotate and organize your pages.</p></article>
            <article class="step-card"><span class="step-number">03</span><span class="step-icon"><svg aria-hidden="true"><use href="#icon-download"></use></svg></span><h3>Download your new copy</h3><p>Your original private upload stays unchanged while you save the edited PDF.</p></article>
        </div>
    </section>

    <section class="feature-section">
        <div class="section-wrap feature-layout">
            <div class="feature-copy">
                <p class="eyebrow">A better kind of utility</p>
                <h2>Useful tools, with your privacy built in.</h2>
                <p>PDF Toolkit is designed to make document work feel simple, dependable, and respectful of your files.</p>
                <a class="button button-secondary" href="#upload">Try a private upload <svg aria-hidden="true"><use href="#icon-arrow"></use></svg></a>
            </div>
            <div class="feature-list">
                <article><span class="feature-check"><svg aria-hidden="true"><use href="#icon-shield"></use></svg></span><div><h3>Privacy-focused processing</h3><p>Files are kept outside the public web directory. Expired uploads are cleaned during later uploads or by the optional scheduled cleanup script.</p></div></article>
                <article><span class="feature-check"><svg aria-hidden="true"><use href="#icon-spark"></use></svg></span><div><h3>Simple by design</h3><p>Clear controls, thoughtful defaults, and no unnecessary steps.</p></div></article>
                <article><span class="feature-check"><svg aria-hidden="true"><use href="#icon-file"></use></svg></span><div><h3>Multiple file formats</h3><p>Upload common PDF, Office, and image formats to the private workspace.</p></div></article>
                <article><span class="feature-check"><svg aria-hidden="true"><use href="#icon-device"></use></svg></span><div><h3>Works across devices</h3><p>A responsive experience for desktop, tablet, and mobile screens.</p></div></article>
                <article><span class="feature-check"><svg aria-hidden="true"><use href="#icon-user"></use></svg></span><div><h3>No mandatory registration</h3><p>Use the current upload experience without creating an account.</p></div></article>
                <article><span class="feature-check"><svg aria-hidden="true"><use href="#icon-bolt"></use></svg></span><div><h3>Local-first foundation</h3><p>Built for your own PHP and XAMPP environment, with no external file APIs.</p></div></article>
            </div>
        </div>
    </section>

    <section class="pricing-placeholder section-wrap" id="pricing">
        <div><p class="eyebrow">Coming later</p><h2>Pricing that stays simple.</h2><p>PDF Toolkit is currently free to explore locally. Account and pricing plans are not available yet.</p></div>
        <span class="placeholder-pill">Not available yet</span>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
