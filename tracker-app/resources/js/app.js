import './bootstrap';

// jQuery
import $ from 'jquery';
window.$ = $;
window.jQuery = $;

// HTMX
import htmx from 'htmx.org';
window.htmx = htmx;

// Bootstrap (bundle includes Popper)
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Typeahead
import 'typeahead.js';

// Flatpickr
import flatpickr from 'flatpickr';
window.flatpickr = flatpickr;

// SimpleMDE
import EasyMDE from "easymde";
window.EasyMDE = EasyMDE;

//  custom 
import './custom/htmx-button-disable.js';
import './custom/htmx-csrf.js';
import './custom/htmx-date-picker.js';
import './custom/htmx-easymde.js';
import './custom/htmx-flash.js';
import './custom/htmx-pickers.js';
import './custom/htmx-typeahead.js';

