<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Un código de seis cifras, no un enlace: la app es Android y un enlace obligaría a
 * mantener una página web solo para esto.
 */
class ResetPasswordCode extends Notification
{
    public function __construct(private string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Tu código para cambiar la contraseña')
            ->greeting('Hola')
            ->line('Este es tu código para cambiar la contraseña en S-RANK:')
            ->line('**'.$this->code.'**')
            ->line('Caduca en 30 minutos.')
            ->line('Si no lo has pedido tú, no hace falta que hagas nada: tu contraseña sigue igual.')
            ->salutation('S-RANK');
    }
}
