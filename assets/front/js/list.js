const listComponent = (currentFilters) => ({
  filters: ["name", "author", "dateEnd", "statusTranslated"],
  items: null,
  list: null,
  isNested: false,
  state: 0,
  defaultData: {},

  init() {
    this.items = this.$el.querySelectorAll(".item");
    this.list = this.$el.querySelector('[data-role="list"');
    this.isNested = this.list.classList.contains("list--nested");
    this.setupDefaultData();
    this.setupFiltersEvent();
  },

  setupDefaultData() {
    if (this.isNested) {
      Object.values(currentFilters).forEach((lvl) => {
        Object.keys(lvl).forEach((filter) => {
          this.defaultData[filter] = this.getData(filter);
        });
      });
    } else {
      Object.keys(currentFilters).forEach((filter) => {
        this.defaultData[filter] = this.getData(filter);
      });
    }
  },

  // event
  setupFiltersEvent() {
    let listFiltersElt = this.$el.querySelector('[data-role="list-filters"');
    if (listFiltersElt) {
      listFiltersElt.addEventListener("click", ({ target }) => {
        this.state < 2 ? this.state++ : (this.state = 0);
        let filterElt = target.closest(".list__sort__filter");
        this.toggleFilter(filterElt);
      });
    }
  },

  // setting up css classes
  toggleFilter(elt) {
    let filter = elt.dataset.filter;
    this.filters.forEach((filterLabel) => {
      let filterElt = this.$refs[filterLabel];
      if (filterElt) {
        if (
          filterElt.classList.contains("filtering") &&
          filterElt.dataset.filter != elt.dataset.filter
        ) {
          // user clicked a different filter than current one
          filterElt.classList.remove("filtering");
          this.state = 1;
        }
      }
    });
    elt.classList.add("filtering");
    this.filter(filter);
  },

  // main filtering method
  filter(type) {
    let data = this.reducer(this.getData(type), {
      type: type,
      state: this.state,
    });

    if (this.isNested) {
      let parents = [];
      data.forEach((item) => {
        if (item.parent) {
          if (!parents.includes(item.parent)) parents.push(item.parent);
        } else {
          this.createNewList(data);
        }
      });
      if (parents.length) {
        parents.forEach((parent) => {
          let oldNestedList = parent.querySelector(".item__nested-list");
          let newNestedList = document.createDocumentFragment();
          data.forEach((item) => {
            if (parent.isEqualNode(item.parent)) {
              newNestedList.appendChild(item.elt.cloneNode(true));
            }
          });
          oldNestedList.innerHTML = null;
          oldNestedList.appendChild(newNestedList);
        });
      }
    } else {
      this.createNewList(data);
    }
  },

  // get data from dom
  getData(type) {
    let dataElts = this.$el.querySelectorAll('[data-type="' + type + '"]');
    let data = [];
    dataElts.forEach((elt) => {
      let itemElt = elt.closest(".item");
      let item = {
        val: elt.innerText,
        elt: itemElt,
        id: itemElt.id,
      };
      if (this.isNested) {
        if (itemElt.dataset.lvl == 0) {
          item.elt = itemElt.closest(".item__dropdown");
        } else if (itemElt.dataset.lvl == 1) {
          item["parent"] = itemElt.closest(".item__dropdown");
        }
      }
      data.push(item);
    });

    // console.log(data);

    // set default datas for this type of datas
    if (this.state == 0 && this.defaultData[type]) {
      data = this.defaultData[type];
    }

    return data;
  },

  // handle data & action
  reducer(input, action) {
    let output = [...input];
    switch (action.type) {
      case "dateEnd":
        switch (action.state) {
          case 0:
            return output;
          case 1:
            return output.sort(
              (a, b) => this.getDate(b.val) - this.getDate(a.val)
            );
          case 2:
            return output.sort(
              (a, b) => this.getDate(a.val) - this.getDate(b.val)
            );
        }
        break;
      case "name":
      case "author":
      case "statusTranslated":
        switch (action.state) {
          case 0:
            return output;
          case 1:
            return output.sort((a, b) => a.val.localeCompare(b.val));
          case 2:
            return output.sort((a, b) => b.val.localeCompare(a.val));
        }
        break;
    }
  },

  // generate dom fragment and replace previous list
  createNewList(data) {
    let newList = document.createDocumentFragment();
    data.forEach((item) => {
      newList.appendChild(item.elt.cloneNode(true));
    });
    this.list.innerHTML = null;
    this.list.appendChild(newList);
  },

  // filtering utilities
  getDate(string) {
    let preformated = string.split(".").reverse();
    return new Date(preformated);
  },
});

export { listComponent };
