(() => {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const fileInput = document.getElementById('file-input');
    const dropZone = document.getElementById('drop-zone');
    const browseButton = document.getElementById('browse-button');
    const filePanel = document.getElementById('file-panel');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const uploadButton = document.getElementById('upload-button');
    const editDocumentButton = document.getElementById('edit-document-button');
    const removeButton = document.getElementById('remove-button');
    const uploadStatus = document.getElementById('upload-status');
    const progressTrack = document.getElementById('progress-track');
    const progressBar = document.getElementById('progress-bar');
    const toast = document.getElementById('toast');
    const menuButton = document.getElementById('mobile-menu-button');
    const navigation = document.getElementById('primary-navigation');
    let selectedFile = null;
    let uploadedId = null;
    let toastTimer;

    const allowedExtensions = new Set(['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);
    const maxFileSize = 25 * 1024 * 1024;

    function showToast(message, type = 'success') {
        window.clearTimeout(toastTimer);
        toast.textContent = message;
        toast.className = `toast toast-${type}`;
        toast.hidden = false;
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 5000);
    }

    function formatBytes(bytes) {
        if (bytes < 1024 * 1024) {
            return `${Math.max(1, Math.round(bytes / 1024))} KB`;
        }
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    function setFile(file) {
        if (!file) return;
        if (uploadedId) {
            showToast('Remove the current private upload before choosing another file.', 'error');
            return;
        }

        const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
        if (!allowedExtensions.has(extension)) {
            showToast('Choose a PDF, Office document, JPG, or PNG file.', 'error');
            fileInput.value = '';
            return;
        }
        if (file.size === 0 || file.size > maxFileSize) {
            showToast(file.size > maxFileSize ? 'This file is larger than the 25 MB limit.' : 'This file is empty.', 'error');
            fileInput.value = '';
            return;
        }

        selectedFile = file;
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        uploadStatus.textContent = '';
        uploadStatus.className = 'upload-status';
        uploadButton.disabled = false;
        uploadButton.textContent = 'Upload securely';
        progressTrack.hidden = true;
        progressBar.style.width = '0%';
        dropZone.hidden = true;
        filePanel.hidden = false;
    }

    function resetSelection() {
        selectedFile = null;
        uploadedId = null;
        fileInput.value = '';
        editDocumentButton.hidden = true;
        editDocumentButton.href = '#';
        filePanel.hidden = true;
        dropZone.hidden = false;
        uploadButton.disabled = false;
        uploadButton.textContent = 'Upload securely';
        uploadStatus.textContent = '';
        progressTrack.hidden = true;
        progressBar.style.width = '0%';
    }

    if (fileInput && dropZone && browseButton && filePanel && uploadButton && removeButton && editDocumentButton) {
        browseButton.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => setFile(fileInput.files?.[0]));
        dropZone.addEventListener('click', (event) => {
            if (!event.target.closest('button')) fileInput.click();
        });
        ['dragenter', 'dragover'].forEach((eventName) => {
            dropZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropZone.classList.add('is-dragging');
            });
        });
        ['dragleave', 'drop'].forEach((eventName) => {
            dropZone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropZone.classList.remove('is-dragging');
            });
        });
        dropZone.addEventListener('drop', (event) => setFile(event.dataTransfer?.files?.[0]));

        uploadButton.addEventListener('click', () => {
            if (!selectedFile || uploadedId) return;

            const formData = new FormData();
            formData.append('document', selectedFile);
            uploadButton.disabled = true;
            uploadStatus.textContent = 'Uploading securely…';
            uploadStatus.className = 'upload-status';
            progressTrack.hidden = false;

            const request = new XMLHttpRequest();
            request.open('POST', 'api/upload.php');
            request.setRequestHeader('X-CSRF-Token', csrfToken);
            request.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    progressBar.style.width = `${Math.round((event.loaded / event.total) * 100)}%`;
                }
            });
            request.addEventListener('load', () => {
                let response;
                try {
                    response = JSON.parse(request.responseText);
                } catch {
                    response = null;
                }

                if (request.status >= 200 && request.status < 300 && response?.id) {
                    uploadedId = response.id;
                    uploadButton.textContent = 'Stored privately';
                    uploadStatus.textContent = response.message;
                    uploadStatus.classList.add('status-success');
                    progressBar.style.width = '100%';
                    if (response.can_edit === true) {
                        editDocumentButton.href = `tools/edit-pdf.php?id=${encodeURIComponent(response.id)}`;
                        editDocumentButton.hidden = false;
                    }
                    showToast('Your document was uploaded to private storage.');
                    return;
                }

                uploadButton.disabled = false;
                uploadStatus.textContent = response?.error ?? 'The upload could not be completed. Please try again.';
                uploadStatus.classList.add('status-error');
                progressTrack.hidden = true;
                showToast(uploadStatus.textContent, 'error');
            });
            request.addEventListener('error', () => {
                uploadButton.disabled = false;
                uploadStatus.textContent = 'A network error interrupted the upload. Please try again.';
                uploadStatus.classList.add('status-error');
                progressTrack.hidden = true;
                showToast(uploadStatus.textContent, 'error');
            });
            request.send(formData);
        });

        removeButton.addEventListener('click', async () => {
            if (!uploadedId) {
                resetSelection();
                return;
            }

            removeButton.disabled = true;
            try {
                const response = await fetch('api/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                    },
                    body: JSON.stringify({ id: uploadedId }),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error ?? 'The upload could not be removed.');
                resetSelection();
                showToast(result.message);
            } catch (error) {
                uploadStatus.textContent = error instanceof Error ? error.message : 'The upload could not be removed.';
                uploadStatus.className = 'upload-status status-error';
                showToast(uploadStatus.textContent, 'error');
            } finally {
                removeButton.disabled = false;
            }
        });
    }

    if (menuButton && navigation) {
        menuButton.addEventListener('click', () => {
            const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', String(!isOpen));
            menuButton.setAttribute('aria-label', isOpen ? 'Open navigation' : 'Close navigation');
            navigation.classList.toggle('is-open', !isOpen);
        });
        navigation.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                menuButton.setAttribute('aria-expanded', 'false');
                menuButton.setAttribute('aria-label', 'Open navigation');
                navigation.classList.remove('is-open');
            });
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && navigation.classList.contains('is-open')) {
                menuButton.setAttribute('aria-expanded', 'false');
                menuButton.setAttribute('aria-label', 'Open navigation');
                navigation.classList.remove('is-open');
                menuButton.focus();
            }
        });
    }
})();
