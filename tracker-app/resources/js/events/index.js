import mapSelector from './mapSelector';
import shiftsView from './shiftsView';

export default function (Alpine) {
    window.Events = window.Events || {};

    window.Events.Search = window.Events.Search || {};

    window.Events.Search.mapSelector = mapSelector;
    window.Events.Search.shiftsView = shiftsView;
}
