import '@tailwindplus/elements';
import { createIcons, icons } from 'lucide';

let lucideRescanTimer;

const renderLucideIcons = () => {
    clearTimeout(lucideRescanTimer);

    lucideRescanTimer = setTimeout(() => {
        createIcons({ icons });
    }, 16);
};

document.addEventListener('DOMContentLoaded', renderLucideIcons);
document.addEventListener('livewire:navigated', renderLucideIcons);

new MutationObserver(renderLucideIcons).observe(document.documentElement, {
    childList: true,
    subtree: true,
});
