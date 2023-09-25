// IMPORTS
import Alpine from "alpinejs";

window.Alpine = Alpine;

import axios from "axios";

axios.defaults.headers = { "X-Requested-With": "XMLHttpRequest" };

import Litepicker from "litepicker";

// CUSTOM ALPINE IMPORTS
import { form } from "./form";
import { stats } from "./stats";
import { homeStats } from "./home_stats";
import { filtersCampaigns } from "./filters_campaigns";
import { exportCampaigns } from "./export_campaigns";
import { listComponent } from "./list";
import { modal } from "./modal";
import { styleNames, getStepColorRGBA, getStepByName } from "./utils";

// setup css
window.styleNames = styleNames;
if (window.allSteps) {
  const allStepsWithColor = window.allSteps.filter((step) => step.color);
  const style = document.createElement("style");
  style.type = "text/css";
  style.innerHTML = `
    body {
      ${allStepsWithColor
        .map(({ name, color }) => `--${name}: ${color};`)
        .join("")}
    }
    
    ${allStepsWithColor
      .map(
        ({ name }) => `
        .stat__${name} {
          --currentColor: var(--${name});
          color: rgb(var(--currentColor));
        }
        `
      )
      .join("")}
  `;
  document.getElementsByTagName("head")[0].appendChild(style);
}

// ALPINE AND JS DROPDOWNS
Alpine.data("dropdown", (defaultState = false) => ({
  open: defaultState,

  toggle() {
    this.open = !this.open;
  },
}));
document.querySelectorAll(".dropdown").forEach((element, index) => {
  if (index === 0) element.classList.toggle("open");
  element.querySelector(".toggle-dropdown").addEventListener("click", () => {
    element.classList.toggle("open");
  });
});

Alpine.data("modal", () => ({
  modalOpen: false,
  msg: null,
  actions: [],

  openModal(e) {
    e.preventDefault();
    e.stopPropagation();
    const { actions, msg } = e.detail;
    if (!actions) return;
    this.msg = msg ?? "Êtes vous sûr ?";
    actions.push({
      callback: () => this.closeModal(),
      name: "Annuler",
    });
    this.actions = actions;
    this.modalOpen = true;
    document.body.style.overflowY = "hidden";
  },

  closeModal() {
    document.body.style.overflowY = "auto";
    this.modalOpen = false;
  },

  handleModal(e) {
    let actionID = e.target.dataset.action;
    if (actionID) {
      let action = this.actions[actionID];
      action.callback();
      this.closeModal();
    }
  },
}));

function safeJsonParse(str) {
  try {
    return [null, JSON.parse(str)];
  } catch (err) {
    return [err];
  }
}

Alpine.data("collabList", () => ({
  active: false,
  loading: false,
  data: false,
  type: "",
  labels: {
    organism: {
      address: "adresse",
      city: "ville",
      country: "pays",
      email: "e-mail",
      phone: "numéro",
      postalCode: "code postal",
    },
    user: {
      address: "adresse",
      city: "ville",
      email: "e-mail",
      phone: "numéro",
      postalCode: "code postal",
      status: "statut",
    },
  },
  entity: {},
  item: {},
  count: "",
  options: null,

  fetchItem(entityInfos) {
    if (this.entity !== entityInfos) {
      let [err, entity] = safeJsonParse(entityInfos);
      if (!err) {
        this.entity = entityInfos;
        let entityType = Object.keys(entity)[0];
        let entityID = entity[entityType];
        let API = Routing.generate("front_" + entityType + "_fetch", entity);

        this.active = true;
        this.data = false;
        this.loading = true;

        axios
          .get(API)
          .then(
            (response) => {
              const rd = response.data;
              this.type = rd.hasOwnProperty("lvl") ? "organism" : "user";
              this.item =
                this.type === "organism"
                  ? this.filterOrganismData(rd)
                  : this.filterUserData(rd);
              this.count = rd.countCampaigns;
              this.count +=
                rd.countCampaigns < 2
                  ? " campagne active"
                  : " campagnes actives";
              let query =
                "#" + this.type + "-" + this.item.id + " .item__options";
              this.options = document
                .querySelector(query)
                .cloneNode(true).children;
              this.loading = false;
              this.data = true;
            },
            (error) => {
              console.log(error);
            }
          )
          .then(() => {
            let detailsOptions = document.getElementById("details-options");
            detailsOptions.innerHTML = "";
            Array.from(this.options).map((option) => {
              detailsOptions.append(option);
            });
          });
      }
    }
  },

  filterOrganismData({ id, lvl, countCampaigns, name, ...data }) {
    data.country = this.translateCountryName("fr", data.country);
    return { id, lvl, countCampaigns, name, data };
  },

  filterUserData({ id, firstname, lastname, ...data }) {
    let name = firstname + " " + lastname;
    let status = { SUPERVISOR: "administrateur", ASSISTANT: "adjoint" };
    data.status = status[data.status];
    return { id, name, data };
  },

  translateCountryName(lang, code) {
    let regionNames = new Intl.DisplayNames([lang], { type: "region" });
    return regionNames.of(code);
  },

  isSameItem(entity) {
    // console.log(JSON.stringify(entity));
    // console.log(this.entity);
    return this.entity === JSON.stringify(entity);
  },
}));

// sfFormActions init on add-campaign page
Alpine.data("initPlugin", () => ({
  availablePlugins: ["sfFormActions"],
  loadPlugin(pluginName, pluginDatas) {
    switch (pluginName) {
      case "sfFormActions": {
        sfFormActions.init(pluginDatas[0], pluginDatas[1]);
      }
    }
  },
}));

//LIGHTPICK
document.addEventListener("DOMContentLoaded", function () {
  const datepicker = document.getElementById("datepicker");
  if (datepicker) {
    let format = {
      year: "numeric",
      month: "long",
      day: "numeric",
    };

    axios
      .get(Routing.generate("front_campaign_searchby", { type: "findall" }))
      .then((response) => {
        let allCampaigns = response.data;
        allCampaigns.forEach((campaign, id) => {
          allCampaigns[id] = campaign.dateEnd.split("T")[0];
        });
        let calendar = new Litepicker({
          lang: "fr-FR",
          format: "DD-MMMM-YYYY",
          element: datepicker,
          inlineMode: true,
          dropdowns: {
            minYear: 2000,
            maxYear: null,
            months: true,
            years: true,
          },
          buttonText: {
            previousMonth: `<svg width="7" height="11" viewBox="0 0 7 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="transform rotate-180 pointer-events-none"><path d="M1.06628 1L6 5.46998L1 10" stroke="#202A3C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            nextMonth: `<svg width="7" height="11" viewBox="0 0 7 11" fill="none" xmlns="http://www.w3.org/2000/svg" class="pointer-events-none"><path d="M1.06628 1L6 5.46998L1 10" stroke="#202A3C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
          },
          highlightedDays: allCampaigns,
          setup: (picker) => {
            picker.on("render", (ui) => {
              let pickLabel = createDateLabel();
              if (picker.getDate()) {
                pickLabel.innerText = picker
                  .getDate()
                  .toLocaleString("fr-FR", format);
              }
            });
            picker.on("selected", (date1, date2) => {
              let currentDate = picker.getDate();
              let campaignAPI = Routing.generate("front_campaign_searchby", {
                date: currentDate.format("YYYY-MM-DD"),
              });
              axios.get(campaignAPI).then((response) => {
                //console.log(response);
                datepicker.dispatchEvent(
                  new CustomEvent("set-campaigns", {
                    detail: {
                      campaigns: response.data,
                      date: currentDate.toLocaleString("fr-FR", format),
                    },
                  })
                );
              });
            });
          },
        });

        function createDateLabel() {
          const pickerToolbar =
            document.querySelector(".month-item-name").parentElement;
          pickerToolbar.classList.add("flex", "px-4");
          const currentDate = document.createElement("div");
          currentDate.id = "picklabel";
          currentDate.classList.add(
            "text-grey-light",
            "text-2xs",
            "leading-4",
            "ml-auto",
            "capitalize"
          );
          pickerToolbar.appendChild(currentDate);
          return currentDate;
        }

        // INIT
        createDateLabel();
      });
  }
});

//CHARTS
import resolveConfig from "tailwindcss/resolveConfig";
import tailwindConfig from "../../../tailwind.config.js";
import Chart from "chart.js/auto";
import { sfFormActions } from "./plugins";

const fullConfig = resolveConfig(tailwindConfig);
const colors = fullConfig.theme.colors;

function initCharts(selector) {
  let statsRadioBilanCampaign =
    selector.getElementsByClassName("chart-radio-bilan");
  for (let i = 0; i < statsRadioBilanCampaign.length; i++) {
    let el = statsRadioBilanCampaign[i];

    let labels = [];
    let values = [];
    let colorsGraph = [];
    let stats = el.getElementsByClassName("stat");
    for (let j = 0; j < stats.length; j++) {
      const stat = stats[j];
      const isSub = stat.classList.contains("stat--sub");
      const currentStep = getStepByName(stat.dataset.name);
      colorsGraph.push(
        getStepColorRGBA(
          currentStep,
          isSub ? ((100 / stats.length) * (stats.length - j)) / 100 : null
        )
      );
      labels.push(stat.dataset.label ?? currentStep.label);
      values.push(parseFloat(stat.dataset.stat));
    }

    let configStatsBilan;
    if (values.length === 0 || values.reduce((a, b) => a + b) === 0) {
      labels = ["No Data"];
      values = [1];
      colorsGraph = [colors.grey.blue];
      configStatsBilan = {
        type: "doughnut",
        data: {
          labels: labels,
          datasets: [
            {
              label: el.dataset.budget
                ? "En euros (HT)"
                : el.dataset.contact
                ? "Grammes equi. CO₂ "
                : "Tonnes equi. CO₂ ",
              data: values,
              backgroundColor: colorsGraph,
              hoverOffset: 3,
            },
          ],
        },
        options: {
          tooltip: {
            enabled: false,
          },
          rotation: 180,
          plugins: {
            legend: {
              display: false,
            },
          },
          responsive: true,
        },
      };
    } else {
      configStatsBilan = {
        type: "doughnut",
        data: {
          labels: labels,
          datasets: [
            {
              label: el.dataset.budget
                ? "En euros (HT)"
                : el.dataset.contact
                ? "Grammes equi. CO₂ "
                : "Tonnes equi. CO₂ ",
              data: values,
              backgroundColor: colorsGraph,
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
            tooltip: {
              callbacks: {
                label: function (context) {
                  return (
                    labels[context.dataIndex] +
                    " : " +
                    context.dataset.data[context.dataIndex] +
                    "%"
                  );
                },
              },
            },
          },
          responsive: true,
        },
      };
    }

    let chart = new Chart(el, configStatsBilan);
  }

  let statsBarBilanCampaign =
    selector.getElementsByClassName("chart-bar-bilan");
  for (let i = 0; i < statsBarBilanCampaign.length; i++) {
    let el = statsBarBilanCampaign[i];

    let labels = [];
    let values = [];
    let colorsGraph = [];
    let stats = el.getElementsByClassName("stat");
    for (let j = 0; j < stats.length; j++) {
      const stat = stats[j];

      if (
        !el.dataset.contact ||
        (el.dataset.contact && stat.dataset.stat > 0)
      ) {
        const isSub = stat.classList.contains("stat--sub");
        const currentStep = getStepByName(stat.dataset.name);
        colorsGraph.push(
          getStepColorRGBA(
            currentStep,
            isSub ? ((100 / stats.length) * (stats.length - j)) / 100 : null
          )
        );
        labels.push(stat.dataset.label ?? currentStep.label);
        values.push(parseFloat(stat.dataset.stat));
      }
    }

    const configStatsBilan = {
      type: "bar",
      data: {
        labels: labels,
        datasets: [
          {
            label: el.dataset.budget
              ? "En euros (HT)"
              : el.dataset.contact
              ? "Grammes equi. CO₂ "
              : "Tonnes equi. CO₂ ",
            data: values,
            backgroundColor: colorsGraph,
            borderColor: colorsGraph,
            hoverOffset: 3,
          },
        ],
      },
      options: {
        indexAxis: el.dataset.contact ? "y" : "x",
        rotation: 180,
        plugins: {
          legend: {
            display: false,
          },
        },
        responsive: true,
      },
    };

    let chart = new Chart(el, configStatsBilan);
  }
}

initCharts(document);

/* ---- ALPINE ---- */
Alpine.data("form", () => form);
Alpine.data("stats", () => stats);
Alpine.data("homeStats", () => homeStats);
Alpine.data("filtersCampaigns", () => ({ ...filtersCampaigns }));
Alpine.data("exportCampaigns", () => ({ ...exportCampaigns }));
Alpine.data("listComponent", listComponent);
Alpine.start();

/* ---- ANALYSE COMPARATIVE ---- */
function statsComparative() {
  const buttonsAddComparative = document.getElementsByClassName(
    "add-campaign-comparative"
  );
  if (buttonsAddComparative) {
    for (let i = 0; i < buttonsAddComparative.length; i++) {
      let button = buttonsAddComparative[i];
      button.addEventListener("click", function ($event) {
        modal.open(["w-full", "md:w-1/2"], function () {
          updateModalComparative({}, button);
        });
      });
      const updateModalComparative = (data, element) => {
        axios
          .post(Routing.generate("front_modal_select_campaign"), data)
          .then((response) => {
            modal.update(response.data, function ($body) {
              $body
                .querySelector('button[type="submit"]')
                .addEventListener("click", function ($event) {
                  filtersCampaigns
                    .generateComparative($event)
                    .then((response) => {
                      const parent = element.parentNode;
                      parent.innerHTML = response.data;
                      initCharts(parent);

                      setTimeout(function () {
                        /* ******************************** */
                        document
                          .querySelectorAll(".same-h-1")
                          .forEach(function (el) {
                            el.style.height = "auto";
                          });

                        let H_1 = 0;
                        document
                          .querySelectorAll(".same-h-1")
                          .forEach(function (el) {
                            H_1 = Math.max(
                              H_1,
                              el.offsetHeight,
                              el.clientHeight
                            );
                            console.log(H_1);
                          });

                        if (H_1 > 0) {
                          document
                            .querySelectorAll(".same-h-1")
                            .forEach(function (el) {
                              el.style.height = H_1 + "px";
                            });
                        }

                        /* ******************************** */

                        document
                          .querySelectorAll(".same-h-2")
                          .forEach(function (el) {
                            el.style.height = "auto";
                          });

                        let H_2 = 0;
                        document
                          .querySelectorAll(".same-h-2")
                          .forEach(function (el) {
                            H_2 = Math.max(
                              H_2,
                              el.offsetHeight,
                              el.clientHeight
                            );
                            console.log(H_2);
                          });

                        if (H_2 > 0) {
                          document
                            .querySelectorAll(".same-h-2")
                            .forEach(function (el) {
                              el.style.height = H_2 + "px";
                            });
                        }

                        /* ******************************** */

                        document
                          .querySelectorAll(".same-h-3")
                          .forEach(function (el) {
                            el.style.height = "auto";
                          });

                        let H_3 = 0;
                        document
                          .querySelectorAll(".same-h-3")
                          .forEach(function (el) {
                            H_3 = Math.max(
                              H_3,
                              el.offsetHeight,
                              el.clientHeight
                            );
                            console.log(H_3);
                          });

                        if (H_3 > 0) {
                          document
                            .querySelectorAll(".same-h-3")
                            .forEach(function (el) {
                              el.style.height = H_3 + "px";
                            });
                        }
                      }, 100);

                      parent
                        .querySelector(".close")
                        .addEventListener("click", function () {
                          parent.innerHTML = "";
                          parent.append(element);
                          statsComparative();
                        });
                      modal.close();
                    });
                });
            });
          });
      };
    }
  }
}

statsComparative();

/* ---- NOUS CONTACTER ---- */
if (document.getElementById("btn-contact")) {
  document.getElementById("btn-contact").addEventListener("click", function () {
    modal.open(["w-full", "md:w-1/2", "xl:w-1/3"], function () {
      updateModalContact({});
    });
  });
  const updateModalContact = (data) => {
    axios
      .post(Routing.generate("front_modal_contact"), data)
      .then((response) => {
        modal.update(response.data, function ($body) {
          if ($body.querySelector("form")) {
            $body
              .querySelector("form")
              .addEventListener("submit", function (e) {
                e.preventDefault();
                modal.toggleLoading(true);
                let formData = new FormData(e.target);
                updateModalContact(formData);
              });
          }
        });
      });
  };
}
