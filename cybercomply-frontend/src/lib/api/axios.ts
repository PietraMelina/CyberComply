import axios from "axios";

const baseURL = process.env.NEXT_PUBLIC_API_BASE_URL;

export const apiAuth = axios.create({
  baseURL,
  headers: { "Content-Type": "application/json" },
});

export const apiTenant = axios.create({
  baseURL,
  headers: { "Content-Type": "application/json" },
});

function getStoredAccessToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem("access_token");
}

function getStoredClientId(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem("current_client_id");
}

apiAuth.interceptors.request.use((config) => {
  const token = getStoredAccessToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

apiTenant.interceptors.request.use((config) => {
  const token = getStoredAccessToken();
  const clientId = getStoredClientId();

  if (token) config.headers.Authorization = `Bearer ${token}`;
  if (clientId) config.headers["X-Client-ID"] = clientId;

  return config;
});

apiAuth.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (
      typeof window !== "undefined" &&
      error?.response?.status === 401 &&
      !originalRequest?._retry
    ) {
      originalRequest._retry = true;

      const currentToken = getStoredAccessToken();
      if (!currentToken) {
        return Promise.reject(error);
      }

      try {
        const refreshResponse = await axios.post(
          `${baseURL}/auth/refresh`,
          {},
          { headers: { Authorization: `Bearer ${currentToken}` } }
        );

        const newToken = refreshResponse.data?.access_token;
        if (!newToken) {
          throw new Error("Missing refreshed token");
        }

        localStorage.setItem("access_token", newToken);
        originalRequest.headers.Authorization = `Bearer ${newToken}`;

        return apiAuth(originalRequest);
      } catch (refreshError) {
        localStorage.removeItem("access_token");
        localStorage.removeItem("current_client_id");
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);
