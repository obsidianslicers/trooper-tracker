import notificationSelector from './profile/notificationSelector';

export default function (Alpine) {
    window.Account = window.Account || {};
    window.Account.Profile = window.Account.Profile || {};
    window.Account.Profile.notificationSelector = notificationSelector;
}
