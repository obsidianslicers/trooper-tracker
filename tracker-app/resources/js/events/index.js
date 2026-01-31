import eventSelector from './eventSelector';
import mapSelector from './mapSelector';

export default function (Alpine) {
    window.Events = window.Events || {};

    window.Events.Search = window.Events.Search || {};

    window.Events.Search.eventSelector = eventSelector;
    window.Events.Search.mapSelector = mapSelector;
}
