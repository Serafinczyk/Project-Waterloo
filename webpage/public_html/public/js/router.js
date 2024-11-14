class Router {
    constructor(routes) {
        if (window.location.hash.length == 0) {
            window.location.hash = "#/";
        }

        window.addEventListener("hashchange", () => this.locationHandler(routes));
        this.locationHandler(routes);
    }

    locationHandler(routes) {
        const location = window.location.hash.replace("#", "");
        const route = routes[location] || routes["404"];

        const root = document.querySelector("#root");
        while (root.lastChild) {
            root.lastChild.remove();
        }

        route.action(root);
    }
}