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

Livewire.start();
