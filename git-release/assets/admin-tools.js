(function () {
    if (!document.body.classList.contains('page-admin')) {
        return;
    }

    if (window.location.pathname !== '/admin/plugins/git-release/settings') {
        return;
    }

    var settingsForm = document.querySelector('form[action="/admin/plugins/git-release/settings"]');
    if (!settingsForm) {
        return;
    }

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
        return;
    }

    var section = document.createElement('section');
    section.className = 'form-section';
    section.setAttribute('aria-labelledby', 'git-release-cache-heading');

    var heading = document.createElement('h2');
    heading.id = 'git-release-cache-heading';
    heading.textContent = 'Release cache';
    section.appendChild(heading);

    var description = document.createElement('p');
    description.className = 'muted';
    description.textContent = 'Server-side GitHub API responses are stored under storage/plugins/git-release/cache/. Purge to force a fresh fetch on the next home page visit.';
    section.appendChild(description);

    var purgeForm = document.createElement('form');
    purgeForm.method = 'post';
    purgeForm.action = '/admin/plugins/git-release/purge-cache';
    purgeForm.className = 'form-inline';

    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_csrf';
    csrfInput.value = csrfMeta.getAttribute('content') || '';
    purgeForm.appendChild(csrfInput);

    var button = document.createElement('button');
    button.type = 'submit';
    button.className = 'btn btn-secondary';
    button.textContent = 'Purge release cache';
    purgeForm.appendChild(button);

    section.appendChild(purgeForm);
    settingsForm.insertAdjacentElement('afterend', section);
}());