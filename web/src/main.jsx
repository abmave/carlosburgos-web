import { PublicContent, ResetPassword, modules } from "./editorial.jsx";
import React, { useState } from "react";
import { createRoot } from "react-dom/client";
import "./style.css";
import { portrait, leadership } from "./media.js";
import CMS, { usePublicSettings } from "./cms.jsx";
const areas = [
  "Penal",
  "Civil y Familia",
  "Laboral",
  "Constitucional",
  "Administrativo",
  "Empresarial",
];
function App() {
  const [menu, setMenu] = useState(false),
    [selected, setSelected] = useState("Todas"),
    [notice, setNotice] = useState(""),
    [consultArea, setConsultArea] = useState("");
  const settings = usePublicSettings();
  const admin =
    location.pathname === "/admin" || location.pathname.startsWith("/admin/");
  if (location.pathname === "/admin/reset") return <ResetPassword />;
  if (admin) return <CMS />;
  const route = location.pathname.split("/").filter(Boolean),
    preview = route[0] === "preview";
  if (route.length) {
    const kind = route[preview ? 1 : 0],
      slug = route[preview ? 2 : 1];
    return (
      <>
        <header>
          <a className="brand" href="/">
            CB · Carlos Burgos Huapaya
          </a>
          <a href="/">Volver al inicio</a>
        </header>
        <main>
          {modules[kind] && route.length <= (preview ? 3 : 2) ? (
            <PublicContent kind={kind} slug={slug} preview={preview} />
          ) : (
            <section className="section">
              <h1>Página no encontrada</h1>
              <a href="/">Volver al inicio</a>
            </section>
          )}
        </main>
      </>
    );
  }
  return (
    <>
      <header>
        <a className="brand" href="/">
          CB
          <span>
            {settings.brandName}
            <small>ESTUDIO JURÍDICO</small>
          </span>
        </a>
        <button
          className="toggle"
          aria-label="Abrir menú"
          aria-expanded={menu}
          onClick={() => setMenu(!menu)}
        >
          ☰
        </button>
        <nav className={menu ? "open" : ""}>
          {[
            ["El estudio", "estudio"],
            ["Especialidades", "areas"],
            ["Multimedia", "multimedia"],
            ["Contacto", "contacto"],
          ].map(([t, id]) => (
            <a key={id} href={"/#" + id} onClick={() => setMenu(false)}>
              {t}
            </a>
          ))}
          <a className="navcta" href="/#contacto">
            Solicitar consulta ↗
          </a>
        </nav>
      </header>
      <main>
        <section className="hero">
          <div className="heroText">
            <p className="eyebrow">CARLOS BURGOS HUAPAYA · ABOGADO</p>
            <h1>
              {settings.heroTitle}
              <br />
              <em>{settings.heroAccent}</em>
            </h1>
            <p>{settings.heroDescription}</p>
            <div className="actions">
              <a className="button" href="#contacto">
                Solicitar una consulta ↗
              </a>
              <a className="textlink" href="#areas">
                Explorar especialidades ↓
              </a>
            </div>
            <div className="heroNote">
              Escuchar. Evaluar. Diseñar una estrategia.
            </div>
          </div>
          <div className="portrait">
            <img
              src={
                settings.heroImageId
                  ? "/api/public/media/" + settings.heroImageId
                  : portrait
              }
              alt="Retrato de Carlos Burgos Huapaya"
            />
            <span>Compromiso con cada consulta.</span>
          </div>
        </section>
        <div className="values">
          <span>Atención personalizada</span>
          <span>Comunicación clara</span>
          <span>Compromiso profesional</span>
        </div>
        <section id="estudio" className="section intro">
          <div>
            <p className="eyebrow">EL ESTUDIO</p>
            <h2>{settings.studyTitle}</h2>
          </div>
          <div>
            <p className="large">{settings.studyLead}</p>
            <p>{settings.studyDescription}</p>
            <a className="textlink" href="#contacto">
              Conversemos sobre su consulta ↗
            </a>
            <p className="pending">
              Perfil, formación y colegiatura: información pendiente de
              validación.
            </p>
          </div>
        </section>
        <section id="areas" className="section">
          <PublicContent kind="specialties" compact />
        </section>
        <section className="section process">
          <p className="eyebrow">CÓMO TRABAJAMOS</p>
          <h2>Claridad en cada paso.</h2>
          <div className="steps">
            {[
              ["Escuchamos", "Conocemos su consulta y sus necesidades."],
              ["Evaluamos", "Revisamos los antecedentes y las alternativas."],
              ["Definimos", "Acordamos el alcance y los próximos pasos."],
              ["Acompañamos", "Mantenemos una comunicación clara."],
            ].map(([t, d], i) => (
              <article key={t}>
                <span className="number">0{i + 1}</span>
                <h3>{t}</h3>
                <p>{d}</p>
              </article>
            ))}
          </div>
        </section>
        <section id="multimedia" className="section">
          <PublicContent kind="multimedia" compact />
        </section>
        <section className="section">
          <PublicContent kind="team" compact />
          <PublicContent kind="articles" compact />
          <PublicContent kind="cases" compact />
        </section>
        <section id="contacto" className="section contact">
          <div>
            <p className="eyebrow">HABLEMOS</p>
            <h2>
              Cuéntenos cómo
              <br />
              podemos ayudarle.
            </h2>
            <p>
              Utilice los canales de contacto publicados para solicitar una
              consulta.
            </p>
            <div className="publicContact">
              {["linkedin", "facebook", "instagram"].map(
                (k) =>
                  settings[k] && (
                    <p key={k}>
                      <a href={settings[k]} target="_blank" rel="noreferrer">
                        {k}
                      </a>
                    </p>
                  ),
              )}
              {settings.contactEmail && (
                <p>
                  <a href={"mailto:" + settings.contactEmail}>
                    {settings.contactEmail}
                  </a>
                </p>
              )}
              {settings.contactPhone && (
                <p>
                  <a
                    href={
                      "tel:" + settings.contactPhone.replace(/[^+0-9]/g, "")
                    }
                  >
                    {settings.contactPhone}
                  </a>
                </p>
              )}
              {settings.whatsapp && (
                <p>
                  <a
                    className="textlink"
                    href={"https://wa.me/" + settings.whatsapp}
                    target="_blank"
                    rel="noreferrer"
                  >
                    Escribir por WhatsApp ↗
                  </a>
                </p>
              )}
              {settings.address && <p>{settings.address}</p>}
              {settings.schedule && <p>{settings.schedule}</p>}
            </div>
            <p className="pending">
              Formulario en preparación. Todavía no envía ni guarda datos; puede
              utilizar los canales de contacto publicados.
            </p>
          </div>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              setNotice(
                "La conexión con el servicio de consultas está pendiente. No se han enviado ni guardado sus datos.",
              );
            }}
          >
            <label>
              Nombre
              <input required name="name" autoComplete="name" maxLength="120" />
            </label>
            <div className="formrow">
              <label>
                Correo
                <input
                  type="email"
                  required
                  name="email"
                  autoComplete="email"
                />
              </label>
              <label>
                Teléfono
                <input type="tel" required name="phone" autoComplete="tel" />
              </label>
            </div>
            <label>
              Área de consulta
              <select
                name="area"
                required
                value={consultArea}
                onChange={(e) => setConsultArea(e.target.value)}
              >
                <option value="">Seleccione una especialidad</option>
                <option>Consulta general</option>
              </select>
            </label>
            <label>
              Descripción breve
              <textarea
                required
                name="message"
                maxLength="500"
                rows="3"
                placeholder="Evite incluir información confidencial."
              />
            </label>
            <button className="button" type="submit">
              Revisar solicitud ↗
            </button>
            <p role="status">{notice}</p>
          </form>
        </section>
      </main>
      <footer>
        <div className="brand">
          CB
          <span>
            {settings.brandName}
            <small>ESTUDIO JURÍDICO</small>
          </span>
        </div>
        <p>Diseño en revisión · Información profesional por validar.</p>
        <a href="/admin">Acceso al CMS ↗</a>
      </footer>
    </>
  );
}
createRoot(document.getElementById("root")).render(<App />);
