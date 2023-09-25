//MODALS
const modal = (() => {
    let current = null;
    const open = (
        classes = [],
        afterOpen = () => {},
        afterClose = () => {}
    ) => {
        const options = {
            classes: classes,
            afterOpen: afterOpen,
            afterClose: afterClose,
        };
        close();
        let $modalContainer =
            document.getElementsByClassName("modal-container")[0];
        if ($modalContainer) $modalContainer.remove();
        $modalContainer = document.createElement("div");
        $modalContainer.classList.add("modal-container");
        let $modal = document.createElement("div");
        $modal.classList.add("modal-content", "box-shadow");
        let $bgModal = document.createElement("div");
        $bgModal.classList.add("bg-modal");
        $modalContainer.prepend($bgModal);
        let $modalBody = document.createElement("div");
        $modalBody.classList.add("body", "loading");
        $modal.append($modalBody);
        //CLOSE
        let $close = document.createElement("div");
        $close.classList.add("close-modal");
        $close.addEventListener("click", () => {
            close();
        });
        $modal.prepend($close);
        $modalContainer.addEventListener("click", (e) => {
            if (
                current &&
                e.target ===
                    $modalContainer.getElementsByClassName("bg-modal")[0]
            )
                close();
        });
        $modal.classList.add(...options.classes);
        $modalContainer.append($modal);
        document.querySelector("body").classList.add("overflow-hidden");
        document.querySelector("body").append($modalContainer);
        current = {
            element: $modalContainer,
            options: options,
        };
        options.afterOpen();
        toggleLoading(true);
    };
    const close = () => {
        if (current) {
            document.querySelector("body").classList.remove("overflow-hidden");
            current.element.remove();
            current.options.afterClose();
            current = null;
        }
    };
    const update = (
        html,
        afterUpdate = () => {},
        toggleLoadingAfterUpdate = true
    ) => {
        let $body = current.element.getElementsByClassName("body")[0];
        $body.innerHTML = html;
        setTimeout(function () {
            if (toggleLoadingAfterUpdate) toggleLoading(false);
            let btnClose = $body.getElementsByClassName("btn-modal-close")[0];
            if (btnClose) {
                $body
                    .getElementsByClassName("btn-modal-close")[0]
                    .addEventListener("click", () => {
                        close();
                    });
            }
            afterUpdate($body);
        }, 100);
    };
    const updateClasses = (classes) => {
        if (current) {
            classes.push("modal");
            current.element
                .getElementsByClassName("modal")[0]
                .classList.add(classes);
        }
    };
    const toggleLoading = (toggle) => {
        if (current) {
            if (toggle) {
                current.element
                    .getElementsByClassName("body")[0]
                    .classList.add("loading");
            } else {
                current.element
                    .getElementsByClassName("body")[0]
                    .classList.remove("loading");
            }
        }
    };
    return { open, update, close, toggleLoading };
})();

export { modal };
