import cardNavigator from './cardNavigator';

export default function (Alpine) {
    window.Admin = window.Admin || {};

    window.Admin.cardNavigator = cardNavigator;
}