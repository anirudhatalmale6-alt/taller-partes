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
function setupAutocomplete(inputId, listId, url, onSelect) {
    var input = document.getElementById(inputId);
    var list = document.getElementById(listId);
    if (!input || !list) return;

    var debounce = null;
    input.addEventListener('input', function() {
        clearTimeout(debounce);
        var q = this.value.trim();
        if (q.length < 2) { list.innerHTML = ''; list.style.display = 'none'; return; }
        debounce = setTimeout(function() {
            fetch(url + '?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    list.innerHTML = '';
                    if (data.length === 0) { list.style.display = 'none'; return; }
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
        }, 250);
    });

    // Hide on click outside
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !list.contains(e.target)) {
            list.innerHTML = '';
            list.style.display = 'none';
        }
    });
}
