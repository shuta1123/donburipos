// src/screens/ScreenZ.jsx
import React, { useEffect, useMemo, useState } from "react";
import { createOrder, getCategories, getMenus } from "../api.js";

export default function ScreenZ() {
  const [err, setErr] = useState("");
  const [ok, setOk] = useState("");
  const [cashNum, setCashNum] = useState("01");
  const [inOut, setInOut] = useState("IN");

  const [categories, setCategories] = useState([]);
  const [categoryId, setCategoryId] = useState(null);
  const [menus, setMenus] = useState([]);

  const [cart, setCart] = useState([]); // {menu_id, name, quantity}

  useEffect(() => {
    (async () => {
      try {
        const cats = await getCategories();
        setCategories(cats);
        if (cats?.[0]?.category_id) {
          setCategoryId(String(cats[0].category_id));
        }
      } catch (e) {
        setErr(String(e.message || e));
      }
    })();
  }, []);

  useEffect(() => {
    (async () => {
      if (!categoryId) return;
      try {
        const ms = await getMenus(categoryId);
        setMenus(ms);
      } catch (e) {
        setErr(String(e.message || e));
      }
    })();
  }, [categoryId]);

  const itemsPayload = useMemo(() => {
    return cart.map((c) => ({ menu_id: c.menu_id, quantity: c.quantity }));
  }, [cart]);

  function addToCart(menu) {
    setOk("");
    setErr("");
    setCart((prev) => {
      const idx = prev.findIndex((x) => x.menu_id === menu.menu_id);
      if (idx >= 0) {
        const next = [...prev];
        next[idx] = { ...next[idx], quantity: next[idx].quantity + 1 };
        return next;
      }
      return [...prev, { menu_id: menu.menu_id, name: menu.name, quantity: 1 }];
    });
  }

  function dec(menuId) {
    setCart((prev) => {
      const next = prev.map((x) => (x.menu_id === menuId ? { ...x, quantity: x.quantity - 1 } : x));
      return next.filter((x) => x.quantity > 0);
    });
  }

  async function submit() {
    setOk("");
    setErr("");

    try {
      const payload = {
        cash_num: cashNum,
        in_out: inOut,
        items: itemsPayload,
      };
      const res = await createOrder(payload);
      setOk(`作成OK: ${res.display_order_num} / state=${res.state}`);
      setCart([]);
    } catch (e) {
      setErr(String(e.message || e));
    }
  }

  return (
    <div>
      <h2>Z（会計 / 注文作成）</h2>
      {err && <p style={{ color: "crimson" }}>{err}</p>}
      {ok && <p style={{ color: "green" }}>{ok}</p>}

      <div style={{ display: "flex", gap: 16, flexWrap: "wrap" }}>
        <div>
          <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
            <label>cash_num</label>
            <input
              value={cashNum}
              onChange={(e) => setCashNum(e.target.value)}
              style={{ width: 80 }}
              placeholder="01"
            />
            <select value={inOut} onChange={(e) => setInOut(e.target.value)}>
              <option value="IN">IN</option>
              <option value="OUT">OUT</option>
            </select>
          </div>

          <div style={{ marginTop: 12, display: "flex", gap: 8 }}>
            {categories.map((c, idx) => (
            <button
                key={c.category_id ?? c.id ?? `${c.name}-${idx}`}
                onClick={() => setCategoryId(String(c.category_id ?? c.id))}
                style={{
                  border: String(c.category_id) === String(categoryId) ? "2px solid #111" : "1px solid #aaa",
                  borderRadius: 8,
                  padding: "6px 10px",
                  background: "#fff",
                }}
              >
                {c.name}
              </button>
            ))}
          </div>

          <div style={{ marginTop: 12, display: "grid", gridTemplateColumns: "repeat(3, minmax(120px, 1fr))", gap: 8 }}>
            {menus.map((m, idx) => (
            <button
                key={m.menu_id ?? m.id ?? `${m.name}-${idx}`}
                onClick={() => addToCart({ menu_id: m.menu_id ?? m.id, name: m.name })}
                style={{ padding: 10, borderRadius: 10, border: "1px solid #aaa", background: "#fff" }}
              >
                {m.name}
            </button>
            ))}
          </div>
        </div>

        <div style={{ minWidth: 260 }}>
          <h3>カート</h3>
          {cart.length === 0 ? (
            <p>空</p>
          ) : (
            <ul>
              {cart.map((c) => (
                <li key={c.menu_id} style={{ display: "flex", gap: 8, alignItems: "center" }}>
                  <span style={{ flex: 1 }}>{c.name}</span>
                  <span>× {c.quantity}</span>
                  <button onClick={() => dec(c.menu_id)}>-</button>
                </li>
              ))}
            </ul>
          )}

          <button
            onClick={submit}
            disabled={cart.length === 0}
            style={{ marginTop: 12, width: "100%", padding: 10, borderRadius: 10 }}
          >
            会計確定（POST）
          </button>
        </div>
      </div>
    </div>
  );
}
