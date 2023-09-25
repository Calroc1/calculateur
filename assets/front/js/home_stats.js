// IMPORTS
import Alpine from "alpinejs";
window.Alpine = Alpine;

import axios from "axios";
import { stats as statsFunctions } from "./stats";

import Chart from "chart.js/auto";
import { getStepByName, getStepColorRGBA } from "./utils";
axios.defaults.headers = { "X-Requested-With": "XMLHttpRequest" };

let homeStats = {
  organisms: window.organisms,
  availableKeywords: {},
  countries: {},
  dateStart: null,
  dateEnd: null,
  filter: {
    organism: null,
    keyword: {
      label: null,
      word: null,
    },
    country: null,
    dateStart: null,
    dateEnd: null,
  },

  allSteps: window.allSteps,
  statsTotals: null,
  statsFunctions: statsFunctions,
  hideFilters: [],
  isLoading: false,
  barChart: null,

  init() {
    //INIT DATES AS FIRST AND LAST OF CURRENT YEAR
    const dateStart = this.convertDate(
      new Date(new Date().getFullYear(), 0, 1)
    );
    this.dateStart = dateStart;
    this.filter.dateStart = dateStart;
    const dateEnd = this.convertDate(
      new Date(new Date().getFullYear(), 11, 31)
    );
    this.dateEnd = dateEnd;
    this.filter.dateEnd = dateEnd;

    this.organisms.forEach((organism) => {
      this.updateProperty(organism, "availableKeywords");
      this.updateProperty(organism, "countries");
    });

    this.getStatistics();
  },

  convertDate(date) {
    let year = date.getFullYear();
    let month =
      date.getMonth() + 1 < 10
        ? "0" + (date.getMonth() + 1)
        : date.getMonth() + 1;
    let day = date.getDate() < 10 ? "0" + date.getDate() : date.getDate();

    return year + "-" + month + "-" + day;
  },

  updateProperty(object, property) {
    function sortObjectByValues(o) {
      const reducer = (r, k) => ((r[k] = o[k]), r);
      return Object.keys(o)
        .sort((a, b) => o[a].localeCompare(o[b]))
        .reduce(reducer, {});
    }

    const objectProperty = object[property];
    if (objectProperty) {
      let merged = { ...this[property], ...objectProperty };
      if (property === "countries") merged = sortObjectByValues(merged);
      this[property] = merged;
    }
  },

  changeFilter(target) {
    const value = target.value;
    const filter = target.getAttribute("id");

    if (value === "all") {
      this.filter[filter] = null;
    } else {
      //SPECIFIC CASE FOR KEYWORD CONSTRUCT OBJECT FOR AJAX
      if (filter === "keyword") {
        const index = document.querySelector("select#keyword").selectedIndex;
        const optionSelected =
          document.querySelector("select#keyword").options[index];
        this.filter[filter] = {
          label: optionSelected.dataset.label,
          word: optionSelected.value,
        };
      } else {
        //SPECIFIC CASE FOR DATES
        if (filter === "dateStart" || filter === "dateEnd") {
          if (this.filter[filter] === value) return;
        }
        this.filter[filter] = value;
      }
    }
    //SPECIFIC CASES
    // if(filter === 'organism'){
    //     this.filter.campaign = null;
    //     document.querySelector('select#campaign').selectedIndex = 0;
    // }

    this.getStatistics();
  },

  getStatistics() {
    this.isLoading = true;
    axios
      .get(Routing.generate("front_dashboard_statistics", this.filter))
      .then((response) => {
        // Emit stats to stats and barChart functions
        const barChartElement = document.getElementById("dashboard-bar-graph");
        const statsElement = document.getElementById("stats");
        let event = new CustomEvent("loaded", {
          detail: {
            stats: response.data,
            allSteps: this.allSteps,
          },
        });
        barChartElement.dispatchEvent(event);
        statsElement.dispatchEvent(event);
      })
      .catch((error) => console.log(error))
      .finally(() => (this.isLoading = false));
  },
  displayBarGraph($event) {
    if (this.barChart) {
      this.barChart.destroy();
      this.barChart = null;
    }
    const barChartElement = $event.target;
    const stats = $event.detail.stats;

    if (barChartElement) {
      let labels = [];
      let values = [];
      let colorsGraph = [];

      for (const property in stats) {
        let subTotal = statsFunctions.toTons(
          statsFunctions.getTotal(stats[property])
        );
        const currentStep = getStepByName(property);
        labels.push(currentStep.label);
        colorsGraph.push(getStepColorRGBA(currentStep));
        values.push(subTotal);
      }

      const configBarChart = {
        type: "bar",
        data: {
          labels: labels,
          datasets: [
            {
              label: "Tonnes équivalent CO₂",
              data: values,
              backgroundColor: colorsGraph,
              borderColor: colorsGraph,
            },
          ],
        },
        options: {
          plugins: {
            legend: {
              display: false,
            },
          },
          maintainAspectRatio: true,
          responsive: true,
        },
      };
      this.barChart = new Chart(barChartElement, configBarChart);
    }
  },
};

export { homeStats };
