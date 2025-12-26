// src/screens/ScreenD.jsx
import React, { useEffect, useState } from "react";
import { getOrdersByScreen, patchOrderState } from "../api.js";
import OrderCard from "../ui/OrderCard.jsx";

export default function ScreenD() {
  const [orders, setOrders] = useState([]);
  const [err, setErr] = useState("");

  async function reload() {
    setErr("");
    try {
      const data = await getOrdersByScreen("D");
      setOrders(Array.isArray(data) ? data : []);
    } catch (e) {
      setErr(String(e.message || e));
    }
  }

  useEffect(() => {
    reload();
    const t = setInterval(reload, 1500);
    return () => clearInterval(t);
  }, []);

  async function onCall(orderId) {
    try {
      await patchOrderState(orderId, "CALL");
      reload();
    } catch (e) {
      setErr(String(e.message || e));
    }
  }

  async function onFinish(orderId) {
    try {
      await patchOrderState(orderId, "FINISH");
      reload();
    } catch (e) {
      setErr(String(e.message || e));
    }
  }

  return (
    <div>
      <h2>D（提供）</h2>
      {err && <p style={{ color: "crimson" }}>{err}</p>}

      <div className="grid">
        {orders.map((o) => (
          <OrderCard
            key={o.order_id}
            order={o}
            variant={o.state === "cool" ? "cool" : "normal"}
            hideItems={o.state === "cool"}
            footer={
              <div style={{ display: "flex", gap: 8 }}>
                {o.state !== "cool" ? (
                  <button onClick={() => onCall(o.order_id)}>CALL</button>
                ) : (
                  <button onClick={() => onFinish(o.order_id)}>FINISH</button>
                )}
              </div>
            }
          />
        ))}
      </div>
    </div>
  );
}
