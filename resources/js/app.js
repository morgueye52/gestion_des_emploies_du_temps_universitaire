import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const sidebar = document.getElementById('appSidebar');

if (sidebar) {
    const scrollKey = 'performics-sidebar-scroll';
    const savedScroll = Number(sessionStorage.getItem(scrollKey));

    if (Number.isFinite(savedScroll)) {
        sidebar.scrollTop = savedScroll;
    }

    window.addEventListener('pagehide', () => {
        sessionStorage.setItem(scrollKey, String(sidebar.scrollTop));
    });
}
