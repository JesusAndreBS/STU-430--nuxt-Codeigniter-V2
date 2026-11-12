#!/bin/sh

echo "Ejecutando migraciones..."
# La BD puede tardar en aceptar conexiones TCP: reintentamos en vez de arrancar sin tablas.
# OJO: "php spark migrate" siempre devuelve 0 (se traga la excepcion), por eso
# comprobamos despues con verificar-bd.php que la tabla exista.
intento=1
while :; do
  php spark migrate --all > /tmp/migrate.log 2>&1

  if php verificar-bd.php empleados; then
    break
  fi

  if [ "$intento" -ge 12 ]; then
    echo "ERROR: la tabla 'empleados' no se pudo crear tras $intento intentos."
    cat /tmp/migrate.log
    exit 1
  fi

  echo "La BD no esta lista (intento $intento/12). Reintentando en 5s..."
  intento=$((intento + 1))
  sleep 5
done

echo "Migraciones aplicadas: la tabla 'empleados' existe."
exec apache2-foreground
