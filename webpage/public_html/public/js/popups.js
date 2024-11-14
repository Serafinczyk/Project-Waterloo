class Popup {
    constructor(title, content, type, options = null) {
        if (!(title && typeof title == "string")) {
            throw new PopupError("Popup's title must be of type string.");
        }

        if (
            !(
                content &&
                (typeof content == "string" || typeof content == "function")
            )
        ) {
            throw new PopupError(
                "Syntax error: Popup's content must be of type string or function."
            );
        }

        if (!(type && ["ok", "yesNo", "ask"].includes(type))) {
            throw new PopupError(
                'Popup\'s type must be one of the following: "ok", "yesNo", "ask".'
            );
        }

        this._title = title;
        this._content = content;
        this._type = type;
        this._options = options;

        this._state = {};
        this._actions = [];
        this._promise = null;

        this._node = document.createElement("div");
        this._node.classList.add("popup");

        const popupWindow = document.createElement("div");
        popupWindow.classList.add("popup__window");
        this._node.append(popupWindow);

        /* ------ TOP ------ */

        const popupTop = document.createElement("div");
        popupTop.classList.add("popup__top");
        popupWindow.append(popupTop);

        const popupTitle = document.createElement("h3");
        popupTitle.classList.add("popup__title");
        popupTitle.innerText = this._title;
        popupTop.append(popupTitle);

        // Close button for closable popups
        if (this._options && this._options.isClosable) {
            const closeButton = document.createElement("button");
            closeButton.classList.add("popup__close-button");
            closeButton.classList.add("icon");
            closeButton.innerText = "close";
            popupTop.append(closeButton);

            this._actions.push({
                type: "close",
                content: () => "close",
                trigger: closeButton,
            });
        }

        /* ------ BOTTOM ------ */

        const popupBottom = document.createElement("div");
        popupBottom.classList.add("popup__bottom");
        popupWindow.append(popupBottom);

        const popupContent = document.createElement("div");
        popupContent.classList.add("popup__content");
        if (typeof this._content == "function") {
            this._content(popupContent);
        } else {
            popupContent.innerText = this._content;
        }
        popupBottom.append(popupContent);

        // Input for type "ask"
        if (this._type == "ask") {
            const input = document.createElement("div");
            input.classList.add("input");
            popupBottom.append(input);

            this._textInput = document.createElement("input");
            this._textInput.classList.add("input__input");
            input.append(this._textInput);

            this._state.textInput = "";
            this._textInput.addEventListener("change", (event) => {
                this._state.textInput = event.target.value;
            });
        }

        /* ------ BUTTONS ------ */

        const popupButtons = document.createElement("div");
        popupButtons.classList.add("popup__buttons");
        popupBottom.append(popupButtons);

        // Buttons for type "ok"
        if (this._type == "ok") {
            const okButton = Elements.Button("OK", "done", "small");
            popupButtons.append(okButton);

            this._actions.push({
                type: "ok",
                content: () => "ok",
                trigger: okButton,
            });
        }
        // Buttons for type "yesNo"
        else if (this._type == "yesNo") {
            const yesButton = Elements.Button("Tak", "done", "small");
            popupButtons.append(yesButton);

            this._actions.push({
                type: "yesNo",
                content: () => "yes",
                trigger: yesButton,
            });

            const noButton = Elements.Button("Nie", "close", "small");
            popupButtons.append(noButton);

            this._actions.push({
                type: "yesNo",
                content: () => "no",
                trigger: noButton,
            });
        }
        // Buttons for type "ask"
        else if (this._type == "ask") {
            const okButton = Elements.Button("OK", "done", "small");
            popupButtons.append(okButton);

            this._actions.push({
                type: "ask",
                content: () => this._state.textInput,
                trigger: okButton,
            });
        }

        /* ------ PROMISE ------ */

        this._promise = new Promise((resolve) => {
            this._actions.forEach((action) => {
                action.trigger.addEventListener("click", () => {
                    this._node.remove();
                    resolve({
                        type: action.type,
                        content: action.content(),
                    });
                });
            });
        });
    }

    async show() {
        document.body.append(this._node);

        if (this._type == "ask") {
            this._textInput.focus();
        }

        return this._promise;
    }
}

class PopupError extends Error {
    constructor(message) {
        super(message);
        this.name = "PopupError";
    }
}
