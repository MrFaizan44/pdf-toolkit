<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'A thoughtful toolkit for PDF conversion and document work. Upload files privately and explore PDF Toolkit.', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="#ffffff">
    <title><?= htmlspecialchars($pageTitle ?? 'PDF Toolkit — Everything you need to work with PDFs', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>assets/css/style.css">
    <?php if (isset($extraStylesheet) && is_string($extraStylesheet)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($extraStylesheet, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
</head>
<body>
<svg class="svg-definitions" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
    <symbol id="icon-mark" viewBox="0 0 40 40"><path d="M11 4.5h12l8 8V33a3 3 0 0 1-3 3H11a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3Z" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/><path d="M23 5v8h8M14 21h12M14 26h8" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M29 24v10m-5-5h10" stroke="#7186f7" stroke-width="3" stroke-linecap="round"/></symbol>
    <symbol id="icon-file" viewBox="0 0 24 24"><path d="M6 3.5h8l5 5V20a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 20V5a1.5 1.5 0 0 1 1-1.5Z"/><path d="M14 4v5h5M8.5 13h7M8.5 16.5h7"/></symbol>
    <symbol id="icon-word" viewBox="0 0 24 24"><path d="M5 3.5h9l5 5V20a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 20v-15A1.5 1.5 0 0 1 6.5 3.5Z"/><path d="M14 4v5h5M8 13l1.5 5 2-4 2 4 1.5-5"/></symbol>
    <symbol id="icon-slides" viewBox="0 0 24 24"><rect x="3.5" y="4.5" width="17" height="12" rx="2"/><path d="M12 16.5v4m-4 0h8M8 12l2.5-2.5 2 2 3.5-3"/></symbol>
    <symbol id="icon-sheet" viewBox="0 0 24 24"><rect x="4" y="3.5" width="16" height="17" rx="2"/><path d="M4 9h16M9.3 9v11.5M14.7 9v11.5M4 14.5h16"/></symbol>
    <symbol id="icon-merge" viewBox="0 0 24 24"><path d="M7 4v5a3 3 0 0 0 3 3h4a3 3 0 0 1 3 3v5M7 4l-3 3m3-3 3 3m7 13-3-3m3 3 3-3"/></symbol>
    <symbol id="icon-split" viewBox="0 0 24 24"><path d="M7 4v5a3 3 0 0 0 3 3h4a3 3 0 0 0 3-3V4M7 4 4 7m3-3 3 3m7-3-3 3m3-3 3 3M7 20v-4a3 3 0 0 1 3-3m7 7v-4a3 3 0 0 0-3-3"/></symbol>
    <symbol id="icon-compress" viewBox="0 0 24 24"><path d="M8 3H4v4m12-4h4v4M4 17v4h4m12-4v4h-4M9 9l-5-5m11 11 5 5M15 9l5-5M9 15l-5 5"/></symbol>
    <symbol id="icon-edit" viewBox="0 0 24 24"><path d="m14 5 5 5M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z"/></symbol>
    <symbol id="icon-rotate" viewBox="0 0 24 24"><path d="M20 11a8 8 0 0 0-14-5L4 8m0-5v5h5M4 13a8 8 0 0 0 14 5l2-2m0 5v-5h-5"/></symbol>
    <symbol id="icon-image" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m4 17 5-5 3 3 3-4 5 6"/></symbol>
    <symbol id="icon-lock" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3m-4 5v2"/></symbol>
    <symbol id="icon-unlock" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 7.5-2M12 15v2"/></symbol>
    <symbol id="icon-shield" viewBox="0 0 24 24"><path d="M12 21s8-4 8-11V5l-8-3-8 3v5c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></symbol>
    <symbol id="icon-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
    <symbol id="icon-upload" viewBox="0 0 24 24"><path d="M12 16V4m-5 5 5-5 5 5M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/></symbol>
    <symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
    <symbol id="icon-close" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></symbol>
    <symbol id="icon-convert" viewBox="0 0 24 24"><path d="M4 7h15m0 0-4-4m4 4-4 4M20 17H5m0 0 4 4m-4-4 4-4"/></symbol>
    <symbol id="icon-sliders" viewBox="0 0 24 24"><path d="M4 6h9m4 0h3M4 12h3m4 0h9M4 18h9m4 0h3"/><circle cx="15" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="15" cy="18" r="2"/></symbol>
    <symbol id="icon-download" viewBox="0 0 24 24"><path d="M12 4v12m-5-5 5 5 5-5M4 20h16"/></symbol>
    <symbol id="icon-spark" viewBox="0 0 24 24"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Zm7 12 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/></symbol>
    <symbol id="icon-device" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/></symbol>
    <symbol id="icon-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
    <symbol id="icon-bolt" viewBox="0 0 24 24"><path d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z"/></symbol>
</svg>
<header class="site-header">
    <div class="header-inner section-wrap">
        <a class="brand" href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#home" aria-label="PDF Toolkit home">
            <span class="brand-mark"><svg aria-hidden="true"><use href="#icon-mark"></use></svg></span>
            <span class="brand-name">PDF <strong>Toolkit</strong></span>
        </a>
        <button class="mobile-menu-button" id="mobile-menu-button" type="button" aria-expanded="false" aria-controls="primary-navigation" aria-label="Open navigation">
            <span></span><span></span><span></span>
        </button>
        <nav class="primary-navigation" id="primary-navigation" aria-label="Main navigation">
            <a class="nav-link active" href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#home">Home</a>
            <a class="nav-link" href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#tools">All PDF tools</a>
            <a class="nav-link" href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#convert-pdf">Convert PDF</a>
            <a class="nav-link" href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#edit-pdf">Edit PDF</a>
            <a class="nav-link" href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#optimize-pdf">Compress PDF</a>
            <a class="nav-link" href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#pricing">Pricing</a>
            <span class="nav-divider" aria-hidden="true"></span>
            <button class="button button-login" type="button" disabled title="Login is not available yet">Log in</button>
            <button class="button button-small button-primary" type="button" disabled title="Sign up is not available yet">Sign up</button>
        </nav>
    </div>
</header>
