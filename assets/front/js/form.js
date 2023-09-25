import axios from "axios";
axios.defaults.headers = {
  "X-Requested-With": "XMLHttpRequest",
};

import { sfFormActions } from "./plugins";
import { modal } from "./modal";

import resolveConfig from "tailwindcss/resolveConfig";
import tailwindConfig from "../../../tailwind.config.js";
import Chart from "chart.js/auto";
import { getStepByName, getStepColorRGBA } from "./utils";
const fullConfig = resolveConfig(tailwindConfig);

let form = {
  campaignID: null,
  campaignAPI: null,

  lastStep: null,
  startStep: window.startStep,
  currentStep: null,
  allSteps: window.allSteps,
  virtualSteps: window.virtualSteps,
  campaignSteps: window.campaignSteps,

  campaignVariants: [],
  currentVariant: window.currentVariant,

  stepContent: null,
  stepStats: null,
  formData: null,
  error: null,

  // Form content is loading
  isLoading: true,
  // Interface widgets are ready
  isInteractive: false,

  confirmModal: document.getElementById("confirm-modal"),
  progressbar: document.getElementById("progressbar"),

  // Called on init
  setup(campaignID) {
    // handle leaving without saving
    document.addEventListener("DOMContentLoaded", () => {
      var observed = document.getElementsByTagName("a");

      for (var i = 0; i < observed.length; i++) {
        observed[i].addEventListener(
          "click",
          (e) => {
            if (!this.isFormClear()) {
              e.preventDefault();
              let link = e.target.closest("a").getAttribute("href");
              this.$dispatch("modal-trigger", {
                msg: "Quitter sans sauvegarder ?",
                actions: [
                  {
                    name: "Sauvegarder",
                    callback: () => {
                      this.postForm(() => window.location.replace(link));
                    },
                  },
                  {
                    name: "Ne pas sauvegarder",
                    class: "btn--secondary",
                    callback: () => {
                      window.location.replace(link);
                    },
                  },
                ],
              });
            }
          },
          false
        );
      }
    });
    // clean campagn steps in case a step has disappeared or has been renamed
    this.campaignSteps.forEach((s, index) => {
      let teststep = getStepByName(s);
      if (typeof teststep === "undefined") this.campaignSteps.splice(index, 1);
    });
    this.addStaticSteps(this.allSteps);
    this.addStaticSteps(this.campaignSteps, true);

    let steps = this.campaignSteps.slice(); // copie des étapes pour ordre d'affichage de l'étape en cours
    steps.push(steps.shift()); // on passe l'étape 'informations' en dernier

    this.campaignVariants = window.campaignVariants.map((variant, index) => {
      return {
        id: index,
        name: variant,
        isMaster: index === 0,
      };
    });

    // Local storage
    // IF LOCALSTORAGE TAKE IT OR TAKE FIRST STEP
    let localCurrentSteps = JSON.parse(localStorage.getItem("current-steps"));
    if (localCurrentSteps) {
      let campaign = localCurrentSteps.find((step) => step.id === campaignID);
      if (typeof campaign !== "undefined") {
        steps.unshift(campaign.currentStep); // on met l'étape stockée au début du tableau
      }
    }
    if (this.startStep) steps.unshift(this.startStep);
    // selection de l'étape à initialiser
    for (let i = 0; i < steps.length && this.currentStep == null; i++) {
      let teststep = getStepByName(steps[i]);
      if (
        typeof teststep !== "undefined" &&
        this.campaignSteps.includes(steps[i])
      )
        // vérification si étape existe
        this.currentStep = teststep;
    }
    this.campaignID = campaignID;
    this.campaignAPI = Routing.generate("front_campaign_variant_fetch", {
      campaign: this.campaignID,
      variantIndex: this.currentVariant,
    });

    // Last step NAME
    this.lastStep = this.campaignSteps.indexOf(this.currentStep.name);
    this.loadWidgets();
    this.loadForm();

    // page switching via arrow keys
    // désactivé temporairement car conflit lorsque focus dans un input à régler
    /*document.addEventListener("keyup", (e) => {
            let availableKeys = [
                { key: "ArrowLeft", dir: "prev" },
                { key: "ArrowRight", dir: "next" },
            ];
            let keyConfig = availableKeys.find(
                (availableKey) => availableKey.key === e.key
            );
            if (
                keyConfig &&
                this.isActionAllowed("goto", {
                    id: this.getStepID(),
                    dir: keyConfig.dir,
                }) &&
                !this.isLoading
            ) {
                // trigger modal confirm for switching page
                let event = new CustomEvent("goto", {
                    detail: {
                        callback: () => this.goTo(keyConfig.dir),
                    },
                });
                document.getElementById("modal-confirm").dispatchEvent(event);
            }
        });*/
  },

  // add "statics" steps to steps array
  addStaticSteps(steps, nameOnly = false) {
    let informationsStep = {
      name: "informations",
      label: "Étape information campagne",
    };
    steps.unshift(nameOnly ? informationsStep.name : informationsStep);
    let requestStep = { name: "requests", label: "Demande spéciale" };
    steps.push(nameOnly ? requestStep.name : requestStep);
    let bilanStep = { name: "bilan", label: "Bilan détaillé" };
    steps.push(nameOnly ? bilanStep.name : bilanStep);
  },

  // Fetch campaign steps when campaign is modified
  loadCampaign(callback = null) {
    axios.get(this.campaignAPI).then((response) => {
      this.campaignSteps = Array.from(response.data.supports);

      // Add "informations" to array
      this.addStaticSteps(this.campaignSteps, true);

      // Callback is defined
      if (typeof callback == "function") {
        callback();
      }
    });
  },

  // Load form widgets : progressbar, pagination
  loadWidgets() {
    // Disable widgets during processing
    this.isInteractive = false;

    // Update progress bar
    let width =
      -(
        1 -
        (this.campaignSteps.indexOf(this.currentStep.name) + 1) /
          this.campaignSteps.length
      ) *
        100 +
      "%";
    this.progressbar.style.transform = "translate(" + width + ")";

    // Widgets are ready
    this.isInteractive = true;
  },

  // Load form from api route
  loadForm() {
    // Form is loading
    this.isLoading = true;
    let formAPI = this.getStepRoute();
    axios.get(formAPI).then((response) => {
      this.stepContent = response.data.step;
      this.isLoading = false;

      // Emit stats to "stats" component
      let event = new CustomEvent("loaded", {
        detail: {
          stats: this.formatStats(response.data.statistics),
          allSteps: this.allSteps,
        },
      });
      let statsElement = document.getElementById("stats");
      statsElement.dispatchEvent(event);
    });
  },

  formatStats(stats) {
    let merged = { ...stats };
    this.virtualSteps.forEach((step) => {
      let statEntries = Object.entries(stats).filter(([key, val]) =>
        key.startsWith(step.name)
      );
      statEntries.forEach(([key, val]) => delete merged[key]);
      merged[step.name] = 0;
      statEntries.forEach(([key, val]) => {
        merged[step.name] += val;
      });
    });
    return merged;
  },

  // Get step related infos
  getStepRoute() {
    return Routing.generate("front_campaign_step", {
      campaign: this.campaignID,
      variantIndex: this.currentVariant,
      stepName: this.currentStep.name,
    });
  },
  getStepID() {
    return this.campaignSteps.indexOf(this.currentStep.name);
  },

  // Check if action is allowed (inverted reducer)
  isActionAllowed(action, data) {
    let pass = false;
    switch (action) {
      case "goto":
        if (data.dir === "next" && data.id < this.campaignSteps.length - 1) {
          pass = true;
        } else if (data.dir === "prev" && data.id >= 1) {
          pass = true;
        }

        break;
    }
    return pass;
  },

  // When user click save changes
  postForm(callback = null) {
    this.isLoading = true;
    const form = new FormData(document.getElementById("form-campaign"));
    axios
      .post(this.getStepRoute(), form, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      })
      .then((response) => {
        // Refresh
        let postCallback = () => {
          this.stepContent = response.data.step;
          this.error = response.data.error;
          this.isLoading = false;

          // Emit stats to "stats" component
          let event = new CustomEvent("loaded", {
            detail: {
              stats: this.formatStats(response.data.statistics),
              allSteps: this.allSteps,
            },
          });
          let statsElement = document.getElementById("stats");
          statsElement.dispatchEvent(event);

          if (!response.data.error) {
            if (typeof callback == "function") {
              callback();
            }
          }
        };
        this.loadCampaign(postCallback);
      });
  },

  canGoTo(step) {
    if (!this.isLoading && !this.isFormClear()) {
      this.$dispatch("modal-trigger", {
        msg: "Changer d'étape sans sauvegarder ?",
        actions: [
          {
            name: "Sauvegarder",
            callback: () => {
              this.postForm(() => this.goTo(step));
            },
          },
          {
            name: "Ne pas sauvegarder",
            class: "btn--secondary",
            callback: () => {
              this.goTo(step);
            },
          },
        ],
      });
    } else {
      this.goTo(step);
    }
  },

  // Called when changing step
  goTo(step) {
    if (!this.isLoading) {
      if (typeof step == "string") {
        // Pagination (arrow)
        // Can be "next" or "prev"
        let direction = step === "next" ? 1 : -1;
        let stepID = this.getStepID() + direction;
        let newStep = this.campaignSteps[stepID];
        this.currentStep = getStepByName(newStep);
        this.lastStep = stepID;
      } else if (typeof step == "object") {
        // Dropdown link or step has been submited
        this.currentStep = step;
      } else if (typeof step == "number") {
        // Pagination (input)
        // No number
        if (isNaN(step)) return;
        // Number too big or too low
        if (step < 1 || this.campaignSteps.length < step) return;

        let stepID = step - 1;
        // User is still requesting the same page
        if (stepID === this.lastStep) {
          return;
        } else {
          this.currentStep = getStepByName(this.campaignSteps[stepID]);
          // Store input
          this.lastStep = stepID;
        }
      } else {
        return;
      }

      //GET LOCALSTORAGE
      let localCurrentSteps = JSON.parse(localStorage.getItem("current-steps"))
        ? JSON.parse(localStorage.getItem("current-steps"))
        : [];
      //IF GET CURRENT CAMPAIGN IN LOCALSTORAGE
      let campaign = localCurrentSteps.find(
        (step) => step.id === this.campaignID
      );
      if (localCurrentSteps.length > 0 && campaign) {
        //SET EXISTING CAMPAIGN
        campaign.currentStep = this.currentStep.name;
      } else {
        //ADD NEW STEP
        campaign = {
          id: this.campaignID,
          currentStep: this.currentStep.name,
        };
        localCurrentSteps.push(campaign);
      }
      localStorage.setItem("current-steps", JSON.stringify(localCurrentSteps));

      this.loadWidgets();
      this.loadForm();
    }
  },

  isFormClear() {
    if (!this.isLoading) {
      if (this.formData) {
        let form = document.getElementById("form-campaign");
        return (
          JSON.stringify([...this.formData]) ===
          JSON.stringify([...new FormData(form)])
        );
      }
      return true;
    }
    return true;
  },

  // Inject form into page, basically a refresh method
  displayStep(stepContainer) {
    if (this.error !== true) {
      this.formData = null;
    }

    // Form content
    stepContainer.innerHTML = this.stepContent;

    let form = stepContainer.querySelector("form");

    //INFO TITLE
    stepContainer.querySelectorAll(".info-title").forEach((element) => {
      element.addEventListener("click", () => {
        element.classList.toggle("open");
      });
    });

    //DROPDOWN
    stepContainer.querySelectorAll(".dropdown").forEach((element, index) => {
      if (index === 0) element.classList.toggle("open");
      element
        .querySelector(".toggle-dropdown")
        .addEventListener("click", () => {
          element.classList.toggle("open");
        });
    });

    if (form) {
      //FORM SUBMIT
      stepContainer
        .querySelector("form")
        .addEventListener("submit", (event) => {
          event.preventDefault();
          this.postForm();
        });

      sfFormActions.init(stepContainer, this.currentStep.name);

      if (this.error !== true) {
        this.formData = new FormData(stepContainer.firstElementChild);
      }
    } else if (this.currentStep.name === "bilan") {
      const colors = fullConfig.theme.colors;

      //BILAN CHARTS
      let statsRadioBilanCampaign =
        stepContainer.getElementsByClassName("chart-radio-bilan");
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
        stepContainer.getElementsByClassName("chart-bar-bilan");
      for (let i = 0; i < statsBarBilanCampaign.length; i++) {
        let el = statsBarBilanCampaign[i];

        let labels = [];
        let values = [];
        let colorsGraph = [];
        let stats = el.getElementsByClassName("stat");
        for (let j = 0; j < stats.length; j++) {
          const stat = stats[j];
          const isSub = stat.classList.contains("stat--sub");
          if (
            !el.dataset.contact ||
            (el.dataset.contact && stat.dataset.stat > 0)
          ) {
            const currentStep = getStepByName(stat.dataset.name);
            colorsGraph.push(
              getStepColorRGBA(
                currentStep,
                isSub ? ((100 / stats.length) * (stats.length - j)) / 100 : 1
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
  },

  // Get variant related infos
  getVariantRoute(params, route) {
    params.campaign = this.campaignID;
    return Routing.generate("front_campaign_" + route, params);
  },

  variantController(action, variantID, parameters = {}) {
    if (!this.isLoading) {
      let variantElt = document.getElementById("variant-" + variantID);
      let newVariantInput = document.querySelector(".name__new_variant");
      switch (action) {
        case "create":
          if (newVariantInput && newVariantInput.value) {
            let params = new URLSearchParams();
            params.append("name", newVariantInput.value);
            axios
              .post(this.getVariantRoute({}, "variant_create"), params)
              .then(() => {
                //new variant = last variant + 1 so length of current variants
                let newVariant = this.campaignVariants.length;
                this.variantController("redirect", newVariant, {
                  step: "informations",
                });
              });
          }
          break;
        case "duplicate":
          if (newVariantInput && newVariantInput.value) {
            let params = new URLSearchParams();
            params.append("name", newVariantInput.value);
            axios
              .post(this.getVariantRoute({}, "variant_duplicate"), params)
              .then((response) => {
                modal.open(["w-full", "md:w-1/2", "xl:w-1/3"]);
                const updateModal = (res) => {
                  const afterUpdate = () => {
                    let form = document.querySelector(
                      ".modal-container #form-campaign"
                    );
                    form.addEventListener("submit", (event) => {
                      event.preventDefault();
                      let formData = new FormData(form);
                      modal.toggleLoading(true);
                      axios
                        .post(
                          this.getVariantRoute({}, "variant_duplicate"),
                          formData
                        )
                        .then((response) => {
                          //IF NOT response.data we can redirect to right variant but else display the response as form
                          if (response.data) {
                            updateModal(response);
                          } else {
                            //new variant = last variant + 1 so length of current variants
                            let newVariant = this.campaignVariants.length;
                            this.variantController("redirect", newVariant, {
                              step: "informations",
                            });
                          }
                        });
                    });
                  };
                  modal.update(res.data, afterUpdate);
                };
                updateModal(response);
              });
          }
          break;
        case "rename":
          let input = variantElt.querySelector(".variant__input");
          let output = variantElt.querySelector(".variant__name");
          if (input && input.value) {
            let params = new URLSearchParams();
            params.append("name", input.value);
            axios.post(
              this.getVariantRoute(
                { variantIndex: variantID },
                "variant_rename"
              ),
              params,
              {
                headers: {
                  "Content-Type": "application/x-www-form-urlencoded",
                },
              }
            );
            output.innerText = input.value;
          }
          break;
        case "delete":
          axios
            .post(
              this.getVariantRoute(
                { variantIndex: variantID },
                "variant_delete"
              )
            )
            .then(() => {
              // assuming we can't delete variant 0
              this.variantController("redirect", variantID - 1);
            });
          break;
        case "redirect":
          parameters.variantIndex = variantID;
          if (!this.isFormClear()) {
            this.$dispatch("modal-trigger", {
              msg: "Changer d'étape sans sauvegarder ?",
              actions: [
                {
                  name: "Sauvegarder",
                  callback: () => {
                    this.postForm(() =>
                      window.location.replace(
                        this.getVariantRoute(parameters, "update")
                      )
                    );
                  },
                },
                {
                  name: "Ne pas sauvegarder",
                  class: "btn--secondary",
                  callback: () => {
                    window.location.replace(
                      this.getVariantRoute(parameters, "update")
                    );
                  },
                },
              ],
            });
          } else {
            window.location.replace(this.getVariantRoute(parameters, "update"));
          }
      }
    }
  }
};

export { form };
