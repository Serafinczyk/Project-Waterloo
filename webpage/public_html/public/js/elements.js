class Elements {
    static _idGen = (function*() {
        let i = 1;
        while (true) {
            yield `element-${i}`;
            i++;
        }
    })();

    static Button(text, icon, type = "normal") {
        const validTypes = ["normal", "small", "text"];
        if (!validTypes.includes(type)) {
            throw new Error(`[Elements] Niepoprawny typ: ${type}`);
        }

        const id = this._idGen.next().value;

        const node = document.createElement("button");
        node.id = id;       
        node.classList.add("button");
        if (type != "normal") {
            node.classList.add(`button--${type}`);
        }

        const btnIcon = document.createElement("span");
        btnIcon.classList.add("icon");
        btnIcon.innerText = icon;
        node.append(btnIcon);

        node.append(document.createTextNode(text));

        return node;
    }

    static Checkbox(text) {
        const id = this._idGen.next().value;

        const node = document.createElement("div");
        node.classList.add("checkbox");

        const input = document.createElement("input");
        input.id = id;
        input.classList.add("checkbox__input");
        input.type = "checkbox";
        node.append(input);

        const label = document.createElement("label");
        label.classList.add("checkbox__label");
        label.htmlFor = id;
        label.innerText = text;
        node.append(label);

        return node;
    }

    static Input(text, icon, type = "text") {
        const id = this._idGen.next().value;

        const node = document.createElement("div");
        node.classList.add("input");

        const label = document.createElement("label");
        label.classList.add("input__label");
        label.htmlFor = id;
        node.append(label);

        if (icon != "") {
            const labelIcon = document.createElement("span");
            labelIcon.classList.add("icon");
            labelIcon.innerText = icon;
            label.append(labelIcon);
        }
        
        label.append(document.createTextNode(text));

        const input = document.createElement("input");
        input.id = id;
        input.classList.add("input__input");
        input.type = type;
        node.append(input);    

        return node;
    }

    static Info(text, type = "info") {
        if (text == "") {
            throw new Error(`[Elements] Treść nie może być pusta.`);
        }

        const validTypes = ["info", "warning", "error"];
        if (!validTypes.includes(type)) {
            throw new Error(`[Elements] Niepoprawny typ: ${type}`);
        }

        const node = document.createElement("div");
        node.classList.add("info");

        if (["warning", "error"].includes(type)) {
            node.classList.add(`info--${type}`);
        }

        const icon = document.createElement("span");
        icon.classList.add("icon", "info__icon");
        icon.innerText = type;
        node.append(icon);

        const content = document.createElement("p");
        content.classList.add("info__content");
        content.innerText = text;
        node.append(content);

        return node;
    }
}