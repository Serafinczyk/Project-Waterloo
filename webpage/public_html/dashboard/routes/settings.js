async function settingsRoute(root) {
    /*
        ----------> LAYOUT <----------
    */
	
	root.innerHTML = `
		<div class="dashboard__header">
			<h1 class="dashboard__title"><span class="icon icon--large">settings</span>Ustawienia</h1>
		</div>
		<div class="settings"></div>
	`;

    const content = root.querySelector(".settings");

    /*
        ----------> NOTIFICATIONS <----------
    */

    const notify = document.createElement("div");
    notify.classList.add("dashboard__tile");
    content.append(notify);

    const notifyTitle = document.createElement("h2");
    notifyTitle.classList.add("dashboard__tile-title");
    notifyTitle.innerHTML = `<span class="icon">notifications</span>Powiadomienia`;
    notify.append(notifyTitle);

    const notifyContent = document.createElement("div");
    notifyContent.classList.add("dashboard__tile-content", "settings__notify");
    notify.append(notifyContent);

    let response = await DeviceAPI.get();
    if (response.status == "error") {
        notifyContent.append(Elements.Info("Podczas ładowania urządzeń wystąpił błąd. Spróbuj ponownie później.", "error"));
        return;
    }
    
    const devices = response.content;

    if (Array.isArray(devices) && devices.length > 0) {
        const notifySched = document.createElement("div");
        notifySched.classList.add("settings__schedule");
        notifyContent.append(notifySched);

        const notifySchedLegend = document.createElement("div");
        notifySchedLegend.classList.add("settings__schedule-legend");
        notifySchedLegend.innerHTML = `
        <p>24</p>
        <p>·</p>
        <p>2</p>
        <p>·</p>
        <p>4</p>
        <p>·</p>
        <p>6</p>
        <p>·</p>
        <p>8</p>
        <p>·</p>
        <p>10</p>
        <p>·</p>
        <p>12</p>
        <p>·</p>
        <p>14</p>
        <p>·</p>
        <p>16</p>
        <p>·</p>
        <p>18</p>
        <p>·</p>
        <p>20</p>
        <p>·</p>
        <p>22</p>
        <p>·</p>
        <p>24</p>`;
        notifySched.append(notifySchedLegend);

        const notifySchedTab = document.createElement("table");
        notifySchedTab.classList.add("settings__schedule-table");
        notifySched.append(notifySchedTab);

        const weekDays = ["poniedziałek", "wtorek", "środa", "czwartek", "piątek", "sobota", "niedziela"];

        weekDays.forEach(day => {
            const row = document.createElement("tr");
            row.classList.add("settings__schedule-row");
            notifySchedTab.append(row);

            const currDay = document.createElement("td");
            currDay.classList.add("settings__schedule-day");
            currDay.innerText = day;
            row.append(currDay);

            for (let i = 0; i < 24; i++) {
                const time = document.createElement("td");
                time.classList.add("settings__schedule-cell");
                row.append(time);

                const check = document.createElement("input");
                check.classList.add("settings__schedule-switch");
                check.type = "checkbox";
                time.append(check);
            }
        });

        const notifyList = document.createElement("div");
        notifyList.classList.add("settings__notify-list");
        notifyContent.append(notifyList);

        devices.forEach(dev => {
            const chb = Elements.Checkbox(dev.name);
            notifyList.append(chb);
    
            chbInput = chb.querySelector("input");
            chbInput.checked = dev.emailNotify == "1";
            chbInput.addEventListener("click", () => {
                dev.emailNotify = dev.emailNotify == "1" ? "0" : "1";
            });
        });
    
        const notifyOptions = document.createElement("div");
        notifyOptions.classList.add("dashboard__tile-footer");
        notify.append(notifyOptions);
    
        const notifyApply = Elements.Button("Zastosuj", "done", "small");
        notifyApply.addEventListener("click", async () => {
            const response = await DeviceAPI.updateNotifications(devices.map(dev => { 
                return {
                    deviceID: dev.deviceID,
                    SMSNotify: dev.SMSNotify,
                    emailNotify: dev.emailNotify,
                };
            }));

            if (response.status == "error") {
                await new Popup("Błąd", "Podczas aktualizowania ustawień wystąpił błąd.", "ok", { isClosable: true }).show();
            }
        });
        notifyOptions.append(notifyApply);
    }
    else {
        notifyContent.append(Elements.Info("Brak urządzeń.", "info"));
    }
    

    /*
        ----------> ACCOUNT <----------
    */

    const acc = document.createElement("div");
    acc.classList.add("dashboard__tile");
    content.append(acc);

    const accTitle = document.createElement("h2");
    accTitle.classList.add("dashboard__tile-title");
    accTitle.innerHTML = `<span class="icon">person</span>Konto`;
    acc.append(accTitle);

    const accContent = document.createElement("div");
    accContent.classList.add("dashboard__tile-content", "settings__account");
    acc.append(accContent);

    response = await UserAPI.getContact();
    if (response.status == "error") {
        accContent.innerText = "Błąd podczas pobierania danych";
        return;
    }
    
    const contact = response.content;

    const accEmail = Elements.Input("E-mail", "", "text");
    accContent.append(accEmail);

    const accEmailInput = accEmail.querySelector("input");
    accEmailInput.value = contact.email;

    const accPass = Elements.Input("Nowe hasło", "", "password");
    accContent.append(accPass);

    const accConfPass = Elements.Input("Potwierdź hasło", "", "password");
    accContent.append(accConfPass);

    const accFooter = document.createElement("div");
    accFooter.classList.add("dashboard__tile-footer");
    acc.append(accFooter);

    const accApply = Elements.Button("Zastosuj", "done", "small");
    accFooter.append(accApply);
}