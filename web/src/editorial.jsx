import React, { useEffect, useState } from "react";

export const modules = {
  specialties: "Especialidades",
  team: "Equipo",
  articles: "Actualidad",
  multimedia: "Multimedia",
  cases: "Casos y experiencia",
};
const labels = {
  summary: "Resumen",
  description: "Descripción",
  faqs: "Preguntas frecuentes (pregunta y respuesta por párrafo)",
  position: "Cargo",
  biography: "Biografía",
  education: "Formación",
  registration: "Colegiatura",
  body: "Cuerpo del artículo",
  category: "Categoría",
  author: "Autor",
  date: "Fecha",
  url: "Enlace YouTube o Vimeo",
  format: "Formato",
  context: "Contexto",
  strategy: "Estrategia",
  result: "Resultado documentado",
  process_status: "Estado jurídico del proceso",
};
const fields = {
  specialties: ["summary", "description", "faqs"],
  team: ["position", "biography", "education", "registration"],
  articles: ["summary", "body", "category", "author", "date"],
  multimedia: ["description", "date", "url", "format"],
  cases: ["context", "strategy", "result", "process_status", "url"],
};
const states = {
  draft: "Borrador",
  review: "En revisión",
  approved: "Aprobado",
  published: "Publicado",
  withdrawn: "Retirado",
};
export async function api(path, options = {}) {
  const r = await fetch("/api/" + path, {
    credentials: "same-origin",
    ...options,
    headers: {
      ...(options.body instanceof FormData
        ? {}
        : { "Content-Type": "application/json" }),
      ...options.headers,
    },
  });
  let data;
  try {
    data = await r.json();
  } catch {
    throw new Error("Servicio no disponible. Intente nuevamente.");
  }
  if (!r.ok) {
    const e = new Error(data.error || "No se pudo completar la operación.");
    e.status = r.status;
    throw e;
  }
  return data;
}
function useDirty(dirty) {
  useEffect(() => {
    if (!dirty) return;
    const f = (e) => {
      e.preventDefault();
      e.returnValue = "";
    };
    window.addEventListener("beforeunload", f);
    return () => window.removeEventListener("beforeunload", f);
  }, [dirty]);
}
function Pager({ page, setPage, total }) {
  return (
    <div className="cmsSave">
      <button
        type="button"
        disabled={page === 1}
        onClick={() => setPage(page - 1)}
      >
        Anterior
      </button>
      <span>Página {page}</span>
      <button
        type="button"
        disabled={total !== undefined ? page * 20 >= total : false}
        onClick={() => setPage(page + 1)}
      >
        Siguiente
      </button>
    </div>
  );
}
export function MediaLibrary({ session, choose, privateCase = null }) {
  const [items, setItems] = useState([]),
    [q, setQ] = useState(""),
    [page, setPage] = useState(1),
    [total, setTotal] = useState(0),
    [error, setError] = useState(""),
    [busy, setBusy] = useState(false),
    [success, setSuccess] = useState("");
  const root = privateCase ? "evidence" : "media",
    headers = { "X-CSRF-Token": session.csrf };
  async function load() {
    setBusy(true);
    try {
      const d = await api(
        `admin/${root}?q=${encodeURIComponent(q)}&page=${page}&case_id=${privateCase || ""}`,
      );
      setItems(d.items);
      setTotal(d.total);
      setError("");
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }
  useEffect(() => {
    load();
  }, [q, page, privateCase]);
  async function upload(e) {
    e.preventDefault();
    const form = e.currentTarget;
    const body = new FormData(form);
    if (body.get("file").size > 8388608) {
      setError("Máximo 8 MiB.");
      return;
    }
    if (privateCase) body.set("case_id", privateCase);
    setBusy(true);
    setError("");
    try {
      await api("admin/" + root, { method: "POST", headers, body });
      form.reset();
      setSuccess("Archivo guardado.");
      await load();
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }
  async function change(item, method) {
    setBusy(true);
    try {
      await api(`admin/${root}/${item.id}`, {
        method,
        headers,
        body: JSON.stringify({ ...item, version: Number(item.version) }),
      });
      setSuccess(
        method === "DELETE" ? "Archivo eliminado." : "Metadatos guardados.",
      );
      await load();
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }
  return (
    <section>
      <h2>
        {privateCase
          ? "Evidencia privada de consentimiento"
          : "Biblioteca multimedia"}
      </h2>
      {privateCase && (
        <p>Solo los administradores pueden consultar estos archivos.</p>
      )}
      {error && (
        <p role="alert" className="cmsError">
          {error}
        </p>
      )}
      {success && <p role="status">{success}</p>}
      <form onSubmit={upload} className="cmsForm">
        <label>
          Archivo (JPEG, PNG, WebP o PDF; máximo 8 MiB)
          <input
            required
            name="file"
            type="file"
            accept=".jpg,.jpeg,.png,.webp,.pdf"
          />
        </label>
        <label>
          Título
          <input required name="title" maxLength={200} />
        </label>
        <label>
          Texto alternativo
          <input name="alt" maxLength={300} />
        </label>
        <button disabled={busy}>Cargar archivo</button>
      </form>
      <label>
        Buscar archivos
        <input
          value={q}
          onChange={(e) => {
            setQ(e.target.value);
            setPage(1);
          }}
        />
      </label>
      {busy && <p role="status">Cargando…</p>}
      {!busy && !items.length && <p>No hay archivos.</p>}
      <div className="editorialGrid">
        {items.map((item, i) => (
          <article key={item.id}>
            {!privateCase && item.mime.startsWith("image/") && (
              <img
                className="mediaThumb"
                src={"/api/public/media/" + item.id}
                alt={item.alt}
              />
            )}
            <label>
              Título
              <input
                value={item.title}
                onChange={(e) =>
                  setItems(
                    items.map((x, j) =>
                      i === j ? { ...x, title: e.target.value } : x,
                    ),
                  )
                }
              />
            </label>
            <label>
              Texto alternativo
              <input
                value={item.alt}
                onChange={(e) =>
                  setItems(
                    items.map((x, j) =>
                      i === j ? { ...x, alt: e.target.value } : x,
                    ),
                  )
                }
              />
            </label>
            {privateCase ? (
              <a
                href={"/api/admin/evidence/" + item.id}
                target="_blank"
                rel="noreferrer"
              >
                Consultar evidencia #{item.id}
              </a>
            ) : (
              <>
                <button disabled={busy} onClick={() => change(item, "PUT")}>
                  Guardar metadatos
                </button>
                <button
                  disabled={busy}
                  onClick={() =>
                    window.confirm("¿Eliminar este archivo?") &&
                    change(item, "DELETE")
                  }
                >
                  Eliminar
                </button>
                {choose && item.mime.startsWith("image/") && (
                  <button onClick={() => choose(Number(item.id))}>
                    Seleccionar imagen
                  </button>
                )}
              </>
            )}
          </article>
        ))}
      </div>
      <Pager page={page} setPage={setPage} total={total} />
    </section>
  );
}
export function Editor({ kind, session, onDirty }) {
  const [items, setItems] = useState([]),
    [record, setRecord] = useState(null),
    [q, setQ] = useState(""),
    [filter, setFilter] = useState(""),
    [page, setPage] = useState(1),
    [total, setTotal] = useState(0),
    [dirty, setDirty] = useState(false),
    [busy, setBusy] = useState(false),
    [error, setError] = useState(""),
    [success, setSuccess] = useState(""),
    [library, setLibrary] = useState(false),
    [galleryMode, setGalleryMode] = useState(false),
    [relations, setRelations] = useState({ specialties: [], team: [] }),
    [approval, setApproval] = useState({
      anonymization: "",
      basis: "",
      consent_required: false,
      evidence_id: null,
    });
  useDirty(dirty);
  useEffect(() => {
    onDirty(dirty);
  }, [dirty]);
  const headers = { "X-CSRF-Token": session.csrf };
  async function load() {
    setBusy(true);
    try {
      const d = await api(
        `admin/content/${kind}?q=${encodeURIComponent(q)}&status=${filter}&page=${page}`,
      );
      setItems(d.items);
      setTotal(d.total);
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }
  useEffect(() => {
    load();
  }, [kind, q, filter, page]);
  useEffect(() => {
    let alive = true;
    async function all(k) {
      let p = 1,
        rows = [],
        d;
      do {
        d = await api(`admin/content/${k}?page=${p++}`);
        rows.push(...d.items);
      } while (rows.length < d.total);
      return rows;
    }
    Promise.all([all("specialties"), all("team")])
      .then(([specialties, team]) => {
        if (alive) setRelations({ specialties, team });
      })
      .catch((e) => setError(e.message));
    return () => {
      alive = false;
    };
  }, [kind]);
  function open(r) {
    if (dirty && !confirm("¿Descartar cambios sin guardar?")) return;
    setRecord(r);
    setDirty(false);
    setError("");
    setSuccess("");
    setLibrary(false);
    setApproval(
      r?.approval || {
        anonymization: "",
        basis: "",
        consent_required: false,
        evidence_id: null,
      },
    );
  }
  function edit(key, value) {
    setRecord({ ...record, [key]: value });
    setDirty(true);
    setSuccess("");
  }
  function field(key, value) {
    edit("content", { ...record.content, [key]: value });
  }
  async function save(status) {
    setBusy(true);
    setError("");
    setSuccess("");
    try {
      const d = await api(
        "admin/content/" + kind + (record.id ? "/" + record.id : ""),
        {
          method: record.id ? "PUT" : "POST",
          headers,
          body: JSON.stringify({ ...record, status, approval }),
        },
      );
      setRecord(d);
      setDirty(false);
      setSuccess("Guardado: " + states[d.status] + ".");
      await load();
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }
  return (
    <section>
      <h1>{modules[kind]}</h1>
      {error && (
        <p role="alert" className="cmsError">
          {error}
        </p>
      )}
      {success && (
        <p role="status" className="cmsSuccess">
          {success}
        </p>
      )}
      {record ? (
        <>
          <button onClick={() => open(null)}>← Volver al listado</button>
          <form
            className="cmsForm"
            onSubmit={(e) => {
              e.preventDefault();
              save(
                kind === "cases" &&
                  ["approved", "published"].includes(record.status)
                  ? session.user.role === "admin"
                    ? record.status
                    : "review"
                  : "draft",
              );
            }}
          >
            <fieldset disabled={busy}>
              <legend>
                {record.id ? "Editar contenido" : "Nuevo contenido"}
              </legend>
              <p>
                {states[record.status]} ·{" "}
                {dirty ? "Cambios sin guardar" : "Sincronizado"}
              </p>
              <label>
                {kind === "team" ? "Nombre" : "Título"}
                <input
                  required
                  maxLength={200}
                  value={record.title}
                  onChange={(e) => edit("title", e.target.value)}
                />
              </label>
              <label>
                Dirección legible (slug)
                <input
                  required
                  pattern="[a-z0-9]+(-[a-z0-9]+)*"
                  maxLength={160}
                  value={record.slug}
                  onChange={(e) => edit("slug", e.target.value)}
                />
              </label>
              {fields[kind].map((f) => (
                <label key={f}>
                  {labels[f]}
                  {f === "format" ? (
                    <select
                      value={record.content[f] || "video"}
                      onChange={(e) => field(f, e.target.value)}
                    >
                      <option value="video">Video</option>
                      <option value="interview">Entrevista</option>
                      <option value="gallery">Galería</option>
                    </select>
                  ) : f === "date" ? (
                    <input
                      type="date"
                      value={record.content[f] || ""}
                      onChange={(e) => field(f, e.target.value)}
                    />
                  ) : (
                    <textarea
                      rows={f === "body" ? 12 : 3}
                      maxLength={f === "body" ? 50000 : 10000}
                      value={record.content[f] || ""}
                      onChange={(e) => field(f, e.target.value)}
                    />
                  )}
                </label>
              ))}
              <p>El texto se publica como párrafos, sin ejecutar HTML.</p>
              <label>
                Orden
                <input
                  type="number"
                  min={-100000}
                  max={100000}
                  value={record.order}
                  onChange={(e) => edit("order", Number(e.target.value))}
                />
              </label>
              {record.content.image_id && (
                <img
                  className="mediaThumb"
                  alt="Imagen seleccionada"
                  src={"/api/public/media/" + record.content.image_id}
                />
              )}
              <button
                type="button"
                onClick={() => {
                  setGalleryMode(false);
                  setLibrary(!library);
                }}
              >
                Elegir imagen de biblioteca
              </button>
              <button type="button" onClick={() => field("image_id", null)}>
                Quitar imagen
              </button>
              {kind === "multimedia" && (
                <>
                  <button
                    type="button"
                    onClick={() => {
                      setGalleryMode(true);
                      setLibrary(true);
                    }}
                  >
                    Añadir imagen a galería
                  </button>
                  {(record.content.gallery_ids || []).map((id) => (
                    <div key={id}>
                      <img
                        className="mediaThumb"
                        alt="Imagen de galería"
                        src={"/api/public/media/" + id}
                      />
                      <button
                        type="button"
                        onClick={() =>
                          field(
                            "gallery_ids",
                            record.content.gallery_ids.filter((x) => x !== id),
                          )
                        }
                      >
                        Quitar de galería
                      </button>
                    </div>
                  ))}
                </>
              )}
              {Object.entries(relations)
                .filter(([k]) => k !== kind)
                .map(([k, rows]) => (
                  <label key={k}>
                    {modules[k]} relacionados
                    <select
                      multiple
                      value={(
                        record.content[
                          k === "team" ? "team_ids" : "specialty_ids"
                        ] || []
                      ).map(String)}
                      onChange={(e) =>
                        field(
                          k === "team" ? "team_ids" : "specialty_ids",
                          Array.from(e.target.selectedOptions, (x) =>
                            Number(x.value),
                          ),
                        )
                      }
                    >
                      {rows.map((r) => (
                        <option key={r.id} value={r.id}>
                          {r.title}
                        </option>
                      ))}
                    </select>
                  </label>
                ))}
              <div className="cmsSave">
                <button type="submit">Guardar borrador / cambios</button>
                {kind !== "cases" && (
                  <button type="button" onClick={() => save("published")}>
                    Publicar
                  </button>
                )}
                {kind === "cases" && (
                  <>
                    <button type="button" onClick={() => save("review")}>
                      Enviar a revisión
                    </button>
                    {session.user.role === "admin" &&
                      record.status === "approved" && (
                        <button type="button" onClick={() => save("published")}>
                          Publicar caso aprobado
                        </button>
                      )}
                  </>
                )}
                {record.id && (
                  <button type="button" onClick={() => save("withdrawn")}>
                    Retirar publicación
                  </button>
                )}
              </div>
              {record.id && (
                <a
                  href={`/preview/${kind}/${record.id}`}
                  target="_blank"
                  rel="noreferrer"
                >
                  Vista previa del contenido guardado ↗
                </a>
              )}
            </fieldset>
          </form>
          {library && (
            <MediaLibrary
              session={session}
              choose={(id) => {
                galleryMode
                  ? field("gallery_ids", [
                      ...new Set([...(record.content.gallery_ids || []), id]),
                    ])
                  : field("image_id", id);
                setLibrary(false);
              }}
            />
          )}
          {kind === "cases" && session.user.role === "admin" && record.id && (
            <>
              <h2>Aprobación editorial</h2>
              <p>
                Modificar un caso aprobado o publicado lo devuelve a revisión y
                lo retira de la web. Cada caso es distinto; los resultados
                anteriores no garantizan resultados similares.
              </p>
              <label>
                Nivel de anonimización
                <input
                  value={approval.anonymization}
                  onChange={(e) =>
                    setApproval({ ...approval, anonymization: e.target.value })
                  }
                />
              </label>
              <label>
                Fundamento de autorización
                <textarea
                  value={approval.basis}
                  onChange={(e) =>
                    setApproval({ ...approval, basis: e.target.value })
                  }
                />
              </label>
              <label>
                <input
                  type="checkbox"
                  checked={approval.consent_required}
                  onChange={(e) =>
                    setApproval({
                      ...approval,
                      consent_required: e.target.checked,
                    })
                  }
                />{" "}
                Requiere evidencia de consentimiento
              </label>
              {approval.consent_required && (
                <label>
                  Número de evidencia privada
                  <input
                    type="number"
                    min="1"
                    value={approval.evidence_id || ""}
                    onChange={(e) =>
                      setApproval({
                        ...approval,
                        evidence_id: Number(e.target.value),
                      })
                    }
                  />
                </label>
              )}
              <button
                disabled={busy || dirty || record.status !== "review"}
                onClick={() => save("approved")}
              >
                Aprobar versión guardada
              </button>
              <MediaLibrary session={session} privateCase={record.id} />
            </>
          )}
        </>
      ) : (
        <>
          <div className="cmsSave">
            <label>
              Buscar
              <input
                value={q}
                onChange={(e) => {
                  setQ(e.target.value);
                  setPage(1);
                }}
              />
            </label>
            <label>
              Estado
              <select
                value={filter}
                onChange={(e) => {
                  setFilter(e.target.value);
                  setPage(1);
                }}
              >
                <option value="">Todos</option>
                {Object.entries(states).map(([k, v]) => (
                  <option key={k} value={k}>
                    {v}
                  </option>
                ))}
              </select>
            </label>
            <button
              onClick={() =>
                open({
                  title: "",
                  slug: "",
                  status: "draft",
                  order: 0,
                  content: {
                    format: "video",
                    specialty_ids: [],
                    team_ids: [],
                    image_id: null,
                  },
                })
              }
            >
              Crear contenido
            </button>
          </div>
          {busy ? (
            <p role="status">Cargando…</p>
          ) : !items.length ? (
            <p>No hay contenido para estos filtros.</p>
          ) : (
            <div className="editorialGrid">
              {items.map((r) => (
                <article key={r.id}>
                  <h2>{r.title}</h2>
                  <p>{states[r.status]}</p>
                  <button onClick={() => open(r)}>Editar {r.title}</button>
                </article>
              ))}
            </div>
          )}
          <Pager page={page} setPage={setPage} total={total} />
        </>
      )}
    </section>
  );
}
export function Users({ session, onDirty = () => {} }) {
  const [items, setItems] = useState([]),
    [page, setPage] = useState(1),
    [q, setQ] = useState(""),
    [record, setRecord] = useState(null),
    [error, setError] = useState(""),
    [link, setLink] = useState(""),
    [busy, setBusy] = useState(false);
  useDirty(!!record);
  useEffect(() => {
    onDirty(!!record);
  }, [record]);
  const headers = { "X-CSRF-Token": session.csrf };
  async function load() {
    try {
      const d = await api(
        `admin/users?page=${page}&q=${encodeURIComponent(q)}`,
      );
      setItems(d.items);
    } catch (e) {
      setError(e.message);
    }
  }
  useEffect(() => {
    load();
  }, [page, q]);
  async function save(e) {
    e.preventDefault();
    setBusy(true);
    setError("");
    try {
      await api("admin/users" + (record.id ? "/" + record.id : ""), {
        method: record.id ? "PUT" : "POST",
        headers,
        body: JSON.stringify({ ...record, version: Number(record.version) }),
      });
      setRecord(null);
      await load();
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }
  async function reset(id) {
    setBusy(true);
    setError("");
    try {
      const d = await api(`admin/users/${id}/reset`, {
        method: "POST",
        headers,
        body: "{}",
      });
      setLink(location.origin + "/admin/reset#" + d.token);
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }
  return (
    <section>
      <h1>Usuarios y permisos</h1>
      {error && <p role="alert">{error}</p>}
      {link && (
        <div role="status">
          <p>
            Enlace privado válido 30 minutos. Entréguelo de forma segura a la
            persona. No se ha enviado correo.
          </p>
          <input
            aria-label="Enlace de restablecimiento"
            readOnly
            value={link}
          />
          <button onClick={() => setLink("")}>Ocultar enlace</button>
        </div>
      )}
      <label>
        Buscar por nombre
        <input
          value={q}
          onChange={(e) => {
            setQ(e.target.value);
            setPage(1);
          }}
        />
      </label>
      <button
        onClick={() =>
          setRecord({ name: "", email: "", role: "editor", active: true })
        }
      >
        Crear usuario
      </button>
      {record && (
        <form className="cmsForm" onSubmit={save}>
          <label>
            Nombre
            <input
              required
              maxLength={120}
              value={record.name}
              onChange={(e) => setRecord({ ...record, name: e.target.value })}
            />
          </label>
          <label>
            Correo
            <input
              required
              type="email"
              maxLength={190}
              value={record.email}
              onChange={(e) => setRecord({ ...record, email: e.target.value })}
            />
          </label>
          <label>
            Rol
            <select
              value={record.role}
              onChange={(e) => setRecord({ ...record, role: e.target.value })}
            >
              <option value="editor">Editor</option>
              <option value="admin">Administrador</option>
            </select>
          </label>
          <label>
            <input
              type="checkbox"
              checked={record.active}
              onChange={(e) =>
                setRecord({ ...record, active: e.target.checked })
              }
            />
            Cuenta activa
          </label>
          <button disabled={busy}>Guardar cuenta</button>
          <button type="button" onClick={() => setRecord(null)}>
            Cancelar
          </button>
        </form>
      )}
      <div className="editorialGrid">
        {items.map((r) => (
          <article key={r.id}>
            <h2>{r.name}</h2>
            <p>
              {r.email} · {r.role} · {Number(r.active) ? "Activo" : "Inactivo"}
            </p>
            <button
              onClick={() => setRecord({ ...r, active: !!Number(r.active) })}
            >
              Editar cuenta
            </button>
            <button disabled={busy} onClick={() => reset(r.id)}>
              Generar enlace de contraseña
            </button>
          </article>
        ))}
      </div>
      <Pager
        page={page}
        setPage={setPage}
        total={items.length < 20 ? (page - 1) * 20 + items.length : undefined}
      />
    </section>
  );
}
export function ResetPassword() {
  const [session, setSession] = useState(null),
    [message, setMessage] = useState(""),
    [token] = useState(() => location.hash.slice(1));
  useEffect(() => {
    history.replaceState(null, "", "/admin/reset");
    api("admin/session")
      .then(setSession)
      .catch((e) => setMessage(e.message));
  }, []);
  async function submit(e) {
    e.preventDefault();
    try {
      await api("admin/reset-password", {
        method: "POST",
        headers: { "X-CSRF-Token": session.csrf },
        body: JSON.stringify({
          token,
          password: new FormData(e.currentTarget).get("password"),
        }),
      });
      setMessage("Contraseña actualizada. Ya puede iniciar sesión.");
    } catch (e) {
      setMessage(e.message);
    }
  }
  return (
    <main className="loginWrap">
      <form className="loginCard" onSubmit={submit}>
        <h1>Restablecer contraseña</h1>
        <label>
          Nueva contraseña
          <input
            name="password"
            type="password"
            required
            minLength={12}
            maxLength={72}
            autoComplete="new-password"
          />
        </label>
        <button disabled={!session}>Guardar contraseña</button>
        <p role="status">{message}</p>
        <a href="/admin">Iniciar sesión</a>
      </form>
    </main>
  );
}
function Video({ url }) {
  const [play, setPlay] = useState(false);
  if (!url) return null;
  let src;
  if (url.includes("vimeo.com/"))
    src = "https://player.vimeo.com/video/" + url.split("/").pop();
  else
    src =
      "https://www.youtube-nocookie.com/embed/" +
      (url.includes("youtu.be/")
        ? url.split("/").pop()
        : new URL(url).searchParams.get("v"));
  return play ? (
    <iframe
      title="Reproductor de video"
      src={src}
      sandbox="allow-scripts allow-same-origin allow-presentation"
      allowFullScreen
    />
  ) : (
    <button onClick={() => setPlay(true)}>Cargar video externo</button>
  );
}
export function PublicContent({
  kind,
  slug,
  preview = false,
  compact = false,
}) {
  const [data, setData] = useState(null),
    [error, setError] = useState(""),
    [page, setPage] = useState(1),
    [q, setQ] = useState("");
  useEffect(() => {
    let active = true;
    setData(null);
    setError("");
    api(
      `${preview ? "admin" : "public"}/content/${kind}${slug ? "/" + encodeURIComponent(slug) : `?page=${page}&q=${encodeURIComponent(q)}`}`,
    )
      .then((d) => {
        if (active) setData(d);
      })
      .catch((e) => {
        if (active)
          setError(e.status === 404 ? "Contenido no encontrado." : e.message);
      });
    return () => {
      active = false;
    };
  }, [kind, slug, preview, page, q]);
  return (
    <section className={compact ? "" : "section"}>
      <h2>{modules[kind] || "Contenido no encontrado"}</h2>
      {preview && <p>Vista previa privada de la versión guardada.</p>}
      {error ? (
        <p role="alert">{error}</p>
      ) : !data ? (
        <p role="status">Cargando…</p>
      ) : slug ? (
        <article>
          <h1>{data.title}</h1>
          {data.content.image_id && (
            <PublicImage id={data.content.image_id} />
          )}{" "}
          {fields[kind]
            ?.filter((f) => !["url", "format"].includes(f))
            .map(
              (f) =>
                data.content[f] && (
                  <div key={f}>
                    <h3>{labels[f]}</h3>
                    <p className="editorialText">{data.content[f]}</p>
                  </div>
                ),
            )}
          <div className="editorialGrid">
            {(data.content.gallery_ids || []).map((id) => (
              <PublicImage key={id} id={id} />
            ))}
          </div>
          <Video url={data.content.url} />
          {data.relations?.length > 0 && (
            <div>
              <h3>Contenido relacionado</h3>
              {data.relations.map((r) => (
                <p key={r.kind + r.slug}>
                  <a href={`/${r.kind}/${r.slug}`}>{r.title}</a>
                </p>
              ))}
            </div>
          )}
          {kind === "cases" && (
            <p>
              “Cada caso es distinto; los resultados anteriores no garantizan
              resultados similares.”
            </p>
          )}
        </article>
      ) : (
        <>
          {!compact && (
            <label>
              Buscar publicaciones
              <input
                value={q}
                onChange={(e) => {
                  setQ(e.target.value);
                  setPage(1);
                }}
              />
            </label>
          )}
          {data.items.length ? (
            <div className="editorialGrid">
              {data.items.map((r) => (
                <article key={r.id}>
                  {r.content.image_id && (
                    <PublicImage id={r.content.image_id} />
                  )}
                  <h3>
                    <a href={`/${kind}/${r.slug}`}>{r.title}</a>
                  </h3>
                  <p>
                    {r.content.summary ||
                      r.content.position ||
                      r.content.description ||
                      ""}
                  </p>
                </article>
              ))}
            </div>
          ) : (
            <p>Próximamente compartiremos contenido del estudio.</p>
          )}
          {compact && <a href={`/${kind}`}>Ver todas las publicaciones →</a>}
          {!compact && (
            <Pager page={page} setPage={setPage} total={data.total} />
          )}
        </>
      )}
    </section>
  );
}
function PublicImage({ id }) {
  const [alt, setAlt] = useState("");
  useEffect(() => {
    api("public/media/" + id + "/metadata")
      .then((d) => setAlt(d.alt))
      .catch(() => {});
  }, [id]);
  return (
    <img
      className="mediaThumb"
      src={"/api/public/media/" + id}
      alt={alt}
      loading="lazy"
    />
  );
}
