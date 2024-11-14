class UserAPI {
    static async getContact() {
        try {
            const response = await fetch("/api/account.php", {
                method: "GET",
            });

            const content = await response.json();

            if (!response.ok) {
                console.error("[UserAPI] Podczas pobierania danych kontaktowych wystąpił błąd:", content);
                return { status: "error", content };
            }

            return { status: "success", content };
        }
        catch (error) {
            console.error("[UserAPI] Podczas pobierania danych kontaktowych wystąpił błąd:", error);
            return { status: "error", content: error };
        }
    }

    static async updateContact(email) {
        try {
            const response = await fetch("/api/account.php", {
                method: "POST",
                body: JSON.stringify({ email }),
            });

            const content = await response.json();

            if (!response.ok) {
                console.error("[UserAPI] Podczas aktualizowania danych kontaktowych wystąpił błąd:", content);
                return { status: "error", content };
            }

            return { status: "success", content };
        }
        catch (error) {
            console.error("[UserAPI] Podczas aktualizowania danych kontaktowych wystąpił błąd:", error);
            return { status: "error", content: error };
        }
    }
}