<?php

namespace App\Commands;

use App\Libraries\FirmaDigital;
use App\Models\FirmaDigitalModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;

class ObtenerFirma extends BaseCommand
{
    protected $group = 'STU-430';
    protected $name = 'obtener-firma';
    protected $description = 'Recupera una firma digital cifrada por su hash principal.';
    protected $usage = 'obtener-firma --hash HASH [--json] [--formato png|jpg] [--salida RUTA]';
    protected $arguments = [];
    protected $options = [
        '--hash'    => 'Hash principal de la firma digital.',
        '--json'    => 'Muestra el paquete descifrado completo (nombre, dni, fecha y firma_base64).',
        '--formato' => 'Formato de salida: png o jpg.',
        '--salida'  => 'Ruta opcional del archivo de salida.',
    ];

    public function run(array $params)
    {
        $hash = strtolower(trim((string) CLI::getOption('hash')));
        $formato = strtolower(trim((string) (CLI::getOption('formato') ?: 'png')));
        $salida = trim((string) (CLI::getOption('salida') ?: ''));
        $mostrarJson = CLI::getOption('json') !== null;

        if ($hash === '' || preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
            CLI::error('El --hash debe ser un SHA-256 hexadecimal de 64 caracteres.');
            return EXIT_ERROR;
        }

        if (! in_array($formato, ['png', 'jpg'], true)) {
            CLI::error('El --formato debe ser png o jpg.');
            return EXIT_ERROR;
        }

        try {
            $firma = (new FirmaDigitalModel())->select('firma_cifrada')->where('firma_hash', $hash)->first();

            if ($firma === null || empty($firma['firma_cifrada'])) {
                throw new RuntimeException('No existe una firma digital asociada al hash indicado.');
            }

            $package = FirmaDigital::descifrar($firma['firma_cifrada']);

            if ($mostrarJson) {
                $json = json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($salida === '') {
                    CLI::write((string) $json);

                    return EXIT_SUCCESS;
                }

                if (file_put_contents($salida, $json) === false) {
                    throw new RuntimeException('No se pudo escribir el archivo de salida.');
                }

                CLI::write('Paquete JSON escrito en: ' . $salida);

                return EXIT_SUCCESS;
            }

            $png = FirmaDigital::pngBinario((string) $package['firma_base64']);
            $binary = $formato === 'jpg' ? $this->convertirJpg($png) : $png;

            if ($salida === '') {
                $salida = getcwd() . DIRECTORY_SEPARATOR . 'firma_' . substr($hash, 0, 12) . '.' . $formato;
            }

            if (file_put_contents($salida, $binary) === false) {
                throw new RuntimeException('No se pudo escribir el archivo de salida.');
            }

            CLI::write('Firma recuperada correctamente.');
            CLI::write('Nombre: ' . $package['nombre']);
            CLI::write('DNI: ' . $package['dni']);
            CLI::write('Fecha: ' . $package['fecha_firma']);
            CLI::write('Archivo: ' . $salida);

            return EXIT_SUCCESS;
        } catch (RuntimeException $e) {
            CLI::error($e->getMessage());
            return EXIT_ERROR;
        }
    }

    private function convertirJpg(string $png): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            throw new RuntimeException('PHP no tiene la extensión GD disponible; no se puede generar JPG. Use --formato png.');
        }

        $image = imagecreatefromstring($png);

        if ($image === false) {
            throw new RuntimeException('No se pudo convertir la imagen PNG a JPG.');
        }

        $background = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($background, 255, 255, 255);
        imagefill($background, 0, 0, $white);
        imagecopy($background, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        $ok = imagejpeg($background, null, 95);
        $jpg = ob_get_clean();

        imagedestroy($image);
        imagedestroy($background);

        if (! $ok || $jpg === false) {
            throw new RuntimeException('No se pudo codificar la imagen JPG.');
        }

        return $jpg;
    }
}
