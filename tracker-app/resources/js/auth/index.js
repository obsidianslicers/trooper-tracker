import organizationSelector from './register/organizationSelector';

export default function (Alpine) {
    window.Auth = window.Auth || {};
    window.Auth.Register = window.Auth.Register || {};
    window.Auth.Register.organizationSelector = organizationSelector;
}
