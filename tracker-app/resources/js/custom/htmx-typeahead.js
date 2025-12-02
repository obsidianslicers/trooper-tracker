/** BIND TYPEAHEADS **/
document.addEventListener('DOMContentLoaded', () => {
    function bindTypeaheadInputs() {
        document.querySelectorAll('.typeahead').forEach(input => {
            // Prevent rebinding if already initialized
            if (input.dataset.typeaheadBound === 'true') return;
            input.dataset.typeaheadBound = 'true';

            const searchUrl = input.dataset.searchUrl;
            const targetId = input.id;

            const costumes = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: {
                    url: searchUrl + '?query=%QUERY',
                    wildcard: '%QUERY'
                }
            });

            $(input).typeahead({
                minLength: 2,
                highlight: true
            }, {
                name: 'costumes',
                display: 'name',
                source: costumes,
                templates: {
                    suggestion: function (data) {
                        return `<div class="dropdown-item d-flex align-items-center p-2">
                      <i class="bi bi-person-badge me-2 text-secondary"></i>
                      <span class="text-truncate">${data.name}</span>
                    </div>`;
                    }
                }
            }).on('typeahead:select', function (e, selection) {
                document.getElementById(targetId).value = selection.id;
                input.form.dispatchEvent(new Event('submit', { bubbles: true }));
            });
        });
    }

    bindTypeaheadInputs();
    document.body.addEventListener('htmx:afterSettle', bindTypeaheadInputs);
});