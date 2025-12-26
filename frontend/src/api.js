// src/api.js
const BASE_URL = "/api";

async function request(path, { method = "GET", body } = {}) {
  const url = `${BASE_URL}${path}`;

  const options = { method, headers: {} };

  // bodyがある時だけJSON送信にする（GETのpreflight回避）
  if (body !== undefined) {
    options.headers["Content-Type"] = "application/json";
    options.body = JSON.stringify(body);
  }

  const res = await fetch(url, options);

  const text = await res.text();
  let data;
  try {
    data = text ? JSON.parse(text) : null;
  } catch {
    data = { raw: text };
  }

  if (!res.ok) {
    const msg = data?.error || data?.message || `HTTP ${res.status}`;
    throw new Error(msg);
  }

  return data;
}

// orders
export function createOrder(payload) {
  return request("/orders.php", { method: "POST", body: payload });
}
export function getOrdersByScreen(screen) {
  return request(`/orders.php?screen=${encodeURIComponent(screen)}`);
}
export function getOrderById(id) {
  return request(`/orders.php?id=${encodeURIComponent(id)}`);
}

// state
export function patchOrderState(id, action) {
  return request(`/orders_state.php?id=${encodeURIComponent(id)}`, {
    method: "PATCH",
    body: { action },
  });
}

// drink
export function patchOrderDrink(id, done = true) {
  return request(`/orders_drink.php?id=${encodeURIComponent(id)}`, {
    method: "PATCH",
    body: { done },
  });
}

// master
export function getCategories() {
  return request("/categories.php");
}
export function getMenus(categoryId) {
  return request(`/menus.php?category_id=${encodeURIComponent(categoryId)}`);
}
