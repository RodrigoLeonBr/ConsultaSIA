export function isUnauthorizedError(error: Error): boolean {
  return /^401: .*/.test(error.message) || /^403: .*/.test(error.message);
}

export function getAuthHeaders() {
  const token = localStorage.getItem("token");
  return token ? { Authorization: `Bearer ${token}` } : {};
}
