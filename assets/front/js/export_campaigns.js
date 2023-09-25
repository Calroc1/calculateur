// IMPORTS
import Alpine from "alpinejs";

window.Alpine = Alpine;

import axios from "axios";

axios.defaults.headers = {"X-Requested-With": "XMLHttpRequest"};

let exportCampaigns = {
    statuses: window.statuses,
    organisms: window.organisms,
    availableKeywords: {},
    countries: {},
    dateStart: null,
    dateEnd: null,
    campaigns: [],

    filter: {
        organism: null,
        campaigns: [],
        status: null,
        keyword: {
            label: null,
            word: null
        },
        country: null,
        dateStart: null,
        dateEnd: null,
    },

    campaignInput: '',
    cumulativeTotal: 0,
    openCampaigns: false,
    isLoading: false,

    init() {
        this.filter.campaigns = window.campaignsURL ? window.campaignsURL : [];

        //INIT DATES AS FIRST AND LAST OF CURRENT YEAR
        const dateStart = this.convertDate(new Date(new Date().getFullYear(), 0, 1));
        this.dateStart = dateStart;
        this.filter.dateStart = dateStart;

        const dateEnd = this.convertDate(new Date(new Date().getFullYear(), 11, 31));
        this.dateEnd = dateEnd;
        this.filter.dateEnd = dateEnd;

        this.updateCampaigns();

        this.campaigns.forEach((campaign) => {
            this.updateProperty(campaign.organism, 'countries');
        });

        this.organisms.forEach((organism) => {
            this.updateProperty(organism, 'countries');
        });
    },

    updateCampaigns() {
        this.isLoading = true;

        axios.get(Routing.generate("front_statistic_filter_campaigns", this.filter))
            .then((response) => {
                this.campaigns = response.data;
                this.filter.campaigns = [];
            }).catch((error) => console.log(error))
            .finally(() => this.isLoading = false);
    },

    convertDate(date) {
        let year = date.getFullYear();
        let month = date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1;
        let day = date.getDate() < 10 ? '0' + date.getDate() : date.getDate();

        return year + '-' + month + '-' + day;
    },

    updateProperty(object, property) {
        function sortObjectByValues(o) {
            const reducer = (r, k) => (r[k] = o[k], r);

            return Object.keys(o).sort((a, b) => o[a].localeCompare(o[b])).reduce(reducer, {});
        }

        const objectProperty = object[property];

        if (objectProperty) {
            let merged = {...this[property], ...objectProperty};

            if (property === "countries") {
                merged = sortObjectByValues(merged);
            }

            this[property] = merged;
        }
    },

    changeFilter(target) {
        const value = target.value;
        const filter = target.getAttribute('id');

        if (value === "all") {
            this.filter[filter] = null;
        } else {
            //SPECIFIC CASE FOR KEYWORD CONSTRUCT OBJECT FOR AJAX
            if (filter === "keyword") {
                const index = document.querySelector('select#keyword').selectedIndex;
                const optionSelected = document.querySelector('select#keyword').options[index];

                this.filter[filter] = {
                    label: optionSelected.dataset.label,
                    word: optionSelected.value
                }
            } else {
                this.filter[filter] = value;
            }
        }

        this.updateCampaigns();
    },

    changeCampaign($event, multiselect) {
        const checked = $event.target.checked;
        const value = $event.target.value;


        if (checked) {
            if (value === "all-campaigns") {
                this.filter.campaigns = [];

                this.campaigns.forEach((campaign) => {
                    this.filter.campaigns.push(campaign.id.toString());
                });
            } else {
                this.filter.campaigns.push(value);
            }
        } else {
            if (multiselect) {
                if (value === "all-campaigns") {
                    this.filter.campaigns = [];
                } else {
                    const campaignFound = this.filter.campaigns.indexOf(value);

                    if (campaignFound > -1) {
                        this.filter.campaigns.splice(campaignFound, 1);
                    }
                }
            } else {
                this.filter.campaigns = [value];
            }
        }
    },

    onClick() {
        if (this.campaigns.length > 0) {
            this.openCampaigns = true;
        }
    },
};

export {exportCampaigns};