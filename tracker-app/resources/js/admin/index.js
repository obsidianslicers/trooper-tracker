import eventCreation from './eventCreation';

export default function (Alpine) {
    window.Admin = window.Admin || {};

    window.Admin.Events = window.Admin.Events || {};
    window.Admin.Events.eventCreation = eventCreation;
}
