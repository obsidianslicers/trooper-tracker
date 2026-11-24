import './bootstrap';

// jQuery
import $ from 'jquery';
window.$ = $;
window.jQuery = $;

// Bootstrap (bundle includes Popper)
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Alpine.js
import Alpine from 'alpinejs';
/**
 *
 * @alpinejs/collapse
 * - Adds smooth height‑aware transitions for show/hide elements.
 * - Perfect for accordions, dropdowns, slide-down panels.
 * - Use: <div x-show="open" x-collapse>...</div>
 *
 * @alpinejs/persist
 * - Persists Alpine state to localStorage automatically.
 * - Great for dark mode, sidebar state, remembered form choices.
 * - Use: x-data="{ open: $persist(false) }"
 *
 * @alpinejs/intersect
 * - Wrapper around Intersection Observer API.
 * - Triggers logic when an element enters the viewport.
 * - Ideal for lazy loading, scroll animations, infinite scroll.
 * - Use: <div x-intersect="loadMore()">...</div>
 */

import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';
import mask from '@alpinejs/mask';
import persist from '@alpinejs/persist';

Alpine.plugin(collapse);
Alpine.plugin(persist);
Alpine.plugin(intersect);
Alpine.plugin(mask);

import AccountPlugin from './account/index.js';
Alpine.plugin(AccountPlugin);

import EventPlugin from './events/index.js';
Alpine.plugin(EventPlugin);

window.Alpine = Alpine;
Alpine.start();

// Typeahead (not used directly, may get removed later)
import 'typeahead.js';

// SimpleMDE
import EasyMDE from "easymde";
window.EasyMDE = EasyMDE;

// SortableJS
import Sortable from 'sortablejs';
window.Sortable = Sortable;

//  custom 
import './custom/bootstrap-events.js';
import './custom/forms.js';
import './custom/hash-link-collapse.js';
import './custom/toasts.js';
import './custom/tooltips.js';

