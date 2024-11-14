function devicesRoute(root) {
    /*
        ----------> LAYOUT <----------
    */
	
	root.innerHTML = `
		<div class="dashboard__header">
			<h1 class="dashboard__title"><span class="icon icon--large">gas_meter</span>Urządzenia</h1>
			<button id="add-dev-btn" class="button button--small"><span class="icon">add</span>Dodaj</button>
		</div>
		<div class="devices"></div>
	`;

    /*
        ----------> SETUP <----------
    */

    const addDevBtn = root.querySelector("#add-dev-btn");
    addDevBtn.addEventListener("click", addDevice);

    const content = root.querySelector(".devices");

    listDevices();

    /*
        ----------> ACTIONS <----------
    */

    async function listDevices() {
        while (content.lastChild) {
            content.lastChild.remove();
        }

        const response = await DeviceAPI.get();
        if (response.status == "error") {
            content.append(Elements.Info("Podczas ładowania urządzeń wystąpił błąd. Spróbuj ponownie później.", "error"));
            return;
        }

        const devices = response.content;

        if (!(Array.isArray(devices) && devices.length > 0)) {
            content.append(Elements.Info("Brak urządzeń.", "info"));
            return;
        }

        const devList = document.createElement("ul");
        devList.classList.add("devices__list");
        content.append(devList);

        devices.forEach(dev => {
            const node = document.createElement("li");
            node.classList.add("devices__dev");
            devList.append(node);

            const name = document.createElement("h3");
            name.classList.add("devices__dev-name");
            name.innerText = dev.name;
            node.append(name);

            const id = document.createElement("p");
            id.classList.add("devices__dev-id");
            id.innerText = `ID: ${dev.deviceID}`;
            node.append(id);

            const options = document.createElement("div");
            options.classList.add("devices__dev-options");
            node.append(options);

            const renameBtn = Elements.Button("Zmień nazwę", "edit", "text");
            renameBtn.addEventListener("click", () => {
                renameDevice(dev.deviceID);
            });
            options.append(renameBtn);

            const deleteBtn = Elements.Button("Usuń", "delete", "text");
            deleteBtn.addEventListener("click", () => {
                deleteDevice(dev.deviceID);
            });
            options.append(deleteBtn);
        });
    }

    async function addDevice() {
        const name = await new Popup("Dodaj urządzenie", "Podaj nazwę urządzenia:", "ask", { isClosable: true }).show();

        if (name.type != "ask") return;
        if (!name.content) {
            await new Popup("Błąd", "Nie podano nazwy urządzenia", "ok", { isClosable: true }).show();
            return;
        }

        const id = await new Popup("Dodaj urządzenie", "Podaj ID urządzenia:", "ask", { isClosable: true }).show();

        if (id.type != "ask") return;
        if (
            !id.content ||
            isNaN(id.content) ||
            !(id.content > 0 && id.content <= 4294967295)
        ) {
            await new Popup("Błąd", "Podano niepoprawne ID", "ok", { isClosable: true }).show();
            return;
        }

        const response = await DeviceAPI.add(id.content, name.content);
        if (response.status == "error") {
            await new Popup("Błąd", `Podczas wykonywania operacji wystąpił błąd: ${response.content}`, "ok", { isClosable: true }).show();
            return;
        }

        listDevices();
    }

    async function renameDevice(id) {
        const name = await new Popup("Zmień nazwę urządzenia", "Podaj nową nazwę:", "ask", { isClosable: true }).show();

        if (name.type != "ask") return;
        if (!name.content) {
            await new Popup("Błąd", "Nie podano nazwy urządzenia.", "ok", { isClosable: true }).show();
            return;
        }

        const response = await DeviceAPI.rename(id, name.content);
        if (response.status == "error") {
            await new Popup("Błąd", `Podczas wykonywania operacji wystąpił błąd: ${response.content}`, "ok", { isClosable: true }).show();
            return;
        }

        listDevices();
    }

    async function deleteDevice(id) {
        const confirm = await new Popup("Usuń urządzenie", "Czy na pewno chcesz usunąć to urządzenie?", "yesNo", { isClosable: true }).show();

        if (confirm.type != "yesNo" || confirm.content == "no") return;

        const response = await DeviceAPI.delete(id);
        if (response.status == "error") {
            await new Popup("Błąd", `Podczas wykonywania operacji wystąpił błąd: ${response.content}`, "ok", { isClosable: true }).show();
            return;
        }

        listDevices();
    }
}