import '@tailwindplus/elements';

const disableDarkMode = () => {
    const root = document.documentElement;

    if (root.classList.contains('dark')) {
        root.classList.remove('dark');
    }

    if (root.style.colorScheme !== 'light') {
        root.style.colorScheme = 'light';
    }

    if (window.localStorage.getItem('flux.appearance') !== 'light') {
        window.localStorage.setItem('flux.appearance', 'light');
    }

    if (window.Flux && window.Flux.appearance !== 'light') {
        window.Flux.appearance = 'light';
    }
};

disableDarkMode();

new MutationObserver(disableDarkMode).observe(document.documentElement, {
    attributeFilter: ['class'],
});

document.addEventListener('livewire:navigated', disableDarkMode);
