/* ============================================================
   STU-430 Documentation - Data & Logic
   ============================================================ */

const SECTIONS = [
  {
    id: 'overview',
    title: 'Visión General',
    icon: '📋',
    content: `
      <p>Sistema para capturar la firma de un empleado con una tableta <strong>Wacom STU-430</strong>. El formulario pide nombre y DNI; el adaptador Wacom genera un único <code>wacom_blob</code> y la base de datos lo guarda tal cual.</p>

      <h3>Características</h3>
      <ul>
        <li><strong>Captura:</strong> nombre, DNI y trazo de la tableta.</li>
        <li><strong>Blob opaco:</strong> el frontend y el backend no cifran, descifran ni hashean; el adaptador lo empaqueta en base64.</li>
        <li><strong>Historial:</strong> cada firma es un registro nuevo; no se edita.</li>
        <li><strong>Eliminación:</strong> el operador puede borrar un registro del historial.</li>
        <li><strong>Reconexión automática:</strong> detecta la tableta ocupada y reintenta.</li>
      </ul>

      <h3>Stack</h3>
      <div class="tech-grid">
        <div class="tech-item"><span class="badge frontend">Frontend</span> Nuxt 4 + Vue 3 + TypeScript</div>
        <div class="tech-item"><span class="badge backend">Backend</span> CodeIgniter 4.7 + PHP 8.3</div>
        <div class="tech-item"><span class="badge db">Base de datos</span> MySQL 8</div>
        <div class="tech-item"><span class="badge tablet">Tablet</span> Wacom STU-430 + SigCaptX</div>
        <div class="tech-item"><span class="badge crypto">Blob</span> WACOM-STU430-...</div>
      </div>

      <div class="callout callout-tip">
        <div class="callout-content">
          <div class="callout-title">Arquitectura</div>
          El frontend corre en el PC con la tableta (puerto 3000) y el backend en Docker (puerto 8090).
          El proxy de Nuxt reenvía <code>/api</code> al backend.
        </div>
      </div>
    `
  },
  {
    id: 'instalacion',
    title: 'Instalación',
    icon: '📦',
    content: `
      <div class="callout callout-danger">
        <div class="callout-content">
          <div class="callout-title">Dependencias obligatorias de la tableta</div>
          Sin <strong>SignatureSDK</strong> y <strong>SigCaptX</strong> la STU-430 no funciona.
          Los instaladores están en <code>Dependencias-install/</code>.
        </div>
      </div>

      <h3>1. Tableta (Windows, como administrador)</h3>
      <ol>
        <li>Desconecta la tableta del USB.</li>
        <li>Instala <code>Dependencias-install/SignatureSDK/Wacom-Signature-SDK-x64-4.10.7.msi</code>.</li>
        <li>Instala <code>Dependencias-install/SigCaptX/Wacom-SigCaptX-1.27.3.exe</code> con el servicio de Windows.</li>
        <li>Conecta la tableta.</li>
        <li>Comprueba en <code>services.msc</code> que <code>WacomSTUSigCaptX</code> esté en ejecución.</li>
      </ol>

      <h3>2. Backend y base de datos</h3>
      <pre class="diagram" data-lang="bash"><code>cp backend/.env.example backend/.env
docker compose up -d --build
docker compose logs -f backend</code></pre>

      <h3>3. Frontend</h3>
      <pre class="diagram" data-lang="bash"><code>cd frontend-nuxt
npm install
npm run dev</code></pre>

      <table class="doc-table">
        <thead><tr><th>Servicio</th><th>URL</th></tr></thead>
        <tbody>
          <tr><td>Frontend</td><td><code>http://localhost:3000</code></td></tr>
          <tr><td>API</td><td><code>http://localhost:8090/api</code></td></tr>
          <tr><td>phpMyAdmin</td><td><code>http://127.0.0.1:8081</code></td></tr>
          <tr><td>MySQL</td><td><code>127.0.0.1:3307</code></td></tr>
        </tbody>
      </table>
    `
  },
  {
    id: 'architecture',
    title: 'Arquitectura',
    icon: '🏗️',
    content: `
      <h3>Componentes</h3>
      <ul>
        <li><strong>Nuxt:</strong> captura Wacom, formulario, listado y descarga del blob.</li>
        <li><strong>API CodeIgniter:</strong> valida que el blob no esté vacío y accede a MySQL.</li>
        <li><strong>MySQL:</strong> una fila por firma en <code>firmas</code>.</li>
        <li><strong>SigCaptX:</strong> servicio local que expone la tableta al navegador.</li>
      </ul>

      <h3>Flujo de guardado</h3>
      <ol>
        <li>El operador introduce nombre y DNI.</li>
        <li>El empleado firma en la tableta.</li>
        <li>El adaptador Wacom genera un único <code>wacom_blob</code>.</li>
        <li>El frontend envía solo <code>{ wacom_blob }</code>.</li>
        <li>El backend hace <code>INSERT</code> y no interpreta el contenido.</li>
      </ol>

      <pre class="diagram" data-lang="text"><code>Nuxt (3000)
   │  { wacom_blob }
   ▼
API CodeIgniter (8090)
   │  INSERT directo
   ▼
MySQL: firmas (id, wacom_blob, fecha_creacion)</code></pre>
    `
  },
  {
    id: 'database',
    title: 'Base de Datos',
    icon: '🗄️',
    content: `
      <h3>firmas</h3>
      <table class="doc-table">
        <thead><tr><th>Columna</th><th>Tipo</th><th>Uso</th></tr></thead>
        <tbody>
          <tr><td><code>id</code></td><td>INT AUTO_INCREMENT</td><td>Clave primaria</td></tr>
          <tr><td><code>wacom_blob</code></td><td>TEXT NOT NULL</td><td>Blob opaco generado por el adaptador Wacom</td></tr>
          <tr><td><code>fecha_creacion</code></td><td>DATETIME NOT NULL</td><td><code>CURRENT_TIMESTAMP</code></td></tr>
        </tbody>
      </table>

      <p>No hay columnas de nombre, DNI, firma ni hash. Todo el paquete vive dentro de <code>wacom_blob</code>.</p>
    `
  },
  {
    id: 'api',
    title: 'API',
    icon: '🔌',
    content: `
      <p>Todas las rutas cuelgan de <code>/api</code> y devuelven JSON.</p>

      <table class="doc-table">
        <thead><tr><th>Método</th><th>Ruta</th><th>Descripción</th></tr></thead>
        <tbody>
          <tr><td>GET</td><td><code>/firmas</code></td><td>Lista <code>id</code>, <code>wacom_blob</code> y <code>fecha_creacion</code></td></tr>
          <tr><td>GET</td><td><code>/firmas/:id</code></td><td>Devuelve el blob tal cual está en la BD</td></tr>
          <tr><td>POST</td><td><code>/firmas</code></td><td>Guarda <code>{ "wacom_blob": "..." }</code></td></tr>
          <tr><td>DELETE</td><td><code>/firmas/:id</code></td><td>Elimina el registro</td></tr>
        </tbody>
      </table>

      <p>No existe ruta de edición ni actualización. Para cambiar una firma se crea un registro nuevo.</p>

      <h3>Guardar una firma</h3>
      <pre class="diagram" data-lang="bash"><code>curl -X POST http://localhost:8090/api/firmas \\
  -H "Content-Type: application/json" \\
  -d '{"wacom_blob":"WACOM-STU430-..."}'</code></pre>

      <p>El backend solo comprueba que <code>wacom_blob</code> no esté vacío; nunca lee su contenido.</p>
    `
  },
  {
    id: 'security',
    title: 'Seguridad',
    icon: '🔐',
    content: `
      <h3>Blob Wacom</h3>
      <table class="doc-table">
        <tbody>
          <tr><td>Origen</td><td>Adaptador del SDK Wacom STU-430</td></tr>
          <tr><td>Contenido</td><td>Nombre, DNI, trazo, serial y timestamp dentro del blob</td></tr>
          <tr><td>Formato</td><td>Base64 de un JSON; no es cifrado de hardware</td></tr>
          <tr><td>Aplicación</td><td>No cifra, no descifra y no hashea</td></tr>
          <tr><td>Backend</td><td>Solo valida que el blob no esté vacío</td></tr>
          <tr><td>Verificación</td><td>No implementada; el blob solo se conserva y se devuelve tal cual</td></tr>
        </tbody>
      </table>

      <div class="callout callout-warning">
        <div class="callout-content">
          <div class="callout-title">Regla de oro</div>
          El backend nunca interpreta el contenido de <code>wacom_blob</code>. La firma no se edita: para reemplazarla se crea un registro nuevo.
        </div>
      </div>

      <h3>Operación</h3>
      <ul>
        <li><code>backend/.env</code> está fuera de Git y se monta como solo lectura.</li>
        <li>MySQL y phpMyAdmin escuchan solo en <code>127.0.0.1</code>.</li>
        <li>La API no tiene autenticación: no exponerla a Internet sin un proxy autenticado.</li>
        <li>Respaldar la base y conservar las claves fuera del repositorio.</li>
      </ul>
    `
  },
  {
    id: 'commands',
    title: 'Comandos',
    icon: '⌨️',
    content: `
      <h3>Consultar una firma</h3>
      <pre class="diagram" data-lang="bash"><code>curl http://localhost:8090/api/firmas
curl http://localhost:8090/api/firmas/1</code></pre>
      <p>El detalle devuelve el <code>wacom_blob</code> sin modificarlo.</p>

      <h3>Pruebas</h3>
      <pre class="diagram" data-lang="bash"><code>docker compose exec backend composer install
docker compose exec backend vendor/bin/phpunit</code></pre>

      <h3>Respaldo</h3>
      <pre class="diagram" data-lang="bash"><code>docker compose exec db mysqldump -ustu430 -pstu430pass stu430 > respaldo.sql</code></pre>

      <h3>Frontend</h3>
      <pre class="diagram" data-lang="bash"><code>cd frontend-nuxt
npm run dev
npm run build</code></pre>
    `
  },
  {
    id: 'troubleshooting',
    title: 'Problemas',
    icon: '🛠️',
    content: `
      <table class="doc-table">
        <thead><tr><th>Síntoma</th><th>Causa probable</th></tr></thead>
        <tbody>
          <tr><td>La tableta no conecta</td><td>SigCaptX detenido u ocupado por otra pestaña</td></tr>
          <tr><td>Error de CORS</td><td>El frontend no corre en <code>localhost:3000</code> o <code>localhost:5173</code></td></tr>
          <tr><td>Migraciones fallan</td><td>Revisar <code>docker compose logs backend</code></td></tr>
        </tbody>
      </table>

      <h3>Verificaciones rápidas</h3>
      <pre class="diagram" data-lang="bash"><code>docker compose ps
curl http://localhost:8090/api/firmas
docker compose exec backend php spark migrate:status</code></pre>
    `
  }
]

const nav = document.getElementById('nav')
const content = document.getElementById('content')
const searchInput = document.getElementById('searchInput')
const mobileMenuBtn = document.getElementById('mobileMenuBtn')
const sidebar = document.querySelector('.doc-sidebar')
const sidebarOverlay = document.getElementById('sidebarOverlay')
const readingProgress = document.getElementById('readingProgress')
const lightbox = document.getElementById('lightbox')
const lightboxImg = document.getElementById('lightboxImg')
const lightboxCaption = document.getElementById('lightboxCaption')
const lightboxClose = document.querySelector('.lightbox-close')

let activeSection = 'overview'
let lastScrollY = 0
let ticking = false

function renderNav() {
  const query = searchInput.value.toLowerCase()
  const filtered = SECTIONS.filter(s =>
    s.title.toLowerCase().includes(query) ||
    s.content.toLowerCase().includes(query)
  )

  nav.innerHTML = filtered.map(section => `
    <button class="nav-item ${activeSection === section.id ? 'active' : ''}"
            data-section="${section.id}"
            aria-current="${activeSection === section.id ? 'true' : 'false'}">
      <span class="nav-icon">${section.icon}</span>
      <span class="nav-title">${section.title}</span>
    </button>
  `).join('')
}

function renderContent() {
  const section = SECTIONS.find(s => s.id === activeSection)
  if (!section) return

  content.innerHTML = `
    <section class="doc-section active" id="${section.id}">
      <header class="section-header">
        <h2>${section.icon} ${section.title}</h2>
      </header>
      <div class="section-body">${section.content}</div>
    </section>
  `

  enhanceContent()
}

function enhanceContent() {
  // Copy buttons for code blocks
  document.querySelectorAll('pre:not([data-lang])').forEach(pre => {
    if (!pre.querySelector('.copy-code-btn')) {
      const lang = pre.querySelector('code')?.className?.replace('language-', '') || 'text'
      pre.setAttribute('data-lang', lang)
      const btn = document.createElement('button')
      btn.className = 'copy-code-btn'
      btn.textContent = 'Copiar'
      btn.addEventListener('click', () => copyCode(pre, btn))
      pre.appendChild(btn)
    }
  })

  // Lightbox for images
  document.querySelectorAll('.figure img:not([data-lightbox-init])').forEach(img => {
    img.dataset.lightboxInit = 'true'
    img.style.cursor = 'zoom-in'
    img.addEventListener('click', () => openLightbox(img))
  })

  // Tabs
  document.querySelectorAll('.tab-list').forEach(list => {
    list.addEventListener('click', e => {
      const btn = e.target.closest('.tab-btn')
      if (!btn) return
      const tabId = btn.dataset.tab
      list.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b === btn))
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.id === `tab-${tabId}`))
    })
  })

  // Screenshot placeholders
  document.querySelectorAll('.screenshot-placeholder:not([data-clickable])').forEach(ph => {
    ph.dataset.clickable = 'true'
    ph.style.cursor = 'pointer'
    ph.addEventListener('click', () => simulateLoad(ph))
  })
}

function simulateLoad(placeholder) {
  const src = placeholder.dataset.src
  if (!src) return
  const testImg = new Image()
  testImg.onload = () => {
    placeholder.src = src
    placeholder.removeAttribute('data-src')
    placeholder.classList.remove('screenshot-placeholder')
    placeholder.classList.add('loaded')
  }
  testImg.onerror = () => {
    placeholder.classList.add('loaded')
    placeholder.innerHTML = `<span>✅ Imagen simulada cargada</span><small>${placeholder.dataset.src}</small>`
  }
  testImg.src = src
}

function openLightbox(img) {
  lightboxImg.src = img.src
  lightboxImg.alt = img.alt
  lightboxCaption.textContent = img.closest('.figure')?.querySelector('figcaption')?.textContent || ''
  lightbox.classList.add('open')
  document.body.style.overflow = 'hidden'
}

function closeLightbox() {
  lightbox.classList.remove('open')
  document.body.style.overflow = ''
}

function copyCode(pre, btn) {
  const code = pre.querySelector('code').textContent
  navigator.clipboard.writeText(code).then(() => {
    btn.textContent = '¡Copiado!'
    btn.classList.add('copied')
    setTimeout(() => {
      btn.textContent = 'Copiar'
      btn.classList.remove('copied')
    }, 2000)
  })
}

// Navigation events
nav.addEventListener('click', e => {
  const item = e.target.closest('.nav-item')
  if (!item) return
  activeSection = item.dataset.section
  renderNav()
  renderContent()
  window.scrollTo(0, 0)
  if (window.innerWidth <= 1024) {
    sidebar.classList.remove('open')
    sidebarOverlay.classList.remove('visible')
  }
})

// Search
let searchTimeout
searchInput.addEventListener('input', () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(renderNav, 150)
})

// Mobile menu
mobileMenuBtn.addEventListener('click', () => {
  sidebar.classList.toggle('open')
  sidebarOverlay.classList.toggle('visible')
})
sidebarOverlay.addEventListener('click', () => {
  sidebar.classList.remove('open')
  sidebarOverlay.classList.remove('visible')
})

// Reading progress
function updateReadingProgress() {
  const docHeight = document.documentElement.scrollHeight - window.innerHeight
  const progress = window.scrollY / docHeight
  readingProgress.style.transform = `scaleX(${progress})`
}
window.addEventListener('scroll', () => {
  if (!ticking) {
    window.requestAnimationFrame(() => {
      updateReadingProgress()
      ticking = false
    })
    ticking = true
  }
}, { passive: true })

// Theme toggle
const themeToggle = document.getElementById('themeToggle')
if (themeToggle) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark'
  document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark')
  localStorage.setItem('theme', isDark ? 'light' : 'dark')
  themeToggle.textContent = isDark ? '☀️' : '🌙'
  themeToggle.title = isDark ? 'Modo claro' : 'Modo oscuro'
}

// Lightbox events
lightboxClose?.addEventListener('click', closeLightbox)
lightbox?.addEventListener('click', e => {
  if (e.target === lightbox) closeLightbox()
})
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeLightbox()
})

// Initialize
const saved = localStorage.getItem('theme')
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
const theme = saved || (prefersDark ? 'dark' : 'light')
document.documentElement.setAttribute('data-theme', theme)

renderNav()
renderContent()

// Keyboard shortcut: Cmd/Ctrl + K for search
document.addEventListener('keydown', e => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    searchInput.focus()
  }
})