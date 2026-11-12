function apiBase(): string {
  return useRuntimeConfig().public.apiBase
}

async function fetchJson(url: string, options: RequestInit = {}) {
  let res: Response

  try {
    res = await fetch(url, {
      ...options,
      headers: {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...options.headers,
      },
    })
  } catch {
    throw new Error('No se pudo conectar con el servidor. ¿El backend está levantado?')
  }

  const texto = await res.text()
  let json: any = null

  try {
    json = texto ? JSON.parse(texto) : null
  } catch {
    json = null
  }

  if (!res.ok) {
    throw new Error(json?.message || `Error HTTP ${res.status}`)
  }

  return json ?? {}
}

export async function listarEmpleados(q: string = '') {
  const url = `${apiBase()}/empleados${q ? `?q=${encodeURIComponent(q)}` : ''}`
  const json = await fetchJson(url)
  return json.data ?? []
}

export async function obtenerEmpleado(id: number) {
  const json = await fetchJson(`${apiBase()}/empleados/${id}`)
  return json.data
}

export async function iniciarFirma(data: { nombre: string; dni: string }) {
  const json = await fetchJson(`${apiBase()}/firmas/iniciar`, {
    method: 'POST',
    body: JSON.stringify(data),
  })
  return json.data
}

export async function registrarEmpleado(data: { nombre: string; dni: string; firma_base64?: string }) {
  const json = await fetchJson(`${apiBase()}/empleados`, {
    method: 'POST',
    body: JSON.stringify(data),
  })
  return json.data
}

export async function actualizarEmpleado(id: number, data: { nombre: string; dni: string }) {
  const json = await fetchJson(`${apiBase()}/empleados/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  })
  return json.data
}

export async function guardarFirmaEmpleado(
  id: number,
  firma_base64: string,
  identidad?: { nombre: string; dni: string },
) {
  const json = await fetchJson(`${apiBase()}/empleados/${id}/firma`, {
    method: 'PUT',
    body: JSON.stringify({ firma_base64, ...identidad }),
  })
  return json.data
}

export async function eliminarEmpleado(id: number) {
  await fetchJson(`${apiBase()}/empleados/${id}`, { method: 'DELETE' })
}
