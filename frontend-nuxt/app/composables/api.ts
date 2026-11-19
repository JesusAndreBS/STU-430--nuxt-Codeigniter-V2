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

export async function listarFirmas() {
  const json = await fetchJson(`${apiBase()}/firmas`)
  return json.data ?? []
}

export async function obtenerFirma(id: number) {
  const json = await fetchJson(`${apiBase()}/firmas/${id}`)
  return json.data
}

export async function registrarFirma(data: { wacom_blob: string; nombre?: string; dni?: string; fecha_firmado?: string }) {
  const json = await fetchJson(`${apiBase()}/firmas`, {
    method: 'POST',
    body: JSON.stringify(data),
  })
  return json.data
}

export async function eliminarFirma(id: number) {
  await fetchJson(`${apiBase()}/firmas/${id}`, { method: 'DELETE' })
}

export async function descargarPngFirma(id: number): Promise<Blob> {
  const res = await fetch(`${apiBase()}/firmas/${id}/png`)
  if (!res.ok) {
    throw new Error('Error al descargar la imagen')
  }
  return res.blob()
}
