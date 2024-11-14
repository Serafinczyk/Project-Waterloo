async function startRoute(root) {
    /*
        ----------> BASE LAYOUT <----------
    */

    const header = document.createElement("div");
    header.classList.add("dashboard__header");
    root.append(header);

    const title = document.createElement("h1");
    title.classList.add("dashboard__title");
    title.innerHTML = `<span class="icon icon--large">home</span>Start`;
    header.append(title);

    const content = document.createElement("div");
    content.classList.add("start");
    content.innerText = "Ładowanie statystyk...";
    root.append(content);

    /*
        ----------> GET DEVICES <----------
    */

    while (content.lastChild) {
        content.lastChild.remove();
    }

    const response = await DeviceAPI.get();
    if (response.status == "error") {
        content.append(Elements.Info("Podczas ładowania statystyk wystąpił błąd. Spróbuj ponownie później.", "error"));
        return;
    }

    const devices = response.content.map(dev => {
        return {
            deviceID: dev.deviceID,
            name: dev.name,
            showInCharts: true,
        };
    });

    if (!(Array.isArray(devices) && devices.length > 0)) {
        content.append(Elements.Info("Brak urządzeń.", "info"));
        return;
    }

    /*
        ----------> FILTERS <----------
    */

    const filters = document.createElement("div");
    filters.classList.add("start__filters");
    content.append(filters);

    const filtersHeader = document.createElement("h2");
    filtersHeader.classList.add("start__filters-header");
    filtersHeader.innerHTML = `<span class="icon">filter_alt</span>Filtry`;
    filters.append(filtersHeader);

    const filtersContent = document.createElement("div");
    filtersContent.classList.add("start__filters-content");
    filters.append(filtersContent);

    filtersHeader.addEventListener("click", () => {
        filtersContent.classList.toggle("start__filters-content--visible");
    });

    /* -----> FILTERS.DEVICES <----- */

    const devFilterTitle = document.createElement("h3");
    devFilterTitle.classList.add("start__filter-title");
    devFilterTitle.innerHTML = `<span class="icon">gas_meter</span>Urządzenia`;
    filtersContent.append(devFilterTitle);

    const devFilterContent = document.createElement("ul");
    devFilterContent.classList.add("start__filter-content", "start__filter-devices");
    filtersContent.append(devFilterContent);

    devices.forEach(dev => {
        const chb = Elements.Checkbox(dev.name);
        devFilterContent.append(chb);

        chbInput = chb.querySelector("input");
        chbInput.checked = dev.showInCharts;
        chbInput.addEventListener("click", () => {
            dev.showInCharts = !dev.showInCharts;
        });
    });

    /* -----> FILTERS.PEROID <----- */

    const perFilterTitle = document.createElement("h3");
    perFilterTitle.classList.add("start__filter-title");
    perFilterTitle.innerHTML = `<span class="icon">calendar_month</span>Data`;
    filtersContent.append(perFilterTitle);

    const perFilterContent = document.createElement("div");
    perFilterContent.classList.add("start__filter-content", "start__filter-peroid");
    filtersContent.append(perFilterContent);

    const perDateFrom = Elements.Input("Od:", "", "date");
    perFilterContent.append(perDateFrom);

    const perDateFromInput = perDateFrom.querySelector("input");
    perDateFromInput.value = moment().subtract(1, "months").format("YYYY-MM-DD");

    const perDateTo = Elements.Input("Do:", "", "date");
    perFilterContent.append(perDateTo);

    const perDateToInput = perDateTo.querySelector("input");
    perDateToInput.value = moment().format("YYYY-MM-DD");

    /* -----> FILTERS.APPLY <----- */

    const filtersFooter = document.createElement("div");
    filtersFooter.classList.add("start__filters-footer");
    filtersContent.append(filtersFooter);

    const applyFilters = Elements.Button("Zastosuj", "done", "small");
    applyFilters.addEventListener("click", refreshCharts);
    filtersFooter.append(applyFilters);

    /*
        ----------> CHARTS <----------
    */

    const charts = document.createElement("div");
    charts.classList.add("start__charts");
    content.append(charts);

    refreshCharts();

    async function refreshCharts() {
        while (charts.lastChild) {
            charts.lastChild.remove();
        }

        const colors = colorsGen();

        const filteredDevices = devices.filter(dev => dev.showInCharts);

        const response = await DeviceAPI.getStats(filteredDevices.map(dev => dev.deviceID), perDateFromInput.value, perDateToInput.value);
        if (response.status == "error") {
            content.append(Elements.Info("Podczas ładowania statystyk wystąpił błąd. Spróbuj ponownie później.", "error"));
            return;
        }

        const stats = response.content.map((statistics, index) => {
            return {
                deviceID: filteredDevices[index].deviceID,
                name: filteredDevices[index].name,
                color: colors.next().value,
                statistics,
            }
        });

        /* -----> CHARTS.SUM <----- */

        addChart("Łączne zużycie wody [litry]", "functions", {
            type: "doughnut",
            data: {
                labels: filteredDevices.map(dev => dev.name),
                datasets: [
                    {
                        data: stats.map(dev => {
                            return dev.statistics.reduce((acc, curr) => acc += parseFloat(curr.flow_per_h), 0);
                        }),
                        backgroundColor: stats.map(dev => `rgba(${dev.color}, 0.2)`),
                        borderColor: stats.map(dev => `rgba(${dev.color}, 0.4)`),
                        borderWidth: 1,
                    }
                ]
            },
            options: {
                responsiveness: true,
                aspectRatio: 16/9,
            },
        });

        /* -----> CHARTS.DAILY <----- */

        let days = [];
        for (
            i = moment(perDateFromInput.value);
            i.isBefore(moment(perDateToInput.value).add(1, "days"));
            i.add(1, "days")
        ) {
            days.push(moment(i));
        }

        addChart("Dzienne zużycie wody [litry]", "opacity", {
            type: "line",
            data: {
                labels: days.map(day => day.format("DD.MM")),
                datasets: stats.map(dev => {
                    return {
                        data: days.map(day => {
                            const query = dev.statistics.filter(x => x.date == day.format("YYYY-MM-DD"));
                            if (query.length == 0) return 0;

                            return query.reduce((acc, curr) => acc += parseFloat(curr.flow_per_h), 0);
                        }),
                        label: dev.name,
                        backgroundColor: [`rgba(${dev.color}, 0.2)`],
                        borderColor: [`rgba(${dev.color}, 0.4)`],
                        borderWidth: 1,
                    };
                }),
            },
            options: {
                responsiveness: true,
                aspectRatio: 16/9,
            },
        });

        /* -----> CHARTS.AVG_PER_HOURS <----- */

        const hours = ["00:00", "01:00", "02:00", "03:00", "04:00", "05:00", "06:00", "07:00", "08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00", "18:00", "19:00", "20:00", "21:00", "22:00", "23:00", "23:59"];

        addChart("Średnie zużycie wody wg. pory dnia [litry]", "hourglass_bottom", {
            type: "line",
            data: {
                labels: hours,
                datasets: stats.map(dev => {
                    return {
                        data: hours.map((hour, index) => {
                            let condition = null;

                            if (index == 0) {
                                condition = x => x.time == "00:00:00";
                            }
                            else if (index == 24) {
                                condition = x => moment(`2022-01-01 ${x.time}`).isBetween(moment(`2022-01-01 23:00`), moment(`2022-01-01 23:59:59`), undefined, "(]");
                            }
                            else {
                                condition = x => moment(`2022-01-01 ${x.time}`).isBetween(moment(`2022-01-01 ${hour}`).subtract(1, "hours"), moment(`2022-01-01 ${hour}`), undefined, "(]");
                            }

                            const query = dev.statistics.filter(condition);

                            if (query.length == 0) return 0;

                            return query.reduce((acc, curr) => acc += parseFloat(curr.flow_per_h), 0) / days.length;
                        }),
                        label: dev.name,
                        backgroundColor: [`rgba(${dev.color}, 0.2)`],
                        borderColor: [`rgba(${dev.color}, 0.4)`],
                        borderWidth: 1,
                    };
                }),
            },
            options: {
                responsiveness: true,
                aspectRatio: 16/9,
            },
        });

        /* -----> CHARTS.PER_DAY_OF_WEEK <----- */

        const weekDays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

        addChart("Średnie zużycie wody wg. dnia tygodnia [litry]", "date_range", {
            type: "bar",
            data: {
                labels: ["poniedziałek", "wtorek", "środa", "czwartek", "piątek", "sobota", "niedziela"],
                datasets: stats.map(dev => {
                    return {
                        data: weekDays.map(day => {
                            const query = dev.statistics.filter(x => moment(x.date).format("dddd") == day);
                            if (query.length == 0) return 0;

                            return query.reduce((acc, curr) => acc += parseFloat(curr.flow_per_h), 0) / days.filter(x => x.format("dddd") == day).length;
                        }),
                        label: dev.name,
                        backgroundColor: [`rgba(${dev.color}, 0.2)`],
                        borderColor: [`rgba(${dev.color}, 0.4)`],
                        borderWidth: 1,
                    };
                }),
            },
            options: {
                responsiveness: true,
                aspectRatio: 16/9,
            },
        });
    }

    function addChart(title, icon, config) {
        const chart = document.createElement("div");
        chart.classList.add("dashboard__tile");

        const titleNode = document.createElement("h3");
        titleNode.classList.add("dashboard__tile-title");
        titleNode.innerHTML = `<span class="icon">${icon}</span>${title}`;
        chart.append(titleNode);

        const canvas = document.createElement("canvas");
        canvas.classList.add("dashboard__tile-canvas");
        chart.append(canvas);

        new Chart(canvas, config);

        charts.append(chart);
    }

    function* colorsGen () {
        const colors = [
            "140, 41, 197",
            "7, 125, 193",
            "34, 140, 34",
            "237, 169, 23",
            "193, 85, 7",
            "194, 24, 7",
        ];
        let i = 0;

        while (true) {
            yield colors[i];
            i = (i + 1) % colors.length;
        }
    }
}