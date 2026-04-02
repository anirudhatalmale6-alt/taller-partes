document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 3000);
    });

    // Sidebar toggle (mobile)
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        // Create overlay
        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
});

// Autocomplete helper
function setupAutocomplete(inputId, listId, url, onSelect, options) {
    var input = document.getElementById(inputId);
    var list = document.getElementById(listId);
    if (!input || !list) return;

    var minChars = (options && options.minChars) || 3;
    var debounce = null;

    input.addEventListener('input', function() {
        clearTimeout(debounce);
        var q = this.value.trim();
        if (q.length < minChars) { list.innerHTML = ''; list.style.display = 'none'; return; }
        debounce = setTimeout(function() {
            var separator = url.indexOf('?') !== -1 ? '&' : '?';
            var fetchUrl = url + separator + 'q=' + encodeURIComponent(q);
            // Append client_id if available (for vehicle filtering)
            if (options && options.getClienteId) {
                var cid = options.getClienteId();
                if (cid) fetchUrl += '&cliente_id=' + cid;
            }
            fetch(fetchUrl)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    list.innerHTML = '';
                    if (data.length === 0) {
                        list.innerHTML = '<div class="autocomplete-item text-muted"><small>Sin resultados</small></div>';
                        list.style.display = 'block';
                        return;
                    }
                    data.forEach(function(item) {
                        var div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = item.label;
                        div.addEventListener('click', function() {
                            onSelect(item);
                            list.innerHTML = '';
                            list.style.display = 'none';
                        });
                        list.appendChild(div);
                    });
                    list.style.display = 'block';
                });
        }, 200);
    });

    // Also trigger on focus if already has enough text
    input.addEventListener('focus', function() {
        var q = this.value.trim();
        if (q.length >= minChars) {
            input.dispatchEvent(new Event('input'));
        }
    });

    // Hide on click outside
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !list.contains(e.target)) {
            list.innerHTML = '';
            list.style.display = 'none';
        }
    });
}
