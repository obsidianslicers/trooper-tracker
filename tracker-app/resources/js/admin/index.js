import cardNavigator from './cardNavigator';
import eventCreation from './eventCreation';
import eventOrganizationAttendance from './eventOrganizationAttendance';

export default function (Alpine) {
    window.Admin = window.Admin || {};

    window.Admin.Events = window.Admin.Events || {};
    window.Admin.Events.eventCreation = eventCreation;
    window.Admin.Events.eventOrganizationAttendance = eventOrganizationAttendance;

    window.Admin.cardNavigator = cardNavigator;
}