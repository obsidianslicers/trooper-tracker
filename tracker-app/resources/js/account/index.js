import costumeSelector from './costumes/costumeSelector';
import notificationSelector from './profile/notificationSelector';

export default function (Alpine) {
    window.Account = window.Account || {};

    window.Account.Profile = window.Account.Profile || {};
    window.Account.Profile.notificationSelector = notificationSelector;

    window.Account.Costumes = window.Account.Costumes || {};
    window.Account.Costumes.costumeSelector = costumeSelector;
}
