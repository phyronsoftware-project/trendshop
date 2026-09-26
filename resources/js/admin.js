import $ from 'jquery';
import 'summernote/dist/summernote-lite';
import { initializeChat } from './chat';

window.$ = window.jQuery = $;

document.addEventListener('DOMContentLoaded', () => {
    initializeChat();
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const backdrop = document.querySelector('[data-admin-backdrop]');
    const loader = document.querySelector('[data-admin-loader]');
    const adminAlert = document.querySelector('[data-admin-alert]');

    // Toggle the responsive sidebar with a short slide animation.
    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        backdrop?.classList.add('hidden');
    };
    document.querySelector('[data-sidebar-open]')?.addEventListener('click', () => {
        sidebar?.classList.remove('-translate-x-full');
        backdrop?.classList.remove('hidden');
    });
    document.querySelector('[data-sidebar-close]')?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);

    // Dismiss admin success, warning and error feedback after three seconds.
    const hideAdminAlert = () => {
        adminAlert?.classList.add('-translate-y-2', 'opacity-0');
        window.setTimeout(() => adminAlert?.remove(), 300);
    };
    if (adminAlert) {
        window.setTimeout(hideAdminAlert, 3000);
    }
    document.querySelector('[data-admin-alert-close]')?.addEventListener('click', hideAdminAlert);

    // Preserve the compact desktop sidebar preference between admin pages.
    if (window.localStorage.getItem('trendshop-admin-sidebar') === 'collapsed') {
        document.body.classList.add('admin-sidebar-collapsed');
    }
    document.querySelector('[data-sidebar-collapse]')?.addEventListener('click', () => {
        document.body.classList.toggle('admin-sidebar-collapsed');
        window.localStorage.setItem('trendshop-admin-sidebar', document.body.classList.contains('admin-sidebar-collapsed') ? 'collapsed' : 'expanded');
    });

    // Expand matching sidebar submenus without navigating away.
    document.querySelectorAll('[data-sidebar-group]').forEach((group) => {
        const toggle = group.querySelector('[data-sidebar-group-toggle]');
        toggle?.addEventListener('click', () => {
            const panel = group.querySelector('[data-sidebar-group-panel]');
            const isOpening = panel?.classList.contains('grid-rows-[0fr]');
            panel?.classList.toggle('grid-rows-[0fr]');
            panel?.classList.toggle('grid-rows-[1fr]');
            group.querySelector('[data-sidebar-chevron]')?.classList.toggle('rotate-180');
            toggle.setAttribute('aria-expanded', String(isOpening));
        });
    });

    // Switch Khmer, English and Chinese form panels while keeping all inputs submitted.
    document.querySelectorAll('[data-language-tabs]').forEach((tabs) => {
        const languageTabs = [...tabs.querySelectorAll('[data-language-tab]')];
        if (!languageTabs.some((tab) => tab.classList.contains('is-active'))) {
            languageTabs[0]?.classList.add('is-active');
        }

        languageTabs.forEach((tab) => {
            tab.setAttribute('aria-selected', String(tab.classList.contains('is-active')));
            tab.addEventListener('click', () => {
                const locale = tab.dataset.languageTab;
                languageTabs.forEach((item) => {
                    const isActive = item === tab;
                    item.classList.toggle('is-active', isActive);
                    item.setAttribute('aria-selected', String(isActive));
                });
                tabs.parentElement.querySelectorAll('[data-language-panel]').forEach((panel) => { panel.hidden = panel.dataset.languagePanel !== locale; });
            });
        });
    });

    // Preview a selected administrator profile image before saving the form.
    const adminProfileInput = document.querySelector('[data-admin-profile-input]');
    const adminProfilePreview = document.querySelector('[data-admin-profile-preview]');
    adminProfileInput?.addEventListener('change', () => {
        const image = adminProfileInput.files?.[0];
        if (image && adminProfilePreview) {
            adminProfilePreview.src = URL.createObjectURL(image);
        }
    });

    // Match the requested full toolbar rich-text editor.
    $('.admin-rich-editor').summernote({
        height: 280,
        toolbar: [
            ['style', ['style']], ['font', ['bold', 'underline', 'clear']], ['fontname', ['fontname']],
            ['fontsize', ['fontsize']], ['color', ['color']], ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']], ['insert', ['link', 'picture', 'video']], ['view', ['fullscreen', 'codeview', 'help']],
        ],
    });

    // Show one body-level loader for server-backed navigation and form submissions.
    const showLoader = () => {
        loader?.classList.remove('hidden');
        loader?.classList.add('flex');
        document.body.classList.add('admin-loading');
    };
    document.querySelectorAll('form:not([data-chat-form])').forEach((form) => form.addEventListener('submit', () => {
        form.querySelectorAll('.admin-rich-editor').forEach((editor) => editor.value = $(editor).summernote('code'));
        showLoader();
    }));
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        const destination = new URL(link.href, window.location.href);
        if (destination.origin === window.location.origin && !(destination.pathname === window.location.pathname && destination.hash)) {
            showLoader();
        }
    });
    window.addEventListener('pageshow', () => {
        loader?.classList.add('hidden'); loader?.classList.remove('flex'); document.body.classList.remove('admin-loading');
    });
});
