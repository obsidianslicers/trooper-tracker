import shiftsView from './shiftsView';

export default function (Alpine) {
    window.Events = window.Events || {};

    window.Events.Search = window.Events.Search || {};

    window.Events.Search.shiftsView = shiftsView;
}
