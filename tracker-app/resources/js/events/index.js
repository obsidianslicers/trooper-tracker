import eventSelector from './eventSelector';

export default function (Alpine) {
    window.Events = window.Events || {};

    window.Events.Search = window.Events.Search || {};

    window.Events.Search.eventSelector = eventSelector;
}
