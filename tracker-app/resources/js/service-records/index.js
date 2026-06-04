import costumeSearch from './costumeSearch';

export default function (Alpine) {
    window.ServiceRecords = window.ServiceRecords || {};
    window.ServiceRecords.costumeSearch = costumeSearch;
}
