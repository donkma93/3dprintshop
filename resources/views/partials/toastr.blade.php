<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    #toast-container > div {
        opacity: 1;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .18);
        border-radius: .75rem;
    }
    #toast-container > .toast {
        background-image: none !important;
    }
    .toast-success { background-color: #0f766e !important; }
    .toast-error { background-color: #dc2626 !important; }
    .toast-info { background-color: #2563eb !important; }
    .toast-warning { background-color: #d97706 !important; }

    /* Confirm toast overlay */
    .toast-confirm-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .45);
        z-index: 9998;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .toast-confirm-overlay.show { display: flex; }
    .toast-confirm-box {
        width: min(420px, 100%);
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 25px 60px rgba(15, 23, 42, .25);
        padding: 1.25rem 1.35rem;
        animation: toastConfirmIn .18s ease;
    }
    .toast-confirm-box h3 {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0 0 .5rem;
        color: #0f172a;
    }
    .toast-confirm-box p {
        margin: 0 0 1.15rem;
        color: #64748b;
        font-size: .95rem;
        line-height: 1.5;
    }
    .toast-confirm-actions {
        display: flex;
        gap: .5rem;
        justify-content: flex-end;
    }
    .toast-confirm-actions .btn {
        min-width: 88px;
    }
    @keyframes toastConfirmIn {
        from { transform: translateY(8px) scale(.98); opacity: 0; }
        to { transform: none; opacity: 1; }
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    toastr.options = {
        closeButton: true,
        progressBar: true,
        newestOnTop: true,
        positionClass: 'toast-top-right',
        preventDuplicates: false,
        timeOut: 3500,
        extendedTimeOut: 1500,
        showDuration: 250,
        hideDuration: 250,
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };

    window.appToast = {
        success: function (message) { toastr.success(message); },
        error: function (message) { toastr.error(message); },
        info: function (message) { toastr.info(message); },
        warning: function (message) { toastr.warning(message); },

        /**
         * Xác nhận kiểu toast/modal gọn — thay cho window.confirm()
         * @returns {Promise<boolean>}
         */
        confirm: function (message, title) {
            title = title || 'Xác nhận';
            message = message || 'Bạn có chắc muốn thực hiện thao tác này?';

            return new Promise(function (resolve) {
                var overlay = document.getElementById('toast-confirm-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'toast-confirm-overlay';
                    overlay.className = 'toast-confirm-overlay';
                    overlay.innerHTML =
                        '<div class="toast-confirm-box" role="dialog" aria-modal="true">' +
                            '<h3 id="toast-confirm-title"></h3>' +
                            '<p id="toast-confirm-message"></p>' +
                            '<div class="toast-confirm-actions">' +
                                '<button type="button" class="btn btn-outline-secondary" data-confirm-cancel>Hủy</button>' +
                                '<button type="button" class="btn btn-danger" data-confirm-ok>Đồng ý</button>' +
                            '</div>' +
                        '</div>';
                    document.body.appendChild(overlay);
                }

                overlay.querySelector('#toast-confirm-title').textContent = title;
                overlay.querySelector('#toast-confirm-message').textContent = message;
                overlay.classList.add('show');

                var okBtn = overlay.querySelector('[data-confirm-ok]');
                var cancelBtn = overlay.querySelector('[data-confirm-cancel]');

                function cleanup(result) {
                    overlay.classList.remove('show');
                    okBtn.removeEventListener('click', onOk);
                    cancelBtn.removeEventListener('click', onCancel);
                    overlay.removeEventListener('click', onBackdrop);
                    document.removeEventListener('keydown', onKey);
                    resolve(result);
                }

                function onOk() { cleanup(true); }
                function onCancel() { cleanup(false); }
                function onBackdrop(e) {
                    if (e.target === overlay) cleanup(false);
                }
                function onKey(e) {
                    if (e.key === 'Escape') cleanup(false);
                    if (e.key === 'Enter') cleanup(true);
                }

                okBtn.addEventListener('click', onOk);
                cancelBtn.addEventListener('click', onCancel);
                overlay.addEventListener('click', onBackdrop);
                document.addEventListener('keydown', onKey);
                okBtn.focus();
            });
        }
    };

    // Form xóa: data-confirm="Nội dung..."
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.hasAttribute('data-confirm')) return;
        if (form.dataset.confirmAccepted === '1') {
            delete form.dataset.confirmAccepted;
            return;
        }

        e.preventDefault();
        var message = form.getAttribute('data-confirm') || 'Bạn có chắc muốn xóa?';
        var title = form.getAttribute('data-confirm-title') || 'Xác nhận xóa';

        window.appToast.confirm(message, title).then(function (ok) {
            if (ok) {
                form.dataset.confirmAccepted = '1';
                form.submit();
            }
        });
    });

    // Flash messages từ Laravel session
    @if(session('success'))
        appToast.success(@json(session('success')));
    @endif
    @if(session('error'))
        appToast.error(@json(session('error')));
    @endif
    @if(session('warning'))
        appToast.warning(@json(session('warning')));
    @endif
    @if(session('info'))
        appToast.info(@json(session('info')));
    @endif
    @if(isset($errors) && $errors->any())
        @foreach($errors->all() as $error)
            appToast.error(@json($error));
        @endforeach
    @endif
</script>
