import './bootstrap';

import Inputmask from "inputmask";

// Initialize input mask for price fields
document.addEventListener('DOMContentLoaded', () => {
    const priceFields = document.querySelectorAll('.price-input');
    Inputmask({ alias: "numeric", groupSeparator: ",", autoGroup: true }).mask(priceFields);
});