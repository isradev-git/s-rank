// public/assets/js/utils.js

const API_URL = "/api";

const auth = {
    getToken: () => localStorage.getItem("fitloop_token"),

    setToken: (token) => localStorage.setItem("fitloop_token", token),

    removeToken: () => localStorage.removeItem("fitloop_token"),

    getUser: () => {
        const user = localStorage.getItem("user");
        return user ? JSON.parse(user) : null;
    },

    setUser: (user) => localStorage.setItem("user", JSON.stringify(user)),

    isAdmin: () => {
        const user = localStorage.getItem("user");
        return user ? JSON.parse(user).is_admin === true : false;
    },

    check: async () => {
        const token = auth.getToken();
        if (!token) {
            window.location.href = "/login";
            return false;
        }
        try {
            const res = await fetch(`${API_URL}/user`, {
                headers: { "Authorization": `Bearer ${token}` }
            });
            if (res.status === 401) {
                auth.removeToken();
                localStorage.removeItem("user");
                window.location.href = "/login";
                return false;
            }
            return true;
        } catch (e) {
            window.location.href = "/login";
            return false;
        }
    },

    logout: async () => {
        const token = auth.getToken();
        try {
            if (token) {
                await fetch(`${API_URL}/auth/logout`, {
                    method: 'POST',
                    headers: { "Authorization": `Bearer ${token}` }
                });
            }
        } catch (e) {
            // Aunque falle, limpiamos local y redirigimos
        }
        auth.removeToken();
        localStorage.removeItem("user");
        window.location.href = "/login";
    },
};

async function apiCall(endpoint, method = "GET", body = null, options = {}) {
    const token = auth.getToken();
    const config = {
        method,
        credentials: "include",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            ...(token ? { "Authorization": `Bearer ${token}` } : {}),
        },
    };

    if (body) {
        config.body = JSON.stringify(body);
    }

    try {
        const response = await fetch(`${API_URL}${endpoint}`, config);

        if (response.status === 401) {
            if (options.skipRedirect401) {
                const data = await response.json();
                const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : "No autorizado");
                throw new Error(msg);
            }
            await auth.logout();
            return null;
        }

        const data = await response.json();

        if (!response.ok) {
            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : "Ocurrió un error");
            throw new Error(msg);
        }

        return data;
    } catch (error) {
        if (!options.suppressErrorAlert) {
            console.error("API Error:", error);
            showToast(error.message, "error");
        }
        throw error;
    }
}
