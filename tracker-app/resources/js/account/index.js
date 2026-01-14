import costumeSelector from './costumeSelector';
import notificationSelector from './notificationSelector';

export default function (Alpine) {
    window.Account = window.Account || {};

    window.Account.Profile = window.Account.Profile || {};
    window.Account.Profile.notificationSelector = notificationSelector;

    window.Account.Costumes = window.Account.Costumes || {};
    window.Account.Costumes.costumeSelector = costumeSelector;
}
