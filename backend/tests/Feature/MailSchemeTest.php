<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Symfony Mailer solo entiende «smtp» y «smtps». Poner «tls» o «ssl» —que es lo que
 * dicen los paneles de hosting y casi cualquier tutorial— no falla al arrancar: falla
 * la primera vez que se envía un correo, semanas después, en producción.
 */
class MailSchemeTest extends TestCase
{
    public static function esquemasInvalidos(): array
    {
        return [
            'ssl' => ['ssl'],
            'tls' => ['tls'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('esquemasInvalidos')]
    public function test_un_esquema_invalido_revienta_al_construir_el_transporte(string $esquema)
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.scheme' => $esquema]);

        $this->expectException(\Symfony\Component\Mailer\Exception\UnsupportedSchemeException::class);

        Mail::mailer('smtp')->getSymfonyTransport();
    }

    public function test_los_dos_esquemas_validos_construyen_el_transporte()
    {
        foreach (['smtp' => 587, 'smtps' => 465] as $esquema => $puerto) {
            config([
                'mail.default'             => 'smtp',
                'mail.mailers.smtp.scheme' => $esquema,
                'mail.mailers.smtp.port'   => $puerto,
            ]);

            Mail::forgetMailers();

            $this->assertNotNull(
                Mail::mailer('smtp')->getSymfonyTransport(),
                "El esquema «{$esquema}» debería ser válido."
            );
        }
    }

    public function test_el_env_de_produccion_no_lleva_un_esquema_invalido()
    {
        $ruta = base_path('.env.produccion');

        if (! file_exists($ruta)) {
            $this->markTestSkipped('No hay .env.produccion en esta máquina.');
        }

        preg_match('/^MAIL_SCHEME=(.*)$/m', file_get_contents($ruta), $m);
        $esquema = trim($m[1] ?? '');

        $this->assertContains(
            $esquema,
            ['', 'smtp', 'smtps'],
            "MAIL_SCHEME del .env de producción es «{$esquema}». Solo valen «smtp» (puerto 587) "
            .'y «smtps» (puerto 465); con cualquier otro no sale un solo correo.'
        );
    }
}
