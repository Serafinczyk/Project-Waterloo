class DeviceAPI {
  static async add(id, name) {
    try {
      const response = await fetch("/api/addDevice.php", {
        method: "POST",
        body: JSON.stringify({ id, name }),
      });

      const content = await response.json();

      if (!response.ok) {
        console.error(
          "[DeviceAPI] Podczas dodawania urządzenia wystąpił błąd:",
          content
        );
        return { status: "error", content };
      }

      return { status: "success", content };
    } catch (error) {
      console.error(
        "[DeviceAPI] Podczas dodawania urządzenia wystąpił błąd:",
        error
      );
      return { status: "error", content: error };
    }
  }

  static async delete(id) {
    try {
      const response = await fetch("/api/deleteDevice.php", {
        method: "POST",
        body: JSON.stringify({ id }),
      });

      const content = await response.json();

      if (!response.ok) {
        console.error(
          "[DeviceAPI] Podczas usuwania urządzenia wystąpił błąd:",
          content
        );
        return { status: "error", content };
      }

      return { status: "success", content };
    } catch (error) {
      console.error(
        "[DeviceAPI] Podczas usuwania urządzenia wystąpił błąd:",
        error
      );
      return { status: "error", content: error };
    }
  }

  static async rename(id, name) {
    try {
      const response = await fetch("/api/renameDevice.php", {
        method: "POST",
        body: JSON.stringify({ id, name }),
      });

      const content = await response.json();

      if (!response.ok) {
        console.error(
          "[DeviceAPI] Podczas zmieniania nazwy urządzenia wystąpił błąd:",
          content
        );
        return { status: "error", content };
      }

      return { status: "success", content };
    } catch (error) {
      console.error(
        "[DeviceAPI] Podczas dodawania urządzenia wystąpił błąd:",
        error
      );
      return { status: "error", content: error };
    }
  }

  static async get() {
    try {
      const response = await fetch("/api/listDevices.php");

      let content = await response.json();

      if (!response.ok) {
        console.error(
          "[DeviceAPI] Podczas ładowania urządzeń wystąpił błąd:",
          content
        );
        return { status: "error", content };
      }

      content = content.sort((a, b) => {
        const nameA = a.name.toUpperCase();
        const nameB = b.name.toUpperCase();

        if (nameA < nameB) return -1;
        if (nameA > nameB) return 1;
        return 0;
      });

      return { status: "success", content };
    } catch (error) {
      console.error(
        "[DeviceAPI] Podczas ładowania urządzeń wystąpił błąd:",
        error
      );
      return { status: "error", content: error };
    }
  }

  static async getStats(devices, minDate, maxDate) {
    try {
      const response = await fetch("/api/readStats.php", {
        method: "POST",
        body: JSON.stringify({ id: devices, minDate, maxDate }),
      });

      const content = await response.json();

      if (!response.ok) {
        console.error(
          "[DeviceAPI] Podczas ładowania statystyk wystąpił błąd:",
          content
        );
        return { status: "error", content };
      }

      return { status: "success", content };
    } catch (error) {
      console.error(
        "[DeviceAPI] Podczas ładowania statystyk wystąpił błąd:",
        error
      );
      return { status: "error", error };
    }
  }

  static async updateNotifications(data) {
    try {
      const response = await fetch("/api/changeAlert.php", {
        method: "POST",
        body: JSON.stringify(data),
      });

      const content = await response.json();

      if (!response.ok) {
        console.error(
          "[DeviceAPI] Podczas aktualizowania ustawień powiadomień wystąpił błąd:",
          content
        );
        return { status: "error", content };
      }

      return { status: "success", content };
    } catch (error) {
      console.error(
        "[DeviceAPI] Podczas aktualizowania ustawień powiadomień wystąpił błąd:",
        error
      );
      return { status: "error", error };
    }
  }
}
