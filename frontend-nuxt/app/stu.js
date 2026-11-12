export function comprobar(resultado) {
  if (resultado && typeof resultado === 'object' && typeof resultado.message === 'string') {
    const codigo = resultado.value
    const fallo =
      typeof codigo === 'number'
        ? codigo !== 0
        : !/complet[oó] correctamente|completed successfully/i.test(resultado.message)

    if (fallo) {
      const error = new Error(resultado.message)
      error.codigo = codigo
      throw error
    }
  }
  return resultado
}