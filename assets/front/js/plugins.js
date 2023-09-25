// PLUGIN COLLECTIONS
const sfCollections = (() => {
    const init = (config = {}) => {
        const {
            selector = "form div[data-prototype]",
            entryCallback = ($entry) => {},
        } = config;
        const $collections = document.querySelectorAll(selector);
        if (!$collections.length) {
            return;
        }
        $collections.forEach(($collection) => {
            $collection.setAttribute(
                "data-entry-index",
                ($collection.children.length - 1)
            );
            const mainLabel = $collection.getAttribute("data-entry-label") || "";
            const processEntry = ($entry, index, $collection, newEntry) => {
                let entryLabel = mainLabel + " " + (parseInt(index) + 1);
                if($collection.dataset.renamable == 1){
                    if(newEntry){
                        $entry.querySelector('[id$="_name"]').value = entryLabel;
                    } else {
                        entryLabel = $entry.querySelector('[id$="_name"]').value;
                    }
                }
                $entry.querySelector(".entry-label").innerHTML = entryLabel;               
                entryCallback($entry, $collection);
            };
            const addEntry = () => {
                const entryIndex = $collection.getAttribute("data-entry-index");
                $collection.setAttribute("data-entry-index", +entryIndex + 1);
                const entryPrototype = $collection.getAttribute("data-prototype");
                const $entry = getEntry(entryPrototype, entryIndex);
                processEntry($entry, entryIndex, $collection, true);
                $collection.insertBefore($entry, $collection.querySelector('[data-entry-action="add"]'));
                $collection.dispatchEvent(new CustomEvent('change'));
            };
            const removeEntry = ($entry) => {
                $entry.remove();
                $collection.dispatchEvent(new CustomEvent('change'));
            };
            const toggleRenameEntry = ($entry) => {
                let $modal =  $entry.querySelector('.modal-rename');
                $modal.classList.toggle("hidden");
            };
            const renameEntry = ($entry) => {
                $entry.querySelector('.entry-label').innerHTML = $entry.querySelector('[id$="_name"]').value;
                $entry.querySelector('.modal-rename').classList.add("hidden");
                $collection.dispatchEvent(new CustomEvent('change'));
            };
            const $entries = [...$collection.children]
                .filter(($entry) => $entry.hasAttribute("data-entry"))
                //.filter((entryElt) => !entryElt.hasAttribute("data-entry-action"))
                .forEach(($entry, index) => {
                    processEntry($entry, index, $collection, false);
                });
            $collection.addEventListener("click", function (e) {  
                if (e.target.closest('[data-entry-action="add"]')) {
                    addEntry();
                }
                else if (e.target.closest('[data-entry-action="remove"]')) {
                    removeEntry(e.target.closest('[data-entry]'));
                }
                else if (e.target.closest('[data-entry-action="toggle-rename"]')) {
                    toggleRenameEntry(e.target.closest('[data-entry]'));
                }
                else if (e.target.closest('[data-entry-action="rename"]')) {
                    renameEntry(e.target.closest('[data-entry]'));
                }
            });
        });
    };
    const getEntry = (entryPrototype, entryIndex) => {
        const entryHtml = entryPrototype.replace(/__name__/g, entryIndex);
        var $div = document.createElement("div");
        $div.innerHTML = entryHtml.trim();
        return $div.firstChild;
    };
    return { init };
})();

// PLUGIN PERCENTAGES
const sfPercents = (() => {
    const init = (config = {}) => {
        const { 
            elementParent = null, 
            selectorParent = null, 
            selector = null
        } = config;
        let $parent = document;        
        if(selectorParent != null){            
            $parent = document.querySelector(selectorParent);            
            if (!$parent)
                return;
        }
        else if(elementParent != null)      
            $parent = elementParent;
        if($parent.hasAttribute('data-percentage-init'))
            return;
        $parent.setAttribute('data-percentage-init', 1);
        const $fields = $parent.querySelectorAll(selector);
        let $percents = [];
        $fields.forEach(($field) => {
            let $div = document.createElement("div");
            $div.className =
                "flex flex-row flex-nowrap justify-start items-center ";
            let $input = document.createElement("input");
            $input.type = "text";
            $input.className =
                "block bg-white-dark rounded-lg text-xs font-light px-5 h-9 disabled:opacity-30 readonly:opacity-30 w-20";
            $input.disabled = true;
            let $unit = document.createElement("span");
            $unit.innerHTML = "%";
            $unit.className = "text-primary text-xs ml-2";

            $div.append($input);
            $div.append($unit);
            $field.parentElement.parentElement.appendChild($div);
            $percents.push($input);

            $field.addEventListener("change", function (evt) {
                update($fields, $percents);
            });
        });
        update($fields, $percents);
    };
    const update = ($fields, $percents) => {
        let total = 0;
        let values = [];
        $fields.forEach(($field, index) => {
            let val = inputValueToInteger($field.value);
            total += parseInt(val);
            values.push(val);
        });
        $fields.forEach(($field, index) => {
            let percent = 0;
            if (total > 0) percent = ((values[index] * 100) / total).toFixed(1);
            $percents[index].value = percent;
        });
    };
    return { init };
})();

// Select with detail
const sfSelectDetail = (() => {
    const init = (config = {}) => {
        const { 
            element = null,
            selector = null
        } = config;
        let $element = element;        
        if(selector != null){            
            $element = document.querySelector(selector);            
            if (!$element)
                return;
        }
        if($element.hasAttribute('data-select-detail-init'))
            return;
        $element.setAttribute('data-select-detail-init', 1);
        let $select = $element.querySelector('[name$="[choice]"]');
        let $input = $element.querySelector('[name$="[detail]"]');
        if($input && $select){              
            $select.addEventListener("change", function (evt) {
                update($select, $input);
            });
            update($select, $input);
        }
    };
    const update = ($select, $input) => {
        let $option = $select.options[$select.selectedIndex];
        let selectValue = $option.dataset.value;   
        let triggered = $option.text == $select.dataset.triggerDetail;    
        $input.readOnly = triggered == false;
        if(triggered == false){
            $input.value = selectValue;
            $input.dispatchEvent(new Event('change'));
        }
    };
    return { init };
})();

// action formulaire
const sfFormActions = (() => {
    const init = ($form, stepName) => {  
        // accordeons
        $form.querySelectorAll('.accordion-control').forEach(($accordion) => {
            $accordion.addEventListener("click", function (evt) {
                $accordion.classList.toggle('open');
                document.getElementById($accordion.dataset.container).classList.toggle('open');
            }); 
        });
        switch (stepName) {
            case 'informations':   
                checkAll($form.querySelector('input#check-all-steps'), $form.querySelectorAll('[name$="campaign[steps][]"]'));
                checkAll($form.querySelector('input#check-all-referentials'), $form.querySelectorAll('[name$="campaign[referentials][]"]'));
                
                if ($form.querySelector('#campaign_budget').checked) {
                    $form.querySelector('#campaign_budget').removeAttribute('disabled');
                } else {
                    $form.querySelector('#campaign_budget').setAttribute('disabled', 'disabled');
                    $form.querySelector('#campaign_budget').value = "";
                }

                $form.querySelectorAll('#campaign_notionBudget input').forEach(($radio) => {
                    $radio.addEventListener("change", function (evt) {
                        if (this.value === "1") {
                            $form.querySelector('#campaign_budget').removeAttribute('disabled');
                        } else {
                            $form.querySelector('#campaign_budget').setAttribute('disabled', 'disabled');
                            $form.querySelector('#campaign_budget').value = "";
                        }
                    });
                });
                break;
            case 'campagne_affichage':
                // -- shooting + conception + supports
                sfCollections.init({
                    selector : '#campagne_affichage_shooting, #campagne_affichage_conception, #campagne_affichage_supports div[data-prototype]',
                    entryCallback : ($entry) => {
                        $entry.querySelectorAll('[data-percentage]').forEach(($section) => {
                            sfPercents.init({
                                elementParent: $section,
                                selector: 'input[name]'
                            });                            
                        });
                        $entry.querySelectorAll('[data-select-detail]').forEach(($section) => {
                            sfSelectDetail.init({
                                element: $section
                            });
                        });
                    }
                });
                //-- impression
                sfCollections.init({
                    selector : '#campagne_affichage_impression', 
                    entryCallback : ($entry, $collection) => {                        
                        enableImpression($entry, $collection);
                        $entry.querySelectorAll('[data-select-detail]').forEach(($section) => {
                            sfSelectDetail.init({
                                element: $section
                            });
                        });
                    }
                });  
                break;
            case 'supports_papier':
                // -- shooting + conception + distribution
                sfCollections.init({
                    selector : '#supports_papier_shooting, #supports_papier_conception, #supports_papier_distribution',
                    entryCallback : ($entry) => {
                        $entry.querySelectorAll('[data-select-detail]').forEach(($section) => {
                            sfSelectDetail.init({
                                element: $section
                            });
                        });
                        $entry.querySelectorAll('[data-percentage]').forEach(($section) => {
                            sfPercents.init({
                                elementParent: $section,
                                selector: 'input[name]'
                            });                            
                        });
                    }
                });
                //-- impression
                sfCollections.init({
                    selector : '#supports_papier_impression', 
                    entryCallback : ($entry, $collection) => {
                        $entry.querySelectorAll('[data-select-detail]').forEach(($section) => {
                            sfSelectDetail.init({
                                element: $section
                            });
                        });
                        enableImpression($entry, $collection);
                    }
                });
                break;
            default:
                sfCollections.init({
                    selector: "[data-collection]",
                    entryCallback : ($entry) => {
                        $entry.querySelectorAll('[data-percentage]').forEach(($section) => {
                            sfPercents.init({
                                elementParent: $section,
                                selector: 'input[name]'
                            });                            
                        });
                        $entry.querySelectorAll('[data-select-detail]').forEach(($section) => {
                            sfSelectDetail.init({
                                element: $section
                            })
                        });                        
                    }
                });
                $form.querySelectorAll('[data-percentage]').forEach(($section) => {
                    sfPercents.init({
                        elementParent: $section,
                        selector: 'input[name]'
                    });                            
                });                
                $form.querySelectorAll('[data-select-detail]').forEach(($section) => {
                    sfSelectDetail.init({
                        element: $section
                    });
                });  
                break;
        }   
    };
    const enableImpression = ($entry, $collection) => {
        $entry.querySelectorAll([
            '[id$="_poids_papier"] input',
            'input[id$="_distance_field"]'
        ].join()).forEach($element => {
            $element.addEventListener("change", function (evt) {
                updateImpression();
            });
        });
        $entry.querySelectorAll([
            '[id$="_surface_encre_quantite_page_field"]'
        ].join()).forEach($element => {
            $element.addEventListener("select_change", function (evt) {
                updateImpression();
            });
        });
        var updateImpression = () => {
            let subtotal = 1;
            $entry.querySelectorAll('[id$="_poids_papier"] input').forEach(($input) => {
                subtotal *= getFieldValue($input);
            });
            let totalPoidsPapier = subtotal / 1000000;
            $entry.querySelector('[id$="_total_field"]').value = totalPoidsPapier;
                      
            /*let totalSurfaceEncre = Math.round(totalPoidsPapier * getFieldValue($entry.querySelector('[id$="_surface_encre_quantite_page_field"]')));
            $entry.querySelector('[id$="_surface_encre_quantite_total_field"]').value = totalSurfaceEncre;*/
            let totalSurfaceEncre = 0;
            let totalAR = Math.ceil((totalPoidsPapier + totalSurfaceEncre/1000)/15);
            $entry.querySelectorAll('[id$="_papetier_imprimeur"], [id$="_imprimeur_afficheur"]').forEach(($div) => {
                let distance = getFieldValue($div.querySelector('[name$="[distance][field]"]'));
                $div.querySelector('[name$="[ar][field]"]').value = totalAR;
                $div.querySelector('[name$="[distance_camion][field]"]').value = distance * totalAR * 2;
            });
            let totalFinDeVie = 0;
            let $entries = $collection.querySelectorAll('div[data-entry]');
            if($entries){
                $entries.forEach(($e) => {
                    totalFinDeVie += getFieldValue($e.querySelector('[id$="_total_field"]'));
                });
            }
            document.querySelector('[id$="_fin_de_vie_poids_media_field"]').value = totalFinDeVie; 
        };        
        updateImpression();
    };
    return { init };
})();

const checkAll = (($master, $children) => {
    const doCheckAll = (($master, $children) => {
        let checked = true;
        $children.forEach(($input) => {
            if($input.checked == false){
                checked = false;
                return;
            }
        });
        $master.checked = checked;
    });
    doCheckAll($master, $children);
    $master.addEventListener("change", function (evt) {
        $children.forEach(($input) => {
            $input.checked = evt.target.checked;
        });
    });
    $children.forEach(($input) => {
        $input.addEventListener("change", function (evt) {
            doCheckAll($master, $children);
        });
    });    
    return { doCheckAll };
});

const getFieldValue = ($field) => {
    switch ($field.nodeName) {
        /*case 'SELECT':   
            return inputValueToInteger($field.getAttribute("data-value"));*/
        default:
            return inputValueToInteger($field.value);
    }
};
const inputValueToInteger = (value) => {
    let val = parseFloat(value.replace(',', '.'));
    if (Number.isNaN(val) == false) return val;
    return 0;
};
const multiply = (array) => {
    var sum = 1;
    for (var i = 0; i < array.length; i++) sum = sum * array[i];
    return sum;
};
const selectHasValue = ($select, value) => {
    return $select.innerHTML.indexOf('value="' + value + '"') > -1;
};

export { sfFormActions, sfPercents, sfSelectDetail }