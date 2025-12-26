// src/screens/ScreenA.jsx
import React, { useEffect, useState } from "react";
import { getOrdersByScreen, patchOrderState } from "../api.js";
import OrderCard from "../ui/OrderCard.jsx";

export default function ScreenA() {
  const [orders, setOrders] = useState([]);
  const [err, setErr] = useState("");

  async function reload() {
    setErr("");
    try {
      const data = await getOrdersByScreen("A");
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

  async function onDone(orderId) {
    try {
      await patchOrderState(orderId, "A_DONE");
      reload();
    } catch (e) {
      setErr(String(e.message || e));
    }
  }

  return (
    <div>
      <h2>A（丼作成中）</h2>
      {err && <p style={{ color: "crimson" }}>{err}</p>}

      <div className="grid">
        {orders.map((o) => (
          <OrderCard
            key={o.order_id}
            order={o}
            footer={
              <button onClick={() => onDone(o.order_id)}>
                A_DONE（作成完了）
              </button>
            }
          />
        ))}
      </div>
    </div>
  );
}
