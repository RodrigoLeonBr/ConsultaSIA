export function isUnauthorizedError(error: Error): boolean {
  return /^401: .*/.test(error.message) || /^403: .*/.test(error.message);
}

export function getAuthHeaders(): Record<string, string> {
  const token = localStorage.getItem("token");
  if (token) {
    return { Authorization: `Bearer ${token}` };
  }
  return {};
}
