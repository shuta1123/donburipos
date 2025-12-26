// src/screens/ScreenB.jsx
import React, { useEffect, useState } from "react";
import { getOrdersByScreen, patchOrderDrink } from "../api.js";
import OrderCard from "../ui/OrderCard.jsx";

export default function ScreenB() {
  const [orders, setOrders] = useState([]);
  const [err, setErr] = useState("");

  async function reload() {
    setErr("");
    try {
      const data = await getOrdersByScreen("B");
      setOrders(Array.isArray(data) ? data : []);
    } catch (e) {
      setErr(String(e.message || e));
    }
  }

  useEffect(() => {
    reload();
    const t = setInterval(reload, 2000);
    return () => clearInterval(t);
  }, []);

  async function onDrinkDone(orderId) {
    try {
      await patchOrderDrink(orderId, true);
      reload();
    } catch (e) {
      setErr(String(e.message || e));
    }
  }

  return (
    <div>
      <h2>B（ドリンク作成）</h2>
      {err && <p style={{ color: "crimson" }}>{err}</p>}

      <div className="grid">
        {orders.map((o) => (
          <OrderCard
            key={o.order_id}
            order={o}
            footer={
              <button onClick={() => onDrinkDone(o.order_id)}>
                DONE（ドリンク完了）
              </button>
            }
          />
        ))}
      </div>
    </div>
  );
}
