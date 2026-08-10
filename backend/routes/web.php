<?php

// Sin rutas web. La app es Android y se autentica con `Authorization: Bearer`.
//
// Aquí vivía /informe-salud, que autenticaba leyendo una cookie propia. Estaba caído
// en producción (500) por dos motivos a la vez: redirigía a una ruta «login» que no
// existe, y su middleware validaba el token sin llegar a autenticar a nadie, así que
// el controlador recibía un usuario vacío. No lo cubría ningún test.
//
// La lógica del informe sigue en ReportController, sin ruta que la alcance. La fase 1.5
// la vuelve a publicar con una URL firmada (URL::temporarySignedRoute), que da un enlace
// que se le puede pasar al médico sin abrir una segunda vía de autenticación.
