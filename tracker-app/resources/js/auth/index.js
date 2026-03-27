import organizationSelector from './organizationSelector';
import registration from './registration';

export default function (Alpine) {
    window.Auth = window.Auth || {};
    window.Auth.Register = window.Auth.Register || {};
    window.Auth.Register.organizationSelector = organizationSelector;
    window.Auth.Register.registration = registration;
}
