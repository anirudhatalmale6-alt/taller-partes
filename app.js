// Minimal JS - most logic is server-side
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 3 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 3000);
    });
});
