import costumeSelector from './costumeSelector';
import notificationSelector from './notificationSelector';
import { organizationSelector, registration } from './deniedResubmit';

export default function (Alpine) {
    window.Account = window.Account || {};

    window.Account.Profile = window.Account.Profile || {};
    window.Account.Profile.notificationSelector = notificationSelector;

    window.Account.Costumes = window.Account.Costumes || {};
    window.Account.Costumes.costumeSelector = costumeSelector;

    window.Auth = window.Auth || {};
    window.Auth.Register = { registration, organizationSelector };
}
