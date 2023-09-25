import axios from "axios";
axios.defaults.headers = { "X-Requested-With": "XMLHttpRequest" };

import Chart from "chart.js/auto";

import resolveConfig from "tailwindcss/resolveConfig";
import tailwindConfig from "../../../tailwind.config";
import { getStepByName, getStepColorRGBA } from "./utils";
const fullConfig = resolveConfig(tailwindConfig);
const colors = fullConfig.theme.colors;

let stats = {
  stats: {},
  allSteps: null,

  campaignTotal: 0,
  charts: [],

  setup({ stats, allSteps }) {
    this.stats = stats;
    this.allSteps = allSteps;

    //TOTAL
    let arrayStats = [];
    for (const property in stats) {
      arrayStats.push(this.getTotal(stats[property]));
    }
    this.campaignTotal = this.getTotal(arrayStats);
  },

  getTotal(stats) {
    if (stats) {
      let values = Object.values(stats);
      let total = 0;
      for (var i = 0; i < values.length; i++) {
        total += values[i];
      }
      return total;
    }
    return 0;
  },

  getStatWidth(stat, total) {
    return `${Math.round((stat * 100) / total)}px`;
  },

  drawGraph(stepName) {
    const canvasElement = document
      .getElementsByClassName("stat__" + stepName)[0]
      .getElementsByClassName("stat-details")[0];
    if (canvasElement) {
      setTimeout(() => {
        let chartInArray = this.charts.find((el) => el.name === stepName);

        const currentStep = getStepByName(stepName);
        const data = Object.values(this.stepStats);
        const labels = Object.keys(this.stepStats);
        const backgroundColor = [
          ...data.map((_, index) => {
            return getStepColorRGBA(
              currentStep,
              ((100 / data.length) * (data.length - index)) / 100
            );
          }),
        ];

        let configStatDetails = {
          type: "doughnut",
          data: {
            labels,
            datasets: [
              {
                label: "Dataset",
                data,
                backgroundColor,
                hoverOffset: 3,
              },
            ],
          },
          options: {
            rotation: 180,
            plugins: {
              legend: {
                display: false,
              },
            },
            responsive: true,
          },
        };
        //CASE NO DATA
        if (this.getTotal(this.stepStats) === 0) {
          canvasElement.style.display = "none";
        }
        if (chartInArray) {
          chartInArray.chart.destroy();
          chartInArray.chart = new Chart(canvasElement, configStatDetails);
        } else {
          this.charts.push({
            name: stepName,
            chart: new Chart(canvasElement, configStatDetails),
          });
        }
      }, 100);
    }
  },

  toTons(kgvalue) {
    // on arrondi seulement si la valeur fait plus de 1t, sinon affiche le resultat à 2 decimales
    let tons = kgvalue / 1000;
    return tons > 1
      ? Math.round(tons)
      : +(Math.round(kgvalue / 1000 + "e+2") + "e-2");
  },

  getStepProp(test, prop) {
    // let step = Array.from(virtualSteps).find((step) =>
    //     test.includes(step.name)
    // );
    // if (step) {
    //     return step[prop];
    // } else {
    //     step = Array.from(allSteps).find((step) => step.name === test);
    //     return step[prop];
    // }
    let step = Array.from(this.allSteps).find((step) => step.name === test);
    return step[prop];
  },
};

export { stats };
