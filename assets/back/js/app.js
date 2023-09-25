// IMPORTS
import Alpine from "alpinejs";
window.Alpine = Alpine;
import { sfSelectDetail, sfPercents } from "./../../front/js/plugins";

import axios from "axios";
axios.defaults.headers = {
    "X-Requested-With": "XMLHttpRequest",
};

// ALPINE AND JS DROPDOWNS
Alpine.data("dropdown", (defaultState = false) => ({
    open: defaultState,

    toggle() {
        this.open = !this.open;
    },
}));

// accordeons
document.querySelectorAll('.accordion-control').forEach(($accordion) => {
    $accordion.addEventListener("click", function (evt) {
        $accordion.classList.toggle('open');
        document.getElementById($accordion.dataset.container).classList.toggle('open');
    }); 
});
document.querySelectorAll('[data-percentage]').forEach(($section) => {
    sfPercents.init({
        elementParent: $section,
        selector: 'input[name]'
    });                            
});                
document.querySelectorAll('[data-select-detail]').forEach(($section) => {
    sfSelectDetail.init({
        element: $section
    });
});

Alpine.start();