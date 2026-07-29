// Fonction principale qui met à jour les champs "Rue", "Code postal" et "Lat/Lng"
function updateLieuDetails() {
    const lieuSelect = document.querySelector('select[name$="[lieu]"]');
    if (!lieuSelect) return;

    const rueInput = document.getElementById('lieu-rue');
    const cpInput = document.getElementById('lieu-code-postal');
    const latLngInput = document.getElementById('lieu-lat-lng');

    if (!rueInput || !cpInput || !latLngInput) return;

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

// 1. Écoute globale des changements (Délégation d'événement)
// Fonctionne toujours, même si le formulaire est remplacé par Turbo !
document.addEventListener('change', function (event) {
    if (event.target && event.target.matches('select[name$="[lieu]"]')) {
        updateLieuDetails();
    }
});

// 2. Exécution au chargement classique ET après chaque mise à jour Turbo
document.addEventListener('DOMContentLoaded', updateLieuDetails);
document.addEventListener('turbo:load', updateLieuDetails);
document.addEventListener('turbo:render', updateLieuDetails);
