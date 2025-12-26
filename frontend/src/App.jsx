// src/App.jsx
import React from "react";
import { Routes, Route, NavLink, Navigate } from "react-router-dom";
import "./App.css";

import ScreenZ from "./screens/ScreenZ.jsx";
import ScreenA from "./screens/ScreenA.jsx";
import ScreenB from "./screens/ScreenB.jsx";
import ScreenC from "./screens/ScreenC.jsx";
import ScreenD from "./screens/ScreenD.jsx";

export default function App() {
  return (
    <div className="app">
      <header className="topbar">
        <h1 className="title">donburipos</h1>
        <nav className="nav">
          <NavLink className="navlink" to="/z">Z</NavLink>
          <NavLink className="navlink" to="/a">A</NavLink>
          <NavLink className="navlink" to="/b">B</NavLink>
          <NavLink className="navlink" to="/c">C</NavLink>
          <NavLink className="navlink" to="/d">D</NavLink>
        </nav>
      </header>

      <main className="main">
        <Routes>
          <Route path="/" element={<Navigate to="/z" replace />} />
          <Route path="/z" element={<ScreenZ />} />
          <Route path="/a" element={<ScreenA />} />
          <Route path="/b" element={<ScreenB />} />
          <Route path="/c" element={<ScreenC />} />
          <Route path="/d" element={<ScreenD />} />
          <Route path="*" element={<div>Not Found</div>} />
        </Routes>
      </main>
    </div>
  );
}
