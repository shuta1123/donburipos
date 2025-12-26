// src/ui/OrderCard.jsx
import React from "react";
import "./orderCard.css";

export default function OrderCard({ order, footer, variant = "normal", hideItems = false }) {
  return (
    <div className={`orderCard ${variant}`}>
      <div className="orderNum">{order.display_order_num}</div>
      <div className="sub">{order.in_out}</div>

      {!hideItems && Array.isArray(order.items) && order.items.length > 0 && (
        <ul className="items">
          {order.items.map((it, i) => (
            <li key={i}>
              {it.name} × {it.quantity}
            </li>
          ))}
        </ul>
      )}

      {footer && <div className="footer">{footer}</div>}
    </div>
  );
}
