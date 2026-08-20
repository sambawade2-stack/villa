/*
 | Alpine est monté ici, une seule fois, et remis à Livewire.
 |
 | Livewire embarque sa propre copie d'Alpine, mais ne la sert que sur les pages
 | comportant un composant Livewire. Nos pages publiques n'en ont pas encore et
 | s'appuient pourtant sur Alpine (menu, visionneuse, filtres). On importe donc
 | Livewire et son Alpine depuis le paquet, on enregistre nos plugins, puis on
 | démarre : une seule instance d'Alpine sur toutes les pages, sans doublon.
 |
 | Le gabarit doit exposer @livewireScriptConfig en regard de cet import.
 */
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

/*
 | Apparition au défilement.
 |
 | La classe `js` n'est posée qu'ici : sans JavaScript, la règle CSS qui masque
 | les éléments ne s'applique jamais et la page reste entièrement lisible. Le
 | contenu n'est donc jamais dépendant d'une animation pour exister.
 */
document.documentElement.classList.add('js');

const revealOnScroll = () => {
    const targets = document.querySelectorAll('.reveal:not(.is-visible)');

    if (targets.length === 0) return;

    // Sans IntersectionObserver, ou si le visiteur veut moins d'animations,
    // on montre tout immédiatement.
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduced || !('IntersectionObserver' in window)) {
        targets.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.05 },
    );

    targets.forEach((el) => observer.observe(el));
};

document.addEventListener('DOMContentLoaded', revealOnScroll);
document.addEventListener('livewire:navigated', revealOnScroll);

Livewire.start();
