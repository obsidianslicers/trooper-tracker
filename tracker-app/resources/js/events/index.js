import mapSelector from './mapSelector';
import shiftsView from './shiftsView';
import signupTour from './signupTour';

export default function (Alpine) {
    window.Events = window.Events || {};

    window.Events.signupTour = signupTour;

    window.Events.Search = window.Events.Search || {};

    window.Events.Search.mapSelector = mapSelector;
    window.Events.Search.shiftsView = shiftsView;
}
