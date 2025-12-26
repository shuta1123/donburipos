// src/screens/ScreenC.jsx
import React, { useEffect, useState } from "react";
import { getOrdersByScreen, getOrderById, patchOrderState } from "../api.js";
import OrderCard from "../ui/OrderCard.jsx";

export default function ScreenC() {
  const [orders, setOrders] = useState([]);
  const [err, setErr] = useState("");
  const [detail, setDetail] = useState(null);

  async function reload() {
    setErr("");
    try {
      const data = await getOrdersByScreen("C");
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

  async function openDetail(orderId) {
    setErr("");
    try {
      const d = await getOrderById(orderId);
      setDetail(d);
    } catch (e) {
      setErr(String(e.message || e));
    }
  }

  async function onCDone(orderId) {
    setErr("");
    try {
      await patchOrderState(orderId, "C_DONE");
      setDetail(null);
      reload();
    } catch (e) {
      setErr(String(e.message || e));
    }
  }

  return (
    <div>
      <h2>C（取りまとめ）</h2>
      {err && <p style={{ color: "crimson" }}>{err}</p>}

      <div className="grid">
        {orders.map((o) => (
          <OrderCard
            key={o.order_id}
            order={o}
            footer={
              <div style={{ display: "flex", gap: 8 }}>
                <button onClick={() => openDetail(o.order_id)}>詳細</button>
                <button onClick={() => onCDone(o.order_id)}>C_DONE</button>
              </div>
            }
          />
        ))}
      </div>

      {detail && (
        <div className="modal">
          <div className="modalBox">
            <h3>注文詳細 {detail.display_order_num}</h3>
            <p>IN/OUT: {detail.in_out}</p>
            <p>state: {detail.state}</p>
            <ul>
              {detail.items?.map((it, i) => (
                <li key={i}>
                  {it.name} × {it.quantity}（cook:{String(it.cook)} / make:{it.make}）
                </li>
              ))}
            </ul>
            <div style={{ display: "flex", gap: 8, justifyContent: "flex-end" }}>
              <button onClick={() => setDetail(null)}>閉じる</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
