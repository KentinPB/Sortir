document.addEventListener('DOMContentLoaded', function () {
    const lieuSelect = document.querySelector('select[name$="[lieu]"]');
    const rueInput = document.getElementById('lieu-rue');
    const cpInput = document.getElementById('lieu-code-postal');
    const latLngInput = document.getElementById('lieu-lat-lng');

    if (!lieuSelect) return;

    function updateLieuDetails() {
        const selectedOption = lieuSelect.options[lieuSelect.selectedIndex];

        if (selectedOption && selectedOption.value) {
            rueInput.value = selectedOption.getAttribute('data-rue') || '';
            cpInput.value = selectedOption.getAttribute('data-code-postal') || '';

            const lat = selectedOption.getAttribute('data-latitude') || '';
            const lng = selectedOption.getAttribute('data-longitude') || '';

            latLngInput.value = (lat && lng) ? `${lat} / ${lng}` : '';
        } else {
            rueInput.value = '';
            cpInput.value = '';
            latLngInput.value = '';
        }
    }

    // Écoute du changement
    lieuSelect.addEventListener('change', updateLieuDetails);

    // Exécution au chargement (utile en mode modification si un lieu est déjà sélectionné en base !)
    updateLieuDetails();
});
